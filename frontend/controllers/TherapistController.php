<?php

namespace frontend\controllers;

use common\models\Absence;
use common\models\Appointment;
use common\models\AuthToken;
use common\models\Specialization;
use common\models\Therapist;
use common\models\TherapistSubstitution;
use common\models\User;
use common\models\UserProfile;
use frontend\models\TherapistSearch;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use Yii;
use common\models\AuthItem;
use common\models\AuthItemChild;
use common\models\AuthAssignment;

/**
 * TherapistController implements the CRUD actions for Therapist model.
 */
class TherapistController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'reset-password' => ['POST', 'GET'],
                    'download-credentials-pdf' => ['GET', 'POST'],
                    'toggle-status' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Therapist models.
     * @return mixed
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->can('view_therapist')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i terapisti.');
        }

        $searchModel = new TherapistSearch();

        // Set default filter to show only active therapists if no filter is applied
        $queryParams = Yii::$app->request->queryParams;
        if (!isset($queryParams['TherapistSearch']['is_active'])) {
            $queryParams['TherapistSearch']['is_active'] = 1;
        }

        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Therapist model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        if (!Yii::$app->user->can('view_therapist')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i terapisti.');
        }

        $model = $this->findModel($id);

        // Coordinators can only view therapists in their own group
        if (Yii::$app->user->can('coordinator') && !Yii::$app->user->can('manager')) {
            $coordinatorGroup = \common\models\CoordinatorGroup::find()
                ->where(['coordinator_user_id' => Yii::$app->user->id])
                ->one();

            $allowedIds = [];
            if ($coordinatorGroup) {
                $allowedIds = \common\models\GroupTherapist::find()
                    ->select('therapist_id')
                    ->where(['group_id' => $coordinatorGroup->id])
                    ->andWhere(['assigned_to' => null])
                    ->column();
            }

            if (!in_array($model->id, $allowedIds)) {
                throw new ForbiddenHttpException('Non hai i permessi per visualizzare questo terapista.');
            }
        }

        // Decodifica i dati sensibili del profilo utente
        $this->decryptSensitiveData($model->user->profile);

        $permissionData = Yii::$app->user->can('manage_permissions')
            ? PermissionController::getPermissionData('therapist', $model->user_id)
            : [];

        return $this->render('view', array_merge([
            'model' => $model,
        ], $permissionData));
    }

    /**
     * Creates a new Therapist model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        if (!Yii::$app->user->can('create_therapist')) {
            throw new ForbiddenHttpException('Non hai i permessi per creare terapisti.');
        }

        $user = new User(['scenario' => 'create']);
        $profile = new UserProfile();
        $therapist = new Therapist();

        // Get specializations for dropdown
        $specializations = ArrayHelper::map(Specialization::find()->all(), 'id', 'name');

        if ($user->load(Yii::$app->request->post()) &&
                $profile->load(Yii::$app->request->post()) &&
                $therapist->load(Yii::$app->request->post())) {
            // Save password before hashing for PDF generation
            $plainPassword = $user->password;

            // Prepare user data
            $user->setPassword($user->password);
            $user->generateAuthKey();
            $user->status = User::STATUS_ACTIVE;
            $user->username = $user->email;  // Usa email come username

            // Set requires_password_change to true for new accounts
            if ($user->hasAttribute('requires_password_change')) {
                $user->requires_password_change = 1;
            }

            // Step 1: Validate and save User first (it's independent)
            if ($user->validate() && $user->save()) {
                // Step 2: Now we have a valid user ID, assign it to dependent models
                $profile->user_id = $user->id;
                $therapist->user_id = $user->id;

                // Step 3: Validate dependent models with real user_id
                $profileValid = $profile->validate();
                $therapistValid = $therapist->validate();

                if ($profileValid && $therapistValid) {
                    // Step 4: Everything is valid, start transaction for remaining saves
                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        // Encrypt sensitive data before saving
                        $this->encryptSensitiveData($profile);

                        // Rimuovi temporaneamente il behavior di logging dall'utente
                        $userLogBehavior = null;
                        foreach ($user->getBehaviors() as $name => $behavior) {
                            if ($behavior instanceof \common\behaviors\ActivityLogBehavior) {
                                $userLogBehavior = $behavior;
                                $user->detachBehavior($name);
                                break;
                            }
                        }

                        // Save profile
                        if (!$profile->save(false)) {
                            throw new \Exception('Errore nel salvare il profilo.');
                        }

                        // Save therapist senza logging
                        $therapistLogBehavior = null;
                        foreach ($therapist->getBehaviors() as $name => $behavior) {
                            if ($behavior instanceof \common\behaviors\ActivityLogBehavior) {
                                $therapistLogBehavior = $behavior;
                                $therapist->detachBehavior($name);
                                break;
                            }
                        }

                        if (!$therapist->save(false)) {
                            throw new \Exception('Errore nel salvare il terapista.');
                        }

                        // Assign therapist role
                        $auth = Yii::$app->authManager;
                        $therapistRole = $auth->getRole('therapist');
                        if ($therapistRole) {
                            $auth->assign($therapistRole, $user->id);
                        }

                        // Save extra permissions
                        $postedPermissions = Yii::$app->request->post('permissions', []);
                        if (!empty($postedPermissions)) {
                            PermissionController::saveExtraPermissions($user->id, 'therapist', $postedPermissions);
                        }

                        // Ora creiamo manualmente i log nell'ordine corretto
                        if ($userLogBehavior) {
                            // Filtra gli attributi dell'utente escludendo quelli sensibili
                            $userAttributes = $user->getAttributes();
                            $userExcludedAttributes = ['password_hash', 'auth_key', 'created_at', 'updated_at'];
                            $filteredUserAttributes = array_diff_key(
                                $userAttributes,
                                array_flip($userExcludedAttributes)
                            );

                            // Crea il log dell'utente
                            $userLog = new \common\models\ActivityLog([
                                'user_id' => Yii::$app->user->id,
                                'action' => \common\models\ActivityLog::ACTION_CREATE,
                                'entity_name' => 'User',
                                'entity_id' => $user->id,
                                'new_values' => json_encode($filteredUserAttributes),
                                'ip_address' => Yii::$app->request->userIP,
                                'user_agent' => Yii::$app->request->userAgent,
                            ]);
                            if (!$userLog->save()) {
                                throw new \Exception('Errore nel salvare il log utente.');
                            }

                            // Filtra gli attributi del terapista
                            $therapistAttributes = $therapist->getAttributes();
                            $therapistExcludedAttributes = ['created_at', 'updated_at'];
                            $filteredTherapistAttributes = array_diff_key(
                                $therapistAttributes,
                                array_flip($therapistExcludedAttributes)
                            );

                            // Crea il log del terapista con riferimento al log utente
                            $therapistLog = new \common\models\ActivityLog([
                                'user_id' => Yii::$app->user->id,
                                'action' => \common\models\ActivityLog::ACTION_CREATE,
                                'entity_name' => 'Terapista',
                                'entity_id' => $therapist->id,
                                'new_values' => json_encode($filteredTherapistAttributes),
                                'ip_address' => Yii::$app->request->userIP,
                                'user_agent' => Yii::$app->request->userAgent,
                                'parent_log_id' => $userLog->id,
                            ]);
                            if (!$therapistLog->save()) {
                                throw new \Exception('Errore nel salvare il log terapista.');
                            }
                        }

                        $transaction->commit();

                        // Generate PDF with credentials automatically
                        $this->generateCredentialsPdf($user, $plainPassword);

                        // Handle AJAX requests for automatic PDF download
                        if (Yii::$app->request->isAjax) {
                            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                            return [
                                'success' => true,
                                'message' => 'Terapista creato con successo!',
                                'downloadUrl' => \yii\helpers\Url::to(['download-credentials-pdf']),
                                'redirectUrl' => \yii\helpers\Url::to(['view', 'id' => $therapist->id])
                            ];
                        }

                        Yii::$app->session->setFlash('success', 'Terapista creato con successo!');
                        return $this->redirect(['view', 'id' => $therapist->id]);
                    } catch (\Exception $e) {
                        $transaction->rollBack();
                        // If Profile/Therapist save fails, we need to rollback the User too
                        $user->delete();
                        Yii::$app->session->setFlash('error', $e->getMessage());
                    }
                } else {
                    // Profile or Therapist validation failed
                    // Rollback: delete the user since dependent models are invalid
                    $user->delete();
                    // The form will show the validation errors automatically
                }
            }
            // If User validation fails, errors will be shown automatically
            // Reset user_id to null for dependent models to avoid foreign key display issues
            $profile->user_id = null;
            $therapist->user_id = null;
        }

        $permissionData = Yii::$app->user->can('manage_permissions')
            ? PermissionController::getPermissionData('therapist')
            : [];

        return $this->render('create', array_merge([
            'user' => $user,
            'profile' => $profile,
            'therapist' => $therapist,
            'specializations' => $specializations,
        ], $permissionData));
    }

    /**
     * Updates an existing Therapist model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        if (!Yii::$app->user->can('update_therapist')) {
            throw new ForbiddenHttpException('Non hai i permessi per modificare terapisti.');
        }

        $therapist = $this->findModel($id);
        $user = $therapist->user;
        $profile = $user->profile ?: new UserProfile(['user_id' => $user->id]);

        // Decodifica i dati sensibili per mostrarli nel form
        $this->decryptSensitiveData($profile);

        // Get specializations for dropdown
        $specializations = ArrayHelper::map(Specialization::find()->all(), 'id', 'name');

        if ($user->load(Yii::$app->request->post()) &&
                $profile->load(Yii::$app->request->post()) &&
                $therapist->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$user->save()) {
                    throw new \Exception("Errore nell'aggiornare l'utente: " . implode(', ', $user->getFirstErrors()));
                }

                // Validate profile BEFORE encryption
                if (!$profile->validate()) {
                    throw new \Exception('Errore nella validazione del profilo: ' . implode(', ', $profile->getFirstErrors()));
                }

                // Crittografa i dati sensibili solo dopo tutte le validazioni
                $this->encryptSensitiveData($profile);

                // Save profile (without validation since we already validated)
                if (!$profile->save(false)) {
                    throw new \Exception("Errore nell'aggiornare il profilo.");
                }

                if (!$therapist->save()) {
                    throw new \Exception("Errore nell'aggiornare il terapista: " . implode(', ', $therapist->getFirstErrors()));
                }

                // Sync extra permissions
                if (Yii::$app->user->can('manage_permissions')) {
                    $postedPermissions = Yii::$app->request->post('permissions', []);
                    PermissionController::saveExtraPermissions($user->id, 'therapist', $postedPermissions);
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Terapista aggiornato con successo.');
                return $this->redirect(['view', 'id' => $therapist->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        $permissionData = Yii::$app->user->can('manage_permissions')
            ? PermissionController::getPermissionData('therapist', $user->id)
            : [];

        return $this->render('update', array_merge([
            'user' => $user,
            'profile' => $profile,
            'therapist' => $therapist,
            'specializations' => $specializations,
        ], $permissionData));
    }

    /**
     * Toggles the active status of an existing Therapist model.
     * If the therapist is active, it will be deactivated, and vice versa.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionToggleStatus($id)
    {
        if (!Yii::$app->user->can('delete_therapist')) {
            throw new ForbiddenHttpException('Non hai i permessi per gestire lo stato dei terapisti.');
        }

        $therapist = $this->findModel($id);
        $user = $therapist->user;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Toggle active status
            $newStatus = !$therapist->is_active;
            $therapist->is_active = $newStatus;

            // Also toggle user account status
            $user->status = $newStatus ? User::STATUS_ACTIVE : User::STATUS_INACTIVE;

            if ($therapist->save(false) && $user->save(false)) {
                $transaction->commit();
                $message = $newStatus ? 'Terapista attivato con successo.' : 'Terapista disattivato con successo.';
                Yii::$app->session->setFlash('success', $message);
            } else {
                throw new \Exception('Errore nel cambiare lo stato del terapista.');
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the Therapist model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Therapist the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Therapist::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Il terapista richiesto non esiste.');
    }

    /**
     * Decodifica i dati sensibili del profilo utente
     * @param UserProfile $profile
     */
    private function decryptSensitiveData($profile)
    {
        if (!$profile) {
            return;
        }

        try {
            $encryptionKey = Yii::$app->params['encryptionKey'];

            // Decodifica il telefono se presente e crittografato
            if (!empty($profile->phone)) {
                try {
                    $decryptedPhone = Yii::$app->security->decryptByKey(
                        base64_decode($profile->phone),
                        $encryptionKey
                    );
                    $profile->phone = $decryptedPhone;
                } catch (\Exception $e) {
                    Yii::error('Failed to decrypt phone number: ' . $e->getMessage(), __METHOD__);
                    // Se la decodifica fallisce, mantieni il valore originale (potrebbe non essere crittografato)
                }
            }

            // Decodifica l'indirizzo se presente e crittografato
            if (!empty($profile->address)) {
                try {
                    $decryptedAddress = Yii::$app->security->decryptByKey(
                        base64_decode($profile->address),
                        $encryptionKey
                    );
                    $profile->address = $decryptedAddress;
                } catch (\Exception $e) {
                    Yii::error('Failed to decrypt address: ' . $e->getMessage(), __METHOD__);
                    // Se la decodifica fallisce, mantieni il valore originale (potrebbe non essere crittografato)
                }
            }
        } catch (\Exception $e) {
            Yii::error('Error in decryptSensitiveData: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Crittografa i dati sensibili del profilo utente prima del salvataggio
     * @param UserProfile $profile
     */
    private function encryptSensitiveData($profile)
    {
        if (!$profile) {
            return;
        }

        try {
            $encryptionKey = Yii::$app->params['encryptionKey'];

            // Crittografa il telefono se presente
            if (!empty($profile->phone)) {
                $encryptedPhone = base64_encode(Yii::$app->security->encryptByKey(
                    $profile->phone,
                    $encryptionKey
                ));
                $profile->phone = $encryptedPhone;
            }

            // Crittografa l'indirizzo se presente
            if (!empty($profile->address)) {
                $encryptedAddress = base64_encode(Yii::$app->security->encryptByKey(
                    $profile->address,
                    $encryptionKey
                ));
                $profile->address = $encryptedAddress;
            }
        } catch (\Exception $e) {
            Yii::error('Error in encryptSensitiveData: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Lists therapists for coordinators (only their own group)
     * @return mixed
     */
    public function actionMyGroup()
    {
        if (!Yii::$app->user->can('view_own_group_therapists') || !Yii::$app->user->can('coordinator')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i terapisti del gruppo.');
        }

        // Find coordinator's group
        $coordinatorUserId = Yii::$app->user->id;
        $coordinatorGroup = \common\models\CoordinatorGroup::find()
            ->where(['coordinator_user_id' => $coordinatorUserId])
            ->one();

        if (!$coordinatorGroup) {
            return $this->render('no-group', [
                'message' => 'Non sei assegnato a nessun gruppo di terapisti.'
            ]);
        }

        // Get therapists in the coordinator's group
        $therapistIds = \common\models\GroupTherapist::find()
            ->select('therapist_id')
            ->where(['group_id' => $coordinatorGroup->id])
            ->andWhere(['assigned_to' => null])
            ->column();

        $searchModel = new TherapistSearch();
        $queryParams = Yii::$app->request->queryParams;

        // Filter to only include therapists in the coordinator's group
        if (!empty($therapistIds)) {
            $searchModel->therapist_ids = $therapistIds;
        } else {
            // No therapists in group, show empty result
            $searchModel->therapist_ids = [0];  // Non-existing ID to force empty result
        }

        $dataProvider = $searchModel->search($queryParams);

        return $this->render('my-group', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'coordinatorGroup' => $coordinatorGroup,
        ]);
    }

    /**
     * Resets password for a therapist user account and generates PDF with credentials
     */
    public function actionResetPassword($userId)
    {
        if (!Yii::$app->user->can('update_therapist')) {
            throw new ForbiddenHttpException('Non hai i permessi per resettare le password dei terapisti.');
        }

        $user = User::findOne($userId);
        if (!$user) {
            throw new NotFoundHttpException('Utente non trovato.');
        }

        // Verify this user is actually a therapist
        $therapist = Therapist::findOne(['user_id' => $userId]);
        if (!$therapist) {
            throw new NotFoundHttpException('Terapista non trovato.');
        }

        // Generate new random password
        $newPassword = $this->generateRandomPassword();

        // Set new password
        $user->setPassword($newPassword);
        $user->generateAuthKey();

        // Set requires_password_change to true
        if ($user->hasAttribute('requires_password_change')) {
            $user->requires_password_change = 1;
        }

        if ($user->save()) {
            // Revoke all existing auth tokens for this user
            $revokedTokens = AuthToken::updateAll(
                ['is_revoked' => 1],
                ['user_id' => $user->id, 'is_revoked' => 0]
            );

            // Generate PDF with credentials
            $this->generateCredentialsPdf($user, $newPassword);

            Yii::$app->session->setFlash('success', 'Password resettata e PDF generato con successo. Tutti i token di accesso precedenti sono stati revocati.');
        } else {
            Yii::$app->session->setFlash('error', 'Errore nel resettare la password.');
        }

        // Handle AJAX requests differently
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['status' => 'success', 'message' => 'Password resettata con successo'];
        }

        return $this->redirect(['index']);
    }

    /**
     * Downloads the generated credentials PDF
     */
    public function actionDownloadCredentialsPdf()
    {
        $pdfData = Yii::$app->session->get('pdf_data');

        if (!$pdfData || !isset($pdfData['content']) || !isset($pdfData['filename'])) {
            throw new NotFoundHttpException('PDF non trovato o scaduto. Genera nuovamente le credenziali.');
        }

        // Clear PDF data from session after download
        Yii::$app->session->remove('pdf_data');

        return Yii::$app->response->sendContentAsFile(
            $pdfData['content'],
            $pdfData['filename'],
            [
                'mimeType' => 'application/pdf',
                'inline' => false
            ]
        );
    }

    /**
     * Generates a random password
     */
    private function generateRandomPassword($length = 12)
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }

    /**
     * Generates PDF with user credentials
     */
    private function generateCredentialsPdf($user, $password)
    {
        try {
            // Create HTML content for PDF
            $html = $this->renderPartial('_credentials_pdf', [
                'user' => $user,
                'password' => $password,
                'generatedAt' => date('d/m/Y H:i'),
            ]);

            // Create mPDF instance with custom temp directory
            $tempDir = Yii::getAlias('@frontend/runtime/mpdf');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 20,
                'margin_right' => 20,
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_header' => 10,
                'margin_footer' => 10,
                'tempDir' => $tempDir,
            ]);

            // Set document properties
            $mpdf->SetTitle('Credenziali di Accesso Terapista - TherapyCRM');
            $mpdf->SetAuthor('TherapyCRM');
            $mpdf->SetCreator('TherapyCRM System');

            // Write HTML to PDF
            $mpdf->WriteHTML($html);

            // Generate filename
            $filename = 'credenziali_terapista_' . str_replace(['@', '.'], ['_', '_'], $user->email) . '_' . date('Y-m-d_H-i-s') . '.pdf';

            // Generate PDF content
            $pdfContent = $mpdf->Output('', 'S');

            // Store PDF data in session for download
            Yii::$app->session->set('pdf_data', [
                'content' => $pdfContent,
                'filename' => $filename
            ]);
        } catch (\Exception $e) {
            Yii::error('Errore nella generazione PDF: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            Yii::$app->session->setFlash('error', 'Errore nella generazione del PDF: ' . $e->getMessage());
        }
    }

    /**
     * Display activity report for a therapist
     * @param integer $id Therapist ID
     * @return mixed
     */
    public function actionActivityReport($id)
    {
        if (!Yii::$app->user->can('view_therapist')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare il report attività.');
        }

        $therapist = $this->findModel($id);

        // Get date range from request or set defaults (last 30 days)
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-d', strtotime('-30 days')));
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-d'));

        // Validate dates
        if (strtotime($dateFrom) > strtotime($dateTo)) {
            Yii::$app->session->setFlash('error', 'La data di inizio non può essere successiva alla data di fine.');
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
            $dateTo = date('Y-m-d');
        }

        // Check if export is requested
        if (Yii::$app->request->get('export') === 'excel') {
            return $this->exportActivityReport($therapist, $dateFrom, $dateTo);
        }

        // Appointments DataProvider
        $appointmentsProvider = new ActiveDataProvider([
            'query' => Appointment::find()
                ->where(['therapist_id' => $therapist->id])
                ->andWhere(['>=', 'DATE(appointment_datetime)', $dateFrom])
                ->andWhere(['<=', 'DATE(appointment_datetime)', $dateTo])
                ->with(['patient', 'planTherapy.therapeuticPlan.patient', 'treatmentType', 'setting'])
                ->orderBy(['appointment_datetime' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        // Absences DataProvider
        $absencesProvider = new ActiveDataProvider([
            'query' => Absence::find()
                ->where(['therapist_id' => $therapist->id])
                ->andWhere([
                    'or',
                    ['and', ['>=', 'start_date', $dateFrom], ['<=', 'start_date', $dateTo]],
                    ['and', ['>=', 'end_date', $dateFrom], ['<=', 'end_date', $dateTo]],
                    ['and', ['<', 'start_date', $dateFrom], ['>', 'end_date', $dateTo]]
                ])
                ->orderBy(['start_date' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        // Substitutions DataProvider (both as original and substitute)
        $substitutionsProvider = new ActiveDataProvider([
            'query' => TherapistSubstitution::find()
                ->joinWith(['appointment'])
                ->where([
                    'or',
                    ['therapist_substitutions.original_therapist_id' => $therapist->id],
                    ['therapist_substitutions.substitute_therapist_id' => $therapist->id]
                ])
                ->andWhere(['>=', 'DATE(appointments.appointment_datetime)', $dateFrom])
                ->andWhere(['<=', 'DATE(appointments.appointment_datetime)', $dateTo])
                ->with(['appointment', 'originalTherapist.user.profile', 'substituteTherapist.user.profile'])
                ->orderBy(['therapist_substitutions.substituted_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('activity-report', [
            'therapist' => $therapist,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'appointmentsProvider' => $appointmentsProvider,
            'absencesProvider' => $absencesProvider,
            'substitutionsProvider' => $substitutionsProvider,
        ]);
    }

    /**
     * Export activity report to Excel
     * @param Therapist $therapist
     * @param string $dateFrom
     * @param string $dateTo
     * @return mixed
     */
    private function exportActivityReport($therapist, $dateFrom, $dateTo)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet
            ->getProperties()
            ->setCreator('TherapyCRM')
            ->setTitle('Report Attività Terapista')
            ->setSubject('Report Attività')
            ->setDescription('Report delle attività del terapista nel periodo selezionato');

        // Header style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];

        $therapistName = $therapist->user->profile->first_name . ' ' . $therapist->user->profile->last_name;

        // SHEET 1: Appointments
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Appuntamenti');

        // Header info
        $sheet1->setCellValue('A1', 'REPORT ATTIVITÀ TERAPISTA');
        $sheet1->mergeCells('A1:H1');
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet1->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet1->setCellValue('A2', 'Terapista: ' . $therapistName);
        $sheet1->mergeCells('A2:H2');
        $sheet1->setCellValue('A3', 'Periodo: ' . date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)));
        $sheet1->mergeCells('A3:H3');

        // Column headers
        $sheet1->setCellValue('A5', 'Data/Ora');
        $sheet1->setCellValue('B5', 'Paziente');
        $sheet1->setCellValue('C5', 'Tipo Trattamento');
        $sheet1->setCellValue('D5', 'Durata (min)');
        $sheet1->setCellValue('E5', 'Stato');
        $sheet1->setCellValue('F5', 'Origine');
        $sheet1->setCellValue('G5', 'Setting');
        $sheet1->setCellValue('H5', 'Note');
        $sheet1->getStyle('A5:H5')->applyFromArray($headerStyle);

        // Data
        $appointments = Appointment::find()
            ->where(['therapist_id' => $therapist->id])
            ->andWhere(['>=', 'DATE(appointment_datetime)', $dateFrom])
            ->andWhere(['<=', 'DATE(appointment_datetime)', $dateTo])
            ->with(['patient', 'planTherapy.therapeuticPlan.patient', 'treatmentType', 'setting'])
            ->orderBy(['appointment_datetime' => SORT_ASC])
            ->all();

        $row = 6;
        foreach ($appointments as $appointment) {
            $patient = $appointment->getActualPatient();
            $patientName = $patient ? ($patient->first_name . ' ' . $patient->last_name) : 'N/A';
            $treatmentType = $appointment->getActualTreatmentType();
            $treatmentName = $treatmentType ? $treatmentType->name : 'N/A';

            $sheet1->setCellValue('A' . $row, date('d/m/Y H:i', strtotime($appointment->appointment_datetime)));
            $sheet1->setCellValue('B' . $row, $patientName);
            $sheet1->setCellValue('C' . $row, $treatmentName);
            $sheet1->setCellValue('D' . $row, $appointment->duration_minutes);
            $sheet1->setCellValue('E' . $row, $appointment->getStatusLabel());
            $sheet1->setCellValue('F' . $row, $appointment->getAppointmentSourceLabel());
            $sheet1->setCellValue('G' . $row, $appointment->setting ? $appointment->setting->nome : 'N/A');
            $sheet1->setCellValue('H' . $row, $appointment->notes ?: '');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        // SHEET 2: Absences
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Assenze');

        $sheet2->setCellValue('A1', 'ASSENZE');
        $sheet2->mergeCells('A1:F1');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet2->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet2->setCellValue('A3', 'Data Inizio');
        $sheet2->setCellValue('B3', 'Data Fine');
        $sheet2->setCellValue('C3', 'Tipo');
        $sheet2->setCellValue('D3', 'Stato');
        $sheet2->setCellValue('E3', 'Durata (giorni)');
        $sheet2->setCellValue('F3', 'Motivo');
        $sheet2->getStyle('A3:F3')->applyFromArray($headerStyle);

        $absences = Absence::find()
            ->where(['therapist_id' => $therapist->id])
            ->andWhere([
                'or',
                ['and', ['>=', 'start_date', $dateFrom], ['<=', 'start_date', $dateTo]],
                ['and', ['>=', 'end_date', $dateFrom], ['<=', 'end_date', $dateTo]],
                ['and', ['<', 'start_date', $dateFrom], ['>', 'end_date', $dateTo]]
            ])
            ->orderBy(['start_date' => SORT_ASC])
            ->all();

        $row = 4;
        foreach ($absences as $absence) {
            $sheet2->setCellValue('A' . $row, date('d/m/Y', strtotime($absence->start_date)));
            $sheet2->setCellValue('B' . $row, date('d/m/Y', strtotime($absence->end_date)));
            $sheet2->setCellValue('C' . $row, $absence->getTypeLabel());
            $sheet2->setCellValue('D' . $row, $absence->getStatusLabel());
            $sheet2->setCellValue('E' . $row, $absence->getDurationDays());
            $sheet2->setCellValue('F' . $row, $absence->reason ?: '');
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // SHEET 3: Substitutions
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Sostituzioni');

        $sheet3->setCellValue('A1', 'SOSTITUZIONI');
        $sheet3->mergeCells('A1:F1');
        $sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet3->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet3->setCellValue('A3', 'Data/Ora');
        $sheet3->setCellValue('B3', 'Terapista Originale');
        $sheet3->setCellValue('C3', 'Terapista Sostituto');
        $sheet3->setCellValue('D3', 'Ruolo');
        $sheet3->setCellValue('E3', 'Motivo');
        $sheet3->setCellValue('F3', 'Sostituito il');
        $sheet3->getStyle('A3:F3')->applyFromArray($headerStyle);

        $substitutions = TherapistSubstitution::find()
            ->joinWith(['appointment'])
            ->where([
                'or',
                ['therapist_substitutions.original_therapist_id' => $therapist->id],
                ['therapist_substitutions.substitute_therapist_id' => $therapist->id]
            ])
            ->andWhere(['>=', 'DATE(appointments.appointment_datetime)', $dateFrom])
            ->andWhere(['<=', 'DATE(appointments.appointment_datetime)', $dateTo])
            ->with(['appointment', 'originalTherapist.user.profile', 'substituteTherapist.user.profile'])
            ->orderBy(['appointments.appointment_datetime' => SORT_ASC])
            ->all();

        $row = 4;
        foreach ($substitutions as $substitution) {
            $originalName = $substitution->originalTherapist->user->profile->first_name . ' '
                . $substitution->originalTherapist->user->profile->last_name;
            $substituteName = $substitution->substituteTherapist->user->profile->first_name . ' '
                . $substitution->substituteTherapist->user->profile->last_name;

            $role = ($substitution->original_therapist_id == $therapist->id) ? 'Sostituito' : 'Sostituto';

            $sheet3->setCellValue('A' . $row, date('d/m/Y H:i', strtotime($substitution->appointment->appointment_datetime)));
            $sheet3->setCellValue('B' . $row, $originalName);
            $sheet3->setCellValue('C' . $row, $substituteName);
            $sheet3->setCellValue('D' . $row, $role);
            $sheet3->setCellValue('E' . $row, $substitution->reason ?: '');
            $sheet3->setCellValue('F' . $row, date('d/m/Y H:i', strtotime($substitution->substituted_at)));
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
        }

        // Set active sheet to first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Generate filename
        $filename = 'report_attivita_' . str_replace(' ', '_', strtolower($therapistName)) . '_'
            . $dateFrom . '_' . $dateTo . '.xlsx';

        // Output file
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}

<?php

namespace frontend\controllers;

use common\components\Helper;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use common\models\Patient;
use common\models\District;
use common\models\User;
use common\models\UserProfile;
use common\models\AccountPatient;
use common\models\AuthToken;
use frontend\models\PatientSearch;

/**
 * PatientController handles CRUD operations for patients
 */
class PatientController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Only authenticated users
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'reset-password' => ['POST', 'GET'], // Allow GET for testing
                    'download-credentials-pdf' => ['GET', 'POST'],
                    'send-notification' => ['POST'],
                    'create-credentials' => ['GET', 'POST'],
                ],
            ],
        ];
    }

    /**
     * Lists patients
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->can('create_patient')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i pazienti.');
        }

        $searchModel = new PatientSearch();
        $dataProvider = $searchModel->searchDataProvider(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new patient
     */
    public function actionCreate()
    {
        if (!Yii::$app->user->can('create_patient')) {
            throw new ForbiddenHttpException('Non hai i permessi per creare pazienti.');
        }

        $patient = new Patient(['scenario' => 'create']);

        // Get districts for dropdown
        $districts = ArrayHelper::map(District::find()->all(), 'id', 'name');
        $province = Helper::getProvinceOptions();

        if ($patient->load(Yii::$app->request->post())) {
            if ($patient->save()) {
                Yii::$app->session->setFlash('success', 'Paziente creato con successo.');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Errore nel salvare il paziente: ' . implode(', ', $patient->getFirstErrors()));
            }
        }

        return $this->render('create', [
            'patient' => $patient,
            'districts' => $districts,
            'province' => $province,
        ]);
    }

    /**
     * Displays a single patient
     */
    public function actionView($id)
    {
        if (!Yii::$app->user->can('view_patient')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i pazienti.');
        }

        $model = $this->findModel($id);

        // Load associated accounts
        $accountPatients = AccountPatient::find()
            ->where(['patient_id' => $id])
            ->with(['user', 'user.profile'])
            ->all();

        return $this->render('view', [
            'model' => $model,
            'accountPatients' => $accountPatients,
        ]);
    }

    /**
     * Updates an existing patient
     */
    public function actionUpdate($id)
    {
        if (!Yii::$app->user->can('update_patient')) {
            throw new ForbiddenHttpException('Non hai i permessi per modificare pazienti.');
        }

        $patient = $this->findModel($id);
        $patient->scenario = 'update';

        // Get districts for dropdown
        $districts = ArrayHelper::map(District::find()->all(), 'id', 'name');

        $province = Helper::getProvinceOptions();

        if ($patient->load(Yii::$app->request->post())) {
            if ($patient->save()) {
                Yii::$app->session->setFlash('success', 'Paziente aggiornato con successo.');
                return $this->redirect(['view', 'id' => $patient->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Errore nell\'aggiornare il paziente: ' . implode(', ', $patient->getFirstErrors()));
            }
        }

        return $this->render('update', [
            'patient' => $patient,
            'districts' => $districts,
            'province' => $province,
        ]);
    }

    /**
     * Deletes an existing patient
     */
    public function actionDelete($id)
    {
        if (!Yii::$app->user->can('delete_patient')) {
            throw new ForbiddenHttpException('Non hai i permessi per eliminare pazienti.');
        }

        $patient = $this->findModel($id);

        if ($patient->delete()) {
            Yii::$app->session->setFlash('success', 'Paziente eliminato con successo.');
        } else {
            Yii::$app->session->setFlash('error', 'Errore nell\'eliminare il paziente.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Creates credentials and account for patient
     */
    public function actionCreateCredentials($id)
    {
        if (!Yii::$app->user->can('create_patient')) {
            throw new ForbiddenHttpException('Non hai i permessi per creare credenziali.');
        }

        $patient = $this->findModel($id);
        $user = new User(['scenario' => 'create']);
        $profile = new UserProfile();
        $accountPatient = new AccountPatient();

        // Pre-fill profile with patient data
        $profile->first_name = $patient->first_name;
        $profile->last_name = $patient->last_name;
        $profile->fiscal_code = $patient->fiscal_code;

        $postData = Yii::$app->request->post();
        Yii::info('POST data received: ' . print_r($postData, true));

        if (
            $user->load($postData) &&
            $profile->load($postData) &&
            $accountPatient->load($postData)
        ) {

            Yii::info('Models loaded successfully - User: ' . print_r($user->attributes, true));
            Yii::info('Profile: ' . print_r($profile->attributes, true));
            Yii::info('AccountPatient: ' . print_r($accountPatient->attributes, true));

            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Save password before hashing for PDF generation
                $plainPassword = $user->password;

                // Set username (use email as username)
                $user->username = $user->email;

                // Set password hash and auth key before saving user
                $user->setPassword($user->password);
                $user->generateAuthKey();

                // Set requires_password_change to true for new accounts
                if ($user->hasAttribute('requires_password_change')) {
                    $user->requires_password_change = 1;
                    Yii::info('Campo requires_password_change impostato a true per nuovo utente');
                }

                // Save user
                if (!$user->save()) {
                    throw new \Exception('Errore nel salvare l\'utente: ' . implode(', ', $user->getFirstErrors()));
                }

                // Save profile
                $profile->user_id = $user->id;
                if (!$profile->save()) {
                    throw new \Exception('Errore nel salvare il profilo: ' . implode(', ', $profile->getFirstErrors()));
                }

                // Create account-patient relationship
                $accountPatient->user_id = $user->id;
                $accountPatient->patient_id = $patient->id;
                if (!$accountPatient->save()) {
                    throw new \Exception('Errore nel collegare utente e paziente: ' . implode(', ', $accountPatient->getFirstErrors()));
                }

                // Assign patient_family role
                $auth = Yii::$app->authManager;
                $patientRole = $auth->getRole('patient_family');
                if ($patientRole) {
                    $auth->assign($patientRole, $user->id);
                }

                $transaction->commit();

                // Generate PDF with credentials automatically
                $this->generateCredentialsPdf($user, $plainPassword);

                // Handle AJAX requests for automatic PDF download
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    return [
                        'success' => true,
                        'message' => 'Credenziali create con successo!',
                        'downloadUrl' => \yii\helpers\Url::to(['download-credentials-pdf']),
                        'redirectUrl' => \yii\helpers\Url::to(['view', 'id' => $patient->id])
                    ];
                }

                Yii::$app->session->setFlash('success', 'Credenziali create con successo!');
                return $this->redirect(['view', 'id' => $patient->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error('Errore durante la creazione credenziali: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());

                // Handle AJAX requests for errors
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    return [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }

                Yii::$app->session->setFlash('error', 'Errore: ' . $e->getMessage());
            }
        } else {
            Yii::info('Models NOT loaded properly from POST data');
            if (Yii::$app->request->isPost) {
                Yii::info('Request is POST but models failed to load');
            } else {
                Yii::info('Request is not POST - initial page load');
            }
        }

        return $this->render('create-credentials', [
            'patient' => $patient,
            'user' => $user,
            'profile' => $profile,
            'accountPatient' => $accountPatient,
            'relationshipLabels' => AccountPatient::getRelationshipLabels(),
        ]);
    }

    /**
     * Resets password for a user account and generates PDF with credentials
     */
    public function actionResetPassword($userId = null)
    {
        // Accept userId from both URL parameter and POST data
        if ($userId === null) {
            $userId = Yii::$app->request->post('userId');
        }

        if (!$userId) {
            throw new \yii\web\BadRequestHttpException('Missing userId parameter');
        }

        if (!Yii::$app->user->can('update_patient')) {
            Yii::error('Permessi insufficienti per utente: ' . Yii::$app->user->id);
            throw new ForbiddenHttpException('Non hai i permessi per resettare password.');
        }

        $user = User::findOne($userId);
        if (!$user) {
            Yii::error('Utente non trovato con ID: ' . $userId);
            throw new NotFoundHttpException('Utente non trovato.');
        }

        Yii::info('Utente trovato: ' . $user->email);

        // Generate new random password
        $newPassword = $this->generateRandomPassword();
        Yii::info('Nuova password generata: ' . $newPassword);

        // Set the new password and require password change on first login
        $user->setPassword($newPassword);

        // Check if user has requires_password_change field and set it to true
        if ($user->hasAttribute('requires_password_change')) {
            $user->requires_password_change = 1;
            Yii::info('Campo requires_password_change impostato a true');
        }

        if ($user->save()) {
            Yii::info('Password salvata con successo per utente: ' . $user->email);

            // Revoke all existing auth tokens for this user
            $revokedTokens = AuthToken::updateAll(
                ['is_revoked' => 1],
                ['user_id' => $user->id, 'is_revoked' => 0]
            );
            Yii::info('Revocati ' . $revokedTokens . ' token esistenti per utente: ' . $user->email);

            // Generate PDF with credentials
            Yii::info('Inizio generazione PDF...');
            $this->generateCredentialsPdf($user, $newPassword);

            Yii::$app->session->setFlash('success', 'Password resettata e PDF generato con successo. Tutti i token di accesso precedenti sono stati revocati.');
            Yii::info('Flash message impostato');
        } else {
            Yii::error('Errore nel salvare la password: ' . implode(', ', $user->getFirstErrors()));
            Yii::$app->session->setFlash('error', 'Errore nel resettare la password.');
        }

        // Handle AJAX requests differently
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['status' => 'success', 'message' => 'Password resettata con successo'];
        }

        // Find patient ID to redirect back for non-AJAX requests
        $accountPatient = AccountPatient::findOne(['user_id' => $userId]);
        if ($accountPatient) {
            return $this->redirect(['view', 'id' => $accountPatient->patient_id]);
        }

        return $this->redirect(['index']);
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
            Yii::info('Inizio generazione PDF per utente: ' . $user->email);

            // Create HTML content for PDF
            $html = $this->renderPartial('_credentials_pdf', [
                'user' => $user,
                'password' => $password,
                'generatedAt' => date('d/m/Y H:i'),
            ]);

            Yii::info('HTML generato per PDF, lunghezza: ' . strlen($html));

            // Create mPDF instance with custom temp directory
            $tempDir = Yii::getAlias('@frontend/runtime/mpdf');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
                Yii::info('Creata directory temporanea: ' . $tempDir);
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

            Yii::info('mPDF istanziato correttamente');

            // Set document properties
            $mpdf->SetTitle('Credenziali di Accesso - San Luca Plus');
            $mpdf->SetAuthor('San Luca Plus');
            $mpdf->SetCreator('San Luca Plus System');

            // Write HTML to PDF
            $mpdf->WriteHTML($html);

            Yii::info('HTML scritto in mPDF');

            // Generate filename
            $filename = 'credenziali_' . str_replace(['@', '.'], ['_', '_'], $user->email) . '_' . date('Y-m-d_H-i-s') . '.pdf';

            // Generate PDF content
            $pdfContent = $mpdf->Output('', 'S');

            Yii::info('PDF generato, dimensione: ' . strlen($pdfContent) . ' bytes');

            // Store PDF data in session for download
            Yii::$app->session->set('pdf_data', [
                'content' => $pdfContent,
                'filename' => $filename
            ]);

            Yii::info('PDF salvato in sessione con nome: ' . $filename);
        } catch (\Exception $e) {
            Yii::error('Errore nella generazione PDF: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            Yii::$app->session->setFlash('error', 'Errore nella generazione del PDF: ' . $e->getMessage());
        }
    }

    /**
     * Downloads the generated credentials PDF
     */
    public function actionDownloadCredentialsPdf()
    {
        Yii::info('Tentativo di download PDF - Inizio');

        $pdfData = Yii::$app->session->get('pdf_data');

        if (!$pdfData) {
            Yii::error('PDF non trovato in sessione');
            throw new NotFoundHttpException('PDF non trovato. Rigenerare le credenziali.');
        }

        Yii::info('PDF trovato in sessione: ' . $pdfData['filename'] . ', dimensione: ' . strlen($pdfData['content']) . ' bytes');

        // Remove from session after use
        Yii::$app->session->remove('pdf_data');

        Yii::info('Headers e content impostati per download PDF');

        // Use Yii response to send PDF properly
        return Yii::$app->response->sendContentAsFile(
            $pdfData['content'],
            $pdfData['filename'],
            [
                'mimeType' => 'application/pdf',
                'inline' => false  // Force download instead of inline display
            ]
        );
    }

    /**
     * Sends notifications to users linked to selected patients
     */
    public function actionSendNotification()
    {
        if (!Yii::$app->user->can('create_patient')) {
            throw new ForbiddenHttpException('Non hai i permessi per inviare notifiche.');
        }

        if (!Yii::$app->request->isPost) {
            throw new \yii\web\BadRequestHttpException('Metodo non consentito.');
        }

        $data = Yii::$app->request->post();

        // Validazione input
        if (empty($data['patient_ids']) || empty($data['title']) || empty($data['message'])) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'success' => false,
                'error' => 'Parametri mancanti: seleziona almeno un paziente e inserisci titolo e messaggio.'
            ];
        }

        $patientIds = is_array($data['patient_ids']) ? $data['patient_ids'] : [$data['patient_ids']];
        $title = trim($data['title']);
        $message = trim($data['message']);
        $requiresReadConfirmation = false;
        if (isset($data['requires_read_confirmation'])) {
            $value = $data['requires_read_confirmation'];
            $requiresReadConfirmation = ($value === true || $value === 'true' || $value === 1 || $value === '1');
        }



        try {
            // Ottieni tutti gli user_id collegati ai pazienti selezionati
            $userIds = $this->getUsersLinkedToPatients($patientIds);

            if (empty($userIds)) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return [
                    'success' => false,
                    'error' => 'Nessun account collegato ai pazienti selezionati.'
                ];
            }

            // Invia le notifiche utilizzando il servizio
            $result = Yii::$app->notificationService->sendNotification(
                $userIds,
                $title,
                $message,
                \common\models\Notification::TYPE_INFO,
                Yii::$app->user->id,
                $requiresReadConfirmation
            );

            // Log dell'operazione
            Yii::info('Invio notifiche ai pazienti: ' . implode(',', $patientIds) .
                ' - Utenti destinatari: ' . implode(',', $userIds) .
                ' - Risultato: ' . print_r($result, true), __METHOD__);

            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'success' => true,
                'message' => sprintf(
                    'Notifica inviata con successo a %d account collegati ai pazienti selezionati.',
                    $result['notifications_created']
                ),
                'details' => [
                    'patients_count' => count($patientIds),
                    'accounts_notified' => $result['notifications_created'],
                    'errors' => $result['errors'] ?? []
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Errore invio notifiche ai pazienti: ' . $e->getMessage(), __METHOD__);

            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'success' => false,
                'error' => 'Errore durante l\'invio delle notifiche: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ottiene gli user_id di tutti gli account collegati ai pazienti specificati
     *
     * @param array $patientIds Array degli ID dei pazienti
     * @return array Array degli user_id collegati
     */
    private function getUsersLinkedToPatients($patientIds)
    {
        return Patient::getLinkedUsersForPatients($patientIds);
    }

    /**
     * Finds the Patient model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Patient the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Patient::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La pagina richiesta non esiste.');
    }
}

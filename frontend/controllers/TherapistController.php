<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use common\models\User;
use common\models\UserProfile;
use common\models\Therapist;
use common\models\Specialization;
use frontend\models\TherapistSearch;

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
        
        // Decodifica i dati sensibili del profilo utente
        $this->decryptSensitiveData($model->user->profile);
        
        return $this->render('view', [
            'model' => $model,
        ]);
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
            
            // Prepare user data
            $user->setPassword($user->password);
            $user->generateAuthKey();
            $user->status = User::STATUS_ACTIVE;
            $user->username = $user->email; // Usa email come username
            
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
                        
                        // Save profile
                        if (!$profile->save(false)) {
                            throw new \Exception('Errore nel salvare il profilo.');
                        }

                        // Save therapist
                        if (!$therapist->save(false)) {
                            throw new \Exception('Errore nel salvare il terapista.');
                        }

                        // Assign therapist role
                        $auth = Yii::$app->authManager;
                        $therapistRole = $auth->getRole('therapist');
                        if ($therapistRole) {
                            $auth->assign($therapistRole, $user->id);
                        }

                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'Terapista creato con successo.');
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

        return $this->render('create', [
            'user' => $user,
            'profile' => $profile,
            'therapist' => $therapist,
            'specializations' => $specializations,
        ]);
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
                    throw new \Exception('Errore nell\'aggiornare l\'utente: ' . implode(', ', $user->getFirstErrors()));
                }

                // Crittografa i dati sensibili prima di salvare
                $this->encryptSensitiveData($profile);

                if (!$profile->save()) {
                    throw new \Exception('Errore nell\'aggiornare il profilo: ' . implode(', ', $profile->getFirstErrors()));
                }

                if (!$therapist->save()) {
                    throw new \Exception('Errore nell\'aggiornare il terapista: ' . implode(', ', $therapist->getFirstErrors()));
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Terapista aggiornato con successo.');
                return $this->redirect(['view', 'id' => $therapist->id]);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('update', [
            'user' => $user,
            'profile' => $profile,
            'therapist' => $therapist,
            'specializations' => $specializations,
        ]);
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
                    Yii::error("Failed to decrypt phone number: " . $e->getMessage(), __METHOD__);
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
                    Yii::error("Failed to decrypt address: " . $e->getMessage(), __METHOD__);
                    // Se la decodifica fallisce, mantieni il valore originale (potrebbe non essere crittografato)
                }
            }
            
        } catch (\Exception $e) {
            Yii::error("Error in decryptSensitiveData: " . $e->getMessage(), __METHOD__);
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
            Yii::error("Error in encryptSensitiveData: " . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Lists therapists for coordinators (only their own group)
     * @return mixed
     */
    public function actionMyGroup()
    {
        if (!Yii::$app->user->can('view_own_group_therapists')) {
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
            $searchModel->therapist_ids = [0]; // Non-existing ID to force empty result
        }
        
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('my-group', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'coordinatorGroup' => $coordinatorGroup,
        ]);
    }
} 
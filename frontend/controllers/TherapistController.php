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

        $dataProvider = new ActiveDataProvider([
            'query' => Therapist::find()
                ->joinWith(['user', 'user.profile', 'specialization'])
                ->where(['therapists.is_active' => 1]) // Show only active therapists
                ->orderBy('user_profiles.last_name, user_profiles.first_name'),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
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
     * Soft deletes an existing Therapist model by setting is_active to 0.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if (!Yii::$app->user->can('delete_therapist')) {
            throw new ForbiddenHttpException('Non hai i permessi per eliminare terapisti.');
        }

        $therapist = $this->findModel($id);
        $user = $therapist->user;
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Soft delete: set therapist as inactive instead of deleting from database
            $therapist->is_active = 0;
            $user->status = User::STATUS_INACTIVE; // Also deactivate the user account
            
            if ($therapist->save(false) && $user->save(false)) {
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Terapista disattivato con successo.');
            } else {
                throw new \Exception('Errore nel disattivare il terapista.');
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
} 
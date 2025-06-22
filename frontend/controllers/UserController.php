<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use common\models\User;
use common\models\UserProfile;
use common\models\Therapist;
use common\models\Specialization;

/**
 * UserController handles CRUD operations for different user types
 */
class UserController extends Controller
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
                ],
            ],
        ];
    }

    /**
     * Lists administrators
     */
    public function actionAdministrators()
    {
        if (!Yii::$app->user->can('create_admin')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare gli amministratori.');
        }

        $searchModel = new \frontend\models\UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, 'admin');

        return $this->render('administrators/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new administrator
     */
    public function actionCreateAdministrator()
    {
        if (!Yii::$app->user->can('create_admin')) {
            throw new ForbiddenHttpException('Non hai i permessi per creare amministratori.');
        }

        $user = new User(['scenario' => 'create']);
        $profile = new UserProfile();

        if ($user->load(Yii::$app->request->post()) && $profile->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Save user
                if (!$user->save()) {
                    throw new \Exception('Errore nel salvare l\'utente: ' . implode(', ', $user->getFirstErrors()));
                }

                // Save profile
                $profile->user_id = $user->id;
                if (!$profile->save()) {
                    throw new \Exception('Errore nel salvare il profilo: ' . implode(', ', $profile->getFirstErrors()));
                }

                // Assign admin role
                $auth = Yii::$app->authManager;
                $adminRole = $auth->getRole('admin');
                $auth->assign($adminRole, $user->id);

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Amministratore creato con successo.');
                return $this->redirect(['administrators']);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('administrators/create', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    /**
     * Displays a single administrator
     */
    public function actionViewAdministrator($id)
    {
        if (!Yii::$app->user->can('view_admin')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare gli amministratori.');
        }

        $user = $this->findUserModel($id);
        
        return $this->render('administrators/view', [
            'model' => $user,
        ]);
    }

    /**
     * Updates an existing administrator
     */
    public function actionUpdateAdministrator($id)
    {
        if (!Yii::$app->user->can('update_admin')) {
            throw new ForbiddenHttpException('Non hai i permessi per modificare amministratori.');
        }

        $user = $this->findUserModel($id);
        $profile = $user->profile ?: new UserProfile(['user_id' => $user->id]);

        if ($user->load(Yii::$app->request->post()) && $profile->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$user->save()) {
                    throw new \Exception('Errore nell\'aggiornare l\'utente: ' . implode(', ', $user->getFirstErrors()));
                }

                if (!$profile->save()) {
                    throw new \Exception('Errore nell\'aggiornare il profilo: ' . implode(', ', $profile->getFirstErrors()));
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Amministratore aggiornato con successo.');
                return $this->redirect(['view-administrator', 'id' => $user->id]);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('administrators/update', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    /**
     * Deletes an existing administrator
     */
    public function actionDeleteAdministrator($id)
    {
        if (!Yii::$app->user->can('delete_admin')) {
            throw new ForbiddenHttpException('Non hai i permessi per eliminare amministratori.');
        }

        $user = $this->findUserModel($id);
        
        if ($user->delete()) {
            Yii::$app->session->setFlash('success', 'Amministratore eliminato con successo.');
        } else {
            Yii::$app->session->setFlash('error', 'Errore nell\'eliminare l\'amministratore.');
        }

        return $this->redirect(['administrators']);
    }

    /**
     * Lists coordinators
     */
    public function actionCoordinators()
    {
        if (!Yii::$app->user->can('create_coordinator')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i coordinatori.');
        }

        $searchModel = new \frontend\models\UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, 'coordinator');

        return $this->render('coordinators/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new coordinator
     */
    public function actionCreateCoordinator()
    {
        if (!Yii::$app->user->can('create_coordinator')) {
            throw new ForbiddenHttpException('Non hai i permessi per creare coordinatori.');
        }

        $user = new User(['scenario' => 'create']);
        $profile = new UserProfile();

        if ($user->load(Yii::$app->request->post()) && $profile->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Save user
                if (!$user->save()) {
                    throw new \Exception('Errore nel salvare l\'utente: ' . implode(', ', $user->getFirstErrors()));
                }

                // Save profile
                $profile->user_id = $user->id;
                if (!$profile->save()) {
                    throw new \Exception('Errore nel salvare il profilo: ' . implode(', ', $profile->getFirstErrors()));
                }

                // Assign coordinator role
                $auth = Yii::$app->authManager;
                $coordinatorRole = $auth->getRole('coordinator');
                $auth->assign($coordinatorRole, $user->id);

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Coordinatore creato con successo.');
                return $this->redirect(['coordinators']);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('coordinators/create', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    /**
     * Displays a single coordinator
     */
    public function actionViewCoordinator($id)
    {
        if (!Yii::$app->user->can('view_coordinator')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i coordinatori.');
        }

        $user = $this->findUserModel($id);
        
        return $this->render('coordinators/view', [
            'model' => $user,
        ]);
    }

    /**
     * Updates an existing coordinator
     */
    public function actionUpdateCoordinator($id)
    {
        if (!Yii::$app->user->can('update_coordinator')) {
            throw new ForbiddenHttpException('Non hai i permessi per modificare coordinatori.');
        }

        $user = $this->findUserModel($id);
        $profile = $user->profile ?: new UserProfile(['user_id' => $user->id]);

        if ($user->load(Yii::$app->request->post()) && $profile->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$user->save()) {
                    throw new \Exception('Errore nell\'aggiornare l\'utente: ' . implode(', ', $user->getFirstErrors()));
                }

                if (!$profile->save()) {
                    throw new \Exception('Errore nell\'aggiornare il profilo: ' . implode(', ', $profile->getFirstErrors()));
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Coordinatore aggiornato con successo.');
                return $this->redirect(['view-coordinator', 'id' => $user->id]);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('coordinators/update', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    /**
     * Deletes an existing coordinator
     */
    public function actionDeleteCoordinator($id)
    {
        if (!Yii::$app->user->can('delete_coordinator')) {
            throw new ForbiddenHttpException('Non hai i permessi per eliminare coordinatori.');
        }

        $user = $this->findUserModel($id);
        
        if ($user->delete()) {
            Yii::$app->session->setFlash('success', 'Coordinatore eliminato con successo.');
        } else {
            Yii::$app->session->setFlash('error', 'Errore nell\'eliminare il coordinatore.');
        }

        return $this->redirect(['coordinators']);
    }

    /**
     * Lists therapists
     */
    public function actionTherapists()
    {
        if (!Yii::$app->user->can('create_therapist')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i terapisti.');
        }

        $dataProvider = new ActiveDataProvider([
            'query' => Therapist::find()
                ->joinWith(['user', 'user.profile', 'specialization'])
                ->orderBy('user_profiles.last_name, user_profiles.first_name'),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('therapists/index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new therapist
     */
    public function actionCreateTherapist()
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
            
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Save user
                if (!$user->save()) {
                    throw new \Exception('Errore nel salvare l\'utente: ' . implode(', ', $user->getFirstErrors()));
                }

                // Save profile
                $profile->user_id = $user->id;
                
                // Crittografa i dati sensibili prima di salvare
                $this->encryptSensitiveData($profile);
                
                if (!$profile->save()) {
                    throw new \Exception('Errore nel salvare il profilo: ' . implode(', ', $profile->getFirstErrors()));
                }

                // Save therapist
                $therapist->user_id = $user->id;
                if (!$therapist->save()) {
                    throw new \Exception('Errore nel salvare il terapista: ' . implode(', ', $therapist->getFirstErrors()));
                }

                // Assign therapist role
                $auth = Yii::$app->authManager;
                $therapistRole = $auth->getRole('therapist');
                $auth->assign($therapistRole, $user->id);

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Terapista creato con successo.');
                return $this->redirect(['therapists']);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('therapists/create', [
            'user' => $user,
            'profile' => $profile,
            'therapist' => $therapist,
            'specializations' => $specializations,
        ]);
    }

    /**
     * Displays a single therapist
     */
    public function actionViewTherapist($id)
    {
        if (!Yii::$app->user->can('view_therapist')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i terapisti.');
        }

        $therapist = $this->findTherapistModel($id);
        
        // Decodifica i dati sensibili del profilo utente
        $this->decryptSensitiveData($therapist->user->profile);
        
        return $this->render('therapists/view', [
            'model' => $therapist,
        ]);
    }

    /**
     * Updates an existing therapist
     */
    public function actionUpdateTherapist($id)
    {
        if (!Yii::$app->user->can('update_therapist')) {
            throw new ForbiddenHttpException('Non hai i permessi per modificare terapisti.');
        }

        $therapist = $this->findTherapistModel($id);
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
                return $this->redirect(['view-therapist', 'id' => $therapist->id]);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('therapists/update', [
            'user' => $user,
            'profile' => $profile,
            'therapist' => $therapist,
            'specializations' => $specializations,
        ]);
    }

    /**
     * Deletes an existing therapist
     */
    public function actionDeleteTherapist($id)
    {
        if (!Yii::$app->user->can('delete_therapist')) {
            throw new ForbiddenHttpException('Non hai i permessi per eliminare terapisti.');
        }

        $therapist = $this->findTherapistModel($id);
        $user = $therapist->user;
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Delete therapist first, then user (due to foreign key constraints)
            if ($therapist->delete() && $user->delete()) {
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Terapista eliminato con successo.');
            } else {
                throw new \Exception('Errore nell\'eliminare il terapista.');
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['therapists']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findUserModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La pagina richiesta non esiste.');
    }

    /**
     * Finds the Therapist model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Therapist the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findTherapistModel($id)
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
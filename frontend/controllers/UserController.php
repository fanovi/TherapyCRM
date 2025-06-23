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

        // Decrittografa i dati sensibili per tutti i modelli nella lista
        foreach ($dataProvider->getModels() as $user) {
            if ($user->profile) {
                $this->decryptSensitiveData($user->profile);
            }
        }

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

                // Crittografa i dati sensibili prima di salvare
                $this->encryptSensitiveData($profile);

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
        
        // Decodifica i dati sensibili per la visualizzazione
        if ($user->profile) {
            $this->decryptSensitiveData($user->profile);
        }
        
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

        // Decodifica i dati sensibili per mostrarli nel form
        $this->decryptSensitiveData($profile);

        if ($user->load(Yii::$app->request->post()) && $profile->load(Yii::$app->request->post())) {
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
     * Toggles the active status of an existing administrator.
     * If the administrator is active, it will be deactivated, and vice versa.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionToggleStatusAdministrator($id)
    {
        if (!Yii::$app->user->can('delete_admin')) {
            throw new ForbiddenHttpException('Non hai i permessi per gestire lo stato degli amministratori.');
        }

        $user = $this->findUserModel($id);
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Toggle active status - usa i valori stringa corretti (ENUM database)
            $newStatus = ($user->status == User::STATUS_ACTIVE) ? User::STATUS_INACTIVE : User::STATUS_ACTIVE;
            $user->status = $newStatus;
            
            if ($user->save(false)) {
                $transaction->commit();
                $message = ($newStatus == User::STATUS_ACTIVE) ? 'Amministratore attivato con successo.' : 'Amministratore disattivato con successo.';
                Yii::$app->session->setFlash('success', $message);
            } else {
                throw new \Exception('Errore nel cambiare lo stato dell\'amministratore.');
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
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

        // Decrittografa i dati sensibili per tutti i modelli nella lista
        foreach ($dataProvider->getModels() as $user) {
            if ($user->profile) {
                $this->decryptSensitiveData($user->profile);
            }
        }

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

                // Crittografa i dati sensibili prima di salvare
                $this->encryptSensitiveData($profile);

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
        
        // Decodifica i dati sensibili del profilo utente
        $this->decryptSensitiveData($user->profile);
        
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

        // Decodifica i dati sensibili per mostrarli nel form
        $this->decryptSensitiveData($profile);

        if ($user->load(Yii::$app->request->post()) && $profile->load(Yii::$app->request->post())) {
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
     * Toggles the active status of an existing coordinator.
     * If the coordinator is active, it will be deactivated, and vice versa.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionToggleStatus($id)
    {
        if (!Yii::$app->user->can('delete_coordinator')) {
            throw new ForbiddenHttpException('Non hai i permessi per gestire lo stato dei coordinatori.');
        }

        $user = $this->findUserModel($id);
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Toggle active status - usa i valori stringa corretti (ENUM database)
            $newStatus = ($user->status == User::STATUS_ACTIVE) ? User::STATUS_INACTIVE : User::STATUS_ACTIVE;
            $user->status = $newStatus;
            
            if ($user->save(false)) {
                $transaction->commit();
                $message = ($newStatus == User::STATUS_ACTIVE) ? 'Coordinatore attivato con successo.' : 'Coordinatore disattivato con successo.';
                Yii::$app->session->setFlash('success', $message);
            } else {
                throw new \Exception('Errore nel cambiare lo stato del coordinatore.');
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['coordinators']);
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
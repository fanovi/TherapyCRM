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
} 
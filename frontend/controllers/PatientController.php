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
use common\models\Patient;
use common\models\District;
use common\models\User;
use common\models\UserProfile;
use common\models\AccountPatient;
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

        return $this->render('view', [
            'model' => $this->findModel($id),
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

        if ($user->load(Yii::$app->request->post()) && 
            $profile->load(Yii::$app->request->post()) && 
            $accountPatient->load(Yii::$app->request->post())) {
            
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

                // Create account-patient relationship
                $accountPatient->user_id = $user->id;
                $accountPatient->patient_id = $patient->id;
                if (!$accountPatient->save()) {
                    throw new \Exception('Errore nel collegare utente e paziente: ' . implode(', ', $accountPatient->getFirstErrors()));
                }

                // Assign patient_family role
                $auth = Yii::$app->authManager;
                $patientRole = $auth->getRole('patient_family');
                $auth->assign($patientRole, $user->id);

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Credenziali create con successo per il paziente.');
                return $this->redirect(['index']);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
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
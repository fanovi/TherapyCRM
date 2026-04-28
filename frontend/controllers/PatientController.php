<?php

namespace frontend\controllers;

use common\components\CodiceFiscaleGenerator;
use common\components\Helper;
use common\models\AccountPatient;
use common\models\AuthToken;
use common\models\Comune;
use common\models\District;
use common\models\Patient;
use common\models\Provincia;
use common\models\User;
use common\models\UserProfile;
use frontend\models\PatientSearch;
use frontend\models\AccountPatientSearch;
use frontend\models\AccountSearch;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use Yii;

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
                        'roles' => ['@'],  // Only authenticated users
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'reset-password' => ['POST', 'GET'],  // Allow GET for testing
                    'download-credentials-pdf' => ['GET', 'POST'],
                    'send-notification' => ['POST'],
                    'create-credentials' => ['GET', 'POST'],
                    'generate-fiscal-code' => ['POST'],
                    'load-comuni' => ['GET'],
                    'get-age' => ['GET'],
                    'link-patient' => ['POST'],
                    'unlink-patient' => ['POST'],
                    'search-patients' => ['GET'],
                    'search-patients-for-account' => ['GET'],
                    'update-account' => ['POST'],
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
     * Lists all patient family accounts with their linked patients
     */
    public function actionAccounts()
    {
        if (!Yii::$app->user->can('create_patient')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare gli account pazienti.');
        }

        $searchModel = new AccountSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('accounts', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Lists patients assigned to the therapists of the coordinator's group.
     *
     * Specchio di `TherapistController::actionMyGroup` ma sui pazienti:
     * mostra ai coordinatori i pazienti gestiti dai terapisti del proprio
     * gruppo di coordinamento (un paziente "appartiene" a un terapista se
     * ha almeno un appuntamento non cancellato con lui, da piano o privato).
     */
    public function actionMyGroup()
    {
        if (!Yii::$app->user->can('view_own_group_patients')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i pazienti del gruppo.');
        }

        $coordinatorUserId = Yii::$app->user->id;
        $coordinatorGroup = \common\models\CoordinatorGroup::find()
            ->where(['coordinator_user_id' => $coordinatorUserId])
            ->one();

        if (!$coordinatorGroup) {
            return $this->render('no-group', [
                'message' => 'Non sei assegnato a nessun gruppo di terapisti.'
            ]);
        }

        $therapistIds = \common\models\GroupTherapist::find()
            ->select('therapist_id')
            ->where(['group_id' => $coordinatorGroup->id])
            ->andWhere(['assigned_to' => null])
            ->column();

        $searchModel = new PatientSearch();
        // Forza il filtro server-side: anche se vuoto, fail-closed (zero risultati).
        $searchModel->therapist_ids = !empty($therapistIds) ? $therapistIds : [0];

        $dataProvider = $searchModel->searchDataProvider(Yii::$app->request->queryParams);

        // Mappa paziente_id => [{id, name}] dei terapisti del gruppo che hanno
        // appuntamenti (non cancellati) con il paziente. Serve a mostrare in
        // griglia per quale terapista vedo quel paziente.
        $patientTherapistsMap = [];
        if (!empty($therapistIds)) {
            $rows = (new \yii\db\Query())
                ->select([
                    'patient_id' => new \yii\db\Expression('COALESCE(a.patient_id, tp.patient_id)'),
                    'therapist_id' => 'a.therapist_id',
                    'first_name' => 'up.first_name',
                    'last_name' => 'up.last_name',
                ])
                ->distinct()
                ->from(['a' => '{{%appointments}}'])
                ->leftJoin(['pt' => '{{%plan_therapies}}'], 'pt.id = a.plan_therapy_id')
                ->leftJoin(['tp' => '{{%therapeutic_plans}}'], 'tp.id = pt.therapeutic_plan_id')
                ->leftJoin(['up' => '{{%user_profiles}}'], 'up.user_id = a.therapist_id')
                ->where(['a.therapist_id' => $therapistIds])
                ->andWhere(['!=', 'a.status', \common\models\Appointment::STATUS_CANCELLED])
                ->andWhere(['or',
                    ['IS NOT', 'a.patient_id', null],
                    ['IS NOT', 'tp.patient_id', null],
                ])
                ->all();

            foreach ($rows as $row) {
                $pid = (int) $row['patient_id'];
                $tid = (int) $row['therapist_id'];
                if ($pid <= 0 || $tid <= 0) {
                    continue;
                }
                if (!isset($patientTherapistsMap[$pid])) {
                    $patientTherapistsMap[$pid] = [];
                }
                // Evita duplicati per paziente.
                if (isset($patientTherapistsMap[$pid][$tid])) {
                    continue;
                }
                $name = trim(($row['last_name'] ?? '') . ' ' . ($row['first_name'] ?? ''));
                if ($name === '') {
                    $name = 'Terapista #' . $tid;
                }
                $patientTherapistsMap[$pid][$tid] = [
                    'id' => $tid,
                    'name' => $name,
                ];
            }
            // Riordina in liste indicizzate.
            foreach ($patientTherapistsMap as $pid => $list) {
                $patientTherapistsMap[$pid] = array_values($list);
            }
        }

        return $this->render('my-group', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'coordinatorGroup' => $coordinatorGroup,
            'therapistCount' => count($therapistIds),
            'patientTherapistsMap' => $patientTherapistsMap,
        ]);
    }

    /**
     * Links a patient to an account via AJAX
     */
    public function actionLinkPatient()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!Yii::$app->user->can('create_patient')) {
            return ['success' => false, 'error' => 'Non hai i permessi.'];
        }

        $userId = Yii::$app->request->post('user_id');
        $patientId = Yii::$app->request->post('patient_id');
        $relationshipType = Yii::$app->request->post('relationship_type', 'other');
        $hasParentalAuthority = Yii::$app->request->post('has_parental_authority', 0);

        if (!$userId || !$patientId) {
            return ['success' => false, 'error' => 'Parametri mancanti.'];
        }

        // Check if link already exists
        $existing = AccountPatient::findOne(['user_id' => $userId, 'patient_id' => $patientId]);
        if ($existing) {
            return ['success' => false, 'error' => 'Questo paziente è già collegato a questo account.'];
        }

        $accountPatient = new AccountPatient();
        $accountPatient->user_id = $userId;
        $accountPatient->patient_id = $patientId;
        $accountPatient->relationship_type = $relationshipType;
        $accountPatient->has_parental_authority = $hasParentalAuthority;

        if ($accountPatient->save()) {
            $patient = Patient::findOne($patientId);
            return [
                'success' => true,
                'message' => 'Paziente collegato con successo.',
                'patient' => [
                    'id' => $patient->id,
                    'name' => $patient->last_name . ' ' . $patient->first_name,
                ]
            ];
        }

        return ['success' => false, 'error' => 'Errore nel collegamento: ' . implode(', ', $accountPatient->getFirstErrors())];
    }

    /**
     * Unlinks a patient from an account via AJAX
     */
    public function actionUnlinkPatient()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!Yii::$app->user->can('create_patient')) {
            return ['success' => false, 'error' => 'Non hai i permessi.'];
        }

        $userId = Yii::$app->request->post('user_id');
        $patientId = Yii::$app->request->post('patient_id');

        if (!$userId || !$patientId) {
            return ['success' => false, 'error' => 'Parametri mancanti.'];
        }

        $accountPatient = AccountPatient::findOne(['user_id' => $userId, 'patient_id' => $patientId]);
        if (!$accountPatient) {
            return ['success' => false, 'error' => 'Collegamento non trovato.'];
        }

        if ($accountPatient->delete()) {
            return ['success' => true, 'message' => 'Paziente scollegato con successo.'];
        }

        return ['success' => false, 'error' => 'Errore nella rimozione del collegamento.'];
    }

    /**
     * Views a single account with linked patients
     */
    public function actionViewAccount($id)
    {
        if (!Yii::$app->user->can('create_patient')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare gli account.');
        }

        $user = User::find()
            ->joinWith(['authAssignments'])
            ->where(['users.id' => $id, 'auth_assignment.item_name' => 'patient_family'])
            ->one();

        if (!$user) {
            throw new NotFoundHttpException('Account non trovato.');
        }

        $accountPatients = AccountPatient::find()
            ->where(['user_id' => $id])
            ->with(['patient'])
            ->all();

        // Decrypt sensitive data for display
        if ($user->profile) {
            $this->decryptSensitiveData($user->profile);
        }

        return $this->render('view-account', [
            'model' => $user,
            'accountPatients' => $accountPatients,
        ]);
    }

    /**
     * Updates account data via AJAX
     */
    public function actionUpdateAccount()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!Yii::$app->user->can('create_patient')) {
            return ['success' => false, 'error' => 'Non hai i permessi.'];
        }

        $userId = Yii::$app->request->post('user_id');
        if (!$userId) {
            return ['success' => false, 'error' => 'ID utente mancante.'];
        }

        // Find user with patient_family role
        $user = User::find()
            ->joinWith(['authAssignments'])
            ->where(['users.id' => $userId, 'auth_assignment.item_name' => 'patient_family'])
            ->one();

        if (!$user) {
            return ['success' => false, 'error' => 'Account non trovato.'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Update email if changed
            $newEmail = Yii::$app->request->post('email');
            if ($newEmail && $newEmail !== $user->email) {
                // Check if email already exists
                $existingUser = User::find()->where(['email' => $newEmail])->andWhere(['!=', 'id', $userId])->one();
                if ($existingUser) {
                    return ['success' => false, 'error' => 'Questa email è già utilizzata da un altro account.'];
                }
                $user->email = $newEmail;
                $user->username = $newEmail;
                if (!$user->save()) {
                    throw new \Exception('Errore nel salvare l\'email: ' . implode(', ', $user->getFirstErrors()));
                }
            }

            // Update profile
            $profile = $user->profile;
            if (!$profile) {
                $profile = new UserProfile();
                $profile->user_id = $user->id;
            }

            $profile->first_name = Yii::$app->request->post('first_name', $profile->first_name);
            $profile->last_name = Yii::$app->request->post('last_name', $profile->last_name);
            $profile->fiscal_code = Yii::$app->request->post('fiscal_code', $profile->fiscal_code);
            $profile->phone = Yii::$app->request->post('phone', $profile->phone);
            $profile->address = Yii::$app->request->post('address', $profile->address);

            // Encrypt sensitive data before saving
            $this->encryptSensitiveData($profile);

            if (!$profile->save()) {
                throw new \Exception('Errore nel salvare il profilo: ' . implode(', ', $profile->getFirstErrors()));
            }

            $transaction->commit();
            return ['success' => true, 'message' => 'Account aggiornato con successo.'];

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Error updating account: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * AJAX: cerca pazienti per la creazione di un nuovo account paziente.
     *
     * Usato dalla modale "Nuovo Account" in `/patient/accounts`.
     * Rispetta i permessi:
     * - utenti con `create_patient` (admin/manager): vedono tutti i pazienti;
     * - utenti con `view_own_group_patients` (coordinator): vedono solo i
     *   pazienti dei terapisti del proprio gruppo;
     * - altri ruoli: nessun risultato.
     *
     * @return array JSON
     */
    public function actionSearchPatientsForAccount()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $user = Yii::$app->user;
        $hasGlobalAccess = $user->can('create_patient');
        $hasGroupAccess = $user->can('view_own_group_patients');

        if (!$hasGlobalAccess && !$hasGroupAccess) {
            return ['results' => []];
        }

        $term = trim((string) Yii::$app->request->get('term', ''));
        if (mb_strlen($term) < 2) {
            return ['results' => []];
        }

        $query = Patient::find()
            ->where(['or',
                ['like', 'first_name', $term],
                ['like', 'last_name', $term],
                ['like', 'fiscal_code', $term],
                ['like', "CONCAT(last_name, ' ', first_name)", $term],
                ['like', "CONCAT(first_name, ' ', last_name)", $term],
            ])
            ->orderBy('last_name, first_name')
            ->limit(15);

        // Coordinator senza permessi globali: limitiamo ai pazienti del gruppo
        if (!$hasGlobalAccess && $hasGroupAccess) {
            $coordinatorGroup = \common\models\CoordinatorGroup::find()
                ->where(['coordinator_user_id' => $user->id])
                ->one();

            if (!$coordinatorGroup) {
                return ['results' => []];
            }

            $therapistIds = \common\models\GroupTherapist::find()
                ->select('therapist_id')
                ->where(['group_id' => $coordinatorGroup->id])
                ->andWhere(['assigned_to' => null])
                ->column();

            $allowedPatientIds = !empty($therapistIds)
                ? \frontend\models\PatientSearch::patientIdsForTherapists($therapistIds)
                : [];

            if (empty($allowedPatientIds)) {
                return ['results' => []];
            }

            $query->andWhere(['id' => $allowedPatientIds]);
        }

        $patients = $query->all();

        $results = [];
        foreach ($patients as $patient) {
            $accountsCount = AccountPatient::find()
                ->where(['patient_id' => $patient->id])
                ->count();

            $results[] = [
                'id' => (int) $patient->id,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'full_name' => $patient->last_name . ' ' . $patient->first_name,
                'fiscal_code' => $patient->fiscal_code,
                'birth_date' => $patient->birth_date
                    ? Yii::$app->formatter->asDate($patient->birth_date, 'php:d/m/Y')
                    : null,
                'accounts_count' => (int) $accountsCount,
                'create_url' => \yii\helpers\Url::to(['create-credentials', 'id' => $patient->id]),
            ];
        }

        return ['results' => $results];
    }

    /**
     * Search patients for linking via AJAX
     */
    public function actionSearchPatients()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!Yii::$app->user->can('create_patient')) {
            return ['results' => []];
        }

        $term = Yii::$app->request->get('term', '');
        $userId = Yii::$app->request->get('user_id');

        if (strlen($term) < 2) {
            return ['results' => []];
        }

        // Get already linked patient IDs for this user
        $linkedPatientIds = AccountPatient::find()
            ->select('patient_id')
            ->where(['user_id' => $userId])
            ->column();

        // Search patients not already linked
        $query = Patient::find()
            ->where(['or',
                ['like', 'first_name', $term],
                ['like', 'last_name', $term],
                ['like', 'fiscal_code', $term],
            ])
            ->orderBy('last_name, first_name')
            ->limit(10);

        if (!empty($linkedPatientIds)) {
            $query->andWhere(['not in', 'id', $linkedPatientIds]);
        }

        $patients = $query->all();

        $results = [];
        foreach ($patients as $patient) {
            $results[] = [
                'id' => $patient->id,
                'text' => $patient->last_name . ' ' . $patient->first_name . ($patient->fiscal_code ? ' - ' . $patient->fiscal_code : ''),
            ];
        }

        return ['results' => $results];
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
        $districts = District::getDropdownData();
        $province = ArrayHelper::map(Provincia::find()->orderBy('nome')->all(), 'id', 'nome');

        if ($patient->load(Yii::$app->request->post())) {
            // DEBUG: Log dei dati POST
            Yii::error('=== DEBUG POST DATA CREATE ===');
            Yii::error('POST data: ' . json_encode(Yii::$app->request->post(), JSON_UNESCAPED_UNICODE));
            Yii::error('birth_city: ' . ($patient->birth_city ?? 'NULL'));
            Yii::error('residence_city: ' . ($patient->residence_city ?? 'NULL'));
            Yii::error('residence_postal_code: ' . ($patient->residence_postal_code ?? 'NULL'));

            // Se è stata selezionata una provincia di nascita, popola automaticamente nome e sigla
            if ($patient->birth_province_id && $patient->born_in_italy) {
                $provincia = Provincia::findOne($patient->birth_province_id);
                if ($provincia) {
                    $patient->birth_province_name = $provincia->nome;
                    $patient->birth_province_code = $provincia->sigla;
                }
            } elseif (!$patient->born_in_italy) {
                // Se NON è nato in Italia, pulisci i campi provincia
                $patient->birth_province_name = null;
                $patient->birth_province_code = null;
            }

            // Per la residenza, rimuoviamo il controllo residence_in_italy (sempre Italia)
            if ($patient->residence_province_id) {
                $provincia = Provincia::findOne($patient->residence_province_id);
                if ($provincia) {
                    $patient->residence_province_name = $provincia->nome;
                    $patient->residence_province_code = $provincia->sigla;
                }
            }

            if ($patient->save()) {
                Yii::$app->session->setFlash('success', 'Paziente creato con successo.');
                return $this->redirect(['view', 'id' => $patient->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Errore nel salvare il paziente: ' . implode(', ', $patient->getFirstErrors()));
            }
        }

        return $this->render('create', [
            'patient' => $patient,
            'districts' => $districts,
            'province' => $province,
            'isUpdate' => false,
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

        // Decrypt sensitive data for display
        foreach ($accountPatients as $accountPatient) {
            if ($accountPatient->user && $accountPatient->user->profile) {
                $this->decryptSensitiveData($accountPatient->user->profile);
            }
        }

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

        // Se il paziente ha una provincia di nascita, trova l'ID per pre-popolare la select
        if ($patient->birth_province_code && $patient->born_in_italy) {
            $provincia = Provincia::find()->where(['sigla' => $patient->birth_province_code])->one();
            if ($provincia) {
                $patient->birth_province_id = $provincia->id;
            }
        }

        // Se il paziente ha una provincia di residenza, trova l'ID per pre-popolare la select
        if ($patient->residence_province_code) {
            $provincia = Provincia::find()->where(['sigla' => $patient->residence_province_code])->one();
            if ($provincia) {
                $patient->residence_province_id = $provincia->id;
                $patient->residence_in_italy = 1;  // Se ha una provincia italiana, è residente in Italia
            }
        }

        // Get districts for dropdown
        $districts = District::getDropdownData();

        $province = ArrayHelper::map(Provincia::find()->orderBy('nome')->all(), 'id', 'nome');

        if ($patient->load(Yii::$app->request->post())) {
            // Se è stata selezionata una provincia di nascita, popola automaticamente nome e sigla
            if ($patient->birth_province_id && $patient->born_in_italy) {
                $provincia = Provincia::findOne($patient->birth_province_id);
                if ($provincia) {
                    $patient->birth_province_name = $provincia->nome;
                    $patient->birth_province_code = $provincia->sigla;
                }
            } elseif (!$patient->born_in_italy) {
                // Se NON è nato in Italia, pulisci i campi provincia
                $patient->birth_province_name = null;
                $patient->birth_province_code = null;
            }

            // Per la residenza, rimuoviamo il controllo residence_in_italy (sempre Italia)
            if ($patient->residence_province_id) {
                $provincia = Provincia::findOne($patient->residence_province_id);
                if ($provincia) {
                    $patient->residence_province_name = $provincia->nome;
                    $patient->residence_province_code = $provincia->sigla;
                }
            }

            if ($patient->save()) {
                Yii::$app->session->setFlash('success', 'Paziente aggiornato con successo.');
                return $this->redirect(['view', 'id' => $patient->id]);
            } else {
                Yii::$app->session->setFlash('error', "Errore nell'aggiornare il paziente: " . implode(', ', $patient->getFirstErrors()));
            }
        }

        return $this->render('update', [
            'patient' => $patient,
            'districts' => $districts,
            'province' => $province,
            'isUpdate' => true,
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
            Yii::$app->session->setFlash('error', "Errore nell'eliminare il paziente.");
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

        // I campi del profilo NON vengono pre-popolati con i dati del paziente
        // al primo render (GET). Per la modalita' "io stesso" la view mostra
        // un riquadro riassuntivo con i dati del paziente; in caso di
        // genitore/tutore/altro l'utente compila i propri dati.
        // I dati del paziente vengono comunque forzati lato server in POST
        // quando relationship_type === 'self' (vedi piu' sotto).

        // Suggerisci una password generata in automatico (modificabile dall'utente).
        // Viene impostata solo al primo render (GET), in POST i valori arrivano dal form.
        if (!Yii::$app->request->isPost) {
            $suggestedPassword = $this->generateRandomPassword(12);
            $user->password = $suggestedPassword;
            $user->password_repeat = $suggestedPassword;
        }

        $postData = Yii::$app->request->post();
        Yii::info('POST data received: ' . print_r($postData, true));

        if (
            $user->load($postData) &&
            $profile->load($postData) &&
            $accountPatient->load($postData)
        ) {
            // Se la relazione e' "io stesso" (il paziente e' anche l'account
            // utente), forziamo i dati anagrafici a quelli del paziente per
            // evitare disallineamenti, anche se eventualmente la view ha
            // inviato valori diversi (es. campi readonly bypassati).
            if ($accountPatient->relationship_type === AccountPatient::RELATIONSHIP_SELF) {
                $profile->first_name = $patient->first_name;
                $profile->last_name = $patient->last_name;
                $profile->fiscal_code = $patient->fiscal_code;
                if (!empty($patient->phone_number)) {
                    $profile->phone = $patient->phone_number;
                }
                if (!empty($patient->residence_address)) {
                    $profile->address = trim($patient->residence_address . ' '
                        . ($patient->residence_postal_code ?? '') . ' '
                        . ($patient->residence_city ?? '')
                        . ($patient->residence_province_code ? ' (' . $patient->residence_province_code . ')' : ''));
                }
            }
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
                    throw new \Exception("Errore nel salvare l'utente: " . implode(', ', $user->getFirstErrors()));
                }

                // Save profile
                $profile->user_id = $user->id;
                // Encrypt sensitive data before saving
                $this->encryptSensitiveData($profile);
                if (!$profile->save()) {
                    throw new \Exception('Errore nel salvare il profilo: ' . implode(', ', $profile->getFirstErrors()));
                }

                // Create account-patient relationship
                $accountPatient->user_id = $user->id;
                $accountPatient->patient_id = $patient->id;
                if (!$accountPatient->save()) {
                    throw new \Exception('Errore nel collegare utente e paziente: ' . implode(', ', $accountPatient->getFirstErrors()));
                }

                // Assegna il ruolo coerente con la relazione:
                // - self  -> ruolo `patient`        (l'utente E' il paziente)
                // - altro -> ruolo `patient_family` (genitore/tutore/altro familiare)
                $auth = Yii::$app->authManager;
                $roleName = ($accountPatient->relationship_type === AccountPatient::RELATIONSHIP_SELF)
                    ? 'patient'
                    : 'patient_family';
                $patientRole = $auth->getRole($roleName);
                if ($patientRole) {
                    $auth->assign($patientRole, $user->id);
                }

                $transaction->commit();

                // Generate PDF with credentials automatically and send email
                Yii::info('Generazione PDF e invio email per nuovo account: ' . $user->email);
                $this->generateCredentialsPdf($user, $plainPassword);

                // Handle AJAX requests for automatic PDF download
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                    return [
                        'success' => true,
                        'message' => 'Credenziali create con successo! PDF generato e email inviata.',
                        'downloadUrl' => \yii\helpers\Url::to(['download-credentials-pdf']),
                        'redirectUrl' => \yii\helpers\Url::to(['view', 'id' => $patient->id])
                    ];
                }

                Yii::$app->session->setFlash('success', 'Credenziali create con successo! PDF generato e email inviata a ' . $user->email . '.');
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
     * Sends existing credentials via email without changing password
     */
    public function actionSendCredentials($userId = null)
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
            throw new ForbiddenHttpException('Non hai i permessi per inviare credenziali.');
        }

        $user = User::findOne($userId);
        if (!$user) {
            Yii::error('Utente non trovato con ID: ' . $userId);
            throw new NotFoundHttpException('Utente non trovato.');
        }

        Yii::info('Invio credenziali esistenti per utente: ' . $user->email);

        try {
            // Send email with current credentials (no password change)
            $this->sendExistingCredentialsEmail($user);

            Yii::$app->session->setFlash('success', 'Credenziali inviate via email con successo.');
            Yii::info('Email credenziali esistenti inviata con successo a: ' . $user->email);
        } catch (\Exception $e) {
            Yii::error("Errore nell'invio email credenziali: " . $e->getMessage());
            Yii::$app->session->setFlash('error', "Errore nell'invio delle credenziali via email.");
        }

        // Handle AJAX requests differently
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['status' => 'success', 'message' => 'Credenziali inviate via email con successo'];
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
    /**
     * Genera una password casuale che rispetta le regole di validazione di User:
     *  - lunghezza tra 8 e 20 caratteri (clamp del parametro $length);
     *  - almeno una lettera maiuscola, una minuscola, un numero e un carattere
     *    speciale (compatibile con il pattern di regola in `User::rules`).
     *
     * Vengono esclusi caratteri ambigui (0/O/o/1/l/I) per ridurre errori di
     * trascrizione manuale della password.
     *
     * @param int $length Lunghezza desiderata (clampata in [8, 20]).
     * @return string
     */
    private function generateRandomPassword($length = 12)
    {
        $length = max(8, min(20, (int) $length));

        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghijkmnopqrstuvwxyz';
        $digits  = '23456789';
        // Nota: NON includere "_" perche' nel pattern di validazione (\W) e'
        // considerato word-char e fallirebbe il check del carattere speciale.
        $special = '!@#$%^&*-+=?';

        try {
            $pickRandom = function (string $chars) {
                return $chars[random_int(0, strlen($chars) - 1)];
            };
        } catch (\Throwable $e) {
            $pickRandom = function (string $chars) {
                return $chars[mt_rand(0, strlen($chars) - 1)];
            };
        }

        // Garantiamo almeno un carattere per ogni categoria richiesta.
        $chars = [
            $pickRandom($upper),
            $pickRandom($lower),
            $pickRandom($digits),
            $pickRandom($special),
        ];

        $all = $upper . $lower . $digits . $special;
        for ($i = count($chars); $i < $length; $i++) {
            $chars[] = $pickRandom($all);
        }

        // Mescolamento sicuro per non avere posizioni prevedibili.
        for ($i = count($chars) - 1; $i > 0; $i--) {
            try {
                $j = random_int(0, $i);
            } catch (\Throwable $e) {
                $j = mt_rand(0, $i);
            }
            $tmp = $chars[$i];
            $chars[$i] = $chars[$j];
            $chars[$j] = $tmp;
        }

        return implode('', $chars);
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

            // Invia email con PDF allegato
            $this->sendCredentialsEmail($user, $password, $pdfContent, $filename);
        } catch (\Exception $e) {
            Yii::error('Errore nella generazione PDF: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            Yii::$app->session->setFlash('error', 'Errore nella generazione del PDF: ' . $e->getMessage());
        }
    }

    /**
     * Invia email con credenziali e PDF allegato
     */
    private function sendCredentialsEmail($user, $password, $pdfContent, $filename)
    {
        try {
            Yii::info("Invio email credenziali a: {$user->email}");

            $profile = $user->profile;
            $userName = $profile ? $profile->first_name : $user->email;

            $emailSent = Yii::$app
                ->mailer
                ->compose()
                ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
                ->setTo($user->email)
                ->setSubject('Credenziali di Accesso - ' . Yii::$app->name)
                ->setHtmlBody($this->generateCredentialsEmailHtml($user, $password, $userName))
                ->attachContent($pdfContent, [
                    'fileName' => $filename,
                    'contentType' => 'application/pdf'
                ])
                ->send();

            if ($emailSent) {
                Yii::info("Email credenziali inviata con successo a: {$user->email}");
            } else {
                Yii::error("Errore nell'invio email credenziali a: {$user->email}");
            }
        } catch (\Exception $e) {
            Yii::error('Errore invio email credenziali: ' . $e->getMessage());
        }
    }

    /**
     * Invia email con credenziali esistenti (senza cambio password)
     */
    private function sendExistingCredentialsEmail($user)
    {
        try {
            Yii::info("Invio email credenziali esistenti a: {$user->email}");

            $profile = $user->profile;
            $userName = $profile ? $profile->first_name : $user->email;

            $emailSent = Yii::$app
                ->mailer
                ->compose()
                ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
                ->setTo($user->email)
                ->setSubject('Promemoria Credenziali di Accesso San Luca Plus')
                ->setHtmlBody($this->generateExistingCredentialsEmailHtml($user, $userName))
                ->send();

            if ($emailSent) {
                Yii::info("Email credenziali esistenti inviata con successo a: {$user->email}");
            } else {
                Yii::error("Errore nell'invio email credenziali esistenti a: {$user->email}");
                throw new \Exception("Errore nell'invio dell'email");
            }
        } catch (\Exception $e) {
            Yii::error('Errore invio email credenziali esistenti: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Genera contenuto HTML email credenziali esistenti
     */
    private function generateExistingCredentialsEmailHtml($user, $userName)
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            
                <h2 style='color: #333; margin-bottom: 20px;'>Promemoria Credenziali di Accesso San Luca Plus</h2>
                
                <p style='color: #666; line-height: 1.6;'>Ciao {$userName},</p>
                
                <p style='color: #666; line-height: 1.6;'>
                    Ti ricordiamo le tue credenziali di accesso per l'app " . Yii::$app->name . ".
                </p>
                
                <div style='background-color: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #495057; margin-top: 0;'>Le tue credenziali:</h3>
                    <p style='margin: 10px 0;'><strong>Email:</strong> {$user->email}</p>
                    <p style='margin: 10px 0;'><strong>Password:</strong> La password che hai impostato durante il primo accesso</p>
                    <p style='color: #6c757d; font-size: 14px; margin-top: 15px;'>
                        <strong>💡 Nota:</strong> Se hai dimenticato la password, puoi richiedere un reset tramite l'app.
                    </p>
                </div>
                
                <div style='background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h4 style='color: #0c5460; margin-top: 0;'>Come accedere:</h4>
                    <ol style='color: #0c5460; line-height: 1.6; margin: 0;'>
                        <li>Apri l'app " . Yii::$app->name . "</li>
                        <li>Inserisci la tua email: <strong>{$user->email}</strong></li>
                        <li>Inserisci la tua password personale</li>
                        <li>Accedi e utilizza l'app!</li>
                    </ol>
                </div>
                
                <div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='color: #856404; margin: 0; font-size: 14px;'>
                        <strong>🔒 Password dimenticata?</strong><br>
                        Se non ricordi la password, usa la funzione \"Password dimenticata\" nell'app per ricevere un link di reset.
                    </p>
                </div>
                
                <hr style='border: none; border-top: 1px solid #dee2e6; margin: 20px 0;'>
                
                <p style='color: #999; font-size: 12px; text-align: center;'>
                    Email automatica. Le credenziali sono strettamente personali.
                </p>
            </div>
        </div>
        ";
    }

    /**
     * Genera contenuto HTML email credenziali
     */
    private function generateCredentialsEmailHtml($user, $password, $userName)
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px;'>
                <h2 style='color: #333; margin-bottom: 20px;'>Credenziali di Accesso - " . Yii::$app->name . "</h2>
                
                <p style='color: #666; line-height: 1.6;'>Ciao {$userName},</p>
                
                <p style='color: #666; line-height: 1.6;'>
                    Le tue credenziali di accesso sono state create con successo. 
                    Trovi tutti i dettagli nel documento PDF allegato.
                </p>
                
                <div style='background-color: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #495057; margin-top: 0;'>Le tue credenziali:</h3>
                    <p style='margin: 10px 0;'><strong>Email:</strong> {$user->email}</p>
                    <p style='margin: 10px 0;'><strong>Password temporanea:</strong> 
                        <span style='font-family: monospace; background-color: #fff; padding: 4px 8px; border-radius: 4px; color: #007bff;'>{$password}</span>
                    </p>
                    <p style='color: #dc3545; font-size: 14px; margin-top: 15px;'>
                        <strong>⚠️ Importante:</strong> Dovrai cambiare questa password al primo accesso.
                    </p>
                </div>
                
                <div style='background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h4 style='color: #0c5460; margin-top: 0;'>Come accedere all'app:</h4>
                    <ol style='color: #0c5460; line-height: 1.6; margin: 0;'>
                        <li>Scarica l'app " . Yii::$app->name . "</li>
                        <li>Inserisci email e password temporanea</li>
                        <li>Cambia la password quando richiesto</li>
                        <li>Inizia a utilizzare l'app!</li>
                    </ol>
                </div>
                
                <hr style='border: none; border-top: 1px solid #dee2e6; margin: 20px 0;'>
                
                <p style='color: #999; font-size: 12px; text-align: center;'>
                    Email automatica. Le credenziali sono strettamente personali.
                </p>
            </div>
        </div>
        ";
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
            Yii::info('Invio notifiche ai pazienti: ' . implode(',', $patientIds)
                . ' - Utenti destinatari: ' . implode(',', $userIds)
                . ' - Risultato: ' . print_r($result, true), __METHOD__);

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
                'error' => "Errore durante l'invio delle notifiche: " . $e->getMessage()
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
     * Loads comuni based on provincia selection
     */
    public function actionLoadComuni($provincia_id = null, $selected_comune = null)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!$provincia_id) {
            return ['comuni' => [], 'selected' => null, 'cap_data' => []];
        }

        $comuni = Comune::find()
            ->where(['provincia_id' => $provincia_id])
            ->orderBy('nome ASC')
            ->all();

        $comuniArray = ArrayHelper::map($comuni, 'nome', 'nome');

        // Controlla se la tabella comune ha il campo 'cap'
        $capData = [];
        $hasCapField = false;

        try {
            // Verifica se il campo 'cap' esiste nella tabella
            $tableSchema = Yii::$app->db->getTableSchema('comune');
            $hasCapField = isset($tableSchema->columns['cap']);

            if ($hasCapField) {
                // Se il campo cap esiste, crea un array con nome_comune => cap
                foreach ($comuni as $comune) {
                    if (!empty($comune->cap)) {
                        $capData[$comune->nome] = $comune->cap;
                    }
                }
            }
        } catch (\Exception $e) {
            Yii::info('Errore nel controllare il campo cap: ' . $e->getMessage());
        }

        return [
            'comuni' => $comuniArray,
            'selected' => $selected_comune,
            'cap_data' => $capData,
            'has_cap_field' => $hasCapField
        ];
    }

    /**
     * Generates fiscal code based on patient data
     */
    public function actionGenerateFiscalCode()
    {
        if (!Yii::$app->request->isPost) {
            throw new \yii\web\BadRequestHttpException('Metodo non consentito.');
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $data = Yii::$app->request->post();

        // Validazione input richiesti
        $requiredFields = ['first_name', 'last_name', 'birth_date', 'birth_city'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Campo obbligatorio mancante: {$field}"
                ];
            }
        }

        try {
            // Determina il sesso (per ora prendiamo da un campo se presente, altrimenti chiediamo)
            $gender = $data['gender'] ?? null;
            if (empty($gender)) {
                return [
                    'success' => false,
                    'error' => 'Seleziona il sesso per generare il codice fiscale',
                    'requiresGender' => true
                ];
            }

            // Genera il codice fiscale
            $fiscalCode = CodiceFiscaleGenerator::generaCodiceFiscale(
                $data['last_name'],
                $data['first_name'],
                $data['birth_date'],
                $gender,
                $data['birth_city']
            );

            if ($fiscalCode) {
                Yii::info("Codice fiscale generato: {$fiscalCode} per {$data['first_name']} {$data['last_name']}");

                return [
                    'success' => true,
                    'fiscal_code' => $fiscalCode
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Impossibile generare il codice fiscale. Verifica che il comune di nascita sia corretto.'
                ];
            }
        } catch (\Exception $e) {
            Yii::error('Errore nella generazione codice fiscale: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Errore nella generazione: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Gets patient age via AJAX
     */
    public function actionGetAge($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $patient = $this->findModel($id);
            $age = $patient->getAge();

            return [
                'success' => true,
                'age' => $age
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
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

    /**
     * Encrypts sensitive data in user profile (phone and address)
     * @param UserProfile $profile
     */
    private function encryptSensitiveData($profile)
    {
        try {
            $encryptionKey = Yii::$app->params['encryptionKey'];

            if (!empty($profile->phone)) {
                $encryptedPhone = base64_encode(Yii::$app->security->encryptByKey(
                    $profile->phone,
                    $encryptionKey
                ));
                $profile->phone = $encryptedPhone;
            }

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
     * Decrypts sensitive data in user profile (phone and address) for display
     * @param UserProfile $profile
     */
    private function decryptSensitiveData($profile)
    {
        if (!$profile) {
            return;
        }

        try {
            $encryptionKey = Yii::$app->params['encryptionKey'];

            if (!empty($profile->phone)) {
                try {
                    $decryptedPhone = Yii::$app->security->decryptByKey(
                        base64_decode($profile->phone),
                        $encryptionKey
                    );
                    if ($decryptedPhone !== false) {
                        $profile->phone = $decryptedPhone;
                    }
                } catch (\Exception $e) {
                    Yii::error('Failed to decrypt phone number: ' . $e->getMessage(), __METHOD__);
                }
            }

            if (!empty($profile->address)) {
                try {
                    $decryptedAddress = Yii::$app->security->decryptByKey(
                        base64_decode($profile->address),
                        $encryptionKey
                    );
                    if ($decryptedAddress !== false) {
                        $profile->address = $decryptedAddress;
                    }
                } catch (\Exception $e) {
                    Yii::error('Failed to decrypt address: ' . $e->getMessage(), __METHOD__);
                }
            }
        } catch (\Exception $e) {
            Yii::error('Error in decryptSensitiveData: ' . $e->getMessage(), __METHOD__);
        }
    }
}

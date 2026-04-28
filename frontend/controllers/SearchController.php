<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use common\models\User;
use common\models\UserProfile;
use common\models\Therapist;
use common\models\Patient;
use common\models\AccountPatient;
use common\models\Specialization;

/**
 * SearchController per ricerche rapide nel frontend
 */
class SearchController extends Controller
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
                        'actions' => ['generate-fiscal-code'],
                        'roles' => ['?', '@'], // Permetti sia ospiti (?) che utenti autenticati (@)
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'], // Solo utenti autenticati per altre actions
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'generate-fiscal-code' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Disabilita la validazione CSRF per l'action generate-fiscal-code
     */
    public function beforeAction($action)
    {
        if ($action->id === 'generate-fiscal-code') {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Ricerca rapida di utenti nel gestionale
     * 
     * @return array
     */
    public function actionUser()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $query = Yii::$app->request->get('q', '');
        $page = (int) Yii::$app->request->get('page', 1);
        $limit = min((int) Yii::$app->request->get('limit', 10), 50); // Max 50 risultati per page
        
        if (strlen($query) < 2) {
            return [
                'success' => false,
                'message' => 'Query troppo corta. Minimo 2 caratteri.',
                'data' => []
            ];
        }

        try {
            $results = [];

            // Cerca terapisti
            $therapists = $this->searchTherapists($query, $limit);
            $results = array_merge($results, $therapists);

            // Cerca pazienti
            $patients = $this->searchPatients($query, $limit);
            $results = array_merge($results, $patients);

            // Cerca account pazienti (utenti collegati ai pazienti)
            $patientAccounts = $this->searchPatientAccounts($query, $limit);
            $results = array_merge($results, $patientAccounts);

            // Cerca coordinatori e amministratori
            $coordinatorsAndAdmins = $this->searchCoordinatorsAndAdmins($query, $limit);
            $results = array_merge($results, $coordinatorsAndAdmins);

            // Ordina per rilevanza (prima match esatti, poi parziali)
            usort($results, function($a, $b) use ($query) {
                $aExact = $this->isExactMatch($a['name'], $query);
                $bExact = $this->isExactMatch($b['name'], $query);
                
                if ($aExact && !$bExact) return -1;
                if (!$aExact && $bExact) return 1;
                
                return strcasecmp($a['name'], $b['name']);
            });

            // Paginazione
            $total = count($results);
            $offset = ($page - 1) * $limit;
            $paginatedResults = array_slice($results, $offset, $limit);

            return [
                'success' => true,
                'data' => $paginatedResults,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];

        } catch (\Exception $e) {
            Yii::error('Errore nella ricerca: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'message' => 'Errore interno del server',
                'data' => []
            ];
        }
    }

    /**
     * Genera un codice fiscale italiano
     * 
     * ENDPOINT PUBBLICO: Accessibile anche agli utenti non autenticati (ospiti)
     * CSRF DISABILITATO: Non richiede token CSRF per le chiamate
     * 
     * Accetta richieste POST con JSON contenente:
     * - cognome: string
     * - nome: string  
     * - sesso: string (M o F)
     * - data_nascita: string (formato Y-m-d)
     * - comune: string (nome del comune di nascita)
     * 
     * @return array
     */
    public function actionGenerateFiscalCode()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            // Verifica che sia una richiesta POST
            if (!Yii::$app->request->isPost) {
                Yii::$app->response->statusCode = 405;
                return [
                    'success' => false,
                    'message' => 'Metodo non consentito. Utilizzare POST.',
                    'data' => null
                ];
            }
            
            // Ottieni i dati JSON dalla richiesta
            $postData = Yii::$app->request->post();
            
            // Se non ci sono dati POST, prova a leggere il body JSON
            if (empty($postData)) {
                $rawBody = Yii::$app->request->getRawBody();
                if (!empty($rawBody)) {
                    $postData = json_decode($rawBody, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Yii::$app->response->statusCode = 400;
                        return [
                            'success' => false,
                            'message' => 'JSON non valido: ' . json_last_error_msg(),
                            'data' => null
                        ];
                    }
                }
            }
            
            // Valida i parametri richiesti
            $requiredFields = ['cognome', 'nome', 'sesso', 'data_nascita', 'comune'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($postData[$field]) || trim($postData[$field]) === '') {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                Yii::$app->response->statusCode = 400;
                return [
                    'success' => false,
                    'message' => 'Parametri mancanti: ' . implode(', ', $missingFields),
                    'data' => null
                ];
            }
            
            // Estrai e pulisci i parametri
            $cognome = trim($postData['cognome']);
            $nome = trim($postData['nome']);
            $sesso = strtoupper(trim($postData['sesso']));
            $dataNascita = trim($postData['data_nascita']);
            $comune = trim($postData['comune']);
            
            // Valida il sesso
            if (!in_array($sesso, ['M', 'F'])) {
                Yii::$app->response->statusCode = 400;
                return [
                    'success' => false,
                    'message' => 'Il campo sesso deve essere M o F',
                    'data' => null
                ];
            }
            
            // Valida il formato della data
            $dateFormats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];
            $validDate = false;
            
            foreach ($dateFormats as $format) {
                $date = \DateTime::createFromFormat($format, $dataNascita);
                if ($date && $date->format($format) === $dataNascita) {
                    $validDate = true;
                    // Normalizza al formato Y-m-d per il componente
                    $dataNascita = $date->format('Y-m-d');
                    break;
                }
            }
            
            if (!$validDate) {
                Yii::$app->response->statusCode = 400;
                return [
                    'success' => false,
                    'message' => 'Formato data non valido. Utilizzare Y-m-d, d/m/Y o d-m-Y',
                    'data' => null
                ];
            }
            
            // Valida lunghezza dei campi
            if (strlen($cognome) < 2 || strlen($cognome) > 50) {
                Yii::$app->response->statusCode = 400;
                return [
                    'success' => false,
                    'message' => 'Il cognome deve essere compreso tra 2 e 50 caratteri',
                    'data' => null
                ];
            }
            
            if (strlen($nome) < 2 || strlen($nome) > 50) {
                Yii::$app->response->statusCode = 400;
                return [
                    'success' => false,
                    'message' => 'Il nome deve essere compreso tra 2 e 50 caratteri',
                    'data' => null
                ];
            }
            
            if (strlen($comune) < 2 || strlen($comune) > 100) {
                Yii::$app->response->statusCode = 400;
                return [
                    'success' => false,
                    'message' => 'Il comune deve essere compreso tra 2 e 100 caratteri',
                    'data' => null
                ];
            }
            
            // Log della richiesta per debugging
            Yii::info("Generazione codice fiscale richiesta per: $cognome $nome, $sesso, $dataNascita, $comune", __METHOD__);
            
            // Genera il codice fiscale utilizzando il componente
            $codiceFiscale = Yii::$app->codiceFiscaleGenerator->generaCodiceFiscale(
                $cognome,
                $nome,
                $dataNascita,
                $sesso,
                $comune
            );
            
            if ($codiceFiscale === null) {
                Yii::$app->response->statusCode = 422;
                return [
                    'success' => false,
                    'message' => 'Impossibile generare il codice fiscale. Verificare che il comune sia corretto.',
                    'data' => null
                ];
            }
            
            // Log del successo
            Yii::info("Codice fiscale generato con successo: $codiceFiscale", __METHOD__);
            
            // Restituisce il codice fiscale generato
            Yii::$app->response->statusCode = 200;
            return [
                'success' => true,
                'data' => $codiceFiscale,
                'message' => ''
            ];
            
        } catch (\Exception $e) {
            // Log dell'errore
            Yii::error('Errore nella generazione del codice fiscale: ' . $e->getMessage(), __METHOD__);
            
            // Determina il codice di stato appropriato
            if ($e instanceof BadRequestHttpException) {
                Yii::$app->response->statusCode = 400;
                $message = $e->getMessage();
            } elseif (strpos($e->getMessage(), 'Codice catastale non trovato') !== false) {
                Yii::$app->response->statusCode = 422;
                $message = 'Comune non trovato. Verificare il nome del comune di nascita.';
            } elseif (strpos($e->getMessage(), 'Formato data non valido') !== false) {
                Yii::$app->response->statusCode = 400;
                $message = 'Formato data non valido. Utilizzare il formato Y-m-d.';
            } else {
                Yii::$app->response->statusCode = 500;
                $message = 'Errore interno del server durante la generazione del codice fiscale';
            }
            
            return [
                'success' => false,
                'message' => $message,
                'data' => null
            ];
        }
    }

    /**
     * Cerca terapisti
     */
    private function searchTherapists($query, $limit)
    {
        $results = [];

        // Coordinator senza permessi globali vede solo i terapisti del proprio gruppo.
        $allowedTherapistIds = $this->getCoordinatorTherapistIdsRestriction();
        if ($allowedTherapistIds === []) {
            return $results;
        }

        $therapistsQuery = Therapist::find()
            ->joinWith(['user.profile', 'specialization'])
            ->where(['therapists.is_active' => true])
            ->andWhere(['or',
                ['like', 'user_profiles.first_name', $query],
                ['like', 'user_profiles.last_name', $query],
                ['like', "CONCAT(user_profiles.first_name, ' ', user_profiles.last_name)", $query],
                ['like', "CONCAT(user_profiles.last_name, ' ', user_profiles.first_name)", $query]
            ]);

        if ($allowedTherapistIds !== null) {
            $therapistsQuery->andWhere(['therapists.id' => $allowedTherapistIds]);
        }

        $therapists = $therapistsQuery->limit($limit)->all();

        foreach ($therapists as $therapist) {
            $profile = $therapist->user->profile;
            $specialization = $therapist->specialization;
            
            $results[] = [
                'id' => $therapist->id,
                'user_id' => $therapist->user_id,
                'type' => 'therapist',
                'name' => $profile->getFullName(),
                'role' => 'Terapista' . ($specialization ? ' - ' . $specialization->name : ''),
                'email' => $therapist->user->email,
                'phone' => $this->decryptPhone($profile->phone),
                'detail_url' => $this->generateDetailUrl('therapist', $therapist->id),
                'avatar_initials' => $this->getInitials($profile->first_name, $profile->last_name)
            ];
        }

        return $results;
    }

    /**
     * Cerca pazienti
     */
    private function searchPatients($query, $limit)
    {
        $results = [];

        $allowedPatientIds = $this->getCoordinatorPatientIdsRestriction();
        if ($allowedPatientIds === []) {
            return $results;
        }

        $patientsQuery = Patient::find()
            ->where(['or',
                ['like', 'first_name', $query],
                ['like', 'last_name', $query],
                ['like', "CONCAT(first_name, ' ', last_name)", $query],
                ['like', "CONCAT(last_name, ' ', first_name)", $query],
                ['like', 'fiscal_code', $query]
            ]);

        if ($allowedPatientIds !== null) {
            $patientsQuery->andWhere(['id' => $allowedPatientIds]);
        }

        $patients = $patientsQuery->limit($limit)->all();

        foreach ($patients as $patient) {
            $results[] = [
                'id' => $patient->id,
                'type' => 'patient',
                'name' => $patient->getFullName(),
                'role' => 'Paziente' . ($patient->getAge() ? ' (' . $patient->getAge() . ' anni)' : ''),
                'email' => null,
                'phone' => null,
                'detail_url' => $this->generateDetailUrl('patient', $patient->id),
                'avatar_initials' => $this->getInitials($patient->first_name, $patient->last_name),
                'birth_date' => $patient->birth_date,
                'fiscal_code' => $patient->fiscal_code
            ];
        }

        return $results;
    }

    /**
     * Cerca account collegati ai pazienti (genitori, tutori, etc.)
     */
    private function searchPatientAccounts($query, $limit)
    {
        $results = [];

        $allowedPatientIds = $this->getCoordinatorPatientIdsRestriction();
        if ($allowedPatientIds === []) {
            return $results;
        }

        $accountsQuery = AccountPatient::find()
            ->joinWith(['user.profile', 'patient'])
            ->where(['or',
                ['like', 'user_profiles.first_name', $query],
                ['like', 'user_profiles.last_name', $query],
                ['like', "CONCAT(user_profiles.first_name, ' ', user_profiles.last_name)", $query],
                ['like', "CONCAT(user_profiles.last_name, ' ', user_profiles.first_name)", $query]
            ]);

        if ($allowedPatientIds !== null) {
            $accountsQuery->andWhere(['account_patients.patient_id' => $allowedPatientIds]);
        }

        $accounts = $accountsQuery->limit($limit)->all();

        foreach ($accounts as $account) {
            $profile = $account->user->profile;
            $patient = $account->patient;
            
            $relationshipLabels = [
                'self' => 'Paziente stesso',
                'parent' => 'Genitore',
                'tutor' => 'Tutore',
                'other' => 'Familiare'
            ];
            
            $relationshipLabel = $relationshipLabels[$account->relationship_type] ?? 'Familiare';
            
            $results[] = [
                'id' => $account->id,
                'user_id' => $account->user_id,
                'patient_id' => $account->patient_id,
                'type' => 'patient_account',
                'name' => $profile->getFullName(),
                'role' => $relationshipLabel . ' di ' . $patient->getFullName(),
                'email' => $account->user->email,
                'phone' => $this->decryptPhone($profile->phone),
                'detail_url' => $this->generateDetailUrl('patient_account', $patient->id, $account->user_id),
                'avatar_initials' => $this->getInitials($profile->first_name, $profile->last_name),
                'patient_name' => $patient->getFullName()
            ];
        }

        return $results;
    }

    /**
     * Cerca coordinatori e amministratori
     */
    private function searchCoordinatorsAndAdmins($query, $limit)
    {
        $results = [];
        
        // Cerca utenti con ruoli di admin, manager o coordinator
        $users = User::find()
            ->joinWith('profile')
            ->leftJoin('{{%auth_assignment}}', '{{%auth_assignment}}.user_id = {{%users}}.id')
            ->where(['{{%users}}.status' => User::STATUS_ACTIVE])
            ->andWhere(['in', '{{%auth_assignment}}.item_name', ['admin', 'manager', 'coordinator']])
            ->andWhere(['or',
                ['like', 'user_profiles.first_name', $query],
                ['like', 'user_profiles.last_name', $query],
                ['like', "CONCAT(user_profiles.first_name, ' ', user_profiles.last_name)", $query],
                ['like', "CONCAT(user_profiles.last_name, ' ', user_profiles.first_name)", $query],
                ['like', '{{%users}}.email', $query]
            ])
            ->groupBy('{{%users}}.id') // Evita duplicati se un utente ha più ruoli
            ->limit($limit)
            ->all();

        foreach ($users as $user) {
            $profile = $user->profile;
            if (!$profile) continue; // Salta se non ha profilo
            
            // Ottieni i ruoli dell'utente
            $auth = \Yii::$app->authManager;
            $userRoles = array_keys($auth->getRolesByUser($user->id));
            
            // Determina il ruolo principale (priorità: admin > manager > coordinator)
            $roleLabel = 'Utente';
            $roleType = 'user';
            
            if (in_array('admin', $userRoles)) {
                $roleLabel = 'Amministratore';
                $roleType = 'admin';
            } elseif (in_array('manager', $userRoles)) {
                $roleLabel = 'Manager';
                $roleType = 'manager';
            } elseif (in_array('coordinator', $userRoles)) {
                $roleLabel = 'Coordinatore';
                $roleType = 'coordinator';
            }
            
            $results[] = [
                'id' => $user->id,
                'user_id' => $user->id,
                'type' => $roleType,
                'name' => $profile->getFullName(),
                'role' => $roleLabel,
                'email' => $user->email,
                'phone' => $this->decryptPhone($profile->phone),
                'detail_url' => $this->generateDetailUrl($roleType, $user->id),
                'avatar_initials' => $this->getInitials($profile->first_name, $profile->last_name)
            ];
        }

        return $results;
    }

    /**
     * Verifica se il nome corrisponde esattamente alla query
     */
    private function isExactMatch($name, $query)
    {
        return stripos($name, $query) === 0;
    }

    /**
     * Ottieni le iniziali del nome
     */
    private function getInitials($firstName, $lastName)
    {
        return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
    }

    /**
     * Decrittografa il numero di telefono
     */
    private function decryptPhone($encryptedPhone)
    {
        if (empty($encryptedPhone)) {
            return null;
        }
        
        try {
            $decoded = base64_decode($encryptedPhone);
            return Yii::$app->security->decryptByKey($decoded, Yii::$app->params['encryptionKey']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Genera l'URL corretto in base al tipo di utente e ai permessi dell'utente corrente
     */
    private function generateDetailUrl($type, $id, $userId = null)
    {
        $currentUser = Yii::$app->user->identity;
        
        switch ($type) {
            case 'therapist':
                // I terapisti possono essere visualizzati da admin/manager
                if (Yii::$app->user->can('view_therapist')) {
                    return \yii\helpers\Url::to(['/therapist/view', 'id' => $id]);
                }
                // Se non ha permessi, va alla dashboard
                return \yii\helpers\Url::to(['/site/index']);
                
            case 'patient':
                // I pazienti possono essere visualizzati in base ai permessi
                if (Yii::$app->user->can('view_patient')) {
                    return \yii\helpers\Url::to(['/patient/view', 'id' => $id]);
                }
                // Controlla se l'utente è collegato a questo paziente
                if ($this->canViewPatient($id)) {
                    return \yii\helpers\Url::to(['/patient/view', 'id' => $id]);
                }
                return \yii\helpers\Url::to(['/site/index']);
                
            case 'patient_account':
                // Account collegati ai pazienti
                if ($userId == $currentUser->id) {
                    // È il proprio account, può vedere il paziente collegato
                    return \yii\helpers\Url::to(['/patient/view', 'id' => $id]);
                }
                // Se ha permessi generali sui pazienti
                if (Yii::$app->user->can('view_patient')) {
                    return \yii\helpers\Url::to(['/patient/view', 'id' => $id]);
                }
                return \yii\helpers\Url::to(['/site/index']);
                
            case 'admin':
                if (Yii::$app->user->can('view_admin')) {
                    return \yii\helpers\Url::to(['/user/view-administrator', 'id' => $id]);
                }
                return \yii\helpers\Url::to(['/site/index']);
                
            case 'manager':
                // I manager usano la stessa vista degli admin per ora
                if (Yii::$app->user->can('view_admin')) {
                    return \yii\helpers\Url::to(['/user/view-administrator', 'id' => $id]);
                }
                return \yii\helpers\Url::to(['/site/index']);
                
            case 'coordinator':
                if (Yii::$app->user->can('view_coordinator')) {
                    return \yii\helpers\Url::to(['/user/view-coordinator', 'id' => $id]);
                }
                return \yii\helpers\Url::to(['/site/index']);
                
            default:
                return \yii\helpers\Url::to(['/site/index']);
        }
    }

    /**
     * Verifica se l'utente corrente può visualizzare un paziente specifico
     */
    private function canViewPatient($patientId)
    {
        $currentUserId = Yii::$app->user->id;
        
        // Controlla se l'utente è collegato a questo paziente tramite AccountPatient
        $accountPatient = AccountPatient::find()
            ->where(['user_id' => $currentUserId, 'patient_id' => $patientId])
            ->exists();
            
        return $accountPatient;
    }

    /**
     * Ritorna la restrizione di patient_id per l'utente corrente (coordinator).
     *
     * - null  => nessuna restrizione (admin/manager/altri)
     * - []    => coordinator senza gruppo o senza pazienti collegati: nessun risultato
     * - int[] => lista di patient_id consentiti (pazienti dei terapisti del gruppo)
     *
     * Un coordinator senza permessi globali (`create_patient`) ma con
     * `view_own_group_patients` puo' vedere solo i pazienti dei propri terapisti.
     *
     * @return int[]|null
     */
    private function getCoordinatorPatientIdsRestriction()
    {
        $user = Yii::$app->user;

        // Se ha permessi globali (admin/manager) niente restrizioni
        if ($user->can('create_patient')) {
            return null;
        }

        if (!$user->can('view_own_group_patients')) {
            return null;
        }

        $coordinatorGroup = \common\models\CoordinatorGroup::find()
            ->where(['coordinator_user_id' => $user->id])
            ->one();

        if (!$coordinatorGroup) {
            return [];
        }

        $therapistIds = \common\models\GroupTherapist::find()
            ->select('therapist_id')
            ->where(['group_id' => $coordinatorGroup->id])
            ->andWhere(['assigned_to' => null])
            ->column();

        if (empty($therapistIds)) {
            return [];
        }

        $patientIds = \frontend\models\PatientSearch::patientIdsForTherapists($therapistIds);

        return !empty($patientIds) ? $patientIds : [];
    }

    /**
     * Lista degli id terapisti consentiti in base ai permessi dell'utente:
     *
     * - null  => nessuna restrizione (admin/manager con view_therapist globale)
     * - []    => coordinator senza gruppo o senza terapisti collegati
     * - int[] => lista di therapist_id consentiti (terapisti del gruppo coord)
     *
     * @return int[]|null
     */
    private function getCoordinatorTherapistIdsRestriction()
    {
        $userId = Yii::$app->user->id;
        if (!$userId) {
            return null;
        }

        // Check via auth_assignment: piu affidabile dei can() su permission
        // (es. coordinator ha 'manage_therapists' nei suoi permessi base).
        $assignedRoles = (new \yii\db\Query())
            ->select('item_name')
            ->from('{{%auth_assignment}}')
            ->where(['user_id' => $userId])
            ->column();

        $hasCoord = in_array('coordinator', $assignedRoles, true);
        $hasMan = in_array('manager', $assignedRoles, true);
        $hasAdmin = in_array('admin', $assignedRoles, true)
            || in_array('super_admin', $assignedRoles, true);

        if (!$hasCoord || $hasMan || $hasAdmin) {
            return null;
        }

        $coordinatorGroup = \common\models\CoordinatorGroup::find()
            ->where(['coordinator_user_id' => $userId])
            ->one();

        if (!$coordinatorGroup) {
            return [];
        }

        $therapistIds = \common\models\GroupTherapist::find()
            ->select('therapist_id')
            ->where(['group_id' => $coordinatorGroup->id])
            ->andWhere(['assigned_to' => null])
            ->column();

        return !empty($therapistIds) ? array_map('intval', $therapistIds) : [];
    }

    /**
 * Suggerimenti rapidi per la ricerca (ultimi pazienti visitati o più recenti)
 * 
 * @return array
 */
public function actionQuickSuggestions()
{
    Yii::$app->response->format = Response::FORMAT_JSON;
    
    try {
        $limit = (int) Yii::$app->request->get('limit', 5);
        $results = [];
        
        // Determina il tipo di utente corrente
        $currentUser = Yii::$app->user->identity;
        $auth = Yii::$app->authManager;
        $userRoles = array_keys($auth->getRolesByUser($currentUser->id));
        
        // Se è un terapista, mostra i suoi pazienti recenti
        if (in_array('therapist', $userRoles)) {
            $therapist = Therapist::findOne(['user_id' => $currentUser->id]);
            
            if ($therapist) {
                // Query per ottenere i pazienti con sessioni recenti
                $recentPatientIds = \Yii::$app->db->createCommand("
                    SELECT DISTINCT p.id
                    FROM patients p
                    INNER JOIN therapy_sessions ts ON ts.patient_id = p.id
                    WHERE ts.therapist_id = :therapist_id
                    AND ts.status IN ('completed', 'scheduled')
                    ORDER BY MAX(ts.scheduled_at) DESC
                    LIMIT :limit
                ")
                ->bindValue(':therapist_id', $therapist->id)
                ->bindValue(':limit', $limit)
                ->queryColumn();
                
                if (!empty($recentPatientIds)) {
                    $patients = Patient::find()
                        ->where(['id' => $recentPatientIds])
                        ->all();
                    
                    // Ordina secondo l'ordine originale
                    $orderedPatients = [];
                    foreach ($recentPatientIds as $id) {
                        foreach ($patients as $patient) {
                            if ($patient->id == $id) {
                                $orderedPatients[] = $patient;
                                break;
                            }
                        }
                    }
                    
                    foreach ($orderedPatients as $patient) {
                        // Trova l'ultima sessione
                        $lastSession = \Yii::$app->db->createCommand("
                            SELECT scheduled_at
                            FROM therapy_sessions
                            WHERE patient_id = :patient_id
                            AND therapist_id = :therapist_id
                            AND status IN ('completed', 'scheduled')
                            ORDER BY scheduled_at DESC
                            LIMIT 1
                        ")
                        ->bindValue(':patient_id', $patient->id)
                        ->bindValue(':therapist_id', $therapist->id)
                        ->queryScalar();
                        
                        $results[] = [
                            'id' => $patient->id,
                            'type' => 'patient',
                            'name' => $patient->getFullName(),
                            'role' => 'Paziente',
                            'email' => null,
                            'phone' => null,
                            'detail_url' => $this->generateDetailUrl('patient', $patient->id),
                            'avatar_initials' => $this->getInitials($patient->first_name, $patient->last_name),
                            'last_visit' => $lastSession ? Yii::$app->formatter->asRelativeTime($lastSession) : null
                        ];
                    }
                }
            }
        } 
        // Se è un account paziente, mostra i pazienti collegati
        elseif (in_array('patient_family', $userRoles) || in_array('patient', $userRoles)) {
            $accountPatients = AccountPatient::find()
                ->with('patient')
                ->where(['user_id' => $currentUser->id])
                ->limit($limit)
                ->all();
                
            foreach ($accountPatients as $accountPatient) {
                $patient = $accountPatient->patient;
                
                $results[] = [
                    'id' => $patient->id,
                    'type' => 'patient',
                    'name' => $patient->getFullName(),
                    'role' => 'Paziente',
                    'email' => null,
                    'phone' => null,
                    'detail_url' => $this->generateDetailUrl('patient', $patient->id),
                    'avatar_initials' => $this->getInitials($patient->first_name, $patient->last_name),
                    'last_visit' => null
                ];
            }
        }
        // Per admin, manager e coordinator, mostra gli ultimi pazienti inseriti
        else {
            $allowedPatientIds = $this->getCoordinatorPatientIdsRestriction();
            if ($allowedPatientIds === []) {
                return [
                    'success' => true,
                    'data' => [],
                ];
            }

            $patientsQuery = Patient::find()->orderBy('created_at DESC');
            if ($allowedPatientIds !== null) {
                $patientsQuery->andWhere(['id' => $allowedPatientIds]);
            }

            $patients = $patientsQuery->limit($limit)->all();
                
            foreach ($patients as $patient) {
                $results[] = [
                    'id' => $patient->id,
                    'type' => 'patient',
                    'name' => $patient->getFullName(),
                    'role' => 'Paziente',
                    'email' => null,
                    'phone' => null,
                    'detail_url' => $this->generateDetailUrl('patient', $patient->id),
                    'avatar_initials' => $this->getInitials($patient->first_name, $patient->last_name),
                    'last_visit' => null
                ];
            }
        }
        
        return [
            'success' => true,
            'data' => $results
        ];
        
    } catch (\Exception $e) {
        Yii::error('Error loading quick suggestions: ' . $e->getMessage(), __METHOD__);
        return [
            'success' => false,
            'message' => 'Errore nel caricamento dei suggerimenti',
            'data' => []
        ];
    }
}
    
} 
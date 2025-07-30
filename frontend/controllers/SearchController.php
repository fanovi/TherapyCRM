<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
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
                        'roles' => ['@'], // Solo utenti autenticati
                    ],
                ],
            ],
        ];
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
     * Cerca terapisti
     */
    private function searchTherapists($query, $limit)
    {
        $results = [];
        
        $therapists = Therapist::find()
            ->joinWith(['user.profile', 'specialization'])
            ->where(['therapists.is_active' => true])
            ->andWhere(['or',
                ['like', 'user_profiles.first_name', $query],
                ['like', 'user_profiles.last_name', $query],
                ['like', "CONCAT(user_profiles.first_name, ' ', user_profiles.last_name)", $query],
                ['like', "CONCAT(user_profiles.last_name, ' ', user_profiles.first_name)", $query]
            ])
            ->limit($limit)
            ->all();

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
        
        $patients = Patient::find()
            ->where(['or',
                ['like', 'first_name', $query],
                ['like', 'last_name', $query],
                ['like', "CONCAT(first_name, ' ', last_name)", $query],
                ['like', "CONCAT(last_name, ' ', first_name)", $query],
                ['like', 'fiscal_code', $query]
            ])
            ->limit($limit)
            ->all();

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
        
        $accounts = AccountPatient::find()
            ->joinWith(['user.profile', 'patient'])
            ->where(['or',
                ['like', 'user_profiles.first_name', $query],
                ['like', 'user_profiles.last_name', $query],
                ['like', "CONCAT(user_profiles.first_name, ' ', user_profiles.last_name)", $query],
                ['like', "CONCAT(user_profiles.last_name, ' ', user_profiles.first_name)", $query]
            ])
            ->limit($limit)
            ->all();

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
            $patients = Patient::find()
                ->orderBy('created_at DESC')
                ->limit($limit)
                ->all();
                
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
<?php

namespace api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use common\models\User;
use common\models\UserProfile;
use common\models\Therapist;
use common\models\Patient;
use common\models\AccountPatient;
use common\models\Specialization;

/**
 * SearchController per ricerche rapide
 */
class SearchController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        Yii::$app->response->format = Response::FORMAT_JSON;
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Configurazione CORS
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        // Autenticazione richiesta
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * Ricerca rapida di utenti nel gestionale
     * 
     * @return array
     */
    public function actionUser()
    {
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
                'detail_url' => '/therapist/view/' . $therapist->id,
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
                'detail_url' => '/patient/view/' . $patient->id,
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
                'detail_url' => '/patient/view/' . $patient->id,
                'avatar_initials' => $this->getInitials($profile->first_name, $profile->last_name),
                'patient_name' => $patient->getFullName()
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


} 
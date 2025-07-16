<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\db\Query;
use yii\data\Pagination;

/**
 * Controller per la gestione delle statistiche di TherapyCRM
 * 
 * Gestisce:
 * - Statistiche assenze (orari, terapie, giorni, chi genera l'assenza, recuperi)
 * - Statistiche pazienti (sesso, età, numero pazienti)
 * - Statistiche trattamenti (pazienti per trattamento)
 * - Statistiche regimi (pazienti per regime)
 * - Pazienti con più trattamenti
 */
class StatisticsController extends BaseController
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
                        'actions' => ['index', 'absence-stats', 'patient-stats', 'treatment-stats', 'regime-stats'],
                        'allow' => true,
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->user->can('view_statistics');
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * Pagina principale delle statistiche
     */
    public function actionIndex()
    {
        // Carica i trattamenti per il filtro
        $treatments = (new Query())
            ->select(['id', 'name', 'code'])
            ->from('treatment_types')
            ->orderBy('name')
            ->all();

        return $this->render('index', [
            'treatments' => $treatments,
        ]);
    }

    /**
     * AJAX: Statistiche assenze
     */
    public function actionAbsenceStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $dateFrom = Yii::$app->request->get('date_from');
        $dateTo = Yii::$app->request->get('date_to');
        
        try {
            $query = (new Query())
                ->from('statistics_absences_mv sa');
            
            // Filtri data
            if ($dateFrom) {
                $query->andWhere(['>=', 'sa.absence_date', $dateFrom]);
            }
            if ($dateTo) {
                $query->andWhere(['<=', 'sa.absence_date', $dateTo]);
            }

            // Statistiche semplici per il grafico
            $totalAbsences = (clone $query)->count();
            
            $justifiedCount = (clone $query)
                ->andWhere(['sa.is_justified' => 1])
                ->count();
                
            $unjustifiedCount = (clone $query)
                ->andWhere(['sa.is_justified' => 0])
                ->count();

            $withRecoveryCount = (clone $query)
                ->andWhere(['sa.has_recovery' => 1])
                ->count();

            $treatmentCount = (clone $query)
                ->select('DISTINCT sa.treatment_name')
                ->where(['not', ['sa.treatment_name' => null]])
                ->count();

            return [
                'success' => true,
                'data' => [
                    'labels' => ['Assenze Totali', 'Giustificate', 'Non Giustificate', 'Con Recupero', 'Trattamenti Coinvolti'],
                    'values' => [$totalAbsences, $justifiedCount, $unjustifiedCount, $withRecoveryCount, $treatmentCount]
                ]
            ];

        } catch (\Exception $e) {
            Yii::error("Errore statistiche assenze: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore nel caricamento delle statistiche assenze'
            ];
        }
    }

    /**
     * AJAX: Statistiche pazienti
     */
    public function actionPatientStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $gender = Yii::$app->request->get('gender');
        $ageFrom = Yii::$app->request->get('age_from');
        $ageTo = Yii::$app->request->get('age_to');
        $treatments = Yii::$app->request->get('treatments', []);
        $type = Yii::$app->request->get('type', 'gender'); // 'gender' o 'age'
        
        // Pulisci i parametri per evitare stringhe 'null'
        if ($gender === 'null' || $gender === '') $gender = null;
        if ($ageFrom === 'null' || $ageFrom === '') $ageFrom = null;
        if ($ageTo === 'null' || $ageTo === '') $ageTo = null;
        if ($treatments === 'null' || empty($treatments)) $treatments = [];
        
        try {
            $query = (new Query())
                ->from('statistics_patients_mv sp');
            
            // Filtri
            if ($gender) {
                $query->andWhere(['sp.gender' => $gender]);
            }
            if ($ageFrom !== null && $ageFrom !== '') {
                $query->andWhere(['>=', 'sp.age', (int)$ageFrom]);
            }
            if ($ageTo !== null && $ageTo !== '') {
                $query->andWhere(['<=', 'sp.age', (int)$ageTo]);
            }
            
            // Filtro per trattamenti (se specificato)
            if (!empty($treatments) && is_array($treatments)) {
                $subQuery = (new Query())
                    ->select('DISTINCT tp.patient_id')
                    ->from('plan_therapies pt')
                    ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
                    ->where(['in', 'pt.treatment_type_id', $treatments]);
                
                $query->andWhere(['in', 'sp.id', $subQuery]);
            }

            $labels = [];
            $values = [];
            
            if ($type === 'age') {
                // Distribuzione per fasce d'età - approccio semplificato
                $allPatients = (clone $query)->all();
                
                // Definisci le fasce d'età e inizializza i contatori
                $ageGroups = [
                    'Under 18' => 0,
                    '18-25' => 0,
                    '26-35' => 0,
                    '36-45' => 0,
                    '46-55' => 0,
                    '56-65' => 0,
                    'Over 65' => 0
                ];
                
                // Conta i pazienti unici per fascia d'età
                $seenPatients = [];
                foreach ($allPatients as $patient) {
                    // Evita duplicati
                    if (isset($seenPatients[$patient['id']])) {
                        continue;
                    }
                    $seenPatients[$patient['id']] = true;
                    
                    $age = $patient['age'];
                    if ($age < 18) {
                        $ageGroups['Under 18']++;
                    } elseif ($age >= 18 && $age <= 25) {
                        $ageGroups['18-25']++;
                    } elseif ($age >= 26 && $age <= 35) {
                        $ageGroups['26-35']++;
                    } elseif ($age >= 36 && $age <= 45) {
                        $ageGroups['36-45']++;
                    } elseif ($age >= 46 && $age <= 55) {
                        $ageGroups['46-55']++;
                    } elseif ($age >= 56 && $age <= 65) {
                        $ageGroups['56-65']++;
                    } else {
                        $ageGroups['Over 65']++;
                    }
                }
                
                // Prepara i dati per il grafico
                foreach ($ageGroups as $group => $count) {
                    $labels[] = $group;
                    $values[] = $count;
                }
            } else {
                // Distribuzione per genere (default) - approccio semplificato
                $allPatients = (clone $query)->all();
                
                $genderGroups = [
                    'M' => 0,
                    'F' => 0,
                    'N' => 0
                ];
                
                // Conta i pazienti unici per genere
                $seenPatients = [];
                foreach ($allPatients as $patient) {
                    // Evita duplicati
                    if (isset($seenPatients[$patient['id']])) {
                        continue;
                    }
                    $seenPatients[$patient['id']] = true;
                    
                    $gender = $patient['gender'] ?? 'N';
                    if (isset($genderGroups[$gender])) {
                        $genderGroups[$gender]++;
                    } else {
                        $genderGroups['N']++;
                    }
                }
                
                // Prepara i dati per il grafico
                foreach ($genderGroups as $gender => $count) {
                    if ($count > 0) { // Mostra solo categorie con pazienti
                        switch($gender) {
                            case 'M':
                                $labels[] = 'Maschi';
                                break;
                            case 'F':
                                $labels[] = 'Femmine';
                                break;
                            default:
                                $labels[] = 'Non Specificato';
                                break;
                        }
                        $values[] = $count;
                    }
                }
                
                // Se non ci sono dati, mostra almeno un placeholder
                if (empty($labels)) {
                    $labels[] = 'Nessun Paziente';
                    $values[] = 0;
                }
            }

            // Calcola il totale pazienti
            $totalPatients = array_sum($values);

            return [
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'values' => $values,
                    'total_patients' => $totalPatients
                ]
            ];

        } catch (\Exception $e) {
            Yii::error("Errore statistiche pazienti: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore nel caricamento delle statistiche pazienti'
            ];
        }
    }

    /**
     * AJAX: Statistiche trattamenti
     */
    public function actionTreatmentStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $treatments = Yii::$app->request->get('treatments', []);
        
        try {
            $query = (new Query())
                ->select([
                    'st.name',
                    'st.active_patients_count'
                ])
                ->from('statistics_treatments_mv st')
                ->where(['>', 'st.active_patients_count', 0]);
                
            // Filtro per trattamenti specifici se selezionati
            if (!empty($treatments) && is_array($treatments)) {
                $query->andWhere(['in', 'st.id', $treatments]);
            }
                
            $treatmentStats = $query
                ->orderBy('st.active_patients_count DESC')
                ->limit(10) // Top 10 trattamenti
                ->all();

            $labels = [];
            $values = [];
            
            foreach ($treatmentStats as $stat) {
                $labels[] = $stat['name'];
                $values[] = (int)$stat['active_patients_count'];
            }

            return [
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'values' => $values
                ]
            ];

        } catch (\Exception $e) {
            Yii::error("Errore statistiche trattamenti: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore nel caricamento delle statistiche trattamenti'
            ];
        }
    }

    /**
     * AJAX: Statistiche regimi
     */
    public function actionRegimeStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        try {
            // Conta pazienti con piano attivo vs dimessi
            $activeCount = (new Query())
                ->from('statistics_patients_mv')
                ->where(['piano_terapeutico_attivo' => 'SI'])
                ->count();

            $dismissedCount = (new Query())
                ->from('statistics_patients_mv')
                ->where(['dismesso' => 'SI'])
                ->count();

            return [
                'success' => true,
                'data' => [
                    'labels' => ['Piani Attivi', 'Pazienti Dimessi'],
                    'values' => [(int)$activeCount, (int)$dismissedCount]
                ]
            ];

        } catch (\Exception $e) {
            Yii::error("Errore statistiche regimi: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore nel caricamento delle statistiche regimi'
            ];
        }
    }
} 
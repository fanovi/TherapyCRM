<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use frontend\models\AbsenceStatisticsSearch;
use frontend\models\PatientStatisticsSearch;
use frontend\models\TreatmentStatisticsSearch;
use frontend\models\PlanStatisticsSearch;
use common\services\statistics\StatisticsService;
use common\services\statistics\AbsenceStatisticsService;
use common\services\statistics\PatientStatisticsService;
use common\services\statistics\TreatmentStatisticsService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use yii\helpers\ArrayHelper;

/**
 * Controller per la gestione delle statistiche di TherapyCRM
 * 
 * Gestisce dashboard principale e analisi dettagliate per:
 * - Assenze (heatmap, trend, motivi)
 * - Pazienti (demografia, trattamenti multipli)
 * - Trattamenti (ranking, combinazioni)
 * - Piani terapeutici (stati, scadenze)
 */
class StatisticsController extends BaseController
{
    protected $statisticsService;
    protected $absenceService;
    protected $patientService;
    protected $treatmentService;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        $this->statisticsService = new StatisticsService();
        $this->absenceService = new AbsenceStatisticsService();
        $this->patientService = new PatientStatisticsService();
        $this->treatmentService = new TreatmentStatisticsService();
    }

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
                        'actions' => [
                            'index',
                            'absences',
                            'patients',
                            'treatments',
                            'plans',
                            'chart-data',
                            'export'
                        ],
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
     * Dashboard principale delle statistiche
     */
    public function actionIndex()
    {
        try {
            $summary = $this->statisticsService->getDashboardSummary();
            $topTreatments = $this->treatmentService->getTop(5);
            $patientGrowth = $this->statisticsService->getPatientGrowthData([
                'dateFrom' => date('Y-m-d', strtotime('-6 months'))
            ]);

            return $this->render('index', [
                'summary' => $summary,
                'topTreatments' => $topTreatments,
                'patientGrowth' => $patientGrowth,
            ]);
        } catch (\Exception $e) {
            Yii::error("Errore dashboard statistiche: " . $e->getMessage());

            // Dati vuoti di fallback
            $emptySummary = [
                'patients' => ['active' => 0, 'total' => 0, 'new_this_month' => 0, 'multi_treatment' => 0],
                'absences' => ['total_this_month' => 0, 'justified_this_month' => 0, 'with_recovery' => 0, 'unjustified_rate' => 0],
                'treatments' => ['active_types' => 0, 'total_weekly_hours' => 0, 'top_treatment' => 'N/A'],
                'plans' => ['total' => 0, 'active' => 0, 'expiring_soon' => 0, 'new_this_month' => 0]
            ];

            return $this->render('index', [
                'summary' => $emptySummary,
                'topTreatments' => [],
                'patientGrowth' => [],
                'error' => 'Errore nel caricamento dei dati: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Pagina analisi dettagliata assenze
     */
    public function actionAbsences()
    {
        $searchModel = new AbsenceStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        try {
            // Estrai filtri una volta sola
            $filters = $this->extractFilters($searchModel);
            
            // Pre-carica i dati con filtri
            $monthlyRate = $this->absenceService->getMonthlyRate($filters);
            $byReason = $this->absenceService->getByReason($searchModel);
            $byGenerator = $this->absenceService->getByGenerator($searchModel);
            $byTreatmentType = $this->absenceService->getByTreatmentType($filters);
            $topAbsentees = $this->absenceService->getTopAbsentees($filters);

            // Opzioni per i filtri
            $therapistOptions = $this->getTherapistOptions();
            $patientOptions = $this->getPatientOptions();
            $treatmentOptions = $this->getTreatmentOptions();

            return $this->render('absences', [
                'searchModel' => $searchModel,
                'monthlyRate' => $monthlyRate,
                'byReason' => $byReason,
                'byGenerator' => $byGenerator,
                'byTreatmentType' => $byTreatmentType,
                'topAbsentees' => $topAbsentees,
                'therapistOptions' => $therapistOptions,
                'patientOptions' => $patientOptions,
                'treatmentOptions' => $treatmentOptions,
            ]);
        } catch (\Exception $e) {
            Yii::error("Errore pagina assenze: " . $e->getMessage());
            throw new NotFoundHttpException('Errore nel caricamento dei dati');
        }
    }

    /**
     * Pagina analisi dettagliata pazienti
     */
    public function actionPatients()
    {
        $searchModel = new PatientStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        try {
            $demographics = $this->patientService->getDemographics($searchModel);
            $byTreatment = $this->patientService->getByTreatment($searchModel);
            $byRegime = $this->patientService->getByRegime($searchModel);
            $multiTreatmentStats = $this->patientService->getMultiTreatmentStats($searchModel);

            // Opzioni per i filtri
            $treatmentOptions = $this->getTreatmentOptions();
            $regimeOptions = $this->getRegimeOptions();
            $districtOptions = $this->getDistrictOptions();

            return $this->render('patients', [
                'searchModel' => $searchModel,
                'demographics' => $demographics,
                'byTreatment' => $byTreatment,
                'byRegime' => $byRegime,
                'multiTreatmentStats' => $multiTreatmentStats,
                'treatmentOptions' => $treatmentOptions,
                'regimeOptions' => $regimeOptions,
                'districtOptions' => $districtOptions,
            ]);
        } catch (\Exception $e) {
            Yii::error("Errore pagina pazienti: " . $e->getMessage());
            throw new NotFoundHttpException('Errore nel caricamento dei dati');
        }
    }

    // Helper per estrarre filtri
    protected function extractFilters($searchModel)
    {
        $filters = [];

        if ($searchModel->dateFrom) {
            $filters['dateFrom'] = $searchModel->dateFrom;
        }
        if ($searchModel->dateTo) {
            $filters['dateTo'] = $searchModel->dateTo;
        }
        if ($searchModel->therapistId) {
            $filters['therapistId'] = $searchModel->therapistId;
        }
        if ($searchModel->patientId) {
            $filters['patientId'] = $searchModel->patientId;
        }
        if ($searchModel->treatmentTypeId) {
            $filters['treatmentTypeId'] = $searchModel->treatmentTypeId;
        }
        if ($searchModel->absenceSource) {
            $filters['absenceSource'] = $searchModel->absenceSource;
        }
        if (isset($searchModel->isJustified)) {
            $filters['isJustified'] = $searchModel->isJustified;
        }

        return $filters;
    }

    /**
     * Pagina analisi dettagliata trattamenti
     */
    public function actionTreatments()
    {
        $searchModel = new TreatmentStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        try {
            // Debug: prova ogni chiamata singolarmente con filtri
            try {
                $ranking = $this->treatmentService->getRankingData($searchModel);
            } catch (\Exception $e) {
                Yii::error("Errore in getRankingData: " . $e->getMessage());
                $ranking = [];
            }

            try {
                $combinations = $this->treatmentService->getMostFrequentCombinations($searchModel, 10);
            } catch (\Exception $e) {
                Yii::error("Errore in getMostFrequentCombinations: " . $e->getMessage());
                $combinations = [];
            }

            try {
                $bySettingType = $this->treatmentService->getBySettingType($searchModel);
            } catch (\Exception $e) {
                Yii::error("Errore in getBySettingType: " . $e->getMessage());
                $bySettingType = [];
            }

            try {
                $hoursDistribution = $this->treatmentService->getWeeklyHoursDistribution($searchModel);
            } catch (\Exception $e) {
                Yii::error("Errore in getWeeklyHoursDistribution: " . $e->getMessage());
                $hoursDistribution = [];
            }

            // Statistiche specifiche per i filtri del search model
            try {
                // Statistiche specifiche per i filtri del search model
                // Mostra risultati solo se ci sono filtri attivi
                if (!empty($searchModel->treatmentIds)) {
                    $searchResults = $searchModel->getStatistics();
                } else {
                    $searchResults = [];
                }
            } catch (\Exception $e) {
                Yii::error("Errore in getStatistics: " . $e->getMessage());
                $searchResults = [];
            }

            // Opzioni per i filtri
            $treatmentOptions = $this->getTreatmentOptions();
            $regimeOptions = $this->getRegimeOptions();

            return $this->render('treatments', [
                'searchModel' => $searchModel,
                'ranking' => $ranking,
                'combinations' => $combinations,
                'bySettingType' => $bySettingType,
                'hoursDistribution' => $hoursDistribution,
                'searchResults' => $searchResults,
                'treatmentOptions' => $treatmentOptions,
                'regimeOptions' => $regimeOptions,
            ]);
        } catch (\Exception $e) {
            Yii::error("Errore pagina trattamenti: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            throw new NotFoundHttpException('Errore nel caricamento dei dati');
        }
    }

    /**
     * Pagina analisi piani terapeutici
     */
    public function actionPlans()
    {
        $searchModel = new PlanStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        try {
            $plansStats = $this->statisticsService->getPlansStatistics();

            return $this->render('plans', [
                'searchModel' => $searchModel,
                'plansStats' => $plansStats,
            ]);
        } catch (\Exception $e) {
            Yii::error("Errore pagina piani: " . $e->getMessage());
            throw new NotFoundHttpException('Errore nel caricamento dei dati');
        }
    }

    /**
     * Endpoint AJAX per dati grafici
     */
    public function actionChartData($type)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            switch ($type) {
                case 'absence-heatmap':
                    return $this->getAbsenceHeatmapData();

                case 'absence-trend':
                    return $this->getAbsenceTrendData();

                case 'absence-by-day':
                    return $this->getAbsenceByDayData();

                case 'patient-age-groups':
                    return $this->getPatientAgeGroupsData();

                case 'patient-gender':
                    return $this->getPatientGenderData();

                case 'treatment-ranking':
                    return $this->getTreatmentRankingData();

                case 'treatment-hours':
                    return $this->getTreatmentHoursData();

                case 'plans-monthly':
                    return $this->getPlansMonthlyData();

                case 'absence-hourly':
                    return $this->getAbsenceHourlyData();

                default:
                    throw new BadRequestHttpException('Tipo grafico non supportato');
            }
        } catch (\Exception $e) {
            Yii::error("Errore chart-data {$type}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore nel caricamento dei dati del grafico'
            ];
        }
    }

    /**
     * Export dati in Excel
     */
    public function actionExport($type)
    {
        try {
            switch ($type) {
                case 'absences':
                    return $this->exportAbsences();

                case 'patients':
                    return $this->exportPatients();

                case 'treatments':
                    return $this->exportTreatments();

                case 'plans':
                    return $this->exportPlans();

                default:
                    throw new BadRequestHttpException('Tipo export non supportato');
            }
        } catch (\Exception $e) {
            Yii::error("Errore export {$type}: " . $e->getMessage());
            Yii::$app->session->setFlash('error', 'Errore durante l\'export dei dati');
            return $this->goBack();
        }
    }

    // ===== METODI PROTETTI PER DATI GRAFICI =====

    protected function getAbsenceHeatmapData()
    {
        $searchModel = new AbsenceStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        $data = $this->absenceService->getHeatmapData($searchModel);

        return [
            'success' => true,
            'data' => $data
        ];
    }

    protected function getAbsenceTrendData()
    {
        $searchModel = new AbsenceStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        $trendData = $this->absenceService->getTrendData($searchModel);

        return [
            'success' => true,
            'data' => [
                'labels' => ArrayHelper::getColumn($trendData, 'month'),
                'datasets' => [
                    [
                        'label' => 'Assenze Totali',
                        'data' => ArrayHelper::getColumn($trendData, 'total_absences'),
                        'borderColor' => 'rgb(255, 99, 132)',
                        'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    ],
                    [
                        'label' => 'Assenze Giustificate',
                        'data' => ArrayHelper::getColumn($trendData, 'justified_absences'),
                        'borderColor' => 'rgb(54, 162, 235)',
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    ]
                ]
            ]
        ];
    }

    protected function getAbsenceByDayData()
    {
        $searchModel = new AbsenceStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        $dayData = $this->absenceService->getByDayOfWeek($searchModel);

        return [
            'success' => true,
            'data' => [
                'labels' => ArrayHelper::getColumn($dayData, 'day_label'),
                'datasets' => [
                    [
                        'label' => 'Assenze per Giorno',
                        'data' => ArrayHelper::getColumn($dayData, 'count'),
                        'backgroundColor' => [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                            'rgba(255, 159, 64, 0.8)',
                            'rgba(199, 199, 199, 0.8)',
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function getPatientAgeGroupsData()
    {
        $searchModel = new PatientStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        // Costruisci la query base
        $query = (new \yii\db\Query())
            ->select([
                'age_group' => new \yii\db\Expression("CASE 
                WHEN age < 18 THEN '0-17'
                WHEN age < 30 THEN '18-29'
                WHEN age < 50 THEN '30-49'
                WHEN age < 65 THEN '50-64'
                ELSE '65+'
            END"),
                'count' => 'COUNT(*)',
                'avg_age' => 'ROUND(AVG(age), 1)'
            ])
            ->from('statistics_patients_mv sp');

        // Applica i filtri dal searchModel
        // Filtro età
        if ($searchModel->ageFrom !== null && $searchModel->ageFrom !== '') {
            $query->andWhere(['>=', 'sp.age', $searchModel->ageFrom]);
        }
        if ($searchModel->ageTo !== null && $searchModel->ageTo !== '') {
            $query->andWhere(['<=', 'sp.age', $searchModel->ageTo]);
        }

        // Filtro genere
        if ($searchModel->gender && $searchModel->gender !== 'all') {
            $query->andWhere(['sp.gender' => $searchModel->gender]);
        }

        // Filtro stato piano terapeutico
        if ($searchModel->status === 'active') {
            $query->andWhere(['sp.piano_terapeutico_attivo' => 'SI']);
        } elseif ($searchModel->status === 'inactive') {
            $query->andWhere(['sp.piano_terapeutico_attivo' => 'NO']);
        }

        // Filtro tipi di trattamento
        if (!empty($searchModel->treatmentTypeIds)) {
            $subQuery = (new \yii\db\Query())
                ->select('tp.patient_id')
                ->distinct()
                ->from('plan_therapies pt')
                ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
                ->where(['pt.treatment_type_id' => $searchModel->treatmentTypeIds]);

            $query->andWhere(['IN', 'sp.id', $subQuery]);
        }

        // Filtro date
        if ($searchModel->dateFrom) {
            $query->andWhere(['>=', 'sp.created_at', $searchModel->dateFrom . ' 00:00:00']);
        }
        if ($searchModel->dateTo) {
            $query->andWhere(['<=', 'sp.created_at', $searchModel->dateTo . ' 23:59:59']);
        }

        // Raggruppa e ordina - usa Expression anche qui
        $query->groupBy(new \yii\db\Expression("CASE 
        WHEN age < 18 THEN '0-17'
        WHEN age < 30 THEN '18-29'
        WHEN age < 50 THEN '30-49'
        WHEN age < 65 THEN '50-64'
        ELSE '65+'
    END"))
            ->orderBy('age_group');

        $ageGroups = $query->all();

        return [
            'success' => true,
            'data' => [
                'labels' => ArrayHelper::getColumn($ageGroups, 'age_group'),
                'datasets' => [
                    [
                        'label' => 'Pazienti per Età',
                        'data' => ArrayHelper::getColumn($ageGroups, 'count'),
                        'backgroundColor' => [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 205, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function getPatientGenderData()
    {
        $searchModel = new PatientStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        $genderData = $searchModel->getGenderDistribution();

        return [
            'success' => true,
            'data' => [
                'labels' => ArrayHelper::getColumn($genderData, 'gender_label'),
                'datasets' => [
                    [
                        'label' => 'Distribuzione Genere',
                        'data' => ArrayHelper::getColumn($genderData, 'count'),
                        'backgroundColor' => [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(201, 203, 207, 0.8)',
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function getTreatmentRankingData()
    {
        $filters = Yii::$app->request->queryParams;
        $ranking = $this->treatmentService->getRankingData($filters);

        // Prende i top 10
        $topRanking = array_slice($ranking, 0, 10);

        return [
            'success' => true,
            'data' => [
                'labels' => ArrayHelper::getColumn($topRanking, 'name'),
                'datasets' => [
                    [
                        'label' => 'Numero Pazienti',
                        'data' => ArrayHelper::getColumn($topRanking, 'patient_count'),
                        'backgroundColor' => 'rgba(75, 192, 192, 0.8)',
                    ]
                ]
            ]
        ];
    }

    protected function getTreatmentHoursData()
    {
        $hoursData = $this->treatmentService->getWeeklyHoursDistribution();

        return [
            'success' => true,
            'data' => [
                'labels' => ArrayHelper::getColumn($hoursData, 'hours_range'),
                'datasets' => [
                    [
                        'label' => 'Numero Terapie',
                        'data' => ArrayHelper::getColumn($hoursData, 'therapy_count'),
                        'backgroundColor' => 'rgba(153, 102, 255, 0.8)',
                    ]
                ]
            ]
        ];
    }

    protected function getPlansMonthlyData()
    {
        $filters = Yii::$app->request->queryParams;
        $monthlyData = $this->treatmentService->getMonthlyTrends($filters);

        return [
            'success' => true,
            'data' => [
                'labels' => ArrayHelper::getColumn($monthlyData, 'month'),
                'datasets' => [
                    [
                        'label' => 'Nuovi Piani',
                        'data' => ArrayHelper::getColumn($monthlyData, 'new_therapies'),
                        'borderColor' => 'rgb(255, 99, 132)',
                        'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    ]
                ]
            ]
        ];
    }

    // ===== METODI PROTETTI PER EXPORT =====

    protected function exportAbsences()
    {
        $searchModel = new AbsenceStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        // Usa la nuova query
        $query = $searchModel->getStatisticsQuery();
        $rawData = $query->all();

        // Raggruppa per absence_group_key per evitare duplicati
        $groupedData = [];
        foreach ($rawData as $row) {
            $groupKey = $row['absence_group_key'];
            if (!isset($groupedData[$groupKey])) {
                $groupedData[$groupKey] = $row;
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers aggiornati
        $headers = ['Data', 'Ora', 'Paziente', 'Terapista', 'Trattamento', 'Motivo', 'Chi è assente', 'Tipo', 'Giustificata', 'Recupero'];
        $sheet->fromArray($headers, null, 'A1');

        // Dati
        $row = 2;
        foreach ($groupedData as $absence) {
            $sheet->setCellValue("A{$row}", $absence['absence_date']);
            $sheet->setCellValue("B{$row}", sprintf('%02d:00', $absence['absence_hour']));
            $sheet->setCellValue("C{$row}", ($absence['patient_name'] ?: '') . ' ' . ($absence['patient_surname'] ?: ''));
            $sheet->setCellValue("D{$row}", ($absence['therapist_name'] ?: '') . ' ' . ($absence['therapist_surname'] ?: ''));
            $sheet->setCellValue("E{$row}", $absence['treatment_name'] ?: '');
            $sheet->setCellValue("F{$row}", $absence['absence_reason'] ?: '');
            $sheet->setCellValue("G{$row}", $absence['generated_by'] === 'therapist' ? 'Terapista' : 'Paziente');
            $sheet->setCellValue("H{$row}", $absence['absence_type_flag'] === 'direct' ? 'Diretta' : ($absence['absence_type_flag'] === 'substitution' ? 'Sostituzione' : 'Paziente'));
            $sheet->setCellValue("I{$row}", $absence['is_justified'] ? 'Sì' : 'No');
            $sheet->setCellValue("J{$row}", $absence['has_recovery'] === 'SI' ? 'Sì' : 'No');
            $row++;
        }

        return $this->sendExcelFile($spreadsheet, 'statistiche_assenze_' . date('Y-m-d') . '.xlsx');
    }

    protected function exportPatients()
    {
        $searchModel = new PatientStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        $patientData = $searchModel->getStatisticsQuery()->all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['Nome', 'Cognome', 'Età', 'Genere', 'Piano Attivo', 'Trattamenti (no ABA)', 'Data Creazione'];
        $sheet->fromArray($headers, null, 'A1');

        // Dati
        $row = 2;
        foreach ($patientData as $patient) {
            $sheet->setCellValue("A{$row}", $patient['first_name']);
            $sheet->setCellValue("B{$row}", $patient['last_name']);
            $sheet->setCellValue("C{$row}", $patient['age']);
            $sheet->setCellValue("D{$row}", $patient['gender']);
            $sheet->setCellValue("E{$row}", $patient['piano_terapeutico_attivo']);
            $sheet->setCellValue("F{$row}", $patient['trattamenti_count_no_aba']);
            $sheet->setCellValue("G{$row}", date('d/m/Y', strtotime($patient['created_at'])));
            $row++;
        }

        return $this->sendExcelFile($spreadsheet, 'statistiche_pazienti_' . date('Y-m-d') . '.xlsx');
    }

    protected function exportTreatments()
    {
        $ranking = $this->treatmentService->getRankingData();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['Trattamento', 'Codice', 'Pazienti', 'Terapie Totali', 'Ore Settimanali', 'Ore Medie'];
        $sheet->fromArray($headers, null, 'A1');

        // Dati
        $row = 2;
        foreach ($ranking as $treatment) {
            $sheet->setCellValue("A{$row}", $treatment['name']);
            $sheet->setCellValue("B{$row}", $treatment['code']);
            $sheet->setCellValue("C{$row}", $treatment['patient_count']);
            $sheet->setCellValue("D{$row}", $treatment['therapy_count']);
            $sheet->setCellValue("E{$row}", $treatment['total_weekly_hours']);
            $sheet->setCellValue("F{$row}", round($treatment['avg_weekly_hours'], 2));
            $row++;
        }

        return $this->sendExcelFile($spreadsheet, 'statistiche_trattamenti_' . date('Y-m-d') . '.xlsx');
    }

    protected function exportPlans()
    {
        $plansStats = $this->statisticsService->getPlansStatistics();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['Piano ID', 'Paziente', 'Appuntamenti Totali', 'Completati', 'Tasso Completamento'];
        $sheet->fromArray($headers, null, 'A1');

        // Dati
        $row = 2;
        foreach ($plansStats['completion_rates'] as $plan) {
            $sheet->setCellValue("A{$row}", $plan['id']);
            $sheet->setCellValue("B{$row}", $plan['patient_name']);
            $sheet->setCellValue("C{$row}", $plan['total_appointments']);
            $sheet->setCellValue("D{$row}", $plan['completed_appointments']);
            $sheet->setCellValue("E{$row}", $plan['completion_rate'] . '%');
            $row++;
        }

        return $this->sendExcelFile($spreadsheet, 'statistiche_piani_' . date('Y-m-d') . '.xlsx');
    }

    protected function sendExcelFile($spreadsheet, $filename)
    {
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    // ===== METODI PROTETTI PER OPZIONI FILTRI =====

    protected function getTreatmentOptions()
    {
        return ArrayHelper::map(
            \common\models\TreatmentType::find()->orderBy('name')->all(),
            'id',
            'name'
        );
    }

    protected function getTherapistOptions()
    {
        return ArrayHelper::map(
            \common\models\Therapist::find()
                ->joinWith('user.profile')
                ->where(['therapists.is_active' => 1])
                ->orderBy('user_profiles.last_name, user_profiles.first_name')
                ->all(),
            'id',
            function ($model) {
                return $model->user->profile->first_name . ' ' . $model->user->profile->last_name;
            }
        );
    }

    protected function getPatientOptions()
    {
        return ArrayHelper::map(
            \common\models\Patient::find()
                ->orderBy('last_name, first_name')
                ->limit(100) // Limita per performance
                ->all(),
            'id',
            function ($model) {
                return $model->first_name . ' ' . $model->last_name;
            }
        );
    }

    protected function getRegimeOptions()
    {
        return ArrayHelper::map(
            \common\models\Regime::find()->orderBy('nome')->all(),
            'id',
            'nome'
        );
    }

    protected function getDistrictOptions()
    {
        return ArrayHelper::map(
            \common\models\District::find()->orderBy('name')->all(),
            'id',
            'name'
        );
    }

    protected function getAbsenceHourlyData()
    {
        $searchModel = new AbsenceStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        $hourlyStats = $this->absenceService->getHourlyStatistics($this->extractFilters($searchModel));

        $labels = [];
        $values = [];

        foreach ($hourlyStats as $stat) {
            $labels[] = sprintf('%02d:00', $stat['hour']);
            $values[] = (int)$stat['total_count'];
        }

        return [
            'success' => true,
            'data' => [
                'labels' => $labels,
                'values' => $values
            ]
        ];
    }
}

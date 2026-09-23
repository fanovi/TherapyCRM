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
                            'export',
                            'search-therapists',
                            'search-patients',
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
        $searchModel = $this->createAbsenceSearchModel();

        try {
            // Estrai filtri una volta sola
            $filters = $this->extractFilters($searchModel);

            // Pre-carica i dati con filtri
            $monthlyRate = $this->absenceService->getMonthlyRate($filters);
            $byReason = $this->absenceService->getByReason($searchModel);
            $byGenerator = $this->absenceService->getByGenerator($searchModel);
            $byTreatmentType = $this->absenceService->getByTreatmentType($filters);
            $bySetting = $this->absenceService->getBySetting($filters);
            $topAbsentees = $this->absenceService->getTopAbsentees($filters);

            $therapistOptions = $this->getSelectedTherapistOption($searchModel->therapistId);
            $patientOptions = $this->getSelectedPatientOption($searchModel->patientId);
            $treatmentOptions = $this->getTreatmentOptions();
            $settingOptions = $this->getSettingOptions();

            return $this->render('absences', [
                'searchModel' => $searchModel,
                'monthlyRate' => $monthlyRate,
                'byReason' => $byReason,
                'byGenerator' => $byGenerator,
                'byTreatmentType' => $byTreatmentType,
                'bySetting' => $bySetting,
                'topAbsentees' => $topAbsentees,
                'therapistOptions' => $therapistOptions,
                'patientOptions' => $patientOptions,
                'treatmentOptions' => $treatmentOptions,
                'settingOptions' => $settingOptions,
            ]);
        } catch (\Exception $e) {
            Yii::error("Errore pagina assenze: " . $e->getMessage());
            throw new NotFoundHttpException('Errore nel caricamento dei dati');
        }
    }

    /**
     * Ricerca AJAX terapisti per Select2 (cognome nome).
     */
    public function actionSearchTherapists($q = '', $page = 1)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $q = trim((string) $q);
        $page = max(1, (int) $page);
        $pageSize = 20;

        $query = \common\models\Therapist::find()
            ->joinWith('user.profile')
            ->where(['therapists.is_active' => 1])
            ->orderBy([
                'user_profiles.last_name' => SORT_ASC,
                'user_profiles.first_name' => SORT_ASC,
            ]);

        if ($q !== '') {
            $query->andWhere([
                'or',
                ['like', 'user_profiles.first_name', $q],
                ['like', 'user_profiles.last_name', $q],
                ['like', "CONCAT(user_profiles.first_name, ' ', user_profiles.last_name)", $q],
                ['like', "CONCAT(user_profiles.last_name, ' ', user_profiles.first_name)", $q],
            ]);
        }

        $total = (int) $query->count();
        $models = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->all();

        $results = [];
        foreach ($models as $model) {
            $results[] = [
                'id' => (int) $model->id,
                'text' => $this->formatTherapistName($model),
            ];
        }

        return [
            'results' => $results,
            'pagination' => [
                'more' => ($page * $pageSize) < $total,
            ],
        ];
    }

    /**
     * Ricerca AJAX pazienti per Select2 (cognome nome).
     */
    public function actionSearchPatients($q = '', $page = 1)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $q = trim((string) $q);
        $page = max(1, (int) $page);
        $pageSize = 20;

        $query = \common\models\Patient::find()
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC]);

        if ($q !== '') {
            $query->andWhere([
                'or',
                ['like', 'first_name', $q],
                ['like', 'last_name', $q],
                ['like', 'fiscal_code', $q],
                ['like', 'CONCAT(first_name, " ", last_name)', $q],
                ['like', 'CONCAT(last_name, " ", first_name)', $q],
            ]);
        }

        $total = (int) $query->count();
        $models = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->all();

        $results = [];
        foreach ($models as $model) {
            $results[] = [
                'id' => (int) $model->id,
                'text' => $this->formatPersonName($model->last_name, $model->first_name, 'Paziente #' . $model->id),
            ];
        }

        return [
            'results' => $results,
            'pagination' => [
                'more' => ($page * $pageSize) < $total,
            ],
        ];
    }

    /**
     * Pagina analisi dettagliata pazienti
     */
    public function actionPatients()
    {
        $searchModel = $this->createPatientSearchModel(false);

        try {
            if ($searchModel->validate()) {
                $demographics = $this->patientService->getDemographics($searchModel);
                $byTreatment = $this->patientService->getByTreatment($searchModel);
                $byRegime = $this->patientService->getByRegime($searchModel);
                $multiTreatmentStats = $this->patientService->getMultiTreatmentStats($searchModel);
            } else {
                $demographics = [
                    'age_groups' => [],
                    'gender_distribution' => [],
                    'age_stats' => [
                        'avg_age' => 0,
                        'min_age' => 0,
                        'max_age' => 0,
                        'total_patients' => 0,
                    ],
                ];
                $byTreatment = [];
                $byRegime = [];
                $multiTreatmentStats = [
                    'patients' => [],
                    'stats' => [
                        'avg_treatments' => 0,
                        'max_treatments' => 0,
                        'total_multi_patients' => 0,
                    ],
                ];
            }

            // Opzioni per i filtri
            $treatmentOptions = $this->getTreatmentOptions();
            $regimeOptions = $this->getRegimeOptions();
            $districtOptions = $this->getDistrictOptions();

            // DataProvider per la lista pazienti
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

            return $this->render('patients', [
                'searchModel' => $searchModel,
                'demographics' => $demographics,
                'byTreatment' => $byTreatment,
                'byRegime' => $byRegime,
                'multiTreatmentStats' => $multiTreatmentStats,
                'dataProvider' => $dataProvider,
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
        if ($searchModel->settingId) {
            $filters['settingId'] = $searchModel->settingId;
        }
        if ($searchModel->absenceSource) {
            $filters['absenceSource'] = $searchModel->absenceSource;
        }
        if ($searchModel->absenceTypeFlag) {
            $filters['absenceTypeFlag'] = $searchModel->absenceTypeFlag;
        }
        if ($searchModel->isJustified !== null && $searchModel->isJustified !== '') {
            $filters['isJustified'] = $searchModel->isJustified;
        }

        return $filters;
    }

    /**
     * Carica, normalizza e valida i filtri delle statistiche assenze.
     */
    protected function createAbsenceSearchModel($applyDefaultPeriod = true)
    {
        $searchModel = new AbsenceStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        if ($applyDefaultPeriod) {
            $searchModel->applyDefaultPeriod();
        }

        if (!$searchModel->validate()) {
            $messages = $searchModel->getFirstErrors();
            throw new BadRequestHttpException(
                'Filtri assenze non validi: ' . implode('; ', $messages)
            );
        }

        return $searchModel;
    }

    /**
     * Carica e valida i filtri delle statistiche pazienti.
     */
    protected function createPatientSearchModel($throwOnInvalid = true)
    {
        $searchModel = new PatientStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        if ($throwOnInvalid && !$searchModel->validate()) {
            $messages = $searchModel->getFirstErrors();
            throw new BadRequestHttpException(
                'Filtri pazienti non validi: ' . implode('; ', $messages)
            );
        }

        return $searchModel;
    }

    /**
     * Carica e valida i filtri delle statistiche trattamenti.
     */
    protected function createTreatmentSearchModel($throwOnInvalid = true)
    {
        $searchModel = new TreatmentStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        if ($throwOnInvalid && !$searchModel->validate()) {
            $messages = $searchModel->getFirstErrors();
            throw new BadRequestHttpException(
                'Filtri trattamenti non validi: ' . implode('; ', $messages)
            );
        }

        return $searchModel;
    }

    /**
     * Carica e valida i filtri delle statistiche piani.
     */
    protected function createPlanSearchModel($throwOnInvalid = true)
    {
        $searchModel = new PlanStatisticsSearch();
        $searchModel->load(Yii::$app->request->queryParams);

        if ($throwOnInvalid && !$searchModel->validate()) {
            $messages = $searchModel->getFirstErrors();
            throw new BadRequestHttpException(
                'Filtri piani non validi: ' . implode('; ', $messages)
            );
        }

        return $searchModel;
    }

    /**
     * Pagina analisi dettagliata trattamenti
     */
    public function actionTreatments()
    {
        $searchModel = $this->createTreatmentSearchModel(false);

        try {
            if ($searchModel->validate()) {
                $ranking = $this->treatmentService->getRankingData($searchModel);
                $comboResult = $this->treatmentService->getMostFrequentCombinations($searchModel, 10);
                $combinations = $comboResult['items'];
                $combinationsTotalPatients = $comboResult['total_multi_patients'];
                $bySettingType = $this->treatmentService->getBySettingType($searchModel);
                $hoursDistribution = $this->treatmentService->getWeeklyHoursDistribution($searchModel);
                $distinctPatientCount = $this->treatmentService->getDistinctPatientCount($searchModel);
                $searchResults = !empty($searchModel->treatmentIds)
                    ? $searchModel->getStatistics()
                    : [];
            } else {
                $ranking = [];
                $combinations = [];
                $combinationsTotalPatients = 0;
                $bySettingType = [];
                $hoursDistribution = [];
                $distinctPatientCount = 0;
                $searchResults = [];
            }

            $treatmentOptions = $this->getTreatmentOptions();
            $regimeOptions = $this->getRegimeOptions();

            return $this->render('treatments', [
                'searchModel' => $searchModel,
                'ranking' => $ranking,
                'combinations' => $combinations,
                'combinationsTotalPatients' => $combinationsTotalPatients,
                'bySettingType' => $bySettingType,
                'hoursDistribution' => $hoursDistribution,
                'distinctPatientCount' => $distinctPatientCount,
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
        $searchModel = $this->createPlanSearchModel(false);

        try {
            if ($searchModel->validate()) {
                $plansStats = $this->statisticsService->getPlansStatistics($searchModel);
            } else {
                $plansStats = [
                    'by_status' => [],
                    'by_duration' => [],
                    'completion_rates' => [],
                    'expiring_list' => [],
                    'monthly_trends' => [],
                    'by_regime' => [],
                    'kpis' => [
                        'active_today' => 0,
                        'completed' => 0,
                        'expiring_soon' => 0,
                        'total' => 0,
                        'avg_completion' => 0,
                    ],
                ];
            }

            return $this->render('plans', [
                'searchModel' => $searchModel,
                'plansStats' => $plansStats,
                'therapistOptions' => $this->getTherapistOptions(),
                'patientOptions' => $this->getPatientOptions(),
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
        if (!Yii::$app->user->can('export_data')) {
            throw new \yii\web\ForbiddenHttpException('Non hai i permessi per esportare i dati.');
        }

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

    protected function getAbsenceTrendData()
    {
        $searchModel = $this->createAbsenceSearchModel();

        $trendData = $this->absenceService->getTrendData($searchModel);

        $from = $searchModel->dateFrom ?: date('Y-m-01', strtotime('-11 months'));
        $to = $searchModel->dateTo ?: date('Y-m-d');
        $spanDays = (int) round((strtotime($to) - strtotime($from)) / 86400);
        $byDay = $spanDays <= 62;

        $labels = ArrayHelper::getColumn($trendData, 'month_label');
        if (empty(array_filter($labels))) {
            $labels = ArrayHelper::getColumn($trendData, 'month');
        }

        return [
            'success' => true,
            'data' => [
                'labels' => $labels,
                'xAxisTitle' => $byDay ? 'Giorno' : 'Mese',
                'datasets' => [
                    [
                        'label' => 'Assenze totali',
                        'data' => ArrayHelper::getColumn($trendData, 'total_absences'),
                        'borderColor' => 'rgb(255, 99, 132)',
                        'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    ],
                    [
                        'label' => 'Assenze giustificate',
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
        $searchModel = $this->createAbsenceSearchModel();

        $dayData = $this->absenceService->getByDayOfWeek($searchModel);

        return [
            'success' => true,
            'data' => [
                'labels' => ArrayHelper::getColumn($dayData, 'day_label'),
                'datasets' => [
                    [
                        'label' => 'Assenze Terapisti',
                        'data' => ArrayHelper::getColumn($dayData, 'therapist_count'),
                        'backgroundColor' => 'rgba(147, 51, 234, 0.8)',
                        'borderColor' => 'rgba(147, 51, 234, 1)',
                    ],
                    [
                        'label' => 'Assenze Pazienti',
                        'data' => ArrayHelper::getColumn($dayData, 'patient_count'),
                        'backgroundColor' => 'rgba(251, 146, 60, 0.8)',
                        'borderColor' => 'rgba(251, 146, 60, 1)',
                    ]
                ]
            ]
        ];
    }

    protected function getPatientAgeGroupsData()
    {
        $searchModel = $this->createPatientSearchModel();

        // Riusa la query del search model per mantenere allineati tutti i filtri.
        $query = $searchModel->getStatisticsQuery()
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
            ]);

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
        $searchModel = $this->createPatientSearchModel();

        $genderData = $searchModel->getGenderDistribution();

        // Mappa colori per genere (non per indice array)
        $colorMap = [
            'M' => 'rgba(54, 162, 235, 0.8)',   // Blu per maschi
            'F' => 'rgba(255, 99, 132, 0.8)',    // Rosa per femmine
            'N' => 'rgba(201, 203, 207, 0.8)',   // Grigio per non specificato
        ];

        $colors = [];
        foreach ($genderData as $row) {
            $colors[] = $colorMap[$row['gender']] ?? 'rgba(201, 203, 207, 0.8)';
        }

        return [
            'success' => true,
            'data' => [
                'labels' => ArrayHelper::getColumn($genderData, 'gender_label'),
                'datasets' => [
                    [
                        'label' => 'Distribuzione Genere',
                        'data' => ArrayHelper::getColumn($genderData, 'count'),
                        'backgroundColor' => $colors,
                    ]
                ]
            ]
        ];
    }

    protected function getTreatmentRankingData()
    {
        $searchModel = $this->createTreatmentSearchModel();
        $ranking = $this->treatmentService->getRankingData($searchModel);

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
        $hoursData = $this->treatmentService->getWeeklyHoursDistribution($this->createTreatmentSearchModel());

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
        $searchModel = $this->createPlanSearchModel();
        $plansStats = $this->statisticsService->getPlansStatistics($searchModel);
        $monthlyData = $plansStats['monthly_trends'];

        return [
            'success' => true,
            'data' => [
                'labels' => ArrayHelper::getColumn($monthlyData, 'month'),
                'datasets' => [
                    [
                        'label' => 'Nuovi Piani',
                        'data' => ArrayHelper::getColumn($monthlyData, 'count'),
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
        $searchModel = $this->createAbsenceSearchModel();

        // Usa la nuova query
        $query = $searchModel->getStatisticsQuery();
        $rawData = $query->all();

        // Una assenza terapista di gruppo produce una riga per paziente:
        // accorpa lo slot senza perdere i nominativi o i trattamenti coinvolti.
        $groupedData = [];
        foreach ($rawData as $absence) {
            $key = $absence['absence_group_key'];
            if (!isset($groupedData[$key])) {
                $groupedData[$key] = $absence;
                $groupedData[$key]['_patients'] = [];
                $groupedData[$key]['_treatments'] = [];
            }

            $patientName = trim(
                ($absence['patient_name'] ?: '') . ' ' . ($absence['patient_surname'] ?: '')
            );
            if ($patientName !== '' && $patientName !== 'N/A') {
                $groupedData[$key]['_patients'][$patientName] = true;
            }
            if (!empty($absence['treatment_name'])) {
                $groupedData[$key]['_treatments'][$absence['treatment_name']] = true;
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
            $patients = implode(', ', array_keys($absence['_patients']));
            $treatments = implode(', ', array_keys($absence['_treatments']));
            $sheet->setCellValue("A{$row}", $absence['absence_date']);
            $sheet->setCellValue("B{$row}", date('H:i', strtotime($absence['appointment_datetime'])));
            $sheet->setCellValue("C{$row}", $patients);
            $sheet->setCellValue("D{$row}", ($absence['therapist_name'] ?: '') . ' ' . ($absence['therapist_surname'] ?: ''));
            $sheet->setCellValue("E{$row}", $treatments);
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
        $searchModel = $this->createPatientSearchModel();

        $patientData = $searchModel->getStatisticsQuery()
            ->select([
                'sp.first_name',
                'sp.last_name',
                'sp.age',
                'sp.gender',
                'sp.piano_terapeutico_attivo',
                'sp.trattamenti_count_no_aba',
                'sp.created_at',
                'district_name' => new \yii\db\Expression("CASE WHEN d.asl_reference IS NOT NULL AND d.asl_reference != '' AND LOCATE(d.asl_reference, d.name) = 0 THEN CONCAT(d.asl_reference, ' - ', d.name) ELSE d.name END"),
            ])
            ->leftJoin('patients p_export', 'sp.id = p_export.id')
            ->leftJoin('districts d', 'p_export.district_id = d.id')
            ->all();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['Nome', 'Cognome', 'Età', 'Genere', 'Distretto', 'Piano Attivo', 'Trattamenti (no ABA)', 'Data Creazione'];
        $sheet->fromArray($headers, null, 'A1');

        // Dati
        $row = 2;
        foreach ($patientData as $patient) {
            $sheet->setCellValue("A{$row}", $patient['first_name']);
            $sheet->setCellValue("B{$row}", $patient['last_name']);
            $sheet->setCellValue("C{$row}", $patient['age']);
            $sheet->setCellValue("D{$row}", $patient['gender']);
            $sheet->setCellValue("E{$row}", $patient['district_name'] ?: '');
            $sheet->setCellValue("F{$row}", $patient['piano_terapeutico_attivo']);
            $sheet->setCellValue("G{$row}", $patient['trattamenti_count_no_aba']);
            $sheet->setCellValue("H{$row}", date('d/m/Y', strtotime($patient['created_at'])));
            $row++;
        }

        return $this->sendExcelFile($spreadsheet, 'statistiche_pazienti_' . date('Y-m-d') . '.xlsx');
    }

    protected function exportTreatments()
    {
        $searchModel = $this->createTreatmentSearchModel();
        $ranking = $this->treatmentService->getRankingData($searchModel);
        $comboResult = $this->treatmentService->getMostFrequentCombinations($searchModel, 50);
        $settings = $this->treatmentService->getBySettingType($searchModel);
        $distinctPatients = $this->treatmentService->getDistinctPatientCount($searchModel);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ranking');

        $sheet->fromArray(
            ['Trattamento', 'Codice', 'Pazienti', 'Terapie Totali', 'Ore Settimanali', 'Ore Medie'],
            null,
            'A1'
        );

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

        $sheet->setCellValue('A' . ($row + 1), 'Pazienti distinti (filtro corrente)');
        $sheet->setCellValue('C' . ($row + 1), $distinctPatients);

        $comboSheet = $spreadsheet->createSheet();
        $comboSheet->setTitle('Combinazioni');
        $comboSheet->fromArray(['Combinazione', 'N. Pazienti', 'N. Trattamenti'], null, 'A1');
        $comboRow = 2;
        foreach ($comboResult['items'] as $combo) {
            $comboSheet->setCellValue("A{$comboRow}", $combo['combination']);
            $comboSheet->setCellValue("B{$comboRow}", $combo['patient_count']);
            $comboSheet->setCellValue("C{$comboRow}", $combo['treatment_count']);
            $comboRow++;
        }
        $comboSheet->setCellValue('A' . ($comboRow + 1), 'Pazienti multi-trattamento');
        $comboSheet->setCellValue('B' . ($comboRow + 1), $comboResult['total_multi_patients']);

        $settingSheet = $spreadsheet->createSheet();
        $settingSheet->setTitle('Setting');
        $settingSheet->fromArray(['Setting', 'Terapie', 'Pazienti', 'Ore settimanali'], null, 'A1');
        $settingRow = 2;
        foreach ($settings as $setting) {
            $settingSheet->setCellValue("A{$settingRow}", $setting['setting_type']);
            $settingSheet->setCellValue("B{$settingRow}", $setting['therapy_count']);
            $settingSheet->setCellValue("C{$settingRow}", $setting['patient_count']);
            $settingSheet->setCellValue("D{$settingRow}", $setting['total_hours']);
            $settingRow++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $this->sendExcelFile($spreadsheet, 'statistiche_trattamenti_' . date('Y-m-d') . '.xlsx');
    }

    protected function exportPlans()
    {
        $searchModel = $this->createPlanSearchModel();
        $plansStats = $this->statisticsService->getPlansStatistics($searchModel);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Completamento');
        $sheet->fromArray(['Piano ID', 'Paziente', 'Stato', 'Appuntamenti', 'Completati', 'Tasso'], null, 'A1');
        $row = 2;
        foreach ($plansStats['completion_rates'] as $plan) {
            $sheet->setCellValue("A{$row}", $plan['id']);
            $sheet->setCellValue("B{$row}", $plan['patient_name']);
            $sheet->setCellValue("C{$row}", $plan['status'] ?? '');
            $sheet->setCellValue("D{$row}", $plan['total_appointments']);
            $sheet->setCellValue("E{$row}", $plan['completed_appointments']);
            $sheet->setCellValue("F{$row}", $plan['completion_rate'] . '%');
            $row++;
        }
        $sheet->setCellValue('A' . ($row + 1), 'Completamento medio');
        $sheet->setCellValue('F' . ($row + 1), ($plansStats['kpis']['avg_completion'] ?? 0) . '%');

        $statusSheet = $spreadsheet->createSheet();
        $statusSheet->setTitle('Stati');
        $statusSheet->fromArray(['Stato', 'N. Piani'], null, 'A1');
        $srow = 2;
        foreach ($plansStats['by_status'] as $status) {
            $statusSheet->setCellValue("A{$srow}", $status['status_label'] ?? $status['status']);
            $statusSheet->setCellValue("B{$srow}", $status['count']);
            $srow++;
        }

        $expSheet = $spreadsheet->createSheet();
        $expSheet->setTitle('In scadenza');
        $expSheet->fromArray(['Piano ID', 'Paziente', 'Fine', 'Giorni'], null, 'A1');
        $erow = 2;
        foreach ($plansStats['expiring_list'] as $plan) {
            $expSheet->setCellValue("A{$erow}", $plan['id']);
            $expSheet->setCellValue("B{$erow}", $plan['patient_name']);
            $expSheet->setCellValue("C{$erow}", $plan['end_date']);
            $expSheet->setCellValue("D{$erow}", $plan['days_until_expiry']);
            $erow++;
        }
        $expSheet->setCellValue('A' . ($erow + 1), 'Totale in scadenza (30 gg)');
        $expSheet->setCellValue('D' . ($erow + 1), $plansStats['kpis']['expiring_soon'] ?? 0);

        $spreadsheet->setActiveSheetIndex(0);

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
                return $this->formatTherapistName($model);
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
                return $this->formatPersonName(
                    $model->last_name,
                    $model->first_name,
                    'Paziente #' . $model->id
                );
            }
        );
    }

    protected function getSelectedTherapistOption($id)
    {
        if (empty($id)) {
            return [];
        }

        $therapist = \common\models\Therapist::find()
            ->joinWith('user.profile')
            ->where(['therapists.id' => (int) $id])
            ->one();

        return $therapist ? [$therapist->id => $this->formatTherapistName($therapist)] : [];
    }

    protected function getSelectedPatientOption($id)
    {
        if (empty($id)) {
            return [];
        }

        $patient = \common\models\Patient::findOne((int) $id);
        if (!$patient) {
            return [];
        }

        return [$patient->id => $this->formatPersonName(
            $patient->last_name,
            $patient->first_name,
            'Paziente #' . $patient->id
        )];
    }

    protected function formatTherapistName($model)
    {
        $profile = $model->user->profile ?? null;
        if ($profile) {
            return $this->formatPersonName(
                $profile->last_name,
                $profile->first_name,
                'Terapista #' . $model->id
            );
        }

        return 'Terapista #' . $model->id;
    }

    protected function formatPersonName($lastName, $firstName, $fallback)
    {
        $name = trim((string) ($lastName ?? '') . ' ' . (string) ($firstName ?? ''));

        return $name !== '' ? $name : $fallback;
    }

    protected function getRegimeOptions()
    {
        return ArrayHelper::map(
            \common\models\Regime::find()->orderBy('nome')->all(),
            'id',
            'nome'
        );
    }

    protected function getSettingOptions()
    {
        return ArrayHelper::map(
            \common\models\Setting::find()->orderBy('nome')->all(),
            'id',
            'nome'
        );
    }

    protected function getDistrictOptions()
    {
        return \common\models\District::getDropdownData();
    }

    protected function getAbsenceHourlyData()
    {
        $searchModel = $this->createAbsenceSearchModel();

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

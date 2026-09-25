<?php

namespace common\services\statistics;

use Yii;
use common\models\Patient;
use common\models\PlanTherapy;
use common\models\Regime;
use common\models\Setting;
use common\models\TherapeuticPlan;
use common\models\TreatmentType;
use yii\db\Query;
use yii\db\Expression;

/**
 * Service principale per le statistiche
 */
class StatisticsService
{
    /**
     * Ottiene i dati di riepilogo per la dashboard
     */
    public function getDashboardSummary()
    {
        $summary = [];

        // Statistiche pazienti
        $summary['patients'] = $this->getPatientsSummary();

        // Statistiche assenze
        $summary['absences'] = $this->getAbsencesSummary();

        // Statistiche trattamenti
        $summary['treatments'] = $this->getTreatmentsSummary();

        // Statistiche piani
        $summary['plans'] = $this->getPlansSummary();

        return $summary;
    }

    /**
     * Riepilogo pazienti
     */
    protected function getPatientsSummary()
    {
        $query = new Query();

        // Pazienti attivi (con piano terapeutico attivo)
        $activeCount = $query
            ->from('patients p')
            ->innerJoin('therapeutic_plans tp', 'p.id = tp.patient_id')
            ->where(['tp.status' => 'active'])
            ->andWhere(['<=', 'tp.start_date', date('Y-m-d')])
            ->andWhere(['>=', 'tp.end_date', date('Y-m-d')])
            ->count('DISTINCT p.id');

        // Pazienti totali
        $totalCount = (new Query())
            ->from('patients')
            ->count();

        // Nuovi questo mese
        $newThisMonth = (new Query())
            ->from('patients')
            ->where(['>=', 'created_at', date('Y-m-01 00:00:00')])
            ->count();

        // Pazienti con trattamenti multipli (escluso ABA, come in /statistics/patients)
        $multiTreatmentQuery = (new Query())
            ->select('p.id')
            ->from('patients p')
            ->innerJoin('therapeutic_plans tp', 'p.id = tp.patient_id')
            ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->innerJoin('treatment_types tt', 'pt.treatment_type_id = tt.id')
            ->where(['tp.status' => 'active'])
            ->andWhere(['<=', 'tp.start_date', date('Y-m-d')])
            ->andWhere(['>=', 'tp.end_date', date('Y-m-d')])
            ->andWhere(['not like', 'tt.name', '%ABA%'])
            ->groupBy('p.id')
            ->having('COUNT(DISTINCT pt.treatment_type_id) > 1');

        $multiTreatment = (new Query())
            ->from(['multi' => $multiTreatmentQuery])
            ->count();

        return [
            'active' => (int)$activeCount,
            'total' => (int)$totalCount,
            'new_this_month' => (int)$newThisMonth,
            'multi_treatment' => (int)$multiTreatment
        ];
    }

    /**
     * Riepilogo assenze con la nuova logica
     */
    protected function getAbsencesSummary()
    {
        $absenceService = new AbsenceStatisticsService();

        // Query base per assenze del mese corrente
        $monthFilters = [
            'dateFrom' => date('Y-m-01'),
            'dateTo' => date('Y-m-t')
        ];

        $query = $absenceService->getBaseAbsencesQuery($monthFilters);

        // Conta totali
        $totals = $query
            ->select([
                'total' => new Expression('COUNT(DISTINCT absence_group_key)'),
                'justified' => new Expression('COUNT(DISTINCT CASE WHEN is_justified = 1 THEN absence_group_key END)'),
                'with_recovery' => new Expression("COUNT(DISTINCT CASE WHEN has_recovery = 'SI' THEN absence_group_key END)")
            ])
            ->one();

        // Calcola tasso non giustificate
        $unjustifiedCount = $totals['total'] - $totals['justified'];
        $unjustifiedRate = $totals['total'] > 0 ? round(($unjustifiedCount / $totals['total']) * 100, 1) : 0;

        return [
            'total_this_month' => (int)$totals['total'],
            'justified_this_month' => (int)$totals['justified'],
            'with_recovery' => (int)$totals['with_recovery'],
            'unjustified_rate' => $unjustifiedRate
        ];
    }

    /**
     * Riepilogo trattamenti
     */
    protected function getTreatmentsSummary()
    {
        // Tipi di trattamento attivi
        $activeTypes = (new Query())
            ->select('COUNT(DISTINCT pt.treatment_type_id)')
            ->from('plan_therapies pt')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->where(['tp.status' => 'active'])
            ->andWhere(['<=', 'tp.start_date', date('Y-m-d')])
            ->andWhere(['>=', 'tp.end_date', date('Y-m-d')])
            ->scalar();

        // Ore settimanali totali
        $totalHours = (new Query())
            ->select('SUM(pt.weekly_hours)')
            ->from('plan_therapies pt')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->where(['tp.status' => 'active'])
            ->andWhere(['<=', 'tp.start_date', date('Y-m-d')])
            ->andWhere(['>=', 'tp.end_date', date('Y-m-d')])
            ->scalar();

        // Trattamento più frequente
        $topTreatment = (new Query())
            ->select(['tt.name', 'COUNT(DISTINCT tp.patient_id) as patient_count'])
            ->from('treatment_types tt')
            ->innerJoin('plan_therapies pt', 'tt.id = pt.treatment_type_id')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->where(['tp.status' => 'active'])
            ->andWhere(['<=', 'tp.start_date', date('Y-m-d')])
            ->andWhere(['>=', 'tp.end_date', date('Y-m-d')])
            ->groupBy('tt.id, tt.name')
            ->orderBy(['patient_count' => SORT_DESC])
            ->limit(1)
            ->one();

        return [
            'active_types' => (int)$activeTypes,
            'total_weekly_hours' => round((float)$totalHours, 1),
            'top_treatment' => $topTreatment ? $topTreatment['name'] : 'N/A'
        ];
    }

    /**
     * Riepilogo piani terapeutici
     */
    protected function getPlansSummary()
    {
        $query = new Query();

        // Piani totali
        $total = $query->from('therapeutic_plans')->count();

        // Piani attivi
        $active = (new Query())
            ->from('therapeutic_plans')
            ->where(['status' => 'active'])
            ->andWhere(['<=', 'start_date', date('Y-m-d')])
            ->andWhere(['>=', 'end_date', date('Y-m-d')])
            ->count();

        // Piani in scadenza (prossimi 30 giorni)
        $expiringSoon = (new Query())
            ->from('therapeutic_plans')
            ->where(['between', 'end_date', date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))])
            ->andWhere(['status' => 'active'])
            ->andWhere(['<=', 'start_date', date('Y-m-d')])
            ->count();

        // Nuovi questo mese (stessa definizione della dashboard principale)
        $newThisMonth = $this->countPlansByType(TherapeuticPlan::PLAN_TYPE_NEW, date('Y-m-01'), date('Y-m-t'));

        return [
            'total' => (int)$total,
            'active' => (int)$active,
            'expiring_soon' => (int)$expiringSoon,
            'new_this_month' => (int)$newThisMonth
        ];
    }

    /**
     * Dati crescita pazienti per grafico
     */
    /**
     * Dati crescita pazienti per grafico
     */
    /**
 * Dati crescita pazienti per grafico
 */
public function getPatientGrowthData($params = [])
{
    $dateFrom = $params['dateFrom'] ?? date('Y-m-01', strtotime('first day of -11 months'));
    
    // Usa YEAR e MONTH separatamente per evitare problemi con GROUP BY
    $query = (new Query())
        ->select([
            'year' => new Expression('YEAR(created_at)'),
            'month_num' => new Expression('MONTH(created_at)'),
            'count' => 'COUNT(*)'
        ])
        ->from('patients')
        ->where(['>=', 'created_at', $dateFrom])
        ->groupBy(['YEAR(created_at)', 'MONTH(created_at)'])
        ->orderBy(['year' => SORT_ASC, 'month_num' => SORT_ASC]);
    
    $rawData = $query->all();
    
    // Formatta i risultati
    $result = [];
    foreach ($rawData as $row) {
        $monthStr = sprintf('%04d-%02d', $row['year'], $row['month_num']);
        $monthLabel = date('M Y', strtotime($monthStr . '-01'));
        
        $result[] = [
            'month' => $monthStr,
            'month_label' => $monthLabel,
            'count' => $row['count']
        ];
    }
    
    return $result;
}

    /**
     * Statistiche piani terapeutici
     *
     * @param mixed $searchModel PlanStatisticsSearch per filtri (opzionale)
     */
    public function getPlansStatistics($searchModel = null)
    {
        if (!$searchModel instanceof \frontend\models\PlanStatisticsSearch) {
            $searchModel = new \frontend\models\PlanStatisticsSearch();
        }

        $nonCancelled = "COUNT(DISTINCT CASE WHEN a.id IS NOT NULL AND a.status <> 'cancelled' THEN a.id END)";
        $completedAppt = "COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.id END)";

        $completionQuery = (new Query())
            ->select([
                'tp.id',
                'p.id as patient_id',
                "CONCAT(p.first_name, ' ', p.last_name) as patient_name",
                'tp.status',
                'total_appointments' => new Expression($nonCancelled),
                'completed_appointments' => new Expression($completedAppt),
                'completion_rate' => new Expression("ROUND($completedAppt * 100.0 / NULLIF($nonCancelled, 0), 1)"),
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id')
            ->leftJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->leftJoin('appointments a', 'pt.id = a.plan_therapy_id');
        $this->applyPlanFilters($completionQuery, $searchModel);
        $completionQuery
            ->groupBy(['tp.id', 'p.id', 'p.first_name', 'p.last_name', 'tp.status'])
            ->having(['>', new Expression($nonCancelled), 0])
            ->orderBy(['completion_rate' => SORT_DESC]);

        $allCompletionRates = $completionQuery->all();
        $avgCompletion = empty($allCompletionRates)
            ? 0
            : round(array_sum(array_column($allCompletionRates, 'completion_rate')) / count($allCompletionRates), 1);
        $completionRates = array_slice($allCompletionRates, 0, 10);

        // Distribuzione per regime
        $byRegime = (new Query())
            ->select([
                'r.nome as regime_name',
                'COUNT(DISTINCT tp.id) as plan_count',
                'COUNT(DISTINCT tp.patient_id) as patient_count'
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('regime r', 'tp.regime_id = r.id')
            ->innerJoin('patients p', 'tp.patient_id = p.id');
        
        $this->applyPlanFilters($byRegime, $searchModel);
        
        $byRegime = $byRegime
            ->groupBy(['r.id', 'r.nome'])
            ->orderBy(['plan_count' => SORT_DESC])
            ->all();

        $byStatus = (new Query())
            ->select([
                'tp.status',
                'COUNT(*) as count',
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id');
        $this->applyPlanFilters($byStatus, $searchModel);
        $byStatusRows = $byStatus
            ->groupBy(['tp.status'])
            ->orderBy(['count' => SORT_DESC])
            ->all();

        $statusLabels = \frontend\models\PlanStatisticsSearch::getStatusLabels();
        $byStatus = [];
        $completedCount = 0;
        $totalFiltered = 0;
        foreach ($byStatusRows as $row) {
            $totalFiltered += (int) $row['count'];
            if ($row['status'] === 'completed') {
                $completedCount = (int) $row['count'];
            }
            $byStatus[] = [
                'status' => $row['status'],
                'status_label' => $statusLabels[$row['status']] ?? $row['status'],
                'count' => (int) $row['count'],
            ];
        }

        $activeTodayQuery = $this->planKpiQuery($searchModel, true);
        $this->applyActivePlanFilter($activeTodayQuery);
        $activeToday = (int) $activeTodayQuery->count();

        $expiringQuery = $this->planKpiQuery($searchModel, false);
        $this->applyActivePlanFilter($expiringQuery);
        $expiringQuery->andWhere(['between', 'tp.end_date', date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))]);
        $expiringCount = (int) (clone $expiringQuery)->count();
        $expiringList = (clone $expiringQuery)
            ->select([
                'tp.id',
                'tp.start_date',
                'tp.end_date',
                'p.id as patient_id',
                "CONCAT(p.first_name, ' ', p.last_name) as patient_name",
                new Expression('DATEDIFF(tp.end_date, CURDATE()) as days_until_expiry'),
            ])
            ->orderBy(['tp.end_date' => SORT_ASC])
            ->limit(20)
            ->all();

        $durationCase = "CASE
                    WHEN tp.duration_days < 90 THEN 'short'
                    WHEN tp.duration_days <= 365 THEN 'medium'
                    ELSE 'long'
                END";
        $byDuration = (new Query())
            ->select([
                'duration_category' => new Expression($durationCase),
                'COUNT(*) as count',
                'AVG(tp.duration_days) as avg_duration',
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id');
        $this->applyPlanFilters($byDuration, $searchModel);
        $byDuration = $byDuration
            ->groupBy(new Expression($durationCase))
            ->orderBy('avg_duration')
            ->all();

        // Trend mensili nuovi piani: stessa definizione di countPlansByType()
        $monthlyTrends = (new Query())
            ->select([
                new Expression("DATE_FORMAT(tp.start_date, '%Y-%m') as month"),
                'COUNT(*) as count'
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id')
            ->where(['tp.plan_type' => TherapeuticPlan::PLAN_TYPE_NEW])
            ->andWhere(['!=', 'tp.status', TherapeuticPlan::STATUS_DRAFT])
            ->andWhere(['between', 'tp.start_date', date('Y-m-01', strtotime('first day of -11 months')), date('Y-m-t')]);
        
        // Il filtro Stato non si applica: un piano nuovo resta "partito" anche se oggi e' scaduto/sospeso
        $this->applyPlanFilters($monthlyTrends, $searchModel, false);
        
        $monthlyTrends = $monthlyTrends
            ->groupBy('month')
            ->orderBy('month')
            ->all();

        return [
            'completion_rates' => $completionRates,
            'by_regime' => $byRegime,
            'by_status' => $byStatus,
            'expiring_list' => $expiringList,
            'by_duration' => $byDuration,
            'monthly_trends' => $monthlyTrends,
            'kpis' => [
                'active_today' => $activeToday,
                'completed' => $completedCount,
                'expiring_soon' => $expiringCount,
                'total' => $totalFiltered,
                'avg_completion' => $avgCompletion,
            ],
        ];
    }

    /**
     * Dati per analisi temporali
     */
    public function getTimeSeriesData($type, $params = [])
    {
        $dateFrom = $params['dateFrom'] ?? date('Y-m-01', strtotime('first day of -11 months'));
        $dateTo = $params['dateTo'] ?? date('Y-m-t');

        switch ($type) {
            case 'appointments':
                return $this->getAppointmentsTrend($dateFrom, $dateTo);

            case 'absences':
                return $this->getAbsencesTrend($dateFrom, $dateTo);

            case 'patients':
                return $this->getPatientsTrend($dateFrom, $dateTo);

            default:
                return [];
        }
    }

    /**
     * Trend appuntamenti mensili
     */
    protected function getAppointmentsTrend($dateFrom, $dateTo)
    {
        return (new Query())
            ->select([
                'month' => new Expression("DATE_FORMAT(appointment_datetime, '%Y-%m')"),
                'month_label' => new Expression("DATE_FORMAT(appointment_datetime, '%b %Y')"),
                'total' => 'COUNT(*)',
                'completed' => "COUNT(CASE WHEN status = 'completed' THEN 1 END)",
                'absent' => "COUNT(CASE WHEN status IN ('absent_justified', 'absent_not_justified') THEN 1 END)"
            ])
            ->from('appointments')
            ->where(['between', 'appointment_datetime', $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy([new Expression("DATE_FORMAT(appointment_datetime, '%Y-%m')")])
            ->orderBy(['month' => SORT_ASC])
            ->all();
    }

    /**
     * Trend assenze mensili
     */
    protected function getAbsencesTrend($dateFrom, $dateTo)
    {
        $absenceService = new AbsenceStatisticsService();

        $filters = [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ];

        $searchModel = new \frontend\models\AbsenceStatisticsSearch();
        foreach ($filters as $key => $value) {
            $searchModel->$key = $value;
        }

        return $absenceService->getTrendData($searchModel);
    }

    /**
     * Trend pazienti mensili
     */
    protected function getPatientsTrend($dateFrom, $dateTo)
    {
        return (new Query())
            ->select([
                'month' => new Expression("DATE_FORMAT(created_at, '%Y-%m')"),
                'month_label' => new Expression("DATE_FORMAT(created_at, '%b %Y')"),
                'new_patients' => 'COUNT(*)',
                'cumulative' => new Expression("(SELECT COUNT(*) FROM patients p2 WHERE p2.created_at <= MAX(patients.created_at))")
            ])
            ->from('patients')
            ->where(['between', 'created_at', $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy([new Expression("DATE_FORMAT(created_at, '%Y-%m')")])
            ->orderBy(['month' => SORT_ASC])
            ->all();
    }

    /**
     * Statistiche di utilizzo del sistema
     */
    public function getSystemUsageStats()
    {
        // Utenti attivi oggi
        $activeUsersToday = (new Query())
            ->from('activity_log')
            ->where(['>=', 'created_at', date('Y-m-d 00:00:00')])
            ->count('DISTINCT user_id');

        // Azioni totali oggi
        $actionsToday = (new Query())
            ->from('activity_log')
            ->where(['>=', 'created_at', date('Y-m-d 00:00:00')])
            ->count();

        // Top azioni
        $topActions = (new Query())
            ->select(['entity_name', 'action', 'COUNT(*) as count'])
            ->from('activity_log')
            ->where(['>=', 'created_at', date('Y-m-d', strtotime('-7 days'))])
            ->groupBy(['entity_name', 'action'])
            ->orderBy(['count' => SORT_DESC])
            ->limit(10)
            ->all();

        return [
            'active_users_today' => $activeUsersToday,
            'actions_today' => $actionsToday,
            'top_actions' => $topActions
        ];
    }

    /**
     * Conta i piani di una tipologia con data di inizio nel periodo (estremi inclusi).
     * Esclude solo le bozze: lo stato attuale non conta, cosi' il numero di un
     * mese non cambia quando i piani vengono sospesi, interrotti o scadono.
     *
     * @param string $type TherapeuticPlan::PLAN_TYPE_*
     * @param string $from Data Y-m-d
     * @param string $to Data Y-m-d
     * @return int
     */
    public function countPlansByType($type, $from, $to)
    {
        return (int) TherapeuticPlan::find()
            ->ofType($type)
            ->notDraft()
            ->startDateRange($from, $to)
            ->count();
    }

    /**
     * Piani che rendono un paziente "in carico" alla data: status active e data
     * compresa tra inizio e fine (alias tp/p). Unica definizione per il totale
     * della dashboard e per il suo dettaglio.
     *
     * @param string $date Data Y-m-d
     * @return Query
     */
    public function patientsInChargePlansQuery($date)
    {
        return (new Query())
            ->from(['tp' => TherapeuticPlan::tableName()])
            ->innerJoin(['p' => Patient::tableName()], 'p.id = tp.patient_id')
            ->where(['tp.status' => TherapeuticPlan::STATUS_ACTIVE])
            ->andWhere(['<=', 'tp.start_date', $date])
            ->andWhere(['>=', 'tp.end_date', $date]);
    }

    /**
     * @param string $date Data Y-m-d
     * @return int Pazienti distinti in carico alla data
     */
    public function countPatientsInCharge($date)
    {
        return (int) $this->patientsInChargePlansQuery($date)->count('DISTINCT tp.patient_id');
    }

    /**
     * Pazienti in carico divisi per regime → setting → trattamento.
     * Ogni livello conta pazienti distinti: un paziente con più piani o più
     * terapie compare in più righe, quindi le somme possono superare il totale.
     * Ordine per numero di pazienti decrescente a ogni livello.
     *
     * Nodi: name, count, badge (solo setting), warning (riga "Senza terapie"),
     * children, params (solo trattamenti: filtri per l'elenco pazienti).
     *
     * @param string $date Data Y-m-d
     * @return array{total: int, nodes: array}
     */
    public function getPatientsInChargeBreakdown($date)
    {
        $base = $this->patientsInChargePlansQuery($date);
        $countExpr = 'COUNT(DISTINCT tp.patient_id)';

        $regimeRows = (clone $base)
            ->select([
                'regime_id' => 'tp.regime_id',
                'name' => new Expression("COALESCE(r.nome, 'Senza regime')"),
                'c' => new Expression($countExpr),
            ])
            ->leftJoin(['r' => Regime::tableName()], 'r.id = tp.regime_id')
            ->groupBy(['tp.regime_id', 'r.nome'])
            ->all();

        $settingRows = (clone $base)
            ->select([
                'regime_id' => 'tp.regime_id',
                'setting_id' => 's.id',
                'name' => 's.nome',
                'location_type' => 's.location_type',
                'c' => new Expression($countExpr),
            ])
            ->innerJoin(['pt' => PlanTherapy::tableName()], 'pt.therapeutic_plan_id = tp.id')
            ->innerJoin(['s' => Setting::tableName()], 's.id = pt.setting_id')
            ->groupBy(['tp.regime_id', 's.id', 's.nome', 's.location_type'])
            ->all();

        $treatmentRows = (clone $base)
            ->select([
                'regime_id' => 'tp.regime_id',
                'setting_id' => 'pt.setting_id',
                'treatment_type_id' => 'tt.id',
                'name' => 'tt.name',
                'c' => new Expression($countExpr),
            ])
            ->innerJoin(['pt' => PlanTherapy::tableName()], 'pt.therapeutic_plan_id = tp.id')
            ->innerJoin(['tt' => TreatmentType::tableName()], 'tt.id = pt.treatment_type_id')
            ->groupBy(['tp.regime_id', 'pt.setting_id', 'tt.id', 'tt.name'])
            ->all();

        $noTherapyByRegime = (clone $base)
            ->select([
                'c' => new Expression($countExpr),
                'regime_key' => new Expression('COALESCE(tp.regime_id, 0)'),
            ])
            ->leftJoin(['pt' => PlanTherapy::tableName()], 'pt.therapeutic_plan_id = tp.id')
            ->andWhere(['pt.id' => null])
            ->groupBy(['tp.regime_id'])
            ->indexBy('regime_key')
            ->column();

        $treatmentsBySetting = [];
        foreach ($treatmentRows as $row) {
            $treatmentsBySetting[(int) $row['regime_id'] . '|' . (int) $row['setting_id']][] = [
                'name' => $row['name'],
                'count' => (int) $row['c'],
                'params' => [
                    'regime_id' => $row['regime_id'] === null ? '' : (int) $row['regime_id'],
                    'setting_id' => (int) $row['setting_id'],
                    'treatment_type_id' => (int) $row['treatment_type_id'],
                ],
            ];
        }

        $locationLabels = Setting::getLocationTypeLabels();
        $settingsByRegime = [];
        foreach ($settingRows as $row) {
            $settingKey = (int) $row['regime_id'] . '|' . (int) $row['setting_id'];
            $settingsByRegime[(int) $row['regime_id']][] = [
                'name' => $row['name'],
                'count' => (int) $row['c'],
                'badge' => [
                    'text' => $locationLabels[$row['location_type']] ?? $row['location_type'],
                    'variant' => $row['location_type'],
                ],
                'children' => $this->sortByCountDesc($treatmentsBySetting[$settingKey] ?? []),
            ];
        }

        $nodes = [];
        foreach ($regimeRows as $row) {
            $regimeKey = (int) $row['regime_id'];
            $children = $this->sortByCountDesc($settingsByRegime[$regimeKey] ?? []);
            $noTherapy = (int) ($noTherapyByRegime[$regimeKey] ?? 0);
            if ($noTherapy > 0) {
                $children[] = [
                    'name' => 'Senza terapie · piano attivo senza terapie inserite',
                    'count' => $noTherapy,
                    'warning' => true,
                ];
            }
            $nodes[] = [
                'name' => $row['name'],
                'count' => (int) $row['c'],
                'children' => $children,
            ];
        }

        return [
            'total' => $this->countPatientsInCharge($date),
            'nodes' => $this->sortByCountDesc($nodes),
        ];
    }

    /**
     * Pazienti in carico con un trattamento in un setting, dentro un regime.
     * Una riga per paziente e piano; le ore settimanali sommano le terapie
     * dello stesso trattamento nel piano (es. individuale + gruppo).
     *
     * @param string $date Data Y-m-d
     * @param int|null $regimeId Null = piani senza regime
     * @param int $settingId
     * @param int $treatmentTypeId
     * @return array
     */
    public function getPatientsInChargeList($date, $regimeId, $settingId, $treatmentTypeId)
    {
        return $this->patientsInChargePlansQuery($date)
            ->select([
                'patient_id' => 'p.id',
                'first_name' => 'p.first_name',
                'last_name' => 'p.last_name',
                'start_date' => 'tp.start_date',
                'end_date' => 'tp.end_date',
                'weekly_hours' => new Expression('SUM(pt.weekly_hours)'),
            ])
            ->innerJoin(['pt' => PlanTherapy::tableName()], 'pt.therapeutic_plan_id = tp.id')
            ->andWhere(['tp.regime_id' => $regimeId])
            ->andWhere(['pt.setting_id' => $settingId, 'pt.treatment_type_id' => $treatmentTypeId])
            ->groupBy(['p.id', 'p.first_name', 'p.last_name', 'tp.id', 'tp.start_date', 'tp.end_date'])
            ->orderBy(['p.last_name' => SORT_ASC, 'p.first_name' => SORT_ASC, 'tp.start_date' => SORT_ASC])
            ->all();
    }

    /**
     * Ordina i nodi per count decrescente, a parità per nome.
     */
    private function sortByCountDesc(array $nodes)
    {
        usort($nodes, function ($a, $b) {
            return [$b['count'], $a['name']] <=> [$a['count'], $b['name']];
        });
        return $nodes;
    }

    /**
     * Applica i filtri del search model alle query dei piani
     *
     * @param Query $query
     * @param mixed $searchModel
     */
    protected function applyPlanFilters($query, $searchModel, $applyStatus = true)
    {
        if (!$searchModel instanceof \frontend\models\PlanStatisticsSearch) {
            $searchModel = new \frontend\models\PlanStatisticsSearch();
        }

        $searchModel->applyCommonFilters($query, $applyStatus);
    }

    /**
     * Query base KPI piani (alias tp/p).
     */
    protected function planKpiQuery($searchModel, $applyStatus = true)
    {
        $query = (new Query())
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id');
        $this->applyPlanFilters($query, $searchModel, $applyStatus);
        return $query;
    }

    /**
     * Applica la definizione di piano attivo usata dal dominio.
     */
    protected function applyActivePlanFilter($query)
    {
        $today = date('Y-m-d');
        $query->andWhere(['tp.status' => 'active'])
            ->andWhere(['<=', 'tp.start_date', $today])
            ->andWhere(['>=', 'tp.end_date', $today]);
    }
}

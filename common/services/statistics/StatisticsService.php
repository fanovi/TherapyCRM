<?php

namespace common\services\statistics;

use Yii;
use common\models\Appointment;
use common\models\Patient;
use common\models\PlanTherapy;
use common\models\Regime;
use common\models\Setting;
use common\models\TherapeuticPlan;
use common\models\Therapist;
use common\models\TreatmentType;
use common\models\UserProfile;
use yii\db\Query;
use yii\db\Expression;

/**
 * Service principale per le statistiche
 */
class StatisticsService
{
    /** Stati di assenza del paziente (le assenze del terapista sono therapist_absent). */
    const PATIENT_ABSENT_STATUSES = [Appointment::STATUS_ABSENT_JUSTIFIED, Appointment::STATUS_ABSENT_NOT_JUSTIFIED];

    /**
     * Ottiene i dati di riepilogo per la dashboard
     */
    public function getDashboardSummary()
    {
        $summary = [];

        // Statistiche pazienti
        $summary['patients'] = $this->getPatientsSummary();

        // Presenze e assenze dei pazienti nel mese corrente fino a oggi
        $summary['attendance'] = $this->getAttendanceSummary(date('Y-m-01'), date('Y-m-d'));

        // Statistiche piani
        $summary['plans'] = $this->getPlansSummary();

        return $summary;
    }

    /**
     * Riepilogo pazienti
     */
    protected function getPatientsSummary()
    {
        // Pazienti attivi nel mese corrente (stessa definizione del dettaglio della scheda)
        $activeCount = $this->countActivePatients(date('Y-m-01'), date('Y-m-t'));

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
        return $this->plansInPeriodQuery($date, $date)
            ->andWhere(['tp.status' => TherapeuticPlan::STATUS_ACTIVE]);
    }

    /**
     * Piani che rendono un paziente "attivo" nel periodo: periodo del piano che
     * si sovrappone a quello dato, qualunque stato tranne bozza e in attesa.
     * Per i piani interrotti la fine è termination_date, non la end_date prevista:
     * un piano scaduto o interrotto a metà mese conta per quel mese e non per i
     * successivi. Usata dalla scheda "Pazienti attivi" della dashboard statistiche.
     *
     * @param string $from Data Y-m-d
     * @param string $to Data Y-m-d
     * @return Query
     */
    public function activePatientsPlansQuery($from, $to)
    {
        return $this->plansInPeriodQuery($from, $to)
            ->andWhere(['not in', 'tp.status', [TherapeuticPlan::STATUS_DRAFT, TherapeuticPlan::STATUS_PENDING]]);
    }

    /**
     * @param string $from Data Y-m-d
     * @param string $to Data Y-m-d
     * @return int Pazienti distinti attivi nel periodo
     */
    public function countActivePatients($from, $to)
    {
        return (int) $this->activePatientsPlansQuery($from, $to)->count('DISTINCT tp.patient_id');
    }

    /**
     * Piani con periodo che si sovrappone a [from, to] (alias tp/p). La fine
     * effettiva è termination_date se il piano è stato interrotto, altrimenti end_date.
     *
     * @param string $from Data Y-m-d
     * @param string $to Data Y-m-d
     * @return Query
     */
    private function plansInPeriodQuery($from, $to)
    {
        return (new Query())
            ->from(['tp' => TherapeuticPlan::tableName()])
            ->innerJoin(['p' => Patient::tableName()], 'p.id = tp.patient_id')
            ->where(['<=', 'tp.start_date', $to])
            ->andWhere(['>=', new Expression('COALESCE(tp.termination_date, tp.end_date)'), $from]);
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
        return $this->buildPatientsBreakdown($this->patientsInChargePlansQuery($date));
    }

    /**
     * Pazienti attivi nel periodo divisi per regime → setting → trattamento,
     * come getPatientsInChargeBreakdown() ma su activePatientsPlansQuery().
     *
     * @param string $from Data Y-m-d
     * @param string $to Data Y-m-d
     * @return array{total: int, nodes: array}
     */
    public function getActivePatientsBreakdown($from, $to)
    {
        return $this->buildPatientsBreakdown($this->activePatientsPlansQuery($from, $to));
    }

    /**
     * @param Query $base Piani da considerare (alias tp/p)
     * @return array{total: int, nodes: array}
     */
    private function buildPatientsBreakdown(Query $base)
    {
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
                    'name' => 'Senza terapie · piano senza terapie inserite',
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
            'total' => (int) (clone $base)->count('DISTINCT tp.patient_id'),
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
        return $this->buildPatientsList($this->patientsInChargePlansQuery($date), $regimeId, $settingId, $treatmentTypeId);
    }

    /**
     * Pazienti attivi nel periodo con un trattamento in un setting, dentro un
     * regime; righe come getPatientsInChargeList().
     *
     * @param string $from Data Y-m-d
     * @param string $to Data Y-m-d
     * @param int|null $regimeId Null = piani senza regime
     * @param int $settingId
     * @param int $treatmentTypeId
     * @return array
     */
    public function getActivePatientsList($from, $to, $regimeId, $settingId, $treatmentTypeId)
    {
        return $this->buildPatientsList($this->activePatientsPlansQuery($from, $to), $regimeId, $settingId, $treatmentTypeId);
    }

    /**
     * @param Query $base Piani da considerare (alias tp/p)
     */
    private function buildPatientsList(Query $base, $regimeId, $settingId, $treatmentTypeId)
    {
        return $base
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
     * Appuntamenti previsti nel giorno: programmati + completati (alias a), cioè
     * il denominatore della scheda "Appuntamenti di oggi". Assenze e annullati
     * esclusi. Una seduta di gruppo conta una riga per paziente.
     *
     * @param string $date Data Y-m-d
     * @return Query
     */
    public function expectedAppointmentsQuery($date)
    {
        return (new Query())
            ->from(['a' => Appointment::tableName()])
            ->where(['a.status' => [Appointment::STATUS_SCHEDULED, Appointment::STATUS_COMPLETED]])
            ->andWhere(['between', 'a.appointment_datetime', $date . ' 00:00:00', $date . ' 23:59:59']);
    }

    /**
     * Appuntamenti previsti nel giorno divisi per setting interno/esterno;
     * 'none' = senza setting (righe vecchie), così le tre voci sommano al totale.
     *
     * @param string $date Data Y-m-d
     * @return array{internal: int, external: int, none: int}
     */
    public function countExpectedAppointmentsByLocation($date)
    {
        $rows = $this->expectedAppointmentsQuery($date)
            ->select(['c' => new Expression('COUNT(*)'), 'location' => new Expression("COALESCE(s.location_type, 'none')")])
            ->leftJoin(['s' => Setting::tableName()], 's.id = a.id_setting')
            ->groupBy(['location'])
            ->indexBy('location')
            ->column();

        return [
            Setting::LOCATION_INTERNAL => (int) ($rows[Setting::LOCATION_INTERNAL] ?? 0),
            Setting::LOCATION_EXTERNAL => (int) ($rows[Setting::LOCATION_EXTERNAL] ?? 0),
            'none' => (int) ($rows['none'] ?? 0),
        ];
    }

    /**
     * Chiave e nome del primo livello del dettaglio appuntamenti: regime del
     * piano ('r<id>', 'r0' = piano senza regime), 'private' per i cicli privati,
     * 'none' per gli appuntamenti senza piano né ciclo. Richiede i join tp e r.
     *
     * @return array{key: string, name: string}
     */
    private function appointmentGroupExpressions()
    {
        return [
            'key' => "CASE WHEN tp.id IS NOT NULL THEN CONCAT('r', COALESCE(tp.regime_id, 0))"
                . " WHEN a.private_cycle_id IS NOT NULL THEN 'private' ELSE 'none' END",
            'name' => "CASE WHEN tp.id IS NOT NULL THEN COALESCE(r.nome, 'Senza regime')"
                . " WHEN a.private_cycle_id IS NOT NULL THEN 'Cicli privati' ELSE 'Senza piano' END",
        ];
    }

    /**
     * Appuntamenti del periodo con presenza o assenza del paziente (alias a):
     * completati, assenti giustificati e non giustificati. Base della scheda
     * "Assenze/Presenze" della dashboard statistiche; le assenze dei terapisti
     * sono escluse (hanno la loro analisi). Una seduta di gruppo conta una
     * riga per paziente.
     *
     * @param string $from Data Y-m-d
     * @param string $to Data Y-m-d
     * @return Query
     */
    public function attendanceAppointmentsQuery($from, $to)
    {
        return (new Query())
            ->from(['a' => Appointment::tableName()])
            ->where(['a.status' => array_merge([Appointment::STATUS_COMPLETED], self::PATIENT_ABSENT_STATUSES)])
            ->andWhere(['between', 'a.appointment_datetime', $from . ' 00:00:00', $to . ' 23:59:59']);
    }

    /**
     * Presenze e assenze dei pazienti nel periodo.
     *
     * @param string $from Data Y-m-d
     * @param string $to Data Y-m-d
     * @return array{present: int, absent: int, justified: int}
     */
    public function getAttendanceSummary($from, $to)
    {
        $row = $this->attendanceAppointmentsQuery($from, $to)
            ->select([
                'present' => new Expression('COALESCE(SUM(a.status = :completed), 0)', [':completed' => Appointment::STATUS_COMPLETED]),
                'justified' => new Expression('COALESCE(SUM(a.status = :justified), 0)', [':justified' => Appointment::STATUS_ABSENT_JUSTIFIED]),
                'c' => new Expression('COUNT(*)'),
            ])
            ->one();

        return [
            'present' => (int) $row['present'],
            'absent' => (int) $row['c'] - (int) $row['present'],
            'justified' => (int) $row['justified'],
        ];
    }

    /**
     * Presenze e assenze nel periodo divise per regime → setting → trattamento,
     * come getExpectedAppointmentsBreakdown(); ogni nodo ha anche
     * counts = [presenze, assenze] (count = la loro somma).
     *
     * @param string $from Data Y-m-d
     * @param string $to Data Y-m-d
     * @return array{total: int, nodes: array}
     */
    public function getAttendanceBreakdown($from, $to)
    {
        return $this->buildAppointmentsBreakdown($this->attendanceAppointmentsQuery($from, $to), true);
    }

    /**
     * Appuntamenti con presenza o assenza nel periodo per un nodo trattamento,
     * in ordine di data e ora.
     *
     * @param string $from Data Y-m-d
     * @param string $to Data Y-m-d
     * @param string $group Chiave del primo livello (vedi appointmentGroupExpressions())
     * @param int $settingId 0 = appuntamenti senza setting
     * @param int $treatmentTypeId 0 = appuntamenti senza trattamento
     * @return array
     */
    public function getAttendanceList($from, $to, $group, $settingId, $treatmentTypeId)
    {
        return $this->buildAppointmentsList($this->attendanceAppointmentsQuery($from, $to), $group, $settingId, $treatmentTypeId);
    }

    /**
     * Aggiunge piano e regime agli appuntamenti (alias pt, tp, r).
     *
     * @param Query $base Appuntamenti (alias a)
     * @return Query
     */
    private function withPlanAndRegime(Query $base)
    {
        return $base
            ->leftJoin(['pt' => PlanTherapy::tableName()], 'pt.id = a.plan_therapy_id')
            ->leftJoin(['tp' => TherapeuticPlan::tableName()], 'tp.id = pt.therapeutic_plan_id')
            ->leftJoin(['r' => Regime::tableName()], 'r.id = tp.regime_id');
    }

    /**
     * Appuntamenti previsti nel giorno divisi per regime → setting → trattamento.
     * Setting e trattamento sono quelli salvati sull'appuntamento, il regime è
     * quello del piano; cicli privati e appuntamenti senza piano hanno un nodo
     * proprio al primo livello. I conteggi sono appuntamenti, quindi si sommano.
     *
     * Nodi come getPatientsInChargeBreakdown(); i params dei trattamenti filtrano
     * getExpectedAppointmentsList() (setting_id/treatment_type_id 0 = assente).
     *
     * @param string $date Data Y-m-d
     * @return array{total: int, nodes: array}
     */
    public function getExpectedAppointmentsBreakdown($date)
    {
        return $this->buildAppointmentsBreakdown($this->expectedAppointmentsQuery($date), false);
    }

    /**
     * Dettaglio regime → setting → trattamento di un insieme qualsiasi di
     * appuntamenti (usato anche dall'Analisi assenze per gli appuntamenti previsti).
     *
     * @param Query $base Appuntamenti (alias a; non usare gli alias pt, tp, r, s, tt)
     * @param bool $splitAttendance true = ogni nodo ha counts [presenze, assenze]
     * @return array{total: int, nodes: array}
     */
    public function buildAppointmentsBreakdown(Query $base, $splitAttendance = false)
    {
        $group = $this->appointmentGroupExpressions();
        $rows = $this->withPlanAndRegime($base)
            ->select([
                'group_key' => new Expression($group['key']),
                'group_name' => new Expression($group['name']),
                'setting_key' => new Expression('COALESCE(a.id_setting, 0)'),
                'setting_name' => new Expression("COALESCE(s.nome, 'Senza setting')"),
                'location_type' => 's.location_type',
                'treatment_key' => new Expression('COALESCE(a.treatment_type_id, 0)'),
                'treatment_name' => new Expression("COALESCE(tt.name, 'Senza trattamento')"),
                'c' => new Expression('COUNT(*)'),
                'present' => new Expression('SUM(a.status = :completed)', [':completed' => Appointment::STATUS_COMPLETED]),
            ])
            ->leftJoin(['s' => Setting::tableName()], 's.id = a.id_setting')
            ->leftJoin(['tt' => TreatmentType::tableName()], 'tt.id = a.treatment_type_id')
            // Alias diversi dai nomi colonna (es. pt.setting_id): nel GROUP BY MySQL darebbe la precedenza alla colonna.
            ->groupBy(['group_key', 'group_name', 'setting_key', 'setting_name', 's.location_type', 'treatment_key', 'treatment_name'])
            ->all();

        $locationLabels = Setting::getLocationTypeLabels();
        $groups = [];
        $total = 0;
        foreach ($rows as $row) {
            $count = (int) $row['c'];
            $present = (int) $row['present'];
            $total += $count;
            $groupKey = $row['group_key'];
            $settingKey = (int) $row['setting_key'];

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = ['name' => $row['group_name'], 'count' => 0, 'present' => 0, 'settings' => []];
            }
            $groups[$groupKey]['count'] += $count;
            $groups[$groupKey]['present'] += $present;

            if (!isset($groups[$groupKey]['settings'][$settingKey])) {
                $groups[$groupKey]['settings'][$settingKey] = [
                    'name' => $row['setting_name'],
                    'count' => 0,
                    'present' => 0,
                    'children' => [],
                ];
                if ($row['location_type'] !== null) {
                    $groups[$groupKey]['settings'][$settingKey]['badge'] = [
                        'text' => $locationLabels[$row['location_type']] ?? $row['location_type'],
                        'variant' => $row['location_type'],
                    ];
                }
            }
            $groups[$groupKey]['settings'][$settingKey]['count'] += $count;
            $groups[$groupKey]['settings'][$settingKey]['present'] += $present;
            $groups[$groupKey]['settings'][$settingKey]['children'][] = [
                'name' => $row['treatment_name'],
                'count' => $count,
                'present' => $present,
                'params' => [
                    'group' => $groupKey,
                    'setting_id' => $settingKey,
                    'treatment_type_id' => (int) $row['treatment_key'],
                ],
            ];
        }

        // 'present' serve solo al calcolo: diventa counts [presenze, assenze] o sparisce.
        $finish = function (array $node) use ($splitAttendance) {
            if ($splitAttendance) {
                $node['counts'] = [$node['present'], $node['count'] - $node['present']];
            }
            unset($node['present']);
            return $node;
        };

        $nodes = [];
        foreach ($groups as $groupNode) {
            $settings = [];
            foreach ($groupNode['settings'] as $setting) {
                $setting['children'] = $this->sortByCountDesc(array_map($finish, $setting['children']));
                $settings[] = $finish($setting);
            }
            $nodes[] = $finish([
                'name' => $groupNode['name'],
                'count' => $groupNode['count'],
                'present' => $groupNode['present'],
                'children' => $this->sortByCountDesc($settings),
            ]);
        }

        return [
            'total' => $total,
            'nodes' => $this->sortByCountDesc($nodes),
        ];
    }

    /**
     * Appuntamenti previsti nel giorno per un nodo trattamento del dettaglio,
     * in ordine di orario.
     *
     * @param string $date Data Y-m-d
     * @param string $group Chiave del primo livello (vedi appointmentGroupExpressions())
     * @param int $settingId 0 = appuntamenti senza setting
     * @param int $treatmentTypeId 0 = appuntamenti senza trattamento
     * @return array
     */
    public function getExpectedAppointmentsList($date, $group, $settingId, $treatmentTypeId)
    {
        return $this->buildAppointmentsList($this->expectedAppointmentsQuery($date), $group, $settingId, $treatmentTypeId);
    }

    /**
     * Appuntamenti di un nodo trattamento di buildAppointmentsBreakdown().
     *
     * @param Query $base Appuntamenti (alias a; non usare gli alias pt, tp, r, p, th, up)
     */
    public function buildAppointmentsList(Query $base, $group, $settingId, $treatmentTypeId)
    {
        $groupExpr = $this->appointmentGroupExpressions()['key'];

        return $this->withPlanAndRegime($base)
            ->select([
                'appointment_datetime' => 'a.appointment_datetime',
                'status' => 'a.status',
                'group_session_id' => 'a.group_session_id',
                'patient_id' => 'p.id',
                'patient_first_name' => 'p.first_name',
                'patient_last_name' => 'p.last_name',
                'therapist_first_name' => 'up.first_name',
                'therapist_last_name' => 'up.last_name',
            ])
            ->leftJoin(['p' => Patient::tableName()], 'p.id = COALESCE(a.patient_id, tp.patient_id)')
            ->leftJoin(['th' => Therapist::tableName()], 'th.id = a.therapist_id')
            ->leftJoin(['up' => UserProfile::tableName()], 'up.user_id = th.user_id')
            ->andWhere(new Expression($groupExpr . ' = :group', [':group' => (string) $group]))
            ->andWhere(['a.id_setting' => $settingId ?: null])
            ->andWhere(['a.treatment_type_id' => $treatmentTypeId ?: null])
            ->orderBy(['a.appointment_datetime' => SORT_ASC, 'p.last_name' => SORT_ASC, 'p.first_name' => SORT_ASC])
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

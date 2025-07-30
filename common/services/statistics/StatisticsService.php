<?php

namespace common\services\statistics;

use Yii;
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
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
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

        // Pazienti con trattamenti multipli
        $multiTreatment = (new Query())
            ->select('COUNT(DISTINCT p.id)')
            ->from('patients p')
            ->innerJoin('therapeutic_plans tp', 'p.id = tp.patient_id')
            ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
            ->groupBy('p.id')
            ->having('COUNT(DISTINCT pt.treatment_type_id) > 1')
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
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
            ->scalar();

        // Ore settimanali totali
        $totalHours = (new Query())
            ->select('SUM(pt.weekly_hours)')
            ->from('plan_therapies pt')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
            ->scalar();

        // Trattamento più frequente
        $topTreatment = (new Query())
            ->select(['tt.name', 'COUNT(DISTINCT tp.patient_id) as patient_count'])
            ->from('treatment_types tt')
            ->innerJoin('plan_therapies pt', 'tt.id = pt.treatment_type_id')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
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
            ->where(['>=', 'end_date', date('Y-m-d')])
            ->count();

        // Piani in scadenza (prossimi 30 giorni)
        $expiringSoon = (new Query())
            ->from('therapeutic_plans')
            ->where(['between', 'end_date', date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))])
            ->count();

        // Nuovi questo mese
        $newThisMonth = (new Query())
            ->from('therapeutic_plans')
            ->where(['>=', 'created_at', date('Y-m-01 00:00:00')])
            ->count();

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
    $dateFrom = $params['dateFrom'] ?? date('Y-m-01', strtotime('-11 months'));
    
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
        // Statistiche di completamento
        $completionRates = (new Query())
            ->select([
                'tp.id',
                'p.id as patient_id',
                "CONCAT(p.first_name, ' ', p.last_name) as patient_name",
                'COUNT(DISTINCT a.id) as total_appointments',
                "COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.id END) as completed_appointments",
                "ROUND(COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.id END) * 100.0 / COUNT(DISTINCT a.id), 1) as completion_rate"
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id')
            ->leftJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->leftJoin('appointments a', 'pt.id = a.plan_therapy_id');
        
        // Applica filtri base
        $this->applyPlanFilters($completionRates, $searchModel);
        
        $completionRates = $completionRates
            ->groupBy(['tp.id', 'p.id', 'p.first_name', 'p.last_name'])
            ->having(['>', 'COUNT(DISTINCT a.id)', 0])
            ->orderBy(['completion_rate' => SORT_DESC])
            ->limit(20)
            ->all();

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

        // Stati dei piani
        $planStates = (new Query())
            ->select([
                new Expression("CASE 
                    WHEN tp.end_date < CURDATE() THEN 'Scaduti'
                    WHEN tp.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'In scadenza'
                    ELSE 'Attivi'
                END as state"),
                'COUNT(*) as count'
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id');
        
        $this->applyPlanFilters($planStates, $searchModel);
        
        $planStates = $planStates
            ->groupBy(['state'])
            ->all();

        // Converti plan_states in by_status per compatibilità con la vista
        $byStatus = [];
        foreach ($planStates as $state) {
            $status = 'active';
            if ($state['state'] === 'Scaduti') {
                $status = 'completed';
            } elseif ($state['state'] === 'In scadenza') {
                $status = 'expiring';
            }
            $byStatus[] = [
                'status' => $status,
                'count' => $state['count']
            ];
        }

        // Lista piani in scadenza
        $expiringList = (new Query())
            ->select([
                'tp.id',
                'tp.start_date',
                'tp.end_date',
                'p.id as patient_id',
                "CONCAT(p.first_name, ' ', p.last_name) as patient_name",
                new Expression('DATEDIFF(tp.end_date, CURDATE()) as days_until_expiry')
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id')
            ->where(['between', 'tp.end_date', date('Y-m-d'), date('Y-m-d', strtotime('+60 days'))]);
        
        $this->applyPlanFilters($expiringList, $searchModel);
        
        $expiringList = $expiringList
            ->orderBy(['tp.end_date' => SORT_ASC])
            ->limit(10)
            ->all();

        // Statistiche per durata
        $byDuration = (new Query())
            ->select([
                new Expression("CASE 
                    WHEN DATEDIFF(tp.end_date, tp.start_date) < 90 THEN 'short'
                    WHEN DATEDIFF(tp.end_date, tp.start_date) < 365 THEN 'medium'
                    ELSE 'long'
                END as duration_category"),
                'COUNT(*) as count',
                'AVG(DATEDIFF(tp.end_date, tp.start_date)) as avg_duration'
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id');
        
        $this->applyPlanFilters($byDuration, $searchModel);
        
        $byDuration = $byDuration
            ->groupBy(new Expression("CASE 
                WHEN DATEDIFF(tp.end_date, tp.start_date) < 90 THEN 'short'
                WHEN DATEDIFF(tp.end_date, tp.start_date) < 365 THEN 'medium'
                ELSE 'long'
            END"))
            ->orderBy('avg_duration')
            ->all();

        // Trend mensili
        $monthlyTrends = (new Query())
            ->select([
                new Expression("DATE_FORMAT(tp.created_at, '%Y-%m') as month"),
                'COUNT(*) as count'
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id')
            ->where(['>=', 'tp.created_at', date('Y-m-01', strtotime('-11 months'))]);
        
        $this->applyPlanFilters($monthlyTrends, $searchModel);
        
        $monthlyTrends = $monthlyTrends
            ->groupBy('month')
            ->orderBy('month')
            ->all();

        return [
            'completion_rates' => $completionRates,
            'by_regime' => $byRegime,
            'plan_states' => $planStates,
            'by_status' => $byStatus,
            'expiring_list' => $expiringList,
            'by_duration' => $byDuration,
            'monthly_trends' => $monthlyTrends
        ];
    }

    /**
     * Dati per analisi temporali
     */
    public function getTimeSeriesData($type, $params = [])
    {
        $dateFrom = $params['dateFrom'] ?? date('Y-m-01', strtotime('-11 months'));
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
     * Applica i filtri del search model alle query dei piani
     *
     * @param Query $query
     * @param mixed $searchModel
     */
    protected function applyPlanFilters($query, $searchModel)
    {
        if (!$searchModel) {
            // Se non ci sono filtri, usa il comportamento predefinito (solo piani attivi)
            $query->andWhere(['>=', 'tp.end_date', date('Y-m-d')]);
            return;
        }

        // Filtro stato
        if (!empty($searchModel->status)) {
            switch ($searchModel->status) {
                case 'active':
                    $query->andWhere(['>=', 'tp.end_date', date('Y-m-d')]);
                    break;
                case 'completed':
                    $query->andWhere(['<', 'tp.end_date', date('Y-m-d')]);
                    break;
                default:
                    // Tutti gli stati - non aggiungere filtro
                    break;
            }
        } else {
            // Default: solo piani attivi se non specificato diversamente
            $query->andWhere(['>=', 'tp.end_date', date('Y-m-d')]);
        }

        // Filtro durata minima
        if (!empty($searchModel->minDuration)) {
            $query->andWhere(['>=', new Expression('DATEDIFF(tp.end_date, tp.start_date)'), $searchModel->minDuration]);
        }

        // Filtro durata massima
        if (!empty($searchModel->maxDuration)) {
            $query->andWhere(['<=', new Expression('DATEDIFF(tp.end_date, tp.start_date)'), $searchModel->maxDuration]);
        }

        // Filtro data inizio
        if (!empty($searchModel->dateFrom)) {
            $query->andWhere(['>=', 'DATE(tp.start_date)', $searchModel->dateFrom]);
        }

        // Filtro data fine
        if (!empty($searchModel->dateTo)) {
            $query->andWhere(['<=', 'DATE(tp.start_date)', $searchModel->dateTo]);
        }

        // Filtro paziente
        if (!empty($searchModel->patientId)) {
            $query->andWhere(['tp.patient_id' => $searchModel->patientId]);
        }

        // Filtro terapista
        if (!empty($searchModel->therapistId)) {
            // Per il filtro terapista, dobbiamo joinare con plan_therapies e poi con therapist assignments
            $subQuery = (new Query())
                ->select('pt.therapeutic_plan_id')
                ->distinct()
                ->from('plan_therapies pt')
                ->innerJoin('appointments a', 'pt.id = a.plan_therapy_id')
                ->where(['a.therapist_id' => $searchModel->therapistId]);

            $query->andWhere(['in', 'tp.id', $subQuery]);
        }
    }
}

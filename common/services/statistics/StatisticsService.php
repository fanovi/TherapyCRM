<?php

namespace common\services\statistics;

use Yii;
use yii\db\Query;
use yii\caching\TagDependency;

/**
 * Service principale per le statistiche di TherapyCRM
 */
class StatisticsService
{
    const CACHE_DURATION = 3600; // 1 ora
    const CACHE_TAG = 'statistics';

    /**
     * Ottiene il riassunto per la dashboard principale
     *
     * @return array
     */
    public function getDashboardSummary()
    {
        $cacheKey = 'statistics_dashboard_summary';
        
        return Yii::$app->cache->getOrSet($cacheKey, function() {
            return [
                'patients' => $this->getPatientsOverview(),
                'absences' => $this->getAbsencesOverview(),
                'treatments' => $this->getTreatmentsOverview(),
                'plans' => $this->getPlansOverview(),
            ];
        }, self::CACHE_DURATION, new TagDependency(['tags' => self::CACHE_TAG]));
    }

    /**
     * Ottiene overview pazienti per dashboard
     *
     * @return array
     */
    protected function getPatientsOverview()
    {
        $total = (new Query())
            ->from('statistics_patients_mv')
            ->count();

        $active = (new Query())
            ->from('statistics_patients_mv')
            ->where(['piano_terapeutico_attivo' => 'SI'])
            ->count();

        $newThisMonth = (new Query())
            ->from('statistics_patients_mv')
            ->where(['>=', 'DATE(created_at)', date('Y-m-01')])
            ->count();

        $multiTreatment = (new Query())
            ->from('statistics_patients_mv')
            ->where(['>', 'trattamenti_count_no_aba', 1])
            ->count();

        return [
            'total' => (int)$total,
            'active' => (int)$active,
            'new_this_month' => (int)$newThisMonth,
            'multi_treatment' => (int)$multiTreatment,
        ];
    }

    /**
     * Ottiene overview assenze per dashboard
     *
     * @return array
     */
    protected function getAbsencesOverview()
    {
        $thisMonth = date('Y-m');
        
        $totalThisMonth = (new Query())
            ->from('statistics_absences_mv')
            ->where(['like', 'absence_date', $thisMonth])
            ->count();

        $justifiedThisMonth = (new Query())
            ->from('statistics_absences_mv')
            ->where(['like', 'absence_date', $thisMonth])
            ->andWhere(['is_justified' => 1])
            ->count();

        $withRecovery = (new Query())
            ->from('statistics_absences_mv')
            ->where(['like', 'absence_date', $thisMonth])
            ->andWhere(['has_recovery' => 'SI'])
            ->count();

        $rate = $totalThisMonth > 0 ? round(($totalThisMonth - $justifiedThisMonth) / $totalThisMonth * 100, 1) : 0;

        return [
            'total_this_month' => (int)$totalThisMonth,
            'justified_this_month' => (int)$justifiedThisMonth,
            'with_recovery' => (int)$withRecovery,
            'unjustified_rate' => $rate,
        ];
    }

    /**
     * Ottiene overview trattamenti per dashboard
     *
     * @return array
     */
    protected function getTreatmentsOverview()
    {
        $totalTypes = (new Query())
            ->from('treatment_types')
            ->count();

        $activeTypes = (new Query())
            ->select('COUNT(DISTINCT pt.treatment_type_id) as count')
            ->from('plan_therapies pt')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
            ->scalar();

        $totalHours = (new Query())
            ->select('SUM(pt.weekly_hours) as total')
            ->from('plan_therapies pt')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
            ->scalar();

        $topTreatment = (new Query())
            ->select(['tt.name', 'COUNT(DISTINCT tp.patient_id) as patient_count'])
            ->from('treatment_types tt')
            ->innerJoin('plan_therapies pt', 'tt.id = pt.treatment_type_id')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
            ->groupBy(['tt.id', 'tt.name'])
            ->orderBy(['patient_count' => SORT_DESC])
            ->limit(1)
            ->one();

        return [
            'total_types' => (int)$totalTypes,
            'active_types' => (int)$activeTypes,
            'total_weekly_hours' => (float)$totalHours ?: 0,
            'top_treatment' => $topTreatment ? $topTreatment['name'] : 'N/A',
        ];
    }

    /**
     * Ottiene overview piani terapeutici per dashboard
     *
     * @return array
     */
    protected function getPlansOverview()
    {
        $total = (new Query())
            ->from('therapeutic_plans')
            ->count();

        $active = (new Query())
            ->from('therapeutic_plans')
            ->where(['>=', 'end_date', date('Y-m-d')])
            ->count();

        $expiringSoon = (new Query())
            ->from('therapeutic_plans')
            ->where(['between', 'end_date', date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))])
            ->count();

        $newThisMonth = (new Query())
            ->from('therapeutic_plans')
            ->where(['>=', 'DATE(start_date)', date('Y-m-01')])
            ->count();

        return [
            'total' => (int)$total,
            'active' => (int)$active,
            'expiring_soon' => (int)$expiringSoon,
            'new_this_month' => (int)$newThisMonth,
        ];
    }

    /**
     * Ottiene statistiche dettagliate dei piani terapeutici
     *
     * @return array
     */
    public function getPlansStatistics()
    {
        $cacheKey = 'statistics_plans_detailed';
        
        return Yii::$app->cache->getOrSet($cacheKey, function() {
            return [
                'by_status' => $this->getPlansByStatus(),
                'by_duration' => $this->getPlansByDuration(),
                'monthly_trends' => $this->getPlansMonthlyTrends(),
                'completion_rates' => $this->getPlansCompletionRates(),
                'expiring_list' => $this->getExpiringPlans(),
            ];
        }, self::CACHE_DURATION / 4, new TagDependency(['tags' => self::CACHE_TAG])); // 15 min cache
    }

    /**
     * Ottiene piani per stato
     *
     * @return array
     */
    protected function getPlansByStatus()
    {
        return (new Query())
            ->select([
                'CASE 
                    WHEN end_date >= CURDATE() THEN "active"
                    ELSE "completed"
                END as status',
                'COUNT(*) as count'
            ])
            ->from('therapeutic_plans')
            ->groupBy('status')
            ->all();
    }

    /**
     * Ottiene piani per durata
     *
     * @return array
     */
    protected function getPlansByDuration()
    {
        return (new Query())
            ->select([
                'CASE 
                    WHEN duration_days < 90 THEN "short"
                    WHEN duration_days < 365 THEN "medium"
                    ELSE "long"
                END as duration_category',
                'COUNT(*) as count',
                'AVG(duration_days) as avg_duration'
            ])
            ->from('therapeutic_plans')
            ->groupBy('duration_category')
            ->all();
    }

    /**
     * Ottiene trend mensili di creazione piani
     *
     * @return array
     */
    protected function getPlansMonthlyTrends()
    {
        return (new Query())
            ->select([
                'DATE_FORMAT(start_date, "%Y-%m") as month',
                'COUNT(*) as count'
            ])
            ->from('therapeutic_plans')
            ->where(['>=', 'start_date', date('Y-m-d', strtotime('-12 months'))])
            ->groupBy('month')
            ->orderBy('month')
            ->all();
    }

    /**
     * Ottiene tassi di completamento piani (percentuale appuntamenti completati)
     *
     * @return array
     */
    protected function getPlansCompletionRates()
    {
        return (new Query())
            ->select([
                'tp.id',
                'CONCAT(p.first_name, " ", p.last_name) as patient_name',
                'COUNT(a.id) as total_appointments',
                'SUM(CASE WHEN a.status = "completed" THEN 1 ELSE 0 END) as completed_appointments',
                'ROUND(SUM(CASE WHEN a.status = "completed" THEN 1 ELSE 0 END) * 100.0 / COUNT(a.id), 1) as completion_rate'
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id')
            ->leftJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->leftJoin('appointments a', 'pt.id = a.plan_therapy_id')
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
            ->groupBy(['tp.id', 'p.first_name', 'p.last_name'])
            ->having(['>', 'total_appointments', 0])
            ->orderBy(['completion_rate' => SORT_DESC])
            ->limit(10)
            ->all();
    }

    /**
     * Ottiene piani in scadenza
     *
     * @return array
     */
    protected function getExpiringPlans()
    {
        return (new Query())
            ->select([
                'tp.id',
                'tp.end_date',
                'CONCAT(p.first_name, " ", p.last_name) as patient_name',
                'DATEDIFF(tp.end_date, CURDATE()) as days_until_expiry'
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id')
            ->where(['between', 'tp.end_date', date('Y-m-d'), date('Y-m-d', strtotime('+60 days'))])
            ->orderBy(['tp.end_date' => SORT_ASC])
            ->all();
    }

    /**
     * Ottiene dati per grafico crescita pazienti
     *
     * @param array $filters
     * @return array
     */
    public function getPatientGrowthData($filters = [])
    {
        $dateFrom = $filters['dateFrom'] ?? date('Y-m-d', strtotime('-12 months'));
        $dateTo = $filters['dateTo'] ?? date('Y-m-d');

        return (new Query())
            ->select([
                'DATE_FORMAT(created_at, "%Y-%m") as month',
                'COUNT(*) as new_patients'
            ])
            ->from('statistics_patients_mv')
            ->where(['between', 'DATE(created_at)', $dateFrom, $dateTo])
            ->groupBy('month')
            ->orderBy('month')
            ->all();
    }

    /**
     * Pulisce la cache delle statistiche
     */
    public function clearCache()
    {
        TagDependency::invalidate(Yii::$app->cache, self::CACHE_TAG);
    }
} 
<?php

namespace common\services\statistics;

use Yii;
use yii\db\Query;
use yii\caching\TagDependency;
use frontend\models\TreatmentStatisticsSearch;

/**
 * Service per le statistiche dei trattamenti
 */
class TreatmentStatisticsService
{
    const CACHE_DURATION = 1800; // 30 minuti
    const CACHE_TAG = 'treatment_statistics';

    /**
     * Ottiene statistiche dei trattamenti
     *
     * @param array $params
     * @return array
     */
    public function getStatistics($params = [])
    {
        $searchModel = new TreatmentStatisticsSearch();
        $searchModel->load($params);

        return $searchModel->getStatistics();
    }

    /**
     * Ottiene dati per ranking trattamenti
     *
     * @param array $filters
     * @return array
     */
    public function getRankingData($filters = [])
    {
        $cacheKey = 'treatment_ranking_' . md5(serialize($filters));
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($filters) {
            $query = (new Query())
                ->select([
                    'tt.id',
                    'tt.name',
                    'tt.code',
                    'tt.description',
                    'COUNT(DISTINCT tp.patient_id) as patient_count',
                    'COUNT(pt.id) as therapy_count',
                    'SUM(pt.weekly_hours) as total_weekly_hours',
                    'AVG(pt.weekly_hours) as avg_weekly_hours',
                    'SUM(pt.weekly_hours * 4.33 * tp.duration_days / 365) as estimated_total_hours'
                ])
                ->from('treatment_types tt')
                ->leftJoin('plan_therapies pt', 'tt.id = pt.treatment_type_id')
                ->leftJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id');

            // Applica filtri
            if (!empty($filters['includeInactive']) && !$filters['includeInactive']) {
                $query->andWhere(['>=', 'tp.end_date', date('Y-m-d')]);
            }

            if (!empty($filters['dateFrom'])) {
                $query->andWhere(['>=', 'tp.start_date', $filters['dateFrom']]);
            }

            if (!empty($filters['dateTo'])) {
                $query->andWhere(['<=', 'tp.start_date', $filters['dateTo']]);
            }

            if (!empty($filters['regimeId'])) {
                $query->andWhere(['tp.regime_id' => $filters['regimeId']]);
            }

            return $query->groupBy(['tt.id', 'tt.name', 'tt.code', 'tt.description'])
                ->orderBy(['patient_count' => SORT_DESC])
                ->all();
        }, self::CACHE_DURATION, new TagDependency(['tags' => self::CACHE_TAG]));
    }

    /**
     * Ottiene top N trattamenti
     *
     * @param int $limit
     * @return array
     */
    public function getTop($limit = 5)
    {
        $cacheKey = "treatment_top_{$limit}";
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($limit) {
            return (new Query())
                ->select([
                    'tt.id',
                    'tt.name',
                    'tt.code',
                    'COUNT(DISTINCT tp.patient_id) as patient_count',
                    'COUNT(pt.id) as therapy_count'
                ])
                ->from('treatment_types tt')
                ->innerJoin('plan_therapies pt', 'tt.id = pt.treatment_type_id')
                ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
                ->where(['>=', 'tp.end_date', date('Y-m-d')])
                ->groupBy(['tt.id', 'tt.name', 'tt.code'])
                ->orderBy(['patient_count' => SORT_DESC])
                ->limit($limit)
                ->all();
        }, self::CACHE_DURATION, new TagDependency(['tags' => self::CACHE_TAG]));
    }

    /**
     * Ottiene statistiche combinazioni trattamenti
     *
     * @param array $treatmentIds
     * @param string $mode any|all|exact
     * @return array
     */
    public function getCombinationStats($treatmentIds, $mode = 'any')
    {
        if (empty($treatmentIds)) {
            return [];
        }

        $cacheKey = "treatment_combination_" . md5(implode(',', $treatmentIds) . '_' . $mode);
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($treatmentIds, $mode) {
            $searchModel = new TreatmentStatisticsSearch();
            $searchModel->treatmentIds = $treatmentIds;
            $searchModel->combinationMode = $mode;
            
            return $searchModel->getStatistics();
        }, self::CACHE_DURATION / 2, new TagDependency(['tags' => self::CACHE_TAG]));
    }

    /**
     * Ottiene statistiche distribuzione ore settimanali
     *
     * @return array
     */
    public function getWeeklyHoursDistribution()
    {
        return (new Query())
            ->select([
                'CASE 
                    WHEN pt.weekly_hours <= 2 THEN "1-2h"
                    WHEN pt.weekly_hours <= 5 THEN "3-5h"
                    WHEN pt.weekly_hours <= 10 THEN "6-10h"
                    WHEN pt.weekly_hours <= 20 THEN "11-20h"
                    ELSE "20h+"
                END as hours_range',
                'COUNT(*) as therapy_count',
                'COUNT(DISTINCT tp.patient_id) as patient_count',
                'AVG(pt.weekly_hours) as avg_hours'
            ])
            ->from('plan_therapies pt')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
            ->groupBy('hours_range')
            ->orderBy('avg_hours')
            ->all();
    }

    /**
     * Ottiene statistiche per setting (individuale/gruppo)
     *
     * @return array
     */
    public function getBySettingType()
    {
        return (new Query())
            ->select([
                'CASE WHEN pt.is_group = 1 THEN "Gruppo" ELSE "Individuale" END as setting_type',
                'COUNT(*) as therapy_count',
                'COUNT(DISTINCT tp.patient_id) as patient_count',
                'SUM(pt.weekly_hours) as total_hours',
                'AVG(pt.weekly_hours) as avg_hours'
            ])
            ->from('plan_therapies pt')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
            ->groupBy('pt.is_group')
            ->all();
    }

    /**
     * Ottiene trend mensili trattamenti
     *
     * @param array $filters
     * @return array
     */
    public function getMonthlyTrends($filters = [])
    {
        $query = (new Query())
            ->select([
                'DATE_FORMAT(tp.start_date, "%Y-%m") as month',
                'COUNT(DISTINCT pt.id) as new_therapies',
                'COUNT(DISTINCT tp.patient_id) as new_patients',
                'SUM(pt.weekly_hours) as total_hours'
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->where(['>=', 'tp.start_date', date('Y-m-d', strtotime('-12 months'))]);

        // Applica filtri
        if (!empty($filters['treatmentIds'])) {
            $query->andWhere(['in', 'pt.treatment_type_id', $filters['treatmentIds']]);
        }

        if (!empty($filters['regimeId'])) {
            $query->andWhere(['tp.regime_id' => $filters['regimeId']]);
        }

        return $query->groupBy('month')
            ->orderBy('month')
            ->all();
    }

    /**
     * Ottiene statistiche per regime sanitario
     *
     * @return array
     */
    public function getByRegime()
    {
        return (new Query())
            ->select([
                'r.id',
                'r.nome as regime_name',
                'COUNT(DISTINCT pt.id) as therapy_count',
                'COUNT(DISTINCT tp.patient_id) as patient_count',
                'SUM(pt.weekly_hours) as total_hours',
                'AVG(pt.weekly_hours) as avg_hours'
            ])
            ->from('regime r')
            ->leftJoin('therapeutic_plans tp', 'r.id = tp.regime_id')
            ->leftJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
            ->groupBy(['r.id', 'r.nome'])
            ->orderBy(['therapy_count' => SORT_DESC])
            ->all();
    }

    /**
     * Ottiene combinazioni più frequenti di trattamenti
     *
     * @param int $limit
     * @return array
     */
    public function getMostFrequentCombinations($limit = 10)
    {
        $cacheKey = "frequent_combinations_{$limit}";
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($limit) {
            // Trova pazienti con più di un trattamento
            $multiTreatmentPatients = (new Query())
                ->select([
                    'tp.patient_id',
                    'GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name) as treatment_combination',
                    'COUNT(DISTINCT pt.treatment_type_id) as treatment_count'
                ])
                ->from('therapeutic_plans tp')
                ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
                ->innerJoin('treatment_types tt', 'pt.treatment_type_id = tt.id')
                ->where(['>=', 'tp.end_date', date('Y-m-d')])
                ->groupBy('tp.patient_id')
                ->having(['>', 'treatment_count', 1])
                ->all();

            // Conta frequenza combinazioni
            $combinations = [];
            foreach ($multiTreatmentPatients as $patient) {
                $combo = $patient['treatment_combination'];
                if (!isset($combinations[$combo])) {
                    $combinations[$combo] = [
                        'combination' => $combo,
                        'patient_count' => 0,
                        'treatment_count' => $patient['treatment_count']
                    ];
                }
                $combinations[$combo]['patient_count']++;
            }

            // Ordina per frequenza
            usort($combinations, function($a, $b) {
                return $b['patient_count'] - $a['patient_count'];
            });

            return array_slice($combinations, 0, $limit);
        }, self::CACHE_DURATION, new TagDependency(['tags' => self::CACHE_TAG]));
    }

    /**
     * Ottiene utilizzo trattamenti per terapista
     *
     * @param int|null $therapistId
     * @return array
     */
    public function getByTherapist($therapistId = null)
    {
        $query = (new Query())
            ->select([
                't.id as therapist_id',
                'CONCAT(up.first_name, " ", up.last_name) as therapist_name',
                'tt.name as treatment_name',
                'COUNT(DISTINCT a.id) as appointment_count',
                'COUNT(DISTINCT tp.patient_id) as patient_count',
                'SUM(a.duration_minutes) as total_minutes'
            ])
            ->from('therapists t')
            ->innerJoin('users u', 't.user_id = u.id')
            ->innerJoin('user_profiles up', 'u.id = up.user_id')
            ->innerJoin('appointments a', 't.id = a.therapist_id')
            ->innerJoin('plan_therapies pt', 'a.plan_therapy_id = pt.id')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->innerJoin('treatment_types tt', 'pt.treatment_type_id = tt.id')
            ->where(['>=', 'DATE(a.appointment_datetime)', date('Y-m-d', strtotime('-3 months'))]);

        if ($therapistId) {
            $query->andWhere(['t.id' => $therapistId]);
        }

        return $query->groupBy(['t.id', 'up.first_name', 'up.last_name', 'tt.name'])
            ->orderBy(['therapist_name' => SORT_ASC, 'appointment_count' => SORT_DESC])
            ->all();
    }

    /**
     * Ottiene efficacia trattamenti (percentuale completamento appuntamenti)
     *
     * @return array
     */
    public function getEffectivenessStats()
    {
        return (new Query())
            ->select([
                'tt.id',
                'tt.name',
                'COUNT(a.id) as total_appointments',
                'SUM(CASE WHEN a.status = "completed" THEN 1 ELSE 0 END) as completed_appointments',
                'SUM(CASE WHEN a.status LIKE "%absent%" THEN 1 ELSE 0 END) as absent_appointments',
                'ROUND(SUM(CASE WHEN a.status = "completed" THEN 1 ELSE 0 END) * 100.0 / COUNT(a.id), 1) as completion_rate'
            ])
            ->from('treatment_types tt')
            ->innerJoin('plan_therapies pt', 'tt.id = pt.treatment_type_id')
            ->innerJoin('appointments a', 'pt.id = a.plan_therapy_id')
            ->where(['>=', 'DATE(a.appointment_datetime)', date('Y-m-d', strtotime('-6 months'))])
            ->groupBy(['tt.id', 'tt.name'])
            ->having(['>', 'total_appointments', 10]) // Almeno 10 appuntamenti per statistiche significative
            ->orderBy(['completion_rate' => SORT_DESC])
            ->all();
    }

    /**
     * Pulisce la cache delle statistiche trattamenti
     */
    public function clearCache()
    {
        TagDependency::invalidate(Yii::$app->cache, self::CACHE_TAG);
    }
} 
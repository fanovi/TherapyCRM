<?php

namespace common\services\statistics;

use Yii;
use yii\db\Query;
use yii\db\Expression;
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
     * @param mixed $searchModelOrFilters SearchModel o array di filtri per compatibilità
     * @return array
     */
        public function getRankingData($searchModelOrFilters = [])
    {
        // Determina se è un searchModel o array di filtri
        $isSearchModel = is_object($searchModelOrFilters) && $searchModelOrFilters instanceof \frontend\models\TreatmentStatisticsSearch;
        $filters = $isSearchModel ? $this->extractFiltersFromSearchModel($searchModelOrFilters) : $searchModelOrFilters;
        
        // Se c'è un searchModel con filtri, non usare cache
        if ($isSearchModel && !empty(array_filter($filters))) {
            return $this->getRankingDataWithFilters($filters);
        }
        
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
     * @param mixed $searchModel SearchModel per filtri (opzionale)
     * @return array
     */
    public function getWeeklyHoursDistribution($searchModel = null)
    {
        return (new Query())
            ->select([
                'hours_range' => new Expression('CASE 
                    WHEN pt.weekly_hours <= 2 THEN "1-2h"
                    WHEN pt.weekly_hours <= 5 THEN "3-5h"
                    WHEN pt.weekly_hours <= 10 THEN "6-10h"
                    WHEN pt.weekly_hours <= 20 THEN "11-20h"
                    ELSE "20h+"
                END'),
                'therapy_count' => 'COUNT(*)',
                'patient_count' => 'COUNT(DISTINCT tp.patient_id)',
                'avg_hours' => 'AVG(pt.weekly_hours)'
            ])
            ->from('plan_therapies pt')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->where(['>=', 'tp.end_date', date('Y-m-d')])
            ->groupBy(new Expression('CASE 
                WHEN pt.weekly_hours <= 2 THEN "1-2h"
                WHEN pt.weekly_hours <= 5 THEN "3-5h"
                WHEN pt.weekly_hours <= 10 THEN "6-10h"
                WHEN pt.weekly_hours <= 20 THEN "11-20h"
                ELSE "20h+"
            END'))
            ->orderBy('avg_hours')
            ->all();
    }

    /**
     * Ottiene statistiche per setting (individuale/gruppo)
     *
     * @param mixed $searchModel SearchModel per filtri (opzionale)
     * @return array
     */
    public function getBySettingType($searchModel = null)
    {
        return (new Query())
            ->select([
                'setting_type' => new Expression('CASE WHEN pt.is_group = 1 THEN "Gruppo" ELSE "Individuale" END'),
                'therapy_count' => 'COUNT(*)',
                'patient_count' => 'COUNT(DISTINCT tp.patient_id)',
                'total_hours' => 'SUM(pt.weekly_hours)',
                'avg_hours' => 'AVG(pt.weekly_hours)'
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
                'month' => new Expression('DATE_FORMAT(tp.start_date, "%Y-%m")'),
                'new_therapies' => 'COUNT(DISTINCT pt.id)',
                'new_patients' => 'COUNT(DISTINCT tp.patient_id)',
                'total_hours' => 'SUM(pt.weekly_hours)'
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

        return $query->groupBy(new Expression('DATE_FORMAT(tp.start_date, "%Y-%m")'))
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
     * @param mixed $searchModelOrLimit SearchModel o limite numerico per compatibilità
     * @param int $limit Limite quando il primo parametro è searchModel
     * @return array
     */
    public function getMostFrequentCombinations($searchModelOrLimit = 10, $limit = 10)
    {
        // Determina se è un searchModel o un limite numerico
        $isSearchModel = is_object($searchModelOrLimit) && $searchModelOrLimit instanceof \frontend\models\TreatmentStatisticsSearch;
        $actualLimit = $isSearchModel ? $limit : $searchModelOrLimit;
        
        $cacheKey = "frequent_combinations_{$actualLimit}";
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($actualLimit) {
            // Trova pazienti con più di un trattamento
            $multiTreatmentPatients = (new Query())
                ->select([
                    'tp.patient_id',
                    'treatment_combination' => new Expression('GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name)'),
                    'treatment_count' => 'COUNT(DISTINCT pt.treatment_type_id)'
                ])
                ->from('therapeutic_plans tp')
                ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
                ->innerJoin('treatment_types tt', 'pt.treatment_type_id = tt.id')
                ->where(['>=', 'tp.end_date', date('Y-m-d')])
                ->groupBy('tp.patient_id')
                ->having(['>', new Expression('COUNT(DISTINCT pt.treatment_type_id)'), 1])
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

            return array_slice($combinations, 0, $actualLimit);
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
                'therapist_name' => new Expression('CONCAT(up.first_name, " ", up.last_name)'),
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
                'total_appointments' => 'COUNT(a.id)',
                'completed_appointments' => new Expression('SUM(CASE WHEN a.status = "completed" THEN 1 ELSE 0 END)'),
                'absent_appointments' => new Expression('SUM(CASE WHEN a.status LIKE "%absent%" THEN 1 ELSE 0 END)'),
                'completion_rate' => new Expression('ROUND(SUM(CASE WHEN a.status = "completed" THEN 1 ELSE 0 END) * 100.0 / COUNT(a.id), 1)')
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

    /**
     * Estrae i filtri dal search model
     */
    protected function extractFiltersFromSearchModel($searchModel)
    {
        $filters = [];
        
        if (!empty($searchModel->treatmentIds)) {
            $filters['treatmentIds'] = $searchModel->treatmentIds;
        }
        
        if (!empty($searchModel->regimeId)) {
            $filters['regimeId'] = $searchModel->regimeId;
        }
        
        if (!empty($searchModel->dateFrom)) {
            $filters['dateFrom'] = $searchModel->dateFrom;
        }
        
        if (!empty($searchModel->dateTo)) {
            $filters['dateTo'] = $searchModel->dateTo;
        }
        
        return $filters;
    }

    /**
     * Metodo helper per getRankingData con filtri applicati
     */
    protected function getRankingDataWithFilters($filters)
    {
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
        if (!empty($filters['treatmentIds'])) {
            $query->andWhere(['tt.id' => $filters['treatmentIds']]);
        }
        
        if (!empty($filters['regimeId'])) {
            $query->andWhere(['tp.regime_id' => $filters['regimeId']]);
        }
        
        if (!empty($filters['dateFrom'])) {
            $query->andWhere(['>=', 'tp.start_date', $filters['dateFrom']]);
        }
        
        if (!empty($filters['dateTo'])) {
            $query->andWhere(['<=', 'tp.start_date', $filters['dateTo']]);
        }

        return $query->groupBy(['tt.id', 'tt.name', 'tt.code', 'tt.description'])
            ->having(['>', 'COUNT(DISTINCT tp.patient_id)', 0])
            ->orderBy(['patient_count' => SORT_DESC])
            ->all();
    }
}
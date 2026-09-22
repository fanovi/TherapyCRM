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
        $searchModel = $this->resolveSearchModel($searchModelOrFilters);

        $loader = function () use ($searchModel) {
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
                ])
                ->from('treatment_types tt')
                ->innerJoin('plan_therapies pt', 'tt.id = pt.treatment_type_id')
                ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id');

            $this->applyTreatmentFilters($query, $searchModel, ['restrictTypes' => true, 'restrictPatients' => true]);

            return $query->groupBy(['tt.id', 'tt.name', 'tt.code', 'tt.description'])
                ->having(['>', 'COUNT(DISTINCT tp.patient_id)', 0])
                ->orderBy(['patient_count' => SORT_DESC])
                ->all();
        };

        if ($this->hasRestrictiveFilters($searchModel)) {
            return $loader();
        }

        $cacheKey = 'treatment_ranking_' . md5(serialize($searchModel->attributes));
        return Yii::$app->cache->getOrSet($cacheKey, $loader, self::CACHE_DURATION, new TagDependency(['tags' => self::CACHE_TAG]));
    }

    /**
     * Pazienti distinti con almeno una terapia nei filtri correnti.
     */
    public function getDistinctPatientCount($searchModel)
    {
        $query = (new Query())
            ->from('therapeutic_plans tp')
            ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id');

        $this->applyTreatmentFilters($query, $searchModel, ['restrictTypes' => false, 'restrictPatients' => true]);

        return (int) $query->count('DISTINCT tp.patient_id');
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
                ->where(['tp.status' => 'active'])
                ->andWhere(['<=', 'tp.start_date', date('Y-m-d')])
                ->andWhere(['>=', 'tp.end_date', date('Y-m-d')])
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
        $hoursCase = 'CASE
                    WHEN pt.weekly_hours <= 2 THEN "<=2h"
                    WHEN pt.weekly_hours <= 5 THEN "2-5h"
                    WHEN pt.weekly_hours <= 10 THEN "5-10h"
                    WHEN pt.weekly_hours <= 20 THEN "10-20h"
                    ELSE ">20h"
                END';

        $query = (new Query())
            ->select([
                'hours_range' => new Expression($hoursCase),
                'therapy_count' => 'COUNT(*)',
                'patient_count' => 'COUNT(DISTINCT tp.patient_id)',
                'avg_hours' => 'AVG(pt.weekly_hours)',
            ])
            ->from('plan_therapies pt')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id');

        $this->applyTreatmentFilters($query, $searchModel, ['restrictTypes' => true, 'restrictPatients' => true]);

        return $query
            ->groupBy(new Expression($hoursCase))
            ->orderBy('avg_hours')
            ->all();
    }

    /**
     * Ottiene statistiche per setting (ambulatoriale, domiciliare, ecc.)
     *
     * @param mixed $searchModel SearchModel per filtri (opzionale)
     * @return array
     */
    public function getBySettingType($searchModel = null)
    {
        $query = (new Query())
            ->select([
                'setting_type' => new Expression('COALESCE(s.nome, "N/D")'),
                'therapy_count' => 'COUNT(*)',
                'patient_count' => 'COUNT(DISTINCT tp.patient_id)',
                'total_hours' => 'SUM(pt.weekly_hours)',
                'avg_hours' => 'AVG(pt.weekly_hours)',
            ])
            ->from('plan_therapies pt')
            ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
            ->leftJoin('setting s', 'pt.setting_id = s.id');

        $this->applyTreatmentFilters($query, $searchModel, ['restrictTypes' => true, 'restrictPatients' => true]);

        return $query
            ->groupBy(['s.id', 's.nome'])
            ->having(['>', 'COUNT(*)', 0])
            ->orderBy(['therapy_count' => SORT_DESC])
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
            ->where(['tp.status' => 'active'])
            ->andWhere(['<=', 'tp.start_date', date('Y-m-d')])
            ->andWhere(['>=', 'tp.end_date', date('Y-m-d')])
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
        $isSearchModel = is_object($searchModelOrLimit) && $searchModelOrLimit instanceof TreatmentStatisticsSearch;
        $actualLimit = $isSearchModel ? $limit : $searchModelOrLimit;
        $searchModel = $isSearchModel ? $searchModelOrLimit : new TreatmentStatisticsSearch();

        $loader = function () use ($searchModel, $actualLimit) {
            return $this->getFrequentCombinations($searchModel, $actualLimit);
        };

        if ($this->hasRestrictiveFilters($searchModel)) {
            return $loader();
        }

        $cacheKey = 'frequent_combinations_' . $actualLimit . '_' . md5(serialize($searchModel->attributes));
        return Yii::$app->cache->getOrSet($cacheKey, $loader, self::CACHE_DURATION, new TagDependency(['tags' => self::CACHE_TAG]));
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
                // Le sessioni di gruppo condividono group_session_id: la durata dello
                // slot va contata UNA volta sola (riga con id minimo del gruppo),
                // altrimenti la stessa ora viene sommata per ogni paziente del gruppo.
                // I conteggi appuntamenti/pazienti restano invariati.
                'total_minutes' => new Expression(
                    "SUM(CASE WHEN a.group_session_id IS NULL THEN a.duration_minutes "
                    . "WHEN a.id = (SELECT MIN(a2.id) FROM appointments a2 WHERE a2.group_session_id = a.group_session_id) "
                    . "THEN a.duration_minutes ELSE 0 END)"
                )
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
    public static function invalidateCache()
    {
        TagDependency::invalidate(Yii::$app->cache, self::CACHE_TAG);
    }

    /**
     * Pulisce la cache delle statistiche trattamenti
     */
    public function clearCache()
    {
        self::invalidateCache();
    }

    /**
     * Applica i filtri della pagina trattamenti a una query con alias pt/tp.
     *
     * @param Query $query
     * @param TreatmentStatisticsSearch|null $searchModel
     * @param array $options restrictTypes: filtra pt.treatment_type_id; restrictPatients: insieme pazienti della combinazione
     */
    protected function applyTreatmentFilters($query, $searchModel = null, $options = [])
    {
        $restrictTypes = $options['restrictTypes'] ?? true;
        $restrictPatients = $options['restrictPatients'] ?? true;

        if (!$searchModel) {
            $searchModel = new TreatmentStatisticsSearch();
        }

        $searchModel->applyCommonFilters($query);

        if ($restrictPatients) {
            $patientQuery = $searchModel->getMatchingPatientIdQuery();
            if ($patientQuery !== null) {
                $query->andWhere(['in', 'tp.patient_id', $patientQuery]);
            }
        }

        if ($restrictTypes && !empty($searchModel->treatmentIds)) {
            $query->andWhere(['pt.treatment_type_id' => $searchModel->treatmentIds]);
        }
    }

    protected function applyActivePlanFilter($query)
    {
        $today = date('Y-m-d');
        $query->andWhere(['tp.status' => 'active'])
            ->andWhere(['<=', 'tp.start_date', $today])
            ->andWhere(['>=', 'tp.end_date', $today]);
    }

    protected function getFrequentCombinations($searchModel, $limit)
    {
        $query = (new Query())
            ->select([
                'tp.patient_id',
                'treatment_combination' => new Expression('GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name)'),
                'treatment_count' => 'COUNT(DISTINCT pt.treatment_type_id)',
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->innerJoin('treatment_types tt', 'pt.treatment_type_id = tt.id');

        // Tutti i tipi del paziente, ma solo pazienti del filtro combinazione
        $this->applyTreatmentFilters($query, $searchModel, ['restrictTypes' => false, 'restrictPatients' => true]);
        $patients = $query
            ->groupBy('tp.patient_id')
            ->having(['>', new Expression('COUNT(DISTINCT pt.treatment_type_id)'), 1])
            ->all();

        $combinations = [];
        foreach ($patients as $patient) {
            $combo = $patient['treatment_combination'];
            if (!isset($combinations[$combo])) {
                $combinations[$combo] = [
                    'combination' => $combo,
                    'patient_count' => 0,
                    'treatment_count' => $patient['treatment_count'],
                ];
            }
            $combinations[$combo]['patient_count']++;
        }

        usort($combinations, function ($a, $b) {
            return $b['patient_count'] - $a['patient_count'];
        });

        $totalMultiPatients = array_sum(array_column($combinations, 'patient_count'));

        return [
            'items' => array_slice($combinations, 0, $limit),
            'total_multi_patients' => $totalMultiPatients,
        ];
    }

    /**
     * @param mixed $searchModelOrFilters
     * @return TreatmentStatisticsSearch
     */
    protected function resolveSearchModel($searchModelOrFilters)
    {
        if ($searchModelOrFilters instanceof TreatmentStatisticsSearch) {
            return $searchModelOrFilters;
        }

        $searchModel = new TreatmentStatisticsSearch();
        if (is_array($searchModelOrFilters) && $searchModelOrFilters) {
            $searchModel->load($searchModelOrFilters);
        }

        return $searchModel;
    }

    protected function hasRestrictiveFilters($searchModel)
    {
        return !empty($searchModel->treatmentIds)
            || !empty($searchModel->regimeId)
            || !empty($searchModel->dateFrom)
            || !empty($searchModel->dateTo)
            || (bool) $searchModel->includeInactive;
    }
}
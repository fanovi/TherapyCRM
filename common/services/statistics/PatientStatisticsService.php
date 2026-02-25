<?php

namespace common\services\statistics;

use Yii;
use yii\db\Query;
use yii\caching\TagDependency;
use frontend\models\PatientStatisticsSearch;

/**
 * Service per le statistiche dei pazienti
 */
class PatientStatisticsService
{
    const CACHE_DURATION = 1800; // 30 minuti
    const CACHE_TAG = 'patient_statistics';

    /**
     * Ottiene il conteggio pazienti attivi
     *
     * @return int
     */
    public function getActiveCount()
    {
        $cacheKey = 'patient_active_count';

        return Yii::$app->cache->getOrSet($cacheKey, function () {
            return (new Query())
                ->from('statistics_patients_mv')
                ->where(['piano_terapeutico_attivo' => 'SI'])
                ->count();
        }, self::CACHE_DURATION, new TagDependency(['tags' => self::CACHE_TAG]));
    }

    /**
     * Ottiene statistiche pazienti per trattamento
     *
     * @param PatientStatisticsSearch $searchModel
     * @return array
     */
    public function getByTreatment($searchModel)
    {
        $query = (new Query())
            ->select([
                'tt.id',
                'tt.name',
                'tt.code',
                'COUNT(DISTINCT tp.patient_id) as patient_count'
            ])
            ->from('treatment_types tt')
            ->leftJoin('plan_therapies pt', 'tt.id = pt.treatment_type_id')
            ->leftJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id AND tp.end_date >= CURDATE()')
            ->leftJoin('statistics_patients_mv sp', 'tp.patient_id = sp.id');

        // Se ci sono filtri per treatmentTypeIds, mostra solo quelli
        if (!empty($searchModel->treatmentTypeIds)) {
            $query->andWhere(['tt.id' => $searchModel->treatmentTypeIds]);
        }

        // Applica altri filtri del search model (senza treatmentTypeIds per evitare duplicazione)
        $tempSearchModel = clone $searchModel;
        $tempSearchModel->treatmentTypeIds = null;
        $this->applyPatientFilters($query, $tempSearchModel);

        return $query->groupBy(['tt.id', 'tt.name', 'tt.code'])
            ->having(['>', 'COUNT(DISTINCT tp.patient_id)', 0]) // Mostra solo trattamenti con pazienti
            ->orderBy(['patient_count' => SORT_DESC])
            ->all();
    }

    /**
     * Ottiene statistiche pazienti per regime
     *
     * @param PatientStatisticsSearch $searchModel
     * @return array
     */
    public function getByRegime($searchModel)
    {
        $query = (new Query())
            ->select([
                'r.id',
                'r.nome as regime_name',
                'r.descrizione',
                'COUNT(DISTINCT tp.patient_id) as patient_count',
                'AVG(tp.duration_days) as avg_duration'
            ])
            ->from('regime r')
            ->leftJoin('therapeutic_plans tp', 'r.id = tp.regime_id AND tp.end_date >= CURDATE()')
            ->leftJoin('statistics_patients_mv sp', 'tp.patient_id = sp.id');

        // Applica filtri del search model
        $this->applyPatientFilters($query, $searchModel);

        return $query->groupBy(['r.id', 'r.nome', 'r.descrizione'])
            ->orderBy(['patient_count' => SORT_DESC])
            ->all();
    }

    /**
     * Ottiene demografia pazienti (età, genere)
     *
     * @param PatientStatisticsSearch $searchModel
     * @return array
     */
    public function getDemographics($searchModel)
    {
        $cacheKey = 'patient_demographics_' . md5(serialize($searchModel->attributes));

        return Yii::$app->cache->getOrSet($cacheKey, function () use ($searchModel) {
            return [
                'age_groups' => $this->getAgeGroupsData($searchModel),
                'gender_distribution' => $this->getGenderDistribution($searchModel),
                'age_stats' => $this->getAgeStatistics($searchModel),
            ];
        }, self::CACHE_DURATION / 2, new TagDependency(['tags' => self::CACHE_TAG]));
    }

    /**
     * Ottiene distribuzione per gruppi di età
     *
     * @param PatientStatisticsSearch $searchModel
     * @return array
     */
    protected function getAgeGroupsData($searchModel)
    {
        $query = $searchModel->getStatisticsQuery();

        return $query->select([
            new \yii\db\Expression("
            CASE 
                WHEN age < 18 THEN '0-17'
                WHEN age < 30 THEN '18-29'
                WHEN age < 50 THEN '30-49'
                WHEN age < 65 THEN '50-64'
                ELSE '65+'
            END as age_group
        "),
            'COUNT(*) as count',
            new \yii\db\Expression('ROUND(AVG(age), 1) as avg_age')
        ])
            ->groupBy('age_group')
            ->orderBy('age_group')
            ->all();
    }

    /**
     * Ottiene distribuzione per genere
     *
     * @param PatientStatisticsSearch $searchModel
     * @return array
     */
    protected function getGenderDistribution($searchModel)
    {
        $query = $searchModel->getStatisticsQuery();

        $data = $query->select([
            'sp.gender',
            'COUNT(*) as count'
        ])
            ->groupBy('sp.gender')
            ->all();

        $labels = [
            'M' => 'Maschio',
            'F' => 'Femmina',
            'N' => 'Non specificato'
        ];

        foreach ($data as &$row) {
            $row['gender_label'] = $labels[$row['gender']] ?? $row['gender'];
        }

        return $data;
    }

    /**
     * Ottiene statistiche età
     *
     * @param PatientStatisticsSearch $searchModel
     * @return array
     */
    protected function getAgeStatistics($searchModel)
    {
        $query = $searchModel->getStatisticsQuery();

        return $query->select([
            'AVG(sp.age) as avg_age',
            'MIN(sp.age) as min_age',
            'MAX(sp.age) as max_age',
            'COUNT(*) as total_patients'
        ])->one();
    }

    /**
     * Ottiene statistiche pazienti con trattamenti multipli
     *
     * @param mixed $searchModelOrExcludeABA SearchModel per filtri o bool per escludere ABA
     * @return array
     */
    public function getMultiTreatmentStats($searchModelOrExcludeABA = true)
    {
        // Determina se è stato passato un searchModel o un booleano
        $isSearchModel = is_object($searchModelOrExcludeABA) && $searchModelOrExcludeABA instanceof PatientStatisticsSearch;
        $excludeABA = $isSearchModel ? true : $searchModelOrExcludeABA;
        $searchModel = $isSearchModel ? $searchModelOrExcludeABA : null;
        
        // Se c'è un searchModel, non usare cache per rispettare i filtri
        if ($searchModel) {
            return $this->getMultiTreatmentStatsWithFilters($searchModel, $excludeABA);
        }

        $cacheKey = 'multi_treatment_stats_' . ($excludeABA ? 'no_aba' : 'with_aba');

        return Yii::$app->cache->getOrSet($cacheKey, function () use ($excludeABA) {
            // Query base per pazienti con trattamenti multipli
            $query = (new Query())
                ->select([
                    'tp.patient_id',
                    'CONCAT(p.first_name, " ", p.last_name) as patient_name',
                    'COUNT(DISTINCT pt.treatment_type_id) as treatment_count',
                    'GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name) as treatments'
                ])
                ->from('therapeutic_plans tp')
                ->innerJoin('patients p', 'tp.patient_id = p.id')
                ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
                ->innerJoin('treatment_types tt', 'pt.treatment_type_id = tt.id')
                ->where(['>=', 'tp.end_date', date('Y-m-d')]);

            if ($excludeABA) {
                $query->andWhere(['not like', 'tt.name', '%ABA%']);
            }

            $patients = $query->groupBy(['tp.patient_id', 'p.first_name', 'p.last_name'])
                ->having(['>', 'treatment_count', 1])
                ->orderBy(['treatment_count' => SORT_DESC, 'patient_name' => SORT_ASC])
                ->all();

            // Statistiche aggregate
            $stats = (new Query())
                ->select([
                    'AVG(treatment_count) as avg_treatments',
                    'MAX(treatment_count) as max_treatments',
                    'COUNT(*) as total_multi_patients'
                ])
                ->from(['subquery' => $query])
                ->where(['>', 'treatment_count', 1])
                ->one();

            return [
                'patients' => $patients,
                'stats' => $stats
            ];
        }, self::CACHE_DURATION, new TagDependency(['tags' => self::CACHE_TAG]));
    }

    /**
     * Ottiene statistiche pazienti con trattamenti multipli applicando filtri
     *
     * @param PatientStatisticsSearch $searchModel
     * @param bool $excludeABA
     * @return array
     */
    protected function getMultiTreatmentStatsWithFilters($searchModel, $excludeABA = true)
    {
        // Query base per pazienti con trattamenti multipli
        $query = (new Query())
            ->select([
                'tp.patient_id',
                'CONCAT(p.first_name, " ", p.last_name) as patient_name',
                'COUNT(DISTINCT pt.treatment_type_id) as treatment_count',
                'GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name) as treatments'
            ])
            ->from('therapeutic_plans tp')
            ->innerJoin('patients p', 'tp.patient_id = p.id')
            ->innerJoin('plan_therapies pt', 'tp.id = pt.therapeutic_plan_id')
            ->innerJoin('treatment_types tt', 'pt.treatment_type_id = tt.id')
            ->innerJoin('statistics_patients_mv sp', 'p.id = sp.id') // Join per applicare filtri
            ->where(['>=', 'tp.end_date', date('Y-m-d')]);

        if ($excludeABA) {
            $query->andWhere(['not like', 'tt.name', '%ABA%']);
        }

        // Applica filtri dal search model
        $this->applyPatientFilters($query, $searchModel);

        $patients = $query->groupBy(['tp.patient_id', 'p.first_name', 'p.last_name'])
            ->having(['>', 'treatment_count', 1])
            ->orderBy(['treatment_count' => SORT_DESC, 'patient_name' => SORT_ASC])
            ->all();

        // Statistiche aggregate
        $stats = (new Query())
            ->select([
                'AVG(treatment_count) as avg_treatments',
                'MAX(treatment_count) as max_treatments',
                'COUNT(*) as total_multi_patients'
            ])
            ->from(['subquery' => $query])
            ->where(['>', 'treatment_count', 1])
            ->one();

        return [
            'patients' => $patients,
            'stats' => $stats
        ];
    }

    /**
     * Ottiene dati di crescita pazienti nel tempo
     *
     * @param array $filters
     * @return array
     */
    public function getGrowthData($filters = [])
    {
        $dateFrom = $filters['dateFrom'] ?? date('Y-m-d', strtotime('-12 months'));
        $dateTo = $filters['dateTo'] ?? date('Y-m-d');

        return (new Query())
            ->select([
                'DATE_FORMAT(sp.created_at, "%Y-%m") as month',
                'COUNT(*) as new_patients',
                'SUM(COUNT(*)) OVER (ORDER BY DATE_FORMAT(sp.created_at, "%Y-%m")) as cumulative_patients'
            ])
            ->from('statistics_patients_mv sp')
            ->where(['between', 'DATE(sp.created_at)', $dateFrom, $dateTo])
            ->groupBy('month')
            ->orderBy('month')
            ->all();
    }

    /**
     * Ottiene distribuzione geografica (per distretto)
     *
     * @param PatientStatisticsSearch $searchModel
     * @return array
     */
    public function getGeographicDistribution($searchModel)
    {
        $query = (new Query())
            ->select([
                'd.id',
                'd.nome as district_name',
                'COUNT(DISTINCT p.id) as patient_count'
            ])
            ->from('districts d')
            ->leftJoin('patients p', 'd.id = p.district_id')
            ->leftJoin('statistics_patients_mv sp', 'p.id = sp.id');

        // Applica filtri del search model (senza il filtro distretto per evitare circolarità)
        $tempSearchModel = clone $searchModel;
        $tempSearchModel->districtId = null;
        $this->applyPatientFilters($query, $tempSearchModel);

        return $query->groupBy(['d.id', 'd.nome'])
            ->orderBy(['patient_count' => SORT_DESC])
            ->all();
    }

    /**
     * Ottiene pazienti per status di piano terapeutico
     *
     * @return array
     */
    public function getByPlanStatus()
    {
        return (new Query())
            ->select([
                'sp.piano_terapeutico_attivo as status',
                'COUNT(*) as count'
            ])
            ->from('statistics_patients_mv sp')
            ->groupBy('sp.piano_terapeutico_attivo')
            ->all();
    }

    /**
     * Applica filtri del patient search model alla query
     *
     * @param Query $query
     * @param PatientStatisticsSearch $searchModel
     */
    protected function applyPatientFilters($query, $searchModel)
    {
        // Filtro piano terapeutico attivo (coerente con PatientStatisticsSearch)
        if ($searchModel->activePlanOnly) {
            $query->andWhere(['sp.piano_terapeutico_attivo' => 'SI']);
        }

        if ($searchModel->gender && $searchModel->gender !== 'all') {
            $query->andWhere(['sp.gender' => $searchModel->gender]);
        }

        if ($searchModel->ageFrom !== null) {
            $query->andWhere(['>=', 'sp.age', $searchModel->ageFrom]);
        }

        if ($searchModel->ageTo !== null) {
            $query->andWhere(['<=', 'sp.age', $searchModel->ageTo]);
        }

        if ($searchModel->status && $searchModel->status !== 'all') {
            if ($searchModel->status === 'active') {
                $query->andWhere(['sp.piano_terapeutico_attivo' => 'SI']);
            } elseif ($searchModel->status === 'dismissed') {
                $query->andWhere(['sp.dismesso' => 'SI']);
            }
        }

        if ($searchModel->dateFrom) {
            $query->andWhere(['>=', 'DATE(sp.created_at)', $searchModel->dateFrom]);
        }

        if ($searchModel->dateTo) {
            $query->andWhere(['<=', 'DATE(sp.created_at)', $searchModel->dateTo]);
        }

        if ($searchModel->hasMultipleTreatments !== null) {
            if ($searchModel->hasMultipleTreatments) {
                $query->andWhere(['>', 'sp.trattamenti_count_no_aba', 1]);
            } else {
                $query->andWhere(['<=', 'sp.trattamenti_count_no_aba', 1]);
            }
        }

        if (!empty($searchModel->treatmentTypeIds)) {
            // Filtro per tipi di trattamento - applica solo ai pazienti che hanno questi trattamenti
            $subQuery = (new Query())
                ->select('tp_sub.patient_id')
                ->distinct()
                ->from('plan_therapies pt_sub')
                ->innerJoin('therapeutic_plans tp_sub', 'pt_sub.therapeutic_plan_id = tp_sub.id')
                ->where(['pt_sub.treatment_type_id' => $searchModel->treatmentTypeIds]);

            $query->andWhere(['IN', 'tp.patient_id', $subQuery]);
        }

        if ($searchModel->districtId) {
            // Richiede join con patients se non già presente
            if (strpos($query->createCommand()->getRawSql(), 'patients') === false) {
                $query->leftJoin('patients p', 'sp.id = p.id');
            }
            $query->andWhere(['p.district_id' => $searchModel->districtId]);
        }
    }

    /**
     * Pulisce la cache delle statistiche pazienti
     */
    public function clearCache()
    {
        TagDependency::invalidate(Yii::$app->cache, self::CACHE_TAG);
    }
}

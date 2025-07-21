<?php

namespace common\services\statistics;

use Yii;
use yii\db\Query;
use yii\caching\TagDependency;
use frontend\models\AbsenceStatisticsSearch;

/**
 * Service per le statistiche delle assenze
 */
class AbsenceStatisticsService
{
    const CACHE_DURATION = 900; // 15 minuti
    const CACHE_TAG = 'absence_statistics';

    /**
     * Ottiene dati per heatmap assenze (orari x giorni settimana)
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @return array
     */
    public function getHeatmapData($searchModel)
    {
        $cacheKey = 'absence_heatmap_' . md5(serialize($searchModel->attributes));
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($searchModel) {
            $query = $searchModel->getStatisticsQuery();
            
            $data = $query->select([
                'sa.absence_day_number',
                'sa.absence_hour',
                'COUNT(*) as absence_count'
            ])
            ->groupBy(['sa.absence_day_number', 'sa.absence_hour'])
            ->orderBy(['sa.absence_day_number' => SORT_ASC, 'sa.absence_hour' => SORT_ASC])
            ->all();

            // Trasforma i dati per la heatmap
            $heatmapData = [];
            $maxCount = 0;
            
            foreach ($data as $row) {
                $dayIndex = $row['absence_day_number'] - 1; // 0-6 per JS
                $hour = $row['absence_hour'];
                $count = (int)$row['absence_count'];
                
                if (!isset($heatmapData[$dayIndex])) {
                    $heatmapData[$dayIndex] = [];
                }
                
                $heatmapData[$dayIndex][$hour] = $count;
                $maxCount = max($maxCount, $count);
            }

            return [
                'data' => $heatmapData,
                'maxCount' => $maxCount,
                'dayLabels' => ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'],
                'hourLabels' => range(0, 23)
            ];
        }, self::CACHE_DURATION, new TagDependency(['tags' => self::CACHE_TAG]));
    }

    /**
     * Ottiene statistiche assenze per motivo
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @return array
     */
    public function getByReason($searchModel)
    {
        $query = $searchModel->getStatisticsQuery();
        
        return $query->select([
            'COALESCE(sa.reason, "Non specificato") as reason',
            'COUNT(*) as count',
            'SUM(CASE WHEN sa.is_justified = 1 THEN 1 ELSE 0 END) as justified_count',
            'SUM(CASE WHEN sa.has_recovery = "SI" THEN 1 ELSE 0 END) as with_recovery_count'
        ])
        ->groupBy('sa.reason')
        ->orderBy(['count' => SORT_DESC])
        ->all();
    }

    /**
     * Ottiene statistiche per chi genera l'assenza
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @return array
     */
    public function getByGenerator($searchModel)
    {
        $query = $searchModel->getStatisticsQuery();
        
        $data = $query->select([
            'sa.generated_by',
            'COUNT(*) as count',
            'SUM(CASE WHEN sa.is_justified = 1 THEN 1 ELSE 0 END) as justified_count',
            'ROUND(AVG(CASE WHEN sa.is_justified = 1 THEN 100 ELSE 0 END), 1) as justified_percentage'
        ])
        ->groupBy('sa.generated_by')
        ->orderBy(['count' => SORT_DESC])
        ->all();

        // Traduce le etichette
        $labels = [
            'patient' => 'Paziente',
            'therapist' => 'Terapista', 
            'system' => 'Sistema'
        ];

        foreach ($data as &$row) {
            $row['generated_by_label'] = $labels[$row['generated_by']] ?? $row['generated_by'];
        }

        return $data;
    }

    /**
     * Ottiene statistiche per fascia oraria
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @return array
     */
    public function getByTimeSlot($searchModel)
    {
        $query = $searchModel->getStatisticsQuery();
        
        return $query->select([
            'CASE 
                WHEN sa.absence_hour BETWEEN 8 AND 11 THEN "Mattina (8-11)"
                WHEN sa.absence_hour BETWEEN 12 AND 14 THEN "Pranzo (12-14)"
                WHEN sa.absence_hour BETWEEN 15 AND 18 THEN "Pomeriggio (15-18)"
                WHEN sa.absence_hour BETWEEN 19 AND 21 THEN "Sera (19-21)"
                ELSE "Altri orari"
            END as time_slot',
            'COUNT(*) as count',
            'ROUND(AVG(CASE WHEN sa.is_justified = 1 THEN 100 ELSE 0 END), 1) as justified_percentage'
        ])
        ->groupBy('time_slot')
        ->orderBy(['count' => SORT_DESC])
        ->all();
    }

    /**
     * Ottiene statistiche per giorno della settimana
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @return array
     */
    public function getByDayOfWeek($searchModel)
    {
        $query = $searchModel->getStatisticsQuery();
        
        $data = $query->select([
            'sa.absence_day_number',
            'sa.absence_day_name',
            'COUNT(*) as count',
            'SUM(CASE WHEN sa.is_justified = 1 THEN 1 ELSE 0 END) as justified_count',
            'ROUND(AVG(CASE WHEN sa.is_justified = 1 THEN 100 ELSE 0 END), 1) as justified_percentage'
        ])
        ->groupBy(['sa.absence_day_number', 'sa.absence_day_name'])
        ->orderBy(['sa.absence_day_number' => SORT_ASC])
        ->all();

        // Traduce i nomi dei giorni
        $dayTranslations = [
            'Monday' => 'Lunedì',
            'Tuesday' => 'Martedì',
            'Wednesday' => 'Mercoledì',
            'Thursday' => 'Giovedì',
            'Friday' => 'Venerdì',
            'Saturday' => 'Sabato',
            'Sunday' => 'Domenica'
        ];

        foreach ($data as &$row) {
            $row['day_label'] = $dayTranslations[$row['absence_day_name']] ?? $row['absence_day_name'];
        }

        return $data;
    }

    /**
     * Ottiene dati trend assenze nel tempo
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @return array
     */
    public function getTrendData($searchModel)
    {
        $query = $searchModel->getStatisticsQuery();
        
        return $query->select([
            'DATE_FORMAT(sa.absence_date, "%Y-%m") as month',
            'COUNT(*) as total_absences',
            'SUM(CASE WHEN sa.is_justified = 1 THEN 1 ELSE 0 END) as justified_absences',
            'SUM(CASE WHEN sa.has_recovery = "SI" THEN 1 ELSE 0 END) as with_recovery',
            'ROUND((COUNT(*) - SUM(CASE WHEN sa.is_justified = 1 THEN 1 ELSE 0 END)) * 100.0 / COUNT(*), 1) as unjustified_rate'
        ])
        ->groupBy('month')
        ->orderBy('month')
        ->all();
    }

    /**
     * Ottiene il tasso di assenze mensile
     *
     * @param string|null $month Formato Y-m, default mese corrente
     * @return array
     */
    public function getMonthlyRate($month = null)
    {
        if (!$month) {
            $month = date('Y-m');
        }

        $cacheKey = "absence_monthly_rate_{$month}";
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($month) {
            // Conta appuntamenti totali del mese
            $totalAppointments = (new Query())
                ->from('appointments a')
                ->innerJoin('plan_therapies pt', 'a.plan_therapy_id = pt.id')
                ->innerJoin('therapeutic_plans tp', 'pt.therapeutic_plan_id = tp.id')
                ->where(['like', 'DATE(a.appointment_datetime)', $month])
                ->count();

            // Conta assenze del mese
            $totalAbsences = (new Query())
                ->from('statistics_absences_mv')
                ->where(['like', 'absence_date', $month])
                ->count();

            $justifiedAbsences = (new Query())
                ->from('statistics_absences_mv')
                ->where(['like', 'absence_date', $month])
                ->andWhere(['is_justified' => 1])
                ->count();

            $rate = $totalAppointments > 0 ? round($totalAbsences / $totalAppointments * 100, 2) : 0;
            $unjustifiedRate = $totalAppointments > 0 ? round(($totalAbsences - $justifiedAbsences) / $totalAppointments * 100, 2) : 0;

            return [
                'month' => $month,
                'total_appointments' => (int)$totalAppointments,
                'total_absences' => (int)$totalAbsences,
                'justified_absences' => (int)$justifiedAbsences,
                'unjustified_absences' => (int)($totalAbsences - $justifiedAbsences),
                'absence_rate' => $rate,
                'unjustified_rate' => $unjustifiedRate,
            ];
        }, self::CACHE_DURATION, new TagDependency(['tags' => self::CACHE_TAG]));
    }

    /**
     * Ottiene top pazienti per assenze
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @param int $limit
     * @return array
     */
    public function getTopAbsentPatients($searchModel, $limit = 10)
    {
        $query = $searchModel->getStatisticsQuery();
        
        return $query->select([
            'sa.patient_id',
            'sa.patient_name',
            'sa.patient_surname',
            'COUNT(*) as absence_count',
            'SUM(CASE WHEN sa.is_justified = 1 THEN 1 ELSE 0 END) as justified_count',
            'ROUND((COUNT(*) - SUM(CASE WHEN sa.is_justified = 1 THEN 1 ELSE 0 END)) * 100.0 / COUNT(*), 1) as unjustified_rate'
        ])
        ->groupBy(['sa.patient_id', 'sa.patient_name', 'sa.patient_surname'])
        ->orderBy(['absence_count' => SORT_DESC])
        ->limit($limit)
        ->all();
    }

    /**
     * Ottiene top terapisti per assenze generate
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @param int $limit
     * @return array
     */
    public function getTopTherapistsByAbsences($searchModel, $limit = 10)
    {
        $query = $searchModel->getStatisticsQuery();
        
        return $query->select([
            'sa.therapist_id',
            'sa.therapist_name',
            'sa.therapist_surname',
            'COUNT(*) as absence_count',
            'SUM(CASE WHEN sa.is_justified = 1 THEN 1 ELSE 0 END) as justified_count'
        ])
        ->where(['sa.generated_by' => 'therapist'])
        ->groupBy(['sa.therapist_id', 'sa.therapist_name', 'sa.therapist_surname'])
        ->orderBy(['absence_count' => SORT_DESC])
        ->limit($limit)
        ->all();
    }

    /**
     * Pulisce la cache delle statistiche assenze
     */
    public function clearCache()
    {
        TagDependency::invalidate(Yii::$app->cache, self::CACHE_TAG);
    }
} 
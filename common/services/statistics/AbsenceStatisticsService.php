<?php

namespace common\services\statistics;

use Yii;
use yii\db\Query;
use yii\caching\TagDependency;
use frontend\models\AbsenceStatisticsSearch;

/**
 * Service per le statistiche delle assenze dei terapisti
 * 
 * Nuova logica:
 * - Assenze da tabella 'absences' (periodi di non disponibilità)
 * - Conta solo quando ci sono appuntamenti effettivi persi (therapist_absent o sostituzioni)
 * - Raggruppa per group_session_id (stesso ID = 1 assenza, NULL = singole)
 */
class AbsenceStatisticsService
{
    const CACHE_DURATION = 900; // 15 minuti
    const CACHE_TAG = 'absence_statistics';

    /**
     * Ottiene dati per heatmap assenze (orari x giorni settimana)
     * Conta assenze raggruppate per group_session_id
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @return array
     */
    public function getHeatmapData($searchModel)
    {
        $cacheKey = 'absence_heatmap_' . md5(serialize($searchModel->attributes));
        
        return Yii::$app->cache->getOrSet($cacheKey, function() use ($searchModel) {
            
            // Prima otteniamo tutti i dati con la nuova logica
            $rawData = $searchModel->getStatisticsQuery()->all();
            
            // Raggruppiamo per absence_group_key per evitare duplicati di gruppo
            $groupedData = [];
            foreach ($rawData as $row) {
                $groupKey = $row['absence_group_key'];
                if (!isset($groupedData[$groupKey])) {
                    $groupedData[$groupKey] = $row;
                }
            }

            // Ora contiamo per ora e giorno
            $heatmapData = [];
            $maxCount = 0;
            $hourDayCounts = [];
            
            foreach ($groupedData as $row) {
                $dayIndex = $row['absence_day_number'] - 1; // 0-6 per JS (Lun=0)
                $hour = $row['absence_hour'];
                
                if (!isset($hourDayCounts[$dayIndex])) {
                    $hourDayCounts[$dayIndex] = [];
                }
                if (!isset($hourDayCounts[$dayIndex][$hour])) {
                    $hourDayCounts[$dayIndex][$hour] = 0;
                }
                
                $hourDayCounts[$dayIndex][$hour]++;
                $maxCount = max($maxCount, $hourDayCounts[$dayIndex][$hour]);
            }

            return [
                'data' => $hourDayCounts,
                'maxCount' => $maxCount,
                'dayLabels' => ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'],
                'hourLabels' => range(0, 23)
            ];
        }, self::CACHE_DURATION, new TagDependency(['tags' => self::CACHE_TAG]));
    }

    /**
     * Ottiene statistiche assenze per motivo
     * Raggruppa per absence_group_key per conteggio corretto
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @return array
     */
    public function getByReason($searchModel)
    {
        // Ottieni dati grezzi
        $rawData = $searchModel->getStatisticsQuery()->all();
        
        // Raggruppa per absence_group_key per evitare conteggi duplicati
        $groupedData = [];
        foreach ($rawData as $row) {
            $groupKey = $row['absence_group_key'];
            if (!isset($groupedData[$groupKey])) {
                $groupedData[$groupKey] = $row;
            }
        }
        
        // Conta per motivo
        $reasonCounts = [];
        foreach ($groupedData as $row) {
            $reason = $row['absence_reason'] ?: 'Non specificato';
            
            if (!isset($reasonCounts[$reason])) {
                $reasonCounts[$reason] = [
                    'reason' => $reason,
                    'count' => 0,
                    'justified_count' => 0,
                    'with_recovery_count' => 0
                ];
            }
            
            $reasonCounts[$reason]['count']++;
            if ($row['is_justified']) {
                $reasonCounts[$reason]['justified_count']++;
            }
            if ($row['has_recovery'] === 'SI') {
                $reasonCounts[$reason]['with_recovery_count']++;
            }
        }
        
        // Ordina per count discendente
        uasort($reasonCounts, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return array_values($reasonCounts);
    }

    /**
     * Ottiene statistiche per chi genera l'assenza
     * Per le assenze terapisti, il generatore è sempre 'therapist'
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @return array
     */
    public function getByGenerator($searchModel)
    {
        // Ottieni dati grezzi
        $rawData = $searchModel->getStatisticsQuery()->all();
        
        // Raggruppa per absence_group_key
        $groupedData = [];
        foreach ($rawData as $row) {
            $groupKey = $row['absence_group_key'];
            if (!isset($groupedData[$groupKey])) {
                $groupedData[$groupKey] = $row;
            }
        }
        
        // Conta per generatore
        $generatorCounts = [];
        foreach ($groupedData as $row) {
            $generator = $row['generated_by'];
            
            if (!isset($generatorCounts[$generator])) {
                $generatorCounts[$generator] = [
                    'generated_by' => $generator,
                    'count' => 0,
                    'justified_count' => 0,
                    'justified_percentage' => 0
                ];
            }
            
            $generatorCounts[$generator]['count']++;
            if ($row['is_justified']) {
                $generatorCounts[$generator]['justified_count']++;
            }
        }
        
        // Calcola percentuali
        foreach ($generatorCounts as &$item) {
            if ($item['count'] > 0) {
                $item['justified_percentage'] = round(($item['justified_count'] / $item['count']) * 100, 1);
            }
        }
        
        // Traduce le etichette
        $labels = [
            'patient' => 'Paziente',
            'therapist' => 'Terapista', 
            'system' => 'Sistema'
        ];

        foreach ($generatorCounts as &$item) {
            $item['generated_by_label'] = $labels[$item['generated_by']] ?? $item['generated_by'];
        }

        // Ordina per count discendente
        uasort($generatorCounts, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        return array_values($generatorCounts);
    }

    /**
     * Ottiene statistiche per fascia oraria
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @return array
     */
    public function getByTimeSlot($searchModel)
    {
        // Ottieni dati grezzi
        $rawData = $searchModel->getStatisticsQuery()->all();
        
        // Raggruppa per absence_group_key
        $groupedData = [];
        foreach ($rawData as $row) {
            $groupKey = $row['absence_group_key'];
            if (!isset($groupedData[$groupKey])) {
                $groupedData[$groupKey] = $row;
            }
        }
        
        // Conta per fascia oraria
        $timeSlotCounts = [];
        foreach ($groupedData as $row) {
            $hour = $row['absence_hour'];
            
            // Determina fascia oraria
            if ($hour >= 8 && $hour <= 11) {
                $timeSlot = 'Mattina (8-11)';
            } elseif ($hour >= 12 && $hour <= 14) {
                $timeSlot = 'Pranzo (12-14)';
            } elseif ($hour >= 15 && $hour <= 18) {
                $timeSlot = 'Pomeriggio (15-18)';
            } elseif ($hour >= 19 && $hour <= 21) {
                $timeSlot = 'Sera (19-21)';
            } else {
                $timeSlot = 'Altri orari';
            }
            
            if (!isset($timeSlotCounts[$timeSlot])) {
                $timeSlotCounts[$timeSlot] = [
                    'time_slot' => $timeSlot,
                    'count' => 0,
                    'justified_count' => 0,
                    'justified_percentage' => 0
                ];
            }
            
            $timeSlotCounts[$timeSlot]['count']++;
            if ($row['is_justified']) {
                $timeSlotCounts[$timeSlot]['justified_count']++;
            }
        }
        
        // Calcola percentuali
        foreach ($timeSlotCounts as &$item) {
            if ($item['count'] > 0) {
                $item['justified_percentage'] = round(($item['justified_count'] / $item['count']) * 100, 1);
            }
        }
        
        // Ordina per count discendente
        uasort($timeSlotCounts, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return array_values($timeSlotCounts);
    }

    /**
     * Ottiene statistiche per giorno della settimana
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @return array
     */
    public function getByDayOfWeek($searchModel)
    {
        // Ottieni dati grezzi
        $rawData = $searchModel->getStatisticsQuery()->all();
        
        // Raggruppa per absence_group_key
        $groupedData = [];
        foreach ($rawData as $row) {
            $groupKey = $row['absence_group_key'];
            if (!isset($groupedData[$groupKey])) {
                $groupedData[$groupKey] = $row;
            }
        }
        
        // Conta per giorno settimana
        $dayCounts = [];
        foreach ($groupedData as $row) {
            $dayNumber = $row['absence_day_number'];
            $dayName = $row['absence_day_name'];
            
            if (!isset($dayCounts[$dayNumber])) {
                $dayCounts[$dayNumber] = [
                    'absence_day_number' => $dayNumber,
                    'absence_day_name' => $dayName,
                    'count' => 0,
                    'justified_count' => 0,
                    'justified_percentage' => 0
                ];
            }
            
            $dayCounts[$dayNumber]['count']++;
            if ($row['is_justified']) {
                $dayCounts[$dayNumber]['justified_count']++;
            }
        }
        
        // Calcola percentuali
        foreach ($dayCounts as &$item) {
            if ($item['count'] > 0) {
                $item['justified_percentage'] = round(($item['justified_count'] / $item['count']) * 100, 1);
            }
        }

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

        foreach ($dayCounts as &$item) {
            $item['day_label'] = $dayTranslations[$item['absence_day_name']] ?? $item['absence_day_name'];
        }

        // Ordina per numero giorno (Lunedì = 1)
        ksort($dayCounts);
        
        return array_values($dayCounts);
    }

    /**
     * Ottiene dati trend assenze nel tempo
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @return array
     */
    public function getTrendData($searchModel)
    {
        // Ottieni dati grezzi
        $rawData = $searchModel->getStatisticsQuery()->all();
        
        // Raggruppa per absence_group_key
        $groupedData = [];
        foreach ($rawData as $row) {
            $groupKey = $row['absence_group_key'];
            if (!isset($groupedData[$groupKey])) {
                $groupedData[$groupKey] = $row;
            }
        }
        
        // Conta per mese
        $monthCounts = [];
        foreach ($groupedData as $row) {
            $month = date('Y-m', strtotime($row['absence_date']));
            
            if (!isset($monthCounts[$month])) {
                $monthCounts[$month] = [
                    'month' => $month,
                    'total_absences' => 0,
                    'justified_absences' => 0,
                    'with_recovery' => 0,
                    'unjustified_rate' => 0
                ];
            }
            
            $monthCounts[$month]['total_absences']++;
            if ($row['is_justified']) {
                $monthCounts[$month]['justified_absences']++;
            }
            if ($row['has_recovery'] === 'SI') {
                $monthCounts[$month]['with_recovery']++;
            }
        }
        
        // Calcola percentuali unjustified
        foreach ($monthCounts as &$item) {
            if ($item['total_absences'] > 0) {
                $unjustified = $item['total_absences'] - $item['justified_absences'];
                $item['unjustified_rate'] = round(($unjustified / $item['total_absences']) * 100, 1);
            }
        }
        
        // Ordina per mese
        ksort($monthCounts);
        
        return array_values($monthCounts);
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
        
        $searchModel = new AbsenceStatisticsSearch();
        $searchModel->dateFrom = $month . '-01';
        $searchModel->dateTo = date('Y-m-t', strtotime($month . '-01'));
        
        // Ottieni dati grezzi
        $rawData = $searchModel->getStatisticsQuery()->all();
        
        // Raggruppa per absence_group_key
        $groupedData = [];
        foreach ($rawData as $row) {
            $groupKey = $row['absence_group_key'];
            if (!isset($groupedData[$groupKey])) {
                $groupedData[$groupKey] = $row;
            }
        }
        
        $totalAbsences = count($groupedData);
        $justifiedAbsences = 0;
        
        foreach ($groupedData as $row) {
            if ($row['is_justified']) {
                $justifiedAbsences++;
            }
        }
        
        $unjustifiedAbsences = $totalAbsences - $justifiedAbsences;
        $unjustifiedRate = $totalAbsences > 0 ? round(($unjustifiedAbsences / $totalAbsences) * 100, 1) : 0;
        
        return [
            'month' => $month,
            'total_absences' => $totalAbsences,
            'justified_absences' => $justifiedAbsences,
            'unjustified_absences' => $unjustifiedAbsences,
            'unjustified_rate' => $unjustifiedRate,
            'with_recovery' => 0 // Per ora non gestiamo recuperi terapisti
        ];
    }

    /**
     * Ottiene top pazienti coinvolti in assenze terapisti
     *
     * @param AbsenceStatisticsSearch $searchModel
     * @param int $limit
     * @return array
     */
    public function getTopAbsentPatients($searchModel, $limit = 10)
    {
        // Ottieni dati grezzi
        $rawData = $searchModel->getStatisticsQuery()->all();
        
        // Raggruppa per absence_group_key
        $groupedData = [];
        foreach ($rawData as $row) {
            $groupKey = $row['absence_group_key'];
            if (!isset($groupedData[$groupKey])) {
                $groupedData[$groupKey] = $row;
            }
        }
        
        // Conta per paziente
        $patientCounts = [];
        foreach ($groupedData as $row) {
            $patientId = $row['patient_id'];
            
            if (!$patientId) continue; // Skip se non c'è paziente
            
            if (!isset($patientCounts[$patientId])) {
                $patientCounts[$patientId] = [
                    'patient_id' => $patientId,
                    'patient_name' => $row['patient_name'],
                    'patient_surname' => $row['patient_surname'],
                    'absence_count' => 0,
                    'justified_count' => 0,
                    'unjustified_rate' => 0
                ];
            }
            
            $patientCounts[$patientId]['absence_count']++;
            if ($row['is_justified']) {
                $patientCounts[$patientId]['justified_count']++;
            }
        }
        
        // Calcola percentuali
        foreach ($patientCounts as &$item) {
            if ($item['absence_count'] > 0) {
                $unjustified = $item['absence_count'] - $item['justified_count'];
                $item['unjustified_rate'] = round(($unjustified / $item['absence_count']) * 100, 1);
            }
        }
        
        // Ordina per assenze discendente
        uasort($patientCounts, function($a, $b) {
            return $b['absence_count'] - $a['absence_count'];
        });
        
        return array_slice(array_values($patientCounts), 0, $limit);
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
        // Ottieni dati grezzi
        $rawData = $searchModel->getStatisticsQuery()->all();
        
        // Raggruppa per absence_group_key
        $groupedData = [];
        foreach ($rawData as $row) {
            $groupKey = $row['absence_group_key'];
            if (!isset($groupedData[$groupKey])) {
                $groupedData[$groupKey] = $row;
            }
        }
        
        // Conta per terapista
        $therapistCounts = [];
        foreach ($groupedData as $row) {
            $therapistId = $row['therapist_id'];
            
            if (!isset($therapistCounts[$therapistId])) {
                $therapistCounts[$therapistId] = [
                    'therapist_id' => $therapistId,
                    'therapist_name' => $row['therapist_name'],
                    'therapist_surname' => $row['therapist_surname'],
                    'absence_count' => 0,
                    'justified_count' => 0
                ];
            }
            
            $therapistCounts[$therapistId]['absence_count']++;
            if ($row['is_justified']) {
                $therapistCounts[$therapistId]['justified_count']++;
            }
        }
        
        // Ordina per assenze discendente
        uasort($therapistCounts, function($a, $b) {
            return $b['absence_count'] - $a['absence_count'];
        });
        
        return array_slice(array_values($therapistCounts), 0, $limit);
    }

    /**
     * Pulisce la cache delle statistiche assenze
     */
    public function clearCache()
    {
        TagDependency::invalidate(Yii::$app->cache, self::CACHE_TAG);
    }
} 
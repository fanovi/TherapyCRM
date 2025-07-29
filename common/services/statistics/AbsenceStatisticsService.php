<?php

namespace common\services\statistics;

use Yii;
use yii\db\Query;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * Service per statistiche assenze
 * Gestisce sia assenze pazienti che terapisti con logica group_session_id
 */
class AbsenceStatisticsService
{
    /**
     * Query base per tutte le assenze (terapisti + pazienti)
     */
    public function getBaseAbsencesQuery($filters = [])
    {
        // Assenze dirette dei terapisti
        $therapistDirectQuery = (new Query())
            ->select([
                'absence_group_key' => new Expression("COALESCE(a.group_session_id, CONCAT('single_', a.id))"),
                'absence_type' => new Expression("'therapist_direct'"),
                'absence_type_flag' => new Expression("'direct'"),
                'appointment_id' => 'a.id',
                'appointment_datetime' => 'a.appointment_datetime',
                'absence_date' => new Expression('DATE(a.appointment_datetime)'),
                'absence_hour' => new Expression('HOUR(a.appointment_datetime)'),
                'absence_day_name' => new Expression('DAYNAME(a.appointment_datetime)'),
                'absence_day_number' => new Expression('DAYOFWEEK(a.appointment_datetime)'),
                'patient_id' => 'tp.patient_id',
                'patient_name' => 'p.first_name',
                'patient_surname' => 'p.last_name',
                'therapist_id' => 't.id',
                'therapist_name' => 'up_th.first_name',
                'therapist_surname' => 'up_th.last_name',
                'treatment_type_id' => 'pt.treatment_type_id',
                'treatment_name' => 'tt.name',
                'treatment_code' => 'tt.code',
                'absence_reason' => 'ab.reason',
                'is_justified' => new Expression('1'), // Assenze terapisti considerate giustificate
                'has_recovery' => new Expression("CASE WHEN ar.id IS NOT NULL THEN 'SI' ELSE 'NO' END"),
                'generated_by' => new Expression("'therapist'"),
                'duration_minutes' => 'a.duration_minutes',
                'group_session_id' => 'a.group_session_id'
            ])
            ->from(['a' => 'appointments'])
            ->innerJoin(
                ['ab' => 'absences'],
                'a.therapist_id = ab.therapist_id 
                AND DATE(a.appointment_datetime) BETWEEN ab.start_date AND ab.end_date
                AND a.status = "scheduled"
                AND ab.status = "approved"'
            )
            ->leftJoin(['pt' => 'plan_therapies'], 'a.plan_therapy_id = pt.id')
            ->leftJoin(['tp' => 'therapeutic_plans'], 'pt.therapeutic_plan_id = tp.id')
            ->leftJoin(['p' => 'patients'], 'tp.patient_id = p.id')
            ->leftJoin(['t' => 'therapists'], 'a.therapist_id = t.id')
            ->leftJoin(['u_th' => 'users'], 't.user_id = u_th.id')
            ->leftJoin(['up_th' => 'user_profiles'], 'u_th.id = up_th.user_id')
            ->leftJoin(['tt' => 'treatment_types'], 'pt.treatment_type_id = tt.id')
            ->leftJoin(['ar' => 'absence_recoveries'], 'ar.original_appointment_id = a.id');

        // Assenze per sostituzione
        $therapistSubstitutionQuery = (new Query())
            ->select([
                'absence_group_key' => new Expression("COALESCE(a.group_session_id, CONCAT('single_', a.id))"),
                'absence_type' => new Expression("'therapist_substitution'"),
                'absence_type_flag' => new Expression("'substitution'"),
                'appointment_id' => 'a.id',
                'appointment_datetime' => 'a.appointment_datetime',
                'absence_date' => new Expression('DATE(a.appointment_datetime)'),
                'absence_hour' => new Expression('HOUR(a.appointment_datetime)'),
                'absence_day_name' => new Expression('DAYNAME(a.appointment_datetime)'),
                'absence_day_number' => new Expression('DAYOFWEEK(a.appointment_datetime)'),
                'patient_id' => 'tp.patient_id',
                'patient_name' => 'p.first_name',
                'patient_surname' => 'p.last_name',
                'therapist_id' => 't_orig.id',
                'therapist_name' => 'up_th_orig.first_name',
                'therapist_surname' => 'up_th_orig.last_name',
                'treatment_type_id' => 'pt.treatment_type_id',
                'treatment_name' => 'tt.name',
                'treatment_code' => 'tt.code',
                'absence_reason' => 'ab.reason',
                'is_justified' => new Expression('1'),
                'has_recovery' => new Expression("CASE WHEN ar.id IS NOT NULL THEN 'SI' ELSE 'NO' END"),
                'generated_by' => new Expression("'therapist'"),
                'duration_minutes' => 'a.duration_minutes',
                'group_session_id' => 'a.group_session_id'
            ])
            ->from(['a' => 'appointments'])
            ->innerJoin(['ts' => 'therapist_substitutions'], 'ts.appointment_id = a.id')
            ->innerJoin(
                ['ab' => 'absences'],
                'ab.therapist_id = ts.original_therapist_id
                AND DATE(a.appointment_datetime) BETWEEN ab.start_date AND ab.end_date
                AND ab.status = "approved"'
            )
            ->leftJoin(['pt' => 'plan_therapies'], 'a.plan_therapy_id = pt.id')
            ->leftJoin(['tp' => 'therapeutic_plans'], 'pt.therapeutic_plan_id = tp.id')
            ->leftJoin(['p' => 'patients'], 'tp.patient_id = p.id')
            ->leftJoin(['t_orig' => 'therapists'], 'ts.original_therapist_id = t_orig.id')
            ->leftJoin(['u_th_orig' => 'users'], 't_orig.user_id = u_th_orig.id')
            ->leftJoin(['up_th_orig' => 'user_profiles'], 'u_th_orig.id = up_th_orig.user_id')
            ->leftJoin(['tt' => 'treatment_types'], 'pt.treatment_type_id = tt.id')
            ->leftJoin(['ar' => 'absence_recoveries'], 'ar.original_appointment_id = a.id');

        // Assenze pazienti - gestisce anche appuntamenti privati
        $patientQuery = (new Query())
            ->select([
                'absence_group_key' => new Expression("COALESCE(a.group_session_id, CONCAT('single_', a.id))"),
                'absence_type' => new Expression("'patient'"),
                'absence_type_flag' => new Expression("'patient'"),
                'appointment_id' => 'a.id',
                'appointment_datetime' => 'a.appointment_datetime',
                'absence_date' => new Expression('DATE(a.appointment_datetime)'),
                'absence_hour' => new Expression('HOUR(a.appointment_datetime)'),
                'absence_day_name' => new Expression('DAYNAME(a.appointment_datetime)'),
                'absence_day_number' => new Expression('DAYOFWEEK(a.appointment_datetime)'),
                'patient_id' => new Expression('COALESCE(tp.patient_id, a.patient_id)'),
                'patient_name' => 'p.first_name',
                'patient_surname' => 'p.last_name',
                'therapist_id' => 't.id',
                'therapist_name' => 'up_th.first_name',
                'therapist_surname' => 'up_th.last_name',
                'treatment_type_id' => new Expression('COALESCE(pt.treatment_type_id, a.treatment_type_id)'),
                'treatment_name' => 'tt.name',
                'treatment_code' => 'tt.code',
                'absence_reason' => new Expression("CASE 
                    WHEN a.status = 'absent_justified' THEN 'Giustificata'
                    ELSE 'Non giustificata'
                END"),
                'is_justified' => new Expression("CASE WHEN a.status = 'absent_justified' THEN 1 ELSE 0 END"),
                'has_recovery' => new Expression("CASE WHEN ar.id IS NOT NULL THEN 'SI' ELSE 'NO' END"),
                'generated_by' => new Expression("'patient'"),
                'duration_minutes' => 'a.duration_minutes',
                'group_session_id' => 'a.group_session_id'
            ])
            ->from(['a' => 'appointments'])
            ->leftJoin(['pt' => 'plan_therapies'], 'a.plan_therapy_id = pt.id')
            ->leftJoin(['tp' => 'therapeutic_plans'], 'pt.therapeutic_plan_id = tp.id')
            ->leftJoin(['p' => 'patients'], new Expression('COALESCE(tp.patient_id, a.patient_id) = p.id'))
            ->leftJoin(['t' => 'therapists'], 'a.therapist_id = t.id')
            ->leftJoin(['u_th' => 'users'], 't.user_id = u_th.id')
            ->leftJoin(['up_th' => 'user_profiles'], 'u_th.id = up_th.user_id')
            ->leftJoin(['tt' => 'treatment_types'], new Expression('COALESCE(pt.treatment_type_id, a.treatment_type_id) = tt.id'))
            ->leftJoin(['ar' => 'absence_recoveries'], 'ar.original_appointment_id = a.id')
            ->where(['a.status' => ['absent_justified', 'absent_not_justified']]);

        // Unione delle tre query
        $unionQuery = $therapistDirectQuery
            ->union($therapistSubstitutionQuery)
            ->union($patientQuery);

        // Query finale con filtri
        $query = (new Query())
            ->from(['absences' => $unionQuery]);

        // Applica filtri
        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * Applica filtri alla query
     */
    protected function applyFilters($query, $filters)
    {
        // Filtro date
        if (!empty($filters['dateFrom'])) {
            $query->andWhere(['>=', 'absence_date', $filters['dateFrom']]);
        }
        if (!empty($filters['dateTo'])) {
            $query->andWhere(['<=', 'absence_date', $filters['dateTo']]);
        }

        // Filtro terapista
        if (!empty($filters['therapistId'])) {
            $query->andWhere(['therapist_id' => $filters['therapistId']]);
        }

        // Filtro paziente
        if (!empty($filters['patientId'])) {
            $query->andWhere(['patient_id' => $filters['patientId']]);
        }

        // Filtro tipo trattamento
        if (!empty($filters['treatmentTypeId'])) {
            $query->andWhere(['treatment_type_id' => $filters['treatmentTypeId']]);
        }

        // Filtro tipo assenza (therapist/patient)
        if (!empty($filters['absenceSource'])) {
            if ($filters['absenceSource'] === 'therapist') {
                $query->andWhere(['generated_by' => 'therapist']);
            } elseif ($filters['absenceSource'] === 'patient') {
                $query->andWhere(['generated_by' => 'patient']);
            }
        }

        // Filtro giustificata/non giustificata
        if (isset($filters['isJustified']) && $filters['isJustified'] !== '') {
            $query->andWhere(['is_justified' => $filters['isJustified']]);
        }
    }

    /**
     * Ottiene dati per heatmap oraria
     */
    public function getHeatmapData($searchModel)
    {
        $filters = $this->extractFilters($searchModel);
        $query = $this->getBaseAbsencesQuery($filters);

        $data = $query
            ->select([
                'hour' => 'absence_hour',
                'day' => 'absence_day_number',
                'count' => new Expression('COUNT(DISTINCT absence_group_key)')
            ])
            ->groupBy(['absence_hour', 'absence_day_number'])
            ->all();

        // Formatta per la heatmap
        $heatmapData = [];
        foreach ($data as $item) {
            $heatmapData[] = [
                'x' => $this->getDayLabel($item['day']),
                'y' => sprintf('%02d:00', $item['hour']),
                'value' => (int)$item['count']
            ];
        }

        return $heatmapData;
    }

    /**
     * Statistiche per tipo di trattamento
     */
    public function getByTreatmentType($filters = [])
    {
        $query = $this->getBaseAbsencesQuery($filters);

        return $query
            ->select([
                'treatment_type_id',
                'treatment_name' => new Expression('MIN(treatment_name)'),
                'treatment_code' => new Expression('MIN(treatment_code)'),
                'total_absences' => new Expression('COUNT(DISTINCT absence_group_key)'),
                'therapist_absences' => new Expression("COUNT(DISTINCT CASE WHEN generated_by = 'therapist' THEN absence_group_key END)"),
                'patient_absences' => new Expression("COUNT(DISTINCT CASE WHEN generated_by = 'patient' THEN absence_group_key END)"),
                'justified_rate' => new Expression('ROUND(AVG(is_justified) * 100, 1)')
            ])
            ->groupBy(['treatment_type_id'])
            ->having(['>', 'COUNT(DISTINCT absence_group_key)', 0])
            ->orderBy(['total_absences' => SORT_DESC])
            ->limit(10)
            ->all();
    }

    /**
     * Statistiche per giorno della settimana
     */
    public function getByDayOfWeek($searchModel)
    {
        $filters = $this->extractFilters($searchModel);
        $query = $this->getBaseAbsencesQuery($filters);

        $data = $query
            ->select([
                'day_number' => 'absence_day_number',
                'day_name' => new Expression('MIN(absence_day_name)'), // Usa MIN per evitare problemi GROUP BY
                'count' => new Expression('COUNT(DISTINCT absence_group_key)'),
                'therapist_count' => new Expression("COUNT(DISTINCT CASE WHEN generated_by = 'therapist' THEN absence_group_key END)"),
                'patient_count' => new Expression("COUNT(DISTINCT CASE WHEN generated_by = 'patient' THEN absence_group_key END)")
            ])
            ->groupBy(['absence_day_number'])
            ->orderBy(['absence_day_number' => SORT_ASC])
            ->all();

        // Aggiungi label italiano
        foreach ($data as &$item) {
            $item['day_label'] = $this->getDayLabel($item['day_number']);
        }

        return $data;
    }

    /**
     * Statistiche per motivo/ragione
     */
    public function getByReason($searchModel)
    {
        $filters = $this->extractFilters($searchModel);
        $query = $this->getBaseAbsencesQuery($filters);

        return $query
            ->select([
                'reason' => 'absence_reason',
                'source' => 'generated_by',
                'count' => new Expression('COUNT(DISTINCT absence_group_key)')
            ])
            ->groupBy(['absence_reason', 'generated_by'])
            ->having(['>', 'COUNT(DISTINCT absence_group_key)', 0])
            ->orderBy(['count' => SORT_DESC])
            ->limit(20)
            ->all();
    }

    /**
     * Statistiche per chi genera l'assenza
     */
    public function getByGenerator($searchModel)
    {
        $filters = $this->extractFilters($searchModel);
        $query = $this->getBaseAbsencesQuery($filters);

        $data = $query
            ->select([
                'generator' => 'generated_by',
                'absence_type' => 'absence_type_flag',
                'count' => new Expression('COUNT(DISTINCT absence_group_key)')
            ])
            ->groupBy(['generated_by', 'absence_type_flag'])
            ->all();

        // Calcola percentuali
        $total = array_sum(array_column($data, 'count'));
        foreach ($data as &$item) {
            $item['percentage'] = $total > 0 ? round(($item['count'] / $total) * 100, 1) : 0;
        }

        return $data;
    }

    /**
     * Trend mensile
     */
    public function getTrendData($searchModel)
    {
        $filters = $this->extractFilters($searchModel);

        // Default ultimi 12 mesi
        if (empty($filters['dateFrom'])) {
            $filters['dateFrom'] = date('Y-m-01', strtotime('-11 months'));
        }

        // Costruisci la query base
        $baseQuery = $this->getBaseAbsencesQuery($filters);
        $baseSql = $baseQuery->createCommand()->getRawSql();

        // Query finale con GROUP BY corretto
        $sql = "
        SELECT 
            DATE_FORMAT(absence_date, '%Y-%m') as month,
            DATE_FORMAT(MIN(absence_date), '%b %Y') as month_label,
            COUNT(DISTINCT absence_group_key) as total_absences,
            COUNT(DISTINCT CASE WHEN is_justified = 1 THEN absence_group_key END) as justified_absences,
            COUNT(DISTINCT CASE WHEN generated_by = 'therapist' THEN absence_group_key END) as therapist_absences,
            COUNT(DISTINCT CASE WHEN generated_by = 'patient' THEN absence_group_key END) as patient_absences
        FROM ($baseSql) as absences_data
        GROUP BY DATE_FORMAT(absence_date, '%Y-%m')
        ORDER BY month ASC
    ";

        return Yii::$app->db->createCommand($sql)->queryAll();
    }

    /**
     * Tasso mensile di assenze
     */
    public function getMonthlyRate()
    {
        // Calcola per mese corrente
        $currentMonth = date('Y-m');

        $query = $this->getBaseAbsencesQuery([
            'dateFrom' => date('Y-m-01'),
            'dateTo' => date('Y-m-t')
        ]);

        $absences = $query
            ->select([
                'total' => new Expression('COUNT(DISTINCT absence_group_key)'),
                'justified' => new Expression('COUNT(DISTINCT CASE WHEN is_justified = 1 THEN absence_group_key END)')
            ])
            ->one();

        // Conta appuntamenti totali del mese (inclusi quelli con group_session_id)
        $appointmentsQuery = (new Query())
            ->select([
                'total_appointments' => new Expression('COUNT(DISTINCT COALESCE(group_session_id, CONCAT("single_", id)))')
            ])
            ->from('appointments')
            ->where(['between', 'appointment_datetime', date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59')])
            ->andWhere(['status' => ['scheduled', 'completed', 'absent_justified', 'absent_not_justified']]);

        $appointmentData = $appointmentsQuery->one();
        $totalAppointments = $appointmentData['total_appointments'] ?? 0;

        $rate = $totalAppointments > 0 ? round(($absences['total'] / $totalAppointments) * 100, 1) : 0;

        return [
            'total_absences' => $absences['total'] ?? 0,
            'justified_absences' => $absences['justified'] ?? 0,
            'total_appointments' => $totalAppointments,
            'absence_rate' => $rate,
            'month' => $currentMonth
        ];
    }

    /**
     * Top assenti (terapisti e pazienti)
     */
    public function getTopAbsentees($limit = 10)
    {
        $filters = [
            'dateFrom' => date('Y-m-01', strtotime('-3 months'))
        ];

        // Top terapisti assenti
        $therapistQuery = $this->getBaseAbsencesQuery($filters);
        $topTherapists = $therapistQuery
            ->select([
                'id' => 'therapist_id',
                'name' => new Expression("CONCAT(COALESCE(therapist_name, ''), ' ', COALESCE(therapist_surname, ''))"),
                'count' => new Expression('COUNT(DISTINCT absence_group_key)'),
                'type' => new Expression("'therapist'")
            ])
            ->where(['generated_by' => 'therapist'])
            ->andWhere(['IS NOT', 'therapist_id', null])
            ->groupBy(['therapist_id', 'therapist_name', 'therapist_surname'])
            ->having(['>', 'COUNT(DISTINCT absence_group_key)', 0])
            ->orderBy(['count' => SORT_DESC])
            ->limit($limit)
            ->all();

        // Top pazienti assenti
        $patientQuery = $this->getBaseAbsencesQuery($filters);
        $topPatients = $patientQuery
            ->select([
                'id' => 'patient_id',
                'name' => new Expression("CONCAT(COALESCE(patient_name, ''), ' ', COALESCE(patient_surname, ''))"),
                'count' => new Expression('COUNT(DISTINCT absence_group_key)'),
                'type' => new Expression("'patient'")
            ])
            ->where(['generated_by' => 'patient'])
            ->andWhere(['IS NOT', 'patient_id', null])
            ->groupBy(['patient_id', 'patient_name', 'patient_surname'])
            ->having(['>', 'COUNT(DISTINCT absence_group_key)', 0])
            ->orderBy(['count' => SORT_DESC])
            ->limit($limit)
            ->all();

        return [
            'therapists' => $topTherapists,
            'patients' => $topPatients
        ];
    }

    /**
     * Statistiche orarie dettagliate
     */
    public function getHourlyStatistics($filters = [])
    {
        $query = $this->getBaseAbsencesQuery($filters);

        return $query
            ->select([
                'hour' => 'absence_hour',
                'total_count' => new Expression('COUNT(DISTINCT absence_group_key)'),
                'therapist_count' => new Expression("COUNT(DISTINCT CASE WHEN generated_by = 'therapist' THEN absence_group_key END)"),
                'patient_count' => new Expression("COUNT(DISTINCT CASE WHEN generated_by = 'patient' THEN absence_group_key END)"),
                'justified_count' => new Expression('COUNT(DISTINCT CASE WHEN is_justified = 1 THEN absence_group_key END)')
            ])
            ->groupBy(['absence_hour'])
            ->orderBy(['absence_hour' => SORT_ASC])
            ->all();
    }

    /**
     * Helper per estrarre filtri dal search model
     */
    protected function extractFilters($searchModel)
    {
        $filters = [];

        if ($searchModel->dateFrom) {
            $filters['dateFrom'] = $searchModel->dateFrom;
        }
        if ($searchModel->dateTo) {
            $filters['dateTo'] = $searchModel->dateTo;
        }
        if ($searchModel->therapistId) {
            $filters['therapistId'] = $searchModel->therapistId;
        }
        if ($searchModel->patientId) {
            $filters['patientId'] = $searchModel->patientId;
        }
        if ($searchModel->treatmentTypeId) {
            $filters['treatmentTypeId'] = $searchModel->treatmentTypeId;
        }
        if ($searchModel->absenceSource) {
            $filters['absenceSource'] = $searchModel->absenceSource;
        }
        if (isset($searchModel->isJustified)) {
            $filters['isJustified'] = $searchModel->isJustified;
        }

        return $filters;
    }

    /**
     * Helper per etichette giorni
     */
    protected function getDayLabel($dayNumber)
    {
        $days = [
            1 => 'Domenica',
            2 => 'Lunedì',
            3 => 'Martedì',
            4 => 'Mercoledì',
            5 => 'Giovedì',
            6 => 'Venerdì',
            7 => 'Sabato'
        ];

        return $days[$dayNumber] ?? '';
    }
}

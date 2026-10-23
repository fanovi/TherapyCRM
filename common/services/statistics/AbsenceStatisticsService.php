<?php

namespace common\services\statistics;

use Yii;
use common\models\Therapist;
use common\models\UserProfile;
use yii\db\Query;
use yii\db\Expression;

/**
 * Service per statistiche assenze
 * Gestisce sia assenze pazienti che terapisti con logica group_session_id
 */
class AbsenceStatisticsService
{
    /**
     * Nomi con cui i cambi di stato di un appuntamento finiscono in activity_log:
     * 'Appuntamento' dall'ActivityLogBehavior del modello, 'Appointment' dalla
     * riga esplicita scritta dal gestionale (AbsenceController).
     */
    const APPOINTMENT_LOG_ENTITY_NAMES = ['Appuntamento', 'Appointment'];

    /**
     * Piano dell'appuntamento in corso oggi (attivo e data odierna nel periodo,
     * come Pazienti in carico): 1 in corso, 0 concluso o non attivo, NULL senza
     * piano (cicli privati). Richiede l'alias tp.
     */
    const PLAN_IN_PROGRESS_SQL = "CASE WHEN tp.id IS NULL THEN NULL"
        . " WHEN tp.status = 'active' AND CURDATE() BETWEEN tp.start_date AND tp.end_date THEN 1 ELSE 0 END";

    /**
     * Query base per tutte le assenze (terapisti + pazienti)
     */
    public function getBaseAbsencesQuery($filters = [])
    {
        // Assenze dirette dei terapisti
        $therapistDirectQuery = (new Query())
            ->select([
                'absence_group_key' => new Expression("CONCAT('therapist_', a.therapist_id, '_', COALESCE(a.group_session_id, CONCAT('single_', a.id)))"),
                'absence_slot_key' => new Expression("COALESCE(a.group_session_id, CONCAT('single_', a.id))"),
                'absence_type' => new Expression("'therapist_direct'"),
                'absence_type_flag' => new Expression("'direct'"),
                'appointment_id' => 'a.id',
                'appointment_datetime' => 'a.appointment_datetime',
                'absence_date' => new Expression('DATE(a.appointment_datetime)'),
                'absence_hour' => new Expression('HOUR(a.appointment_datetime)'),
                'absence_day_name' => new Expression('DAYNAME(a.appointment_datetime)'),
                'absence_day_number' => new Expression('DAYOFWEEK(a.appointment_datetime)'),
                'patient_id' => new Expression('COALESCE(tp.patient_id, a.patient_id)'),
                'patient_name' => new Expression("COALESCE(p.first_name, 'N/A')"),
                'patient_surname' => new Expression("COALESCE(p.last_name, '')"),
                'therapist_id' => 't.id',
                'therapist_name' => new Expression("COALESCE(up_th.first_name, 'N/A')"),
                'therapist_surname' => new Expression("COALESCE(up_th.last_name, '')"),
                'treatment_type_id' => new Expression('COALESCE(pt.treatment_type_id, a.treatment_type_id)'),
                'treatment_name' => 'tt.name',
                'treatment_code' => 'tt.code',
                'setting_id' => 'a.id_setting',
                'absence_reason' => 'ab.reason',
                'is_justified' => new Expression('1'), // Assenze terapisti considerate giustificate
                'has_recovery' => new Expression("CASE WHEN EXISTS (
                    SELECT 1 FROM absence_recoveries ar
                    WHERE ar.original_appointment_id = a.id
                ) THEN 'SI' ELSE 'NO' END"),
                'generated_by' => new Expression("'therapist'"),
                'duration_minutes' => 'a.duration_minutes',
                'group_session_id' => 'a.group_session_id',
                'absence_id' => 'ab.id',
                'plan_in_progress' => new Expression(self::PLAN_IN_PROGRESS_SQL)
            ])
            ->from(['a' => 'appointments'])
            ->leftJoin(['ab' => 'absences'], $this->buildAbsenceJoinCondition('a.therapist_id'))
            ->where(['a.status' => ['scheduled', 'therapist_absent']])
            ->andWhere([
                'OR',
                ['a.status' => 'therapist_absent'],
                ['IS NOT', 'ab.id', null],
            ])
            ->leftJoin(['pt' => 'plan_therapies'], 'a.plan_therapy_id = pt.id')
            ->leftJoin(['tp' => 'therapeutic_plans'], 'pt.therapeutic_plan_id = tp.id')
            ->leftJoin(['p' => 'patients'], new Expression('COALESCE(tp.patient_id, a.patient_id) = p.id'))
            ->leftJoin(['t' => 'therapists'], 'a.therapist_id = t.id')
            ->leftJoin(['u_th' => 'users'], 't.user_id = u_th.id')
            ->leftJoin(['up_th' => 'user_profiles'], 'u_th.id = up_th.user_id')
            ->leftJoin(['tt' => 'treatment_types'], new Expression('COALESCE(pt.treatment_type_id, a.treatment_type_id) = tt.id'));

        // Assenze per sostituzione
        $therapistSubstitutionQuery = (new Query())
            ->select([
                'absence_group_key' => new Expression("CONCAT('therapist_', ts.original_therapist_id, '_', COALESCE(a.group_session_id, CONCAT('single_', a.id)))"),
                'absence_slot_key' => new Expression("COALESCE(a.group_session_id, CONCAT('single_', a.id))"),
                'absence_type' => new Expression("'therapist_substitution'"),
                'absence_type_flag' => new Expression("'substitution'"),
                'appointment_id' => 'a.id',
                'appointment_datetime' => 'a.appointment_datetime',
                'absence_date' => new Expression('DATE(a.appointment_datetime)'),
                'absence_hour' => new Expression('HOUR(a.appointment_datetime)'),
                'absence_day_name' => new Expression('DAYNAME(a.appointment_datetime)'),
                'absence_day_number' => new Expression('DAYOFWEEK(a.appointment_datetime)'),
                'patient_id' => new Expression('COALESCE(tp.patient_id, a.patient_id)'),
                'patient_name' => new Expression("COALESCE(p.first_name, 'N/A')"),
                'patient_surname' => new Expression("COALESCE(p.last_name, '')"),
                'therapist_id' => 't_orig.id',
                'therapist_name' => new Expression("COALESCE(up_th_orig.first_name, 'N/A')"),
                'therapist_surname' => new Expression("COALESCE(up_th_orig.last_name, '')"),
                'treatment_type_id' => new Expression('COALESCE(pt.treatment_type_id, a.treatment_type_id)'),
                'treatment_name' => 'tt.name',
                'treatment_code' => 'tt.code',
                'setting_id' => 'a.id_setting',
                'absence_reason' => 'ab.reason',
                'is_justified' => new Expression('1'),
                'has_recovery' => new Expression("CASE WHEN EXISTS (
                    SELECT 1 FROM absence_recoveries ar
                    WHERE ar.original_appointment_id = a.id
                ) THEN 'SI' ELSE 'NO' END"),
                'generated_by' => new Expression("'therapist'"),
                'duration_minutes' => 'a.duration_minutes',
                'group_session_id' => 'a.group_session_id',
                'absence_id' => 'ab.id',
                'plan_in_progress' => new Expression(self::PLAN_IN_PROGRESS_SQL)
            ])
            ->from(['a' => 'appointments'])
            ->innerJoin(['ts' => 'therapist_substitutions'], 'ts.appointment_id = a.id')
            ->innerJoin(['ab' => 'absences'], $this->buildAbsenceJoinCondition('ts.original_therapist_id'))
            ->where(['a.status' => ['scheduled', 'therapist_absent']])
            ->leftJoin(['pt' => 'plan_therapies'], 'a.plan_therapy_id = pt.id')
            ->leftJoin(['tp' => 'therapeutic_plans'], 'pt.therapeutic_plan_id = tp.id')
            ->leftJoin(['p' => 'patients'], new Expression('COALESCE(tp.patient_id, a.patient_id) = p.id'))
            ->leftJoin(['t_orig' => 'therapists'], 'ts.original_therapist_id = t_orig.id')
            ->leftJoin(['u_th_orig' => 'users'], 't_orig.user_id = u_th_orig.id')
            ->leftJoin(['up_th_orig' => 'user_profiles'], 'u_th_orig.id = up_th_orig.user_id')
            ->leftJoin(['tt' => 'treatment_types'], new Expression('COALESCE(pt.treatment_type_id, a.treatment_type_id) = tt.id'));

        // Assenze pazienti - gestisce anche appuntamenti privati
        $patientQuery = (new Query())
            ->select([
                'absence_group_key' => new Expression("CONCAT('patient_', a.id)"),
                'absence_slot_key' => new Expression("COALESCE(a.group_session_id, CONCAT('single_', a.id))"),
                'absence_type' => new Expression("'patient'"),
                'absence_type_flag' => new Expression("'patient'"),
                'appointment_id' => 'a.id',
                'appointment_datetime' => 'a.appointment_datetime',
                'absence_date' => new Expression('DATE(a.appointment_datetime)'),
                'absence_hour' => new Expression('HOUR(a.appointment_datetime)'),
                'absence_day_name' => new Expression('DAYNAME(a.appointment_datetime)'),
                'absence_day_number' => new Expression('DAYOFWEEK(a.appointment_datetime)'),
                'patient_id' => new Expression('COALESCE(tp.patient_id, a.patient_id)'),
                'patient_name' => new Expression("COALESCE(p.first_name, 'N/A')"),
                'patient_surname' => new Expression("COALESCE(p.last_name, '')"),
                'therapist_id' => 't.id',
                'therapist_name' => new Expression("COALESCE(up_th.first_name, 'N/A')"),
                'therapist_surname' => new Expression("COALESCE(up_th.last_name, '')"),
                'treatment_type_id' => new Expression('COALESCE(pt.treatment_type_id, a.treatment_type_id)'),
                'treatment_name' => 'tt.name',
                'treatment_code' => 'tt.code',
                'setting_id' => 'a.id_setting',
                'absence_reason' => new Expression("CASE
                    WHEN a.status = 'absent_justified' THEN 'Giustificata'
                    ELSE 'Non giustificata'
                END"),
                'is_justified' => new Expression("CASE WHEN a.status = 'absent_justified' THEN 1 ELSE 0 END"),
                'has_recovery' => new Expression("CASE WHEN EXISTS (
                    SELECT 1 FROM absence_recoveries ar
                    WHERE ar.original_appointment_id = a.id
                ) THEN 'SI' ELSE 'NO' END"),
                'generated_by' => new Expression("'patient'"),
                'duration_minutes' => 'a.duration_minutes',
                'group_session_id' => 'a.group_session_id',
                'absence_id' => new Expression('NULL'),
                'plan_in_progress' => new Expression(self::PLAN_IN_PROGRESS_SQL)
            ])
            ->from(['a' => 'appointments'])
            ->leftJoin(['pt' => 'plan_therapies'], 'a.plan_therapy_id = pt.id')
            ->leftJoin(['tp' => 'therapeutic_plans'], 'pt.therapeutic_plan_id = tp.id')
            ->leftJoin(['p' => 'patients'], new Expression('COALESCE(tp.patient_id, a.patient_id) = p.id'))
            ->leftJoin(['t' => 'therapists'], 'a.therapist_id = t.id')
            ->leftJoin(['u_th' => 'users'], 't.user_id = u_th.id')
            ->leftJoin(['up_th' => 'user_profiles'], 'u_th.id = up_th.user_id')
            ->leftJoin(['tt' => 'treatment_types'], new Expression('COALESCE(pt.treatment_type_id, a.treatment_type_id) = tt.id'))
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
     * Seleziona una sola assenza approvata che copre realmente lo slot.
     * Per le assenze orarie richiede la sovrapposizione degli intervalli.
     */
    protected function buildAbsenceJoinCondition($therapistExpression)
    {
        return "ab.id = (
            SELECT ab2.id
            FROM absences ab2
            WHERE ab2.therapist_id = {$therapistExpression}
              AND DATE(a.appointment_datetime) BETWEEN ab2.start_date AND ab2.end_date
              AND ab2.status = 'approved'
              AND (
                  ab2.start_time IS NULL
                  OR ab2.end_time IS NULL
                  OR (
                      TIME(a.appointment_datetime) < ab2.end_time
                      AND ADDTIME(
                          TIME(a.appointment_datetime),
                          SEC_TO_TIME(COALESCE(a.duration_minutes, 0) * 60)
                      ) > ab2.start_time
                  )
              )
            ORDER BY ab2.approved_at DESC, ab2.id DESC
            LIMIT 1
        )";
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

        // Filtro setting (Domiciliare, Ambulatorio, ecc.)
        if (!empty($filters['settingId'])) {
            $query->andWhere(['setting_id' => $filters['settingId']]);
        }

        // Filtro tipo assenza (therapist/patient)
        if (!empty($filters['absenceSource'])) {
            if ($filters['absenceSource'] === 'therapist') {
                $query->andWhere(['generated_by' => 'therapist']);
            } elseif ($filters['absenceSource'] === 'patient') {
                $query->andWhere(['generated_by' => 'patient']);
            }
        }

        if (!empty($filters['absenceTypeFlag'])) {
            $query->andWhere(['absence_type_flag' => $filters['absenceTypeFlag']]);
        }

        // Filtro giustificata/non giustificata
        if (isset($filters['isJustified']) && $filters['isJustified'] !== '') {
            $query->andWhere(['is_justified' => $filters['isJustified']]);
        }

        // Solo piani in corso: i cicli privati (senza piano) restano sempre
        if (!empty($filters['onlyActivePlans'])) {
            $query->andWhere(['OR', ['plan_in_progress' => 1], ['plan_in_progress' => null]]);
        }

        // Coordinamento: terapista (assente o dell'appuntamento) nel gruppo alla data dell'assenza
        if (!empty($filters['coordinationGroupId'])) {
            $query->andWhere(['EXISTS', $this->groupMembershipQuery(
                $filters['coordinationGroupId'],
                'absences.therapist_id',
                'absences.absence_date'
            )]);
        }
    }

    /**
     * Sottoquery EXISTS: il terapista era assegnato al gruppo di coordinamento
     * alla data indicata (assigned_to NULL = assegnazione ancora aperta).
     *
     * @param int $groupId
     * @param string $therapistColumn Colonna SQL con l'id terapista
     * @param string $dateColumn Colonna SQL con la data (DATE)
     * @return Query
     */
    protected function groupMembershipQuery($groupId, $therapistColumn, $dateColumn)
    {
        return (new Query())
            ->from(['gt_f' => 'group_therapists'])
            ->where("gt_f.therapist_id = {$therapistColumn}")
            ->andWhere(['gt_f.group_id' => (int) $groupId])
            ->andWhere("gt_f.assigned_from <= {$dateColumn}")
            ->andWhere("gt_f.assigned_to IS NULL OR gt_f.assigned_to >= {$dateColumn}");
    }

    /**
     * Statistiche per tipo di trattamento
     */
    public function getByTreatmentType($filters = [])
    {
        $query = $this->getBaseAbsencesQuery($filters);

        // Log rimosso - problema identificato: appuntamenti 499, 508, 509, 524, 525, 526

        return $query
            ->select([
                'treatment_type_id' => new Expression('COALESCE(treatment_type_id, 0)'), // Usa 0 invece di NULL
                'treatment_name' => new Expression("COALESCE(MIN(treatment_name), 'Trattamento non specificato')"),
                'treatment_code' => new Expression("COALESCE(MIN(treatment_code), 'N/A')"),
                'total_absences' => new Expression('COUNT(DISTINCT absence_group_key)'),
                'therapist_absences' => new Expression("COUNT(DISTINCT CASE WHEN generated_by = 'therapist' THEN absence_group_key END)"),
                'patient_absences' => new Expression("COUNT(DISTINCT CASE WHEN generated_by = 'patient' THEN absence_group_key END)"),
                'justified_rate' => new Expression(
                    'ROUND(COUNT(DISTINCT CASE WHEN is_justified = 1 THEN absence_group_key END) '
                    . '* 100.0 / COUNT(DISTINCT absence_group_key), 1)'
                )
            ])
            ->groupBy([new Expression('COALESCE(treatment_type_id, 0)')])
            ->having(['>', 'COUNT(DISTINCT absence_group_key)', 0])
            ->orderBy(['total_absences' => SORT_DESC])
            ->limit(10)
            ->all();
    }

    /**
     * Statistiche per setting (ambulatorio, domiciliare, ecc.).
     */
    public function getBySetting($filters = [])
    {
        $bySetting = (new Query())
            ->select([
                'setting_id' => new Expression('COALESCE(ad.setting_id, 0)'),
                'setting_name' => new Expression("COALESCE(MIN(s.nome), 'Non indicato')"),
                'total_absences' => new Expression('COUNT(DISTINCT ad.absence_group_key)'),
                'therapist_absences' => new Expression(
                    "COUNT(DISTINCT CASE WHEN ad.generated_by = 'therapist' THEN ad.absence_group_key END)"
                ),
                'patient_absences' => new Expression(
                    "COUNT(DISTINCT CASE WHEN ad.generated_by = 'patient' THEN ad.absence_group_key END)"
                ),
            ])
            ->from(['ad' => $this->getBaseAbsencesQuery($filters)])
            ->leftJoin(['s' => '{{%setting}}'], 's.id = ad.setting_id')
            ->groupBy([new Expression('COALESCE(ad.setting_id, 0)')])
            ->having(['>', 'COUNT(DISTINCT ad.absence_group_key)', 0])
            ->orderBy(['total_absences' => SORT_DESC])
            ->all();

        $hoursBySetting = (new Query())
            ->select([
                'setting_id',
                'lost_minutes' => new Expression('COALESCE(SUM(slot_minutes), 0)'),
            ])
            ->from(['slots' => (new Query())
                ->select([
                    'setting_id' => new Expression('COALESCE(ad.setting_id, 0)'),
                    'absence_slot_key',
                    'slot_minutes' => new Expression('MAX(ad.duration_minutes)'),
                ])
                ->from(['ad' => $this->getBaseAbsencesQuery($filters)])
                ->groupBy(['absence_slot_key', new Expression('COALESCE(ad.setting_id, 0)')])
            ])
            ->groupBy('setting_id')
            ->indexBy('setting_id')
            ->all();

        foreach ($bySetting as &$row) {
            $sid = (string) $row['setting_id'];
            $minutes = (int) ($hoursBySetting[$sid]['lost_minutes'] ?? 0);
            $row['lost_hours'] = $this->minutesToHours($minutes);
        }
        unset($row);

        return $bySetting;
    }

    /**
     * Statistiche per giorno della settimana
     */
    public function getByDayOfWeek($searchModel)
    {
        $filters = $searchModel->toFilters();
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
        $filters = $searchModel->toFilters();
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
        $filters = $searchModel->toFilters();
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
        $total = (int)$this->getBaseAbsencesQuery($filters)
            ->select(new Expression('COUNT(DISTINCT absence_group_key)'))
            ->scalar();
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
        $filters = $searchModel->toFilters();

        $from = $filters['dateFrom'] ?? date('Y-m-01', strtotime('first day of -11 months'));
        $to = $filters['dateTo'] ?? date('Y-m-d');
        $spanDays = (int) round((strtotime($to) - strtotime($from)) / 86400);
        $byDay = $spanDays <= 62;

        $periodExpression = $byDay
            ? new Expression('absence_date')
            : new Expression("DATE_FORMAT(absence_date, '%Y-%m')");

        $rows = (new Query())
            ->select([
                'month' => $periodExpression,
                'total_absences' => new Expression('COUNT(DISTINCT absence_group_key)'),
                'justified_absences' => new Expression(
                    'COUNT(DISTINCT CASE WHEN is_justified = 1 THEN absence_group_key END)'
                ),
                'therapist_absences' => new Expression(
                    "COUNT(DISTINCT CASE WHEN generated_by = 'therapist' THEN absence_group_key END)"
                ),
                'patient_absences' => new Expression(
                    "COUNT(DISTINCT CASE WHEN generated_by = 'patient' THEN absence_group_key END)"
                ),
            ])
            ->from(['absences_data' => $this->getBaseAbsencesQuery($filters)])
            ->groupBy($periodExpression)
            ->orderBy(['month' => SORT_ASC])
            ->all();

        $mesi = [1 => 'Gen', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mag', 6 => 'Giu', 7 => 'Lug', 8 => 'Ago', 9 => 'Set', 10 => 'Ott', 11 => 'Nov', 12 => 'Dic'];
        foreach ($rows as &$row) {
            if ($byDay) {
                $ts = strtotime($row['month']);
                $row['month_label'] = $ts ? date('d/m', $ts) : $row['month'];
            } else {
                $ts = strtotime($row['month'] . '-01');
                $row['month_label'] = $ts
                    ? ($mesi[(int) date('n', $ts)] . ' ' . date('Y', $ts))
                    : $row['month'];
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Tasso mensile di assenze
     */
    public function getMonthlyRate($filters = [])
    {
        // Se non ci sono filtri di date, usa il mese corrente come default
        if (empty($filters['dateFrom']) && empty($filters['dateTo'])) {
            $filters['dateFrom'] = date('Y-m-01');
            $filters['dateTo'] = date('Y-m-t');
        }

        $query = $this->getBaseAbsencesQuery($filters);

        $absences = $query
            ->select([
                'total' => new Expression('COUNT(DISTINCT absence_group_key)'),
                'justified' => new Expression('COUNT(DISTINCT CASE WHEN is_justified = 1 THEN absence_group_key END)'),
                'therapist' => new Expression(
                    "COUNT(DISTINCT CASE WHEN generated_by = 'therapist' THEN absence_group_key END)"
                ),
                'patient' => new Expression(
                    "COUNT(DISTINCT CASE WHEN generated_by = 'patient' THEN absence_group_key END)"
                ),
                'with_recovery' => new Expression(
                    "COUNT(DISTINCT CASE WHEN has_recovery = 'SI' THEN absence_group_key END)"
                ),
                'affected_slots' => new Expression('COUNT(DISTINCT absence_slot_key)'),
                'in_progress_plan' => new Expression('COUNT(DISTINCT CASE WHEN plan_in_progress = 1 THEN absence_group_key END)'),
                'old_plan' => new Expression('COUNT(DISTINCT CASE WHEN plan_in_progress = 0 THEN absence_group_key END)'),
                'no_plan' => new Expression('COUNT(DISTINCT CASE WHEN plan_in_progress IS NULL THEN absence_group_key END)'),
            ])
            ->one();

        $slotHours = (new Query())
            ->select([
                'lost_minutes' => new Expression('COALESCE(SUM(slot_minutes), 0)'),
                'unrecovered_minutes' => new Expression(
                    'COALESCE(SUM(CASE WHEN recovered = 0 THEN slot_minutes ELSE 0 END), 0)'
                ),
            ])
            ->from(['slots' => (new Query())
                ->select([
                    'absence_slot_key',
                    'slot_minutes' => new Expression('MAX(duration_minutes)'),
                    'recovered' => new Expression("MAX(CASE WHEN has_recovery = 'SI' THEN 1 ELSE 0 END)"),
                ])
                ->from(['ad' => $this->getBaseAbsencesQuery($filters)])
                ->groupBy('absence_slot_key')
            ])
            ->one();

        // Appuntamenti e slot previsti con gli stessi filtri dimensionali.
        $planned = $this->plannedAppointmentsQuery($filters)
            ->select([
                'total_appointments' => new Expression('COUNT(*)'),
                'total_slots' => new Expression('COUNT(DISTINCT COALESCE(a.group_session_id, CONCAT("single_", a.id)))'),
            ])
            ->one();
        $totalAppointments = (int) ($planned['total_appointments'] ?? 0);
        $totalSlots = (int) ($planned['total_slots'] ?? 0);

        $plannedSlotsQuery = $this->plannedAppointmentsQuery($filters)
            ->select([
                'slot_key' => new Expression('COALESCE(a.group_session_id, CONCAT("single_", a.id))'),
                'slot_minutes' => new Expression('MAX(a.duration_minutes)'),
            ])
            ->groupBy(new Expression('COALESCE(a.group_session_id, CONCAT("single_", a.id))'));

        $plannedMinutes = (int) (new Query())
            ->from(['slots' => $plannedSlotsQuery])
            ->select(['planned_minutes' => new Expression('COALESCE(SUM(slot_minutes), 0)')])
            ->scalar();

        $affectedSlots = (int) ($absences['affected_slots'] ?? 0);
        $totalEvents = (int) ($absences['total'] ?? 0);
        $justified = (int) ($absences['justified'] ?? 0);
        $withRecovery = (int) ($absences['with_recovery'] ?? 0);
        $lostMinutes = (int) ($slotHours['lost_minutes'] ?? 0);
        $unrecoveredMinutes = (int) ($slotHours['unrecovered_minutes'] ?? 0);
        $rate = $totalSlots > 0
            ? round(($affectedSlots / $totalSlots) * 100, 1)
            : 0;
        $hoursRate = $plannedMinutes > 0
            ? round(($lostMinutes / $plannedMinutes) * 100, 1)
            : 0;

        return [
            'total_absences' => $totalEvents,
            'justified_absences' => $justified,
            'unjustified_absences' => max(0, $totalEvents - $justified),
            'therapist_absences' => (int) ($absences['therapist'] ?? 0),
            'patient_absences' => (int) ($absences['patient'] ?? 0),
            'with_recovery' => $withRecovery,
            'without_recovery' => max(0, $totalEvents - $withRecovery),
            'total_appointments' => $totalAppointments,
            'total_slots' => $totalSlots,
            'in_progress_plan_absences' => (int) ($absences['in_progress_plan'] ?? 0),
            'old_plan_absences' => (int) ($absences['old_plan'] ?? 0),
            'no_plan_absences' => (int) ($absences['no_plan'] ?? 0),
            'absence_rate' => $rate,
            'lost_hours' => $this->minutesToHours($lostMinutes),
            'unrecovered_hours' => $this->minutesToHours($unrecoveredMinutes),
            'planned_hours' => $this->minutesToHours($plannedMinutes),
            'hours_rate' => $hoursRate,
        ];
    }

    /**
     * Appuntamenti previsti nel periodo (alias a, pt_rate, tp_rate): programmati,
     * completati, assenti e con terapista assente, senza gli annullati. Applica
     * i filtri dimensionali della pagina (non quelli sul tipo di assenza).
     * Una seduta di gruppo è una riga per paziente.
     *
     * @param array $filters
     * @return Query
     */
    public function plannedAppointmentsQuery($filters = [])
    {
        $query = (new Query())
            ->from(['a' => 'appointments'])
            ->leftJoin(['pt_rate' => 'plan_therapies'], 'a.plan_therapy_id = pt_rate.id')
            ->leftJoin(['tp_rate' => 'therapeutic_plans'], 'pt_rate.therapeutic_plan_id = tp_rate.id')
            ->andWhere([
                'a.status' => [
                    'scheduled',
                    'completed',
                    'absent_justified',
                    'absent_not_justified',
                    'therapist_absent',
                ],
            ]);

        if (!empty($filters['dateFrom'])) {
            $query->andWhere(['>=', 'a.appointment_datetime', $filters['dateFrom'] . ' 00:00:00']);
        }
        if (!empty($filters['dateTo'])) {
            $query->andWhere(['<=', 'a.appointment_datetime', $filters['dateTo'] . ' 23:59:59']);
        }
        if (!empty($filters['therapistId'])) {
            $query->andWhere([
                'OR',
                ['a.therapist_id' => $filters['therapistId']],
                [
                    'EXISTS',
                    (new Query())
                        ->from(['ts_rate' => 'therapist_substitutions'])
                        ->where('ts_rate.appointment_id = a.id')
                        ->andWhere(['ts_rate.original_therapist_id' => $filters['therapistId']]),
                ],
            ]);
        }
        if (!empty($filters['patientId'])) {
            $query->andWhere([
                '=',
                new Expression('COALESCE(tp_rate.patient_id, a.patient_id)'),
                $filters['patientId'],
            ]);
        }
        if (!empty($filters['treatmentTypeId'])) {
            $query->andWhere([
                'OR',
                ['a.treatment_type_id' => $filters['treatmentTypeId']],
                ['pt_rate.treatment_type_id' => $filters['treatmentTypeId']],
            ]);
        }
        if (!empty($filters['settingId'])) {
            $query->andWhere(['a.id_setting' => $filters['settingId']]);
        }
        if (!empty($filters['onlyActivePlans'])) {
            $query->andWhere([
                'OR',
                ['tp_rate.id' => null],
                [
                    'AND',
                    ['tp_rate.status' => 'active'],
                    new Expression('CURDATE() BETWEEN tp_rate.start_date AND tp_rate.end_date'),
                ],
            ]);
        }
        if (!empty($filters['coordinationGroupId'])) {
            $query->andWhere([
                'OR',
                ['EXISTS', $this->groupMembershipQuery($filters['coordinationGroupId'], 'a.therapist_id', 'DATE(a.appointment_datetime)')],
                [
                    'EXISTS',
                    (new Query())
                        ->from(['ts_grp' => 'therapist_substitutions'])
                        ->where('ts_grp.appointment_id = a.id')
                        ->andWhere(['EXISTS', $this->groupMembershipQuery($filters['coordinationGroupId'], 'ts_grp.original_therapist_id', 'DATE(a.appointment_datetime)')]),
                ],
            ]);
        }

        return $query;
    }

    /**
     * Assenze dei terapisti per gruppo di coordinamento: un terapista conta nei
     * gruppi a cui era assegnato alla data dell'assenza (anche più di uno).
     * Ultima riga "Senza coordinamento" per i terapisti non assegnati a nessun gruppo.
     *
     * @param array $filters
     * @return array Righe con group_name, events, therapists, lost_hours
     */
    public function getByCoordinationGroup($filters = [])
    {
        if (($filters['absenceSource'] ?? null) === 'patient') {
            return [];
        }

        // Una riga per evento (terapista + slot) e gruppo, con i minuti dello slot.
        // Alias diversi da gt.group_id: nel GROUP BY MySQL darebbe la precedenza alla colonna.
        $events = (new Query())
            ->select([
                'grp_key' => new Expression('COALESCE(cg.id, 0)'),
                'grp_name' => new Expression("COALESCE(cg.name, 'Senza coordinamento')"),
                'absence_group_key',
                'therapist_id' => new Expression('MAX(absences_c.therapist_id)'),
                'slot_minutes' => new Expression('MAX(absences_c.duration_minutes)'),
            ])
            ->from(['absences_c' => $this->getBaseAbsencesQuery($filters)->andWhere(['generated_by' => 'therapist'])])
            ->leftJoin(
                ['gt' => 'group_therapists'],
                'gt.therapist_id = absences_c.therapist_id AND gt.assigned_from <= absences_c.absence_date'
                . ' AND (gt.assigned_to IS NULL OR gt.assigned_to >= absences_c.absence_date)'
            )
            ->leftJoin(['cg' => 'coordinator_groups'], 'cg.id = gt.group_id')
            ->groupBy(['grp_key', 'grp_name', 'absence_group_key']);

        if (!empty($filters['coordinationGroupId'])) {
            $events->andWhere(['cg.id' => (int) $filters['coordinationGroupId']]);
        }

        $rows = (new Query())
            ->select([
                'group_id' => 'grp_key',
                'group_name' => 'grp_name',
                'events' => new Expression('COUNT(*)'),
                'therapists' => new Expression('COUNT(DISTINCT therapist_id)'),
                'lost_minutes' => new Expression('COALESCE(SUM(slot_minutes), 0)'),
            ])
            ->from(['ev' => $events])
            ->groupBy(['grp_key', 'grp_name'])
            ->orderBy(new Expression('grp_key = 0, events DESC, grp_name'))
            ->all();

        foreach ($rows as &$row) {
            $row['events'] = (int) $row['events'];
            $row['therapists'] = (int) $row['therapists'];
            $row['lost_hours'] = $this->minutesToHours((int) $row['lost_minutes']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Eventi di assenza con i filtri della pagina, dal più recente: una riga per
     * absence_group_key, come il conteggio "Eventi di assenza". Un'assenza del
     * terapista su una seduta di gruppo è una riga sola con tutti i pazienti.
     * Usata dall'elenco della pagina e dall'export Excel.
     *
     * Colonne: absence_group_key, appointment_id (il primo dell'evento), absence_id,
     * absence_type, absence_type_flag, generated_by, appointment_datetime,
     * patients ("Cognome Nome, …"), patient_count, therapist, treatments,
     * setting_id, is_justified, has_recovery, absence_reason, plan_in_progress
     * (1 se almeno un piano è in corso, NULL senza piano), is_group, appointment_notes.
     *
     * @param array $filters
     * @return Query
     */
    public function getAbsenceEventsQuery($filters = [])
    {
        $events = $this->getBaseAbsencesQuery($filters)
            ->select([
                'absence_group_key',
                'appointment_id' => new Expression('MIN(appointment_id)'),
                'absence_id' => new Expression('MAX(absence_id)'),
                'absence_type' => new Expression('MAX(absence_type)'),
                'absence_type_flag' => new Expression('MAX(absence_type_flag)'),
                'generated_by' => new Expression('MAX(generated_by)'),
                'appointment_datetime' => new Expression('MIN(appointment_datetime)'),
                'patients' => new Expression("GROUP_CONCAT(DISTINCT CASE WHEN patient_id IS NULL THEN NULL"
                    . " ELSE TRIM(CONCAT(patient_surname, ' ', patient_name)) END"
                    . " ORDER BY patient_surname, patient_name SEPARATOR ', ')"),
                'patient_count' => new Expression('COUNT(DISTINCT patient_id)'),
                'therapist' => new Expression("MAX(TRIM(CONCAT(therapist_surname, ' ', therapist_name)))"),
                'treatments' => new Expression("GROUP_CONCAT(DISTINCT treatment_name ORDER BY treatment_name SEPARATOR ', ')"),
                'setting_id' => new Expression('MAX(setting_id)'),
                'is_justified' => new Expression('MAX(is_justified)'),
                'has_recovery' => new Expression('MAX(has_recovery)'),
                'absence_reason' => new Expression('MAX(absence_reason)'),
                'plan_in_progress' => new Expression('MAX(plan_in_progress)'),
                'is_group' => new Expression('MAX(group_session_id IS NOT NULL)'),
            ])
            ->groupBy('absence_group_key');

        return (new Query())
            ->select(['ev.*', 'appointment_notes' => 'an.notes'])
            ->from(['ev' => $events])
            ->leftJoin(['an' => 'appointments'], 'an.id = ev.appointment_id')
            ->orderBy(['ev.appointment_datetime' => SORT_DESC, 'ev.appointment_id' => SORT_DESC]);
    }

    /**
     * Motivo di un evento di getAbsenceEventsQuery(), come nel dettaglio:
     * per i pazienti dalla nota dell'appuntamento, per i terapisti dall'assenza.
     *
     * @param array $event
     * @return string
     */
    public function absenceEventReason(array $event)
    {
        if ($event['generated_by'] === 'patient') {
            return $this->reasonFromNote($this->parseAbsenceNote($event['appointment_notes']));
        }
        if (empty($event['absence_id'])) {
            return 'Terapista assente senza assenza approvata collegata';
        }

        return $event['absence_reason'] !== null && $event['absence_reason'] !== '' ? $event['absence_reason'] : 'Non indicato';
    }

    /**
     * Chi ha inserito ogni evento e quando, con poche query per tutto l'elenco
     * (stessa logica di getAbsenceDetail()).
     *
     * @param array $events Righe di getAbsenceEventsQuery()
     * @return array absence_group_key => ['by' => string, 'at' => string Y-m-d H:i:s|null]
     */
    public function getAbsenceEventsInsertion(array $events)
    {
        $appointmentIds = [];
        $absenceIds = [];
        foreach ($events as $event) {
            if ($event['generated_by'] === 'patient') {
                $appointmentIds[] = (int) $event['appointment_id'];
            } elseif (!empty($event['absence_id'])) {
                $absenceIds[] = (int) $event['absence_id'];
            }
        }

        // Ultimo cambio di stato in assenza per appuntamento (id log crescente: l'ultimo vince).
        $logs = [];
        foreach (array_chunk(array_unique($appointmentIds), 1000) as $chunk) {
            foreach ((new Query())
                ->select(['entity_id', 'user_id', 'created_at'])
                ->from('activity_log')
                ->where(['entity_name' => self::APPOINTMENT_LOG_ENTITY_NAMES, 'action' => 'update', 'entity_id' => $chunk])
                ->andWhere(['like', 'new_values', '%"status":"absent%', false])
                ->orderBy(['id' => SORT_ASC])
                ->all() as $log) {
                $logs[(int) $log['entity_id']] = $log;
            }
        }

        $absences = [];
        foreach (array_chunk(array_unique($absenceIds), 1000) as $chunk) {
            $absences += (new Query())
                ->select(['id', 'created_by', 'created_at'])
                ->from('absences')
                ->where(['id' => $chunk])
                ->indexBy('id')
                ->all();
        }

        $names = $this->userNames(array_merge(array_column($logs, 'user_id'), array_column($absences, 'created_by')));
        $nameOf = function ($userId) use ($names) {
            return $names[(int) $userId] ?? 'Utente #' . (int) $userId;
        };

        $result = [];
        foreach ($events as $event) {
            $info = ['by' => 'Non registrato', 'at' => null];
            if ($event['generated_by'] === 'patient') {
                $log = $logs[(int) $event['appointment_id']] ?? null;
                if ($log) {
                    $info = ['by' => $nameOf($log['user_id']), 'at' => $log['created_at']];
                }
            } elseif (!empty($event['absence_id']) && isset($absences[(int) $event['absence_id']])) {
                $absence = $absences[(int) $event['absence_id']];
                $info = ['by' => $nameOf($absence['created_by']), 'at' => $absence['created_at']];
            }
            $result[$event['absence_group_key']] = $info;
        }

        return $result;
    }

    /**
     * Dettaglio di una singola assenza: chi l'ha inserita, quando e perché.
     *
     * Assenze del terapista: dalla tabella absences (inserimento e approvazione).
     * Assenze del paziente: chi e quando dall'ultimo activity_log che porta lo
     * stato dell'appuntamento in assenza (registrato da febbraio 2026), motivo
     * dalla nota scritta sull'appuntamento da app o gestionale.
     *
     * @param int $appointmentId
     * @param string $absenceType patient | therapist_direct | therapist_substitution
     * @param int|null $absenceId absences.id per le assenze del terapista
     * @return array|null Righe [label, value]; null se l'appuntamento non esiste
     */
    public function getAbsenceDetail($appointmentId, $absenceType, $absenceId = null)
    {
        $appointment = (new Query())
            ->from('appointments')
            ->where(['id' => (int) $appointmentId])
            ->one();
        if (!$appointment) {
            return null;
        }

        $formatter = Yii::$app->formatter;
        $rows = [];

        if ($absenceType === 'patient') {
            $log = (new Query())
                ->select(['al.user_id', 'al.created_at'])
                ->from(['al' => 'activity_log'])
                ->where(['al.entity_name' => self::APPOINTMENT_LOG_ENTITY_NAMES, 'al.entity_id' => (int) $appointmentId, 'al.action' => 'update'])
                ->andWhere(['like', 'al.new_values', '%"status":"absent%', false])
                ->orderBy(['al.id' => SORT_DESC])
                ->one();
            $note = $this->parseAbsenceNote($appointment['notes']);

            $rows[] = ['Origine', $note['origin'] ?? 'Non indicata'];
            $rows[] = ['Inserita da', $log ? $this->userName($log['user_id']) : 'Non registrato'];
            $rows[] = ['Inserita il', $log
                ? $formatter->asDatetime($log['created_at'], 'php:d/m/Y H:i')
                : ($note['date'] ?? 'Non registrato')];
            $rows[] = ['Tipo', $appointment['status'] === 'absent_justified' ? 'Giustificata' : 'Non giustificata'];
            $rows[] = ['Motivo', $this->reasonFromNote($note)];
            if (!empty($note['notes'])) {
                $rows[] = ['Note', $note['notes']];
            }
            if (!$log) {
                $rows[] = ['', 'Chi ha inserito l\'assenza è registrato solo dalle modifiche successive a febbraio 2026.'];
            }

            return $rows;
        }

        $absence = $absenceId ? (new Query())->from('absences')->where(['id' => (int) $absenceId])->one() : null;
        if ($absenceType === 'therapist_substitution') {
            $rows[] = ['Origine', 'Sostituzione: l\'appuntamento è passato a ' . $this->therapistName($appointment['therapist_id'])];
        } else {
            $rows[] = ['Origine', 'Assenza del terapista'];
        }

        if (!$absence) {
            $rows[] = ['Motivo', 'Appuntamento segnato con terapista assente, senza un\'assenza approvata collegata.'];
            return $rows;
        }

        $typeLabels = \common\models\Absence::getTypeLabels();
        $period = $formatter->asDate($absence['start_date'], 'php:d/m/Y');
        if ($absence['end_date'] !== $absence['start_date']) {
            $period .= ' – ' . $formatter->asDate($absence['end_date'], 'php:d/m/Y');
        }
        if ($absence['start_time'] && $absence['end_time']) {
            $period .= ' · ' . substr($absence['start_time'], 0, 5) . '–' . substr($absence['end_time'], 0, 5);
        }

        $rows[] = ['Periodo assenza', $period];
        $rows[] = ['Tipo', $typeLabels[$absence['type']] ?? $absence['type']];
        $rows[] = ['Motivo', $absence['reason'] !== null && $absence['reason'] !== '' ? $absence['reason'] : 'Non indicato'];
        if (!empty($absence['notes'])) {
            $rows[] = ['Note', $absence['notes']];
        }
        $rows[] = ['Inserita da', $this->userName($absence['created_by'])];
        $rows[] = ['Inserita il', $formatter->asDatetime($absence['created_at'], 'php:d/m/Y H:i')];
        if ($absence['approved_by']) {
            $rows[] = ['Approvata da', $this->userName($absence['approved_by'])
                . ($absence['approved_at'] ? ' il ' . $formatter->asDatetime($absence['approved_at'], 'php:d/m/Y H:i') : '')];
        }

        return $rows;
    }

    /**
     * Legge l'ultima nota di assenza scritta sull'appuntamento. Formati:
     * - app: "ASSENZA SEGNALATA DAL TERAPISTA - Tipo: … - Motivo: … [- Note: …] [- ASSENZA DI GRUPPO] - Data segnalazione: …"
     * - gestionale: "ASSENZA INSERITA DA GESTIONALE - Tipo: … - Motivo: … [- Note: …] - Da: … - <data>"
     *
     * @param string|null $notes
     * @return array origin, reason, notes, date; raw se il formato non è riconosciuto
     */
    protected function parseAbsenceNote($notes)
    {
        if ($notes === null || trim($notes) === '') {
            return [];
        }

        $block = null;
        foreach (preg_split('/\n\s*\n/', $notes) as $candidate) {
            if (strpos(ltrim($candidate), 'ASSENZA ') === 0) {
                $block = trim($candidate);
            }
        }
        if ($block === null) {
            return ['raw' => trim($notes)];
        }

        $result = [];
        if (strpos($block, 'ASSENZA SEGNALATA DAL TERAPISTA') === 0) {
            $result['origin'] = 'App del terapista';
        } elseif (strpos($block, 'ASSENZA INSERITA DA GESTIONALE') === 0) {
            $result['origin'] = 'Gestionale';
        }

        // Il motivo e le note sono testo libero: finiscono al primo marcatore successivo.
        $endMarkers = ' - Note: | - ASSENZA DI GRUPPO| - Data segnalazione: | - Da: ';
        if (preg_match('/ - Motivo: (.*?)(?=' . $endMarkers . '|$)/s', $block, $m)) {
            $result['reason'] = trim($m[1]);
        }
        if (preg_match('/ - Note: (.*?)(?= - ASSENZA DI GRUPPO| - Data segnalazione: | - Da: |$)/s', $block, $m)) {
            $result['notes'] = trim($m[1]);
        }
        if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}):\d{2}\s*$/', $block, $m)) {
            $result['date'] = Yii::$app->formatter->asDatetime($m[1], 'php:d/m/Y H:i');
        }
        if (!isset($result['reason'])) {
            $result['raw'] = $block;
        }

        return $result;
    }

    /**
     * Motivo di un'assenza del paziente dalla nota già letta con parseAbsenceNote():
     * il motivo se c'è, altrimenti la nota intera se non è nel formato previsto.
     * Stessa regola per dettaglio ed export.
     *
     * @param array $note
     * @return string
     */
    protected function reasonFromNote(array $note)
    {
        if (isset($note['reason']) && $note['reason'] !== '') {
            return $note['reason'];
        }

        return isset($note['raw']) && $note['raw'] !== '' ? $note['raw'] : 'Non indicato';
    }

    /**
     * Nomi utente ("Cognome Nome" da UserProfile::getFullName()) per più id in
     * una query. Chi non ha profilo non compare nel risultato.
     *
     * @param array $userIds
     * @return array user_id => nome
     */
    protected function userNames(array $userIds)
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($userIds === []) {
            return [];
        }

        $names = [];
        foreach (UserProfile::find()->where(['user_id' => $userIds])->all() as $profile) {
            $names[(int) $profile->user_id] = trim($profile->getFullName());
        }

        return $names;
    }

    /**
     * @param int|null $userId
     * @return string Cognome e nome dal profilo, o un'etichetta se manca
     */
    protected function userName($userId)
    {
        if (!$userId) {
            return 'Non registrato';
        }

        return $this->userNames([$userId])[(int) $userId] ?? 'Utente #' . (int) $userId;
    }

    /**
     * @param int|null $therapistId
     * @return string
     */
    protected function therapistName($therapistId)
    {
        $therapist = $therapistId ? Therapist::findOne((int) $therapistId) : null;

        return $therapist ? $this->userName($therapist->user_id) : 'terapista non indicato';
    }

    /**
     * Top assenti (terapisti e pazienti)
     */
    public function getTopAbsentees($filters = [], $limit = 10)
    {
        // Se non ci sono filtri di date, usa gli ultimi 3 mesi come default
        if (empty($filters['dateFrom']) && empty($filters['dateTo'])) {
            $filters['dateFrom'] = date('Y-m-01', strtotime('first day of -3 months'));
        }

        // Top terapisti assenti
        $therapistQuery = $this->getBaseAbsencesQuery($filters);
        $topTherapists = $therapistQuery
            ->select([
                'id' => 'therapist_id',
                'name' => new Expression("TRIM(CONCAT(COALESCE(therapist_name, 'N/A'), ' ', COALESCE(therapist_surname, '')))"),
                'count' => new Expression('COUNT(DISTINCT absence_group_key)'),
                'type' => new Expression("'therapist'")
            ])
            ->andWhere(['generated_by' => 'therapist'])
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
                'name' => new Expression("TRIM(CONCAT(COALESCE(patient_name, 'N/A'), ' ', COALESCE(patient_surname, '')))"),
                'count' => new Expression('COUNT(DISTINCT absence_group_key)'),
                'type' => new Expression("'patient'")
            ])
            ->andWhere(['generated_by' => 'patient'])
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

    /**
     * Le sedute sono in minuti, ma in pagina le ore si mostrano intere.
     */
    protected function minutesToHours($minutes)
    {
        return (int) round(((int) $minutes) / 60);
    }
}

<?php

namespace common\helpers;

use common\models\Appointment;
use Yii;
use yii\helpers\Url;

/**
 * Righe JSON per gli elenchi dei pannelli di dettaglio (drilldown-panel.js),
 * condivise tra dashboard principale e dashboard statistiche.
 */
class DrilldownHelper
{
    /**
     * Elenco pazienti (StatisticsService::getPatientsInChargeList() e simili).
     * Il nome è un link solo con il permesso view_patient.
     *
     * @param array $rows
     * @return array
     */
    public static function patientRows(array $rows)
    {
        $canViewPatient = Yii::$app->user->can('view_patient');
        $formatter = Yii::$app->formatter;
        $patients = [];
        foreach ($rows as $row) {
            $patients[] = [
                'name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'url' => $canViewPatient ? Url::to(['patient/view', 'id' => $row['patient_id']]) : null,
                'plan' => $formatter->asDate($row['start_date'], 'php:d/m/Y') . ' – ' . $formatter->asDate($row['end_date'], 'php:d/m/Y'),
                'end' => $formatter->asDate($row['end_date'], 'php:d/m/Y'),
                'hours' => rtrim(rtrim(number_format((float) $row['weekly_hours'], 2, ',', ''), '0'), ','),
            ];
        }

        return $patients;
    }

    /**
     * Elenco appuntamenti (StatisticsService::getExpectedAppointmentsList() e simili):
     * date = data gg/mm/aaaa, time = ora, paziente con "(gruppo)" per le sedute di gruppo.
     *
     * @param array $rows
     * @return array
     */
    public static function appointmentRows(array $rows)
    {
        $canViewPatient = Yii::$app->user->can('view_patient');
        $statusLabels = Appointment::getStatusLabels();
        $appointments = [];
        foreach ($rows as $row) {
            $patientName = $row['patient_id'] !== null
                ? trim($row['patient_last_name'] . ' ' . $row['patient_first_name'])
                : 'Paziente non indicato';
            if ($row['group_session_id'] !== null) {
                $patientName .= ' (gruppo)';
            }
            $timestamp = strtotime($row['appointment_datetime']);
            $appointments[] = [
                'date' => date('d/m/Y', $timestamp),
                'time' => date('H:i', $timestamp),
                'patient' => $patientName,
                'url' => $canViewPatient && $row['patient_id'] !== null ? Url::to(['patient/view', 'id' => $row['patient_id']]) : null,
                'therapist' => trim($row['therapist_last_name'] . ' ' . $row['therapist_first_name']),
                'status' => $statusLabels[$row['status']] ?? $row['status'],
            ];
        }

        return $appointments;
    }
}

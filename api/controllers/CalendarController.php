<?php

namespace api\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use common\models\Appointment;
use common\models\Therapist;
use common\models\Patient;
use common\models\TreatmentType;
use yii\db\Query;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class CalendarController extends ActiveController
{
    public $modelClass = 'common\models\Appointment';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Configura CORS per React
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['http://localhost:3000', 'http://localhost:8080'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        // Autenticazione JWT per API
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['options'], // Permetti preflight CORS
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        
        // Disabilita azioni default, usiamo custom
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete']);
        
        return $actions;
    }

    /**
     * GET /api/calendar/appointments
     * Recupera appuntamenti in un range di date
     */
    public function actionAppointments()
    {
        $request = Yii::$app->request;
        $startDate = $request->get('start');
        $endDate = $request->get('end');
        $therapistId = $request->get('therapist_id');

        if (!$startDate || !$endDate) {
            throw new BadRequestHttpException('Parametri start e end sono obbligatori');
        }

        $query = Appointment::find()
            ->select([
                'appointments.id',
                'appointments.therapist_id',
                'appointments.patient_id',
                'appointments.appointment_datetime',
                'appointments.duration_minutes',
                'appointments.status',
                'appointments.location_type',
                'patients.first_name as patient_first_name',
                'patients.last_name as patient_last_name',
                'therapists.calendar_color',
                'user_profiles.first_name as therapist_first_name',
                'user_profiles.last_name as therapist_last_name',
                'treatment_types.name as treatment_name',
                'treatment_types.code as treatment_code',
            ])
            ->leftJoin('patients', 'appointments.patient_id = patients.id')
            ->leftJoin('therapists', 'appointments.therapist_id = therapists.id')
            ->leftJoin('users', 'therapists.user_id = users.id')
            ->leftJoin('user_profiles', 'users.id = user_profiles.user_id')
            ->leftJoin('plan_therapies', 'appointments.plan_therapy_id = plan_therapies.id')
            ->leftJoin('treatment_types', 'plan_therapies.treatment_type_id = treatment_types.id')
            ->where(['between', 'DATE(appointments.appointment_datetime)', $startDate, $endDate])
            ->orderBy('appointments.appointment_datetime ASC');

        if ($therapistId) {
            $query->andWhere(['appointments.therapist_id' => $therapistId]);
        }

        $appointments = $query->asArray()->all();

        // Converte in formato FullCalendar
        $events = [];
        foreach ($appointments as $appointment) {
            $events[] = [
                'id' => $appointment['id'],
                'resourceId' => $appointment['therapist_id'],
                'title' => $appointment['patient_first_name'] . ' ' . $appointment['patient_last_name'],
                'start' => $appointment['appointment_datetime'],
                'end' => date('Y-m-d H:i:s', strtotime($appointment['appointment_datetime'] . ' +' . $appointment['duration_minutes'] . ' minutes')),
                'backgroundColor' => $this->getEventColor($appointment['status']),
                'borderColor' => $this->getEventColor($appointment['status']),
                'extendedProps' => [
                    'patientId' => $appointment['patient_id'],
                    'therapistId' => $appointment['therapist_id'],
                    'patientName' => $appointment['patient_first_name'] . ' ' . $appointment['patient_last_name'],
                    'therapistName' => $appointment['therapist_first_name'] . ' ' . $appointment['therapist_last_name'],
                    'treatmentName' => $appointment['treatment_name'],
                    'treatmentCode' => $appointment['treatment_code'],
                    'duration' => $appointment['duration_minutes'],
                    'status' => $appointment['status'],
                    'location' => $appointment['location_type'],
                    'therapistColor' => $appointment['calendar_color'],
                ]
            ];
        }

        return [
            'success' => true,
            'data' => $events,
            'meta' => [
                'count' => count($events),
                'period' => ['start' => $startDate, 'end' => $endDate]
            ]
        ];
    }

    /**
     * GET /api/calendar/therapists
     * Recupera lista terapisti per resources FullCalendar
     */
    public function actionTherapists()
    {
        $therapists = Therapist::find()
            ->select([
                'therapists.id',
                'therapists.calendar_color',
                'therapists.is_active',
                'therapists.weekly_hours_contract',
                'user_profiles.first_name',
                'user_profiles.last_name',
                'specializations.name as specialization_name',
                'specializations.code as specialization_code',
            ])
            ->leftJoin('users', 'therapists.user_id = users.id')
            ->leftJoin('user_profiles', 'users.id = user_profiles.user_id')
            ->leftJoin('specializations', 'therapists.specialization_id = specializations.id')
            ->where(['therapists.is_active' => true])
            ->orderBy('user_profiles.last_name, user_profiles.first_name')
            ->asArray()
            ->all();

        $resources = [];
        foreach ($therapists as $therapist) {
            $resources[] = [
                'id' => $therapist['id'],
                'title' => $therapist['first_name'] . ' ' . $therapist['last_name'],
                'eventColor' => $therapist['calendar_color'],
                'extendedProps' => [
                    'specialization' => $therapist['specialization_name'],
                    'specializationCode' => $therapist['specialization_code'],
                    'weeklyHours' => $therapist['weekly_hours_contract'],
                    'isActive' => $therapist['is_active'],
                ]
            ];
        }

        return [
            'success' => true,
            'data' => $resources,
            'meta' => ['count' => count($resources)]
        ];
    }

    /**
     * PUT /api/calendar/appointment/{id}
     * Aggiorna un appuntamento
     */
    public function actionUpdateAppointment($id)
    {
        $appointment = Appointment::findOne($id);
        if (!$appointment) {
            throw new NotFoundHttpException('Appuntamento non trovato');
        }

        $data = Yii::$app->request->getBodyParams();
        
        // Validazione dati
        if (isset($data['appointment_datetime'])) {
            $appointment->appointment_datetime = $data['appointment_datetime'];
        }
        
        if (isset($data['therapist_id'])) {
            // Verifica disponibilità terapista
            if (!$this->checkTherapistAvailability($data['therapist_id'], $appointment->appointment_datetime, $appointment->duration_minutes, $id)) {
                return [
                    'success' => false,
                    'error' => 'Terapista non disponibile in questo orario',
                    'code' => 'THERAPIST_NOT_AVAILABLE'
                ];
            }
            $appointment->therapist_id = $data['therapist_id'];
        }

        if (isset($data['duration_minutes'])) {
            $appointment->duration_minutes = $data['duration_minutes'];
        }

        if ($appointment->save()) {
            return [
                'success' => true,
                'data' => $appointment->toArray(),
                'message' => 'Appuntamento aggiornato con successo'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Errore nel salvataggio',
                'errors' => $appointment->errors
            ];
        }
    }

    /**
     * POST /api/calendar/appointment/{id}/attendance
     * Segna presenza/assenza
     */
    public function actionMarkAttendance($id)
    {
        $appointment = Appointment::findOne($id);
        if (!$appointment) {
            throw new NotFoundHttpException('Appuntamento non trovato');
        }

        $data = Yii::$app->request->getBodyParams();
        $status = $data['status'] ?? null;
        $reason = $data['reason'] ?? null;

        if (!in_array($status, ['completed', 'absent_justified', 'absent_not_justified'])) {
            throw new BadRequestHttpException('Status non valido');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $appointment->status = $status;
            $appointment->save();

            // Se assente, crea record assenza
            if (in_array($status, ['absent_justified', 'absent_not_justified'])) {
                $absence = new \common\models\Absence();
                $absence->appointment_id = $appointment->id;
                $absence->patient_id = $appointment->patient_id;
                $absence->absence_date = $appointment->appointment_datetime;
                $absence->reason = $reason;
                $absence->is_justified = ($status === 'absent_justified');
                $absence->is_communicated = true;
                $absence->communicated_by = Yii::$app->user->id;
                $absence->communicated_at = date('Y-m-d H:i:s');
                $absence->save();
            }

            $transaction->commit();
            
            return [
                'success' => true,
                'data' => $appointment->toArray(),
                'message' => 'Presenza aggiornata con successo'
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return [
                'success' => false,
                'error' => 'Errore nel salvataggio: ' . $e->getMessage()
            ];
        }
    }

    /**
     * GET /api/calendar/available-slots
     * Recupera slot disponibili per recuperi
     */
    public function actionAvailableSlots()
    {
        $request = Yii::$app->request;
        $specializationId = $request->get('specialization_id');
        $date = $request->get('date');
        $duration = $request->get('duration', 60);

        if (!$specializationId || !$date) {
            throw new BadRequestHttpException('Parametri specialization_id e date sono obbligatori');
        }

        // Trova terapisti disponibili
        $therapists = Therapist::find()
            ->where(['specialization_id' => $specializationId, 'is_active' => true])
            ->all();

        $availableSlots = [];
        
        foreach ($therapists as $therapist) {
            $slots = $this->getAvailableSlots($therapist->id, $date, $duration);
            foreach ($slots as $slot) {
                $availableSlots[] = [
                    'therapist_id' => $therapist->id,
                    'therapist_name' => $therapist->user->userProfile->first_name . ' ' . $therapist->user->userProfile->last_name,
                    'datetime' => $slot,
                    'duration' => $duration,
                ];
            }
        }

        return [
            'success' => true,
            'data' => $availableSlots,
            'meta' => ['date' => $date, 'specialization_id' => $specializationId]
        ];
    }

    /**
     * Determina il colore dell'evento basato sullo status
     */
    private function getEventColor($status)
    {
        switch ($status) {
            case 'completed':
                return '#22c55e'; // Verde
            case 'absent_justified':
                return '#f59e0b'; // Arancione
            case 'absent_not_justified':
                return '#ef4444'; // Rosso
            case 'cancelled':
                return '#6b7280'; // Grigio
            default: // scheduled
                return '#3b82f6'; // Blu
        }
    }

    /**
     * Verifica disponibilità terapista
     */
    private function checkTherapistAvailability($therapistId, $datetime, $duration, $excludeId = null)
    {
        $endTime = date('Y-m-d H:i:s', strtotime($datetime . ' +' . $duration . ' minutes'));
        
        $query = Appointment::find()
            ->where(['therapist_id' => $therapistId])
            ->andWhere(['status' => 'scheduled'])
            ->andWhere([
                'or',
                ['between', 'appointment_datetime', $datetime, $endTime],
                ['between', 'DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)', $datetime, $endTime],
                ['and', 
                    ['<=', 'appointment_datetime', $datetime],
                    ['>=', 'DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)', $endTime]
                ]
            ]);

        if ($excludeId) {
            $query->andWhere(['!=', 'id', $excludeId]);
        }

        return !$query->exists();
    }

    /**
     * Recupera slot disponibili per un terapista in una data
     */
    private function getAvailableSlots($therapistId, $date, $duration)
    {
        $slots = [];
        $startHour = 8; // 8:00
        $endHour = 19; // 19:00
        $slotDuration = 30; // 30 minuti

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += $slotDuration) {
                $slotTime = sprintf('%02d:%02d:00', $hour, $minute);
                $datetime = $date . ' ' . $slotTime;
                
                if ($this->checkTherapistAvailability($therapistId, $datetime, $duration)) {
                    $slots[] = $datetime;
                }
            }
        }

        return $slots;
    }
} 
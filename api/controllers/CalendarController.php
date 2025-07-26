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
use yii\filters\ContentNegotiator;
use common\helpers\NotificationHelper;
use common\models\Notification;

class CalendarController extends ActiveController
{
    public $modelClass = 'common\models\Appointment';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Aggiungi il JwtAuthBehavior per proteggere le azioni del controller
        $behaviors['jwt'] = [
            'class' => 'common\components\JwtAuthBehavior',
            'excludeActions' => [], // Tutte le azioni richiedono autenticazione
        ];
        
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
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
     * POST /api/calendar/patient-appointments
     * Recupera appuntamenti di un paziente per una data specifica
     * 
     * Body:
     * {
     *   "patient_id": 123,
     *   "date": "2024-01-15"
     * }
     */
    /**
 * POST /api/calendar/patient-appointments
 * Recupera appuntamenti di un paziente per una data specifica
 * 
 * Body:
 * {
 *   "patient_id": 123,
 *   "date": "2024-01-15"
 * }
 */
public function actionPatientAppointments()
{
    $request = Yii::$app->request;
    $data = $request->getBodyParams();
    
    $patientId = $data['patient_id'] ?? null;
    $date = $data['date'] ?? null;

    if (!$patientId || !$date) {
        throw new BadRequestHttpException('Parametri patient_id e date sono obbligatori');
    }

    try {
        // Verifica che la data sia valida
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
            throw new BadRequestHttpException('Formato data non valido. Utilizzare YYYY-MM-DD');
        }

        // Metodo più efficiente usando ActiveRecord relationships
        $appointments = $this->getPatientAppointmentsOptimized($patientId, $date);

        // Formatta i dati per l'app mobile
        $formattedAppointments = [];
        foreach ($appointments as $appointment) {
            $datetime = new \DateTime($appointment->appointment_datetime);
            
            // Determina il tipo di trattamento
            $treatmentName = 'Terapia';
            $treatmentCode = null;
            
            // Determina il tipo di trattamento
            $treatmentName = 'Terapia';
            $treatmentCode = null;
            
            if ($appointment->appointment_type === 'private' && $appointment->treatmentType) {
                // Per appuntamenti privati usa direttamente treatment_type_id
                $treatmentName = $appointment->treatmentType->name;
                $treatmentCode = $appointment->treatmentType->code;
            } elseif ($appointment->planTherapy && $appointment->planTherapy->treatmentType) {
                // Per appuntamenti normali usa planTherapy
                $treatmentName = $appointment->planTherapy->treatmentType->name;
                $treatmentCode = $appointment->planTherapy->treatmentType->code;
            }
            
            // Controlli di sicurezza per evitare errori su oggetti null
            $therapistName = 'Terapista non disponibile';
            $therapistFirstName = '';
            $therapistLastName = '';
            $therapistSpecialization = null;
            
            if ($appointment->therapist && $appointment->therapist->user && $appointment->therapist->user->profile) {
                $therapistFirstName = $appointment->therapist->user->profile->first_name ?? '';
                $therapistLastName = $appointment->therapist->user->profile->last_name ?? '';
                $therapistName = trim($therapistFirstName . ' ' . $therapistLastName) ?: 'Terapista non disponibile';
            }
            
            if ($appointment->therapist && $appointment->therapist->specialization) {
                $therapistSpecialization = $appointment->therapist->specialization->name ?? null;
            }
            
            $patientName = 'Paziente non disponibile';
            $patientFirstName = '';
            $patientLastName = '';
            
            if ($appointment->patient) {
                $patientFirstName = $appointment->patient->first_name ?? '';
                $patientLastName = $appointment->patient->last_name ?? '';
                $patientName = trim($patientFirstName . ' ' . $patientLastName) ?: 'Paziente non disponibile';
            }
            
            $formattedAppointments[] = [
                'id' => $appointment->id,
                'date' => $date,
                'time' => $datetime->format('H:i'),
                'datetime' => $appointment->appointment_datetime,
                'duration_minutes' => $appointment->duration_minutes,
                'status' => $this->mapStatusToApp($appointment->status),
                'type' => $treatmentName,
                'appointment_type' => $appointment->appointment_type,
                'treatment_code' => $treatmentCode,
                'notes' => $appointment->notes,
                'location' => 'Centro Terapeutico',
                'therapist' => [
                    'id' => $appointment->therapist->id ?? null,
                    'name' => $therapistName,
                    'first_name' => $therapistFirstName,
                    'last_name' => $therapistLastName,
                    'specialization' => $therapistSpecialization,
                    'avatar' => $this->getTherapistAvatar($appointment->therapist->id ?? null),
                ],
                'patient' => [
                    'name' => $patientName,
                    'first_name' => $patientFirstName,
                    'last_name' => $patientLastName,
                ]
            ];
        }

        return [
            'success' => true,
            'data' => $formattedAppointments,
            'meta' => [
                'patient_id' => $patientId,
                'date' => $date,
                'count' => count($formattedAppointments)
            ]
        ];

    } catch (\Exception $e) {
        Yii::error('Errore recupero appuntamenti paziente: ' . $e->getMessage(), __METHOD__);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

    /**
     * POST /api/calendar/patient-marked-dates
     * Recupera i giorni del mese che hanno appuntamenti per evidenziarli nel calendario
     * 
     * Body:
     * {
     *   "patient_id": 123,
     *   "month": "2024-01"
     * }
     */
    public function actionPatientMarkedDates()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();
        
        $patientId = $data['patient_id'] ?? null;
        $month = $data['month'] ?? null;

        if (!$patientId || !$month) {
            throw new BadRequestHttpException('Parametri patient_id e month sono obbligatori');
        }

        try {
            // Verifica formato mese (YYYY-MM)
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                throw new BadRequestHttpException('Formato month non valido. Utilizzare YYYY-MM');
            }

            // Calcola primo e ultimo giorno del mese
            $firstDay = $month . '-01';
            $lastDay = date('Y-m-t', strtotime($firstDay));

            // Metodo più efficiente usando ActiveRecord relationships
            $markedDates = $this->getPatientMarkedDatesOptimized($patientId, $firstDay, $lastDay);

            return [
                'success' => true,
                'data' => $markedDates,
                'meta' => [
                    'patient_id' => $patientId,
                    'month' => $month,
                    'total_days_with_appointments' => count($markedDates)
                ]
            ];

        } catch (\Exception $e) {
            Yii::error('Errore recupero date marcate paziente: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * POST /api/calendar/cancel-appointment
     * Cancella un appuntamento del paziente con motivo e note
     * 
     * Body:
     * {
     *   "appointment_id": 123,
     *   "reason": "Motivo della cancellazione",
     *   "notes": "Note aggiuntive (opzionale)"
     * }
     */
    public function actionCancelAppointment()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();
        
        $appointmentId = $data['appointment_id'] ?? null;
        $reason = $data['reason'] ?? null;
        $notes = $data['notes'] ?? '';

        if (!$appointmentId || !$reason) {
            throw new BadRequestHttpException('Parametri appointment_id e reason sono obbligatori');
        }

        try {
            // Trova l'appuntamento
            $appointment = Appointment::findOne($appointmentId);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }

            // Verifica che l'appuntamento possa essere cancellato
            if ($appointment->status !== Appointment::STATUS_SCHEDULED) {
                throw new BadRequestHttpException('Solo gli appuntamenti confermati possono essere cancellati');
            }

            // Verifica che l'appuntamento non sia nel passato
            $appointmentDateTime = new \DateTime($appointment->appointment_datetime);
            $now = new \DateTime();
            if ($appointmentDateTime <= $now) {
                throw new BadRequestHttpException('Non è possibile cancellare appuntamenti passati');
            }

            $transaction = Yii::$app->db->beginTransaction();

            try {
                // Aggiorna lo stato dell'appuntamento
                $appointment->status = Appointment::STATUS_ABSENT_JUSTIFIED;
                
                // Aggiungi le note sulla cancellazione
                $cancellationNote = "CANCELLATO DAL PAZIENTE - Motivo: {$reason}";
                if (!empty($notes)) {
                    $cancellationNote .= " - Note: {$notes}";
                }
                $cancellationNote .= " - Data cancellazione: " . date('Y-m-d H:i:s');
                
                // Aggiungi la nota di cancellazione alle note esistenti
                if (!empty($appointment->notes)) {
                    $appointment->notes .= "\n\n" . $cancellationNote;
                } else {
                    $appointment->notes = $cancellationNote;
                }

                if (!$appointment->save()) {
                    throw new \Exception('Errore nel salvataggio dell\'appuntamento: ' . json_encode($appointment->errors));
                }

                // Invia notifiche di cancellazione
                $this->sendCancellationNotifications($appointment, $reason, $notes);

                $transaction->commit();

                Yii::info("Appuntamento {$appointmentId} cancellato dal paziente. Motivo: {$reason}", __METHOD__);

                return [
                    'success' => true,
                    'message' => 'Appuntamento cancellato con successo',
                    'data' => [
                        'appointment_id' => $appointment->id,
                        'new_status' => $this->mapStatusToApp($appointment->status),
                        'reason' => $reason,
                        'notes' => $notes,
                        'cancelled_at' => date('Y-m-d H:i:s')
                    ]
                ];

            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Yii::error('Errore cancellazione appuntamento: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * POST /api/calendar/therapist-appointments
     * Recupera appuntamenti del terapista autenticato per una data specifica
     * 
     * Body:
     * {
     *   "date": "2024-01-15"
     * }
     */
    /**
 * POST /api/calendar/therapist-appointments
 * Recupera appuntamenti del terapista autenticato per una data specifica
 * 
 * Body:
 * {
 *   "date": "2024-01-15"
 * }
 */
public function actionTherapistAppointments()
{
    $request = Yii::$app->request;
    $data = $request->getBodyParams();
    
    $date = $data['date'] ?? null;

    if (!$date) {
        throw new BadRequestHttpException('Parametro date è obbligatorio');
    }

    // Recupera il terapista dall'utente autenticato
    $therapistId = $this->getAuthenticatedTherapistId();
    if (!$therapistId) {
        throw new BadRequestHttpException('Utente non associato a nessun terapista');
    }

    try {
        // Verifica che la data sia valida
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
            throw new BadRequestHttpException('Formato data non valido. Utilizzare YYYY-MM-DD');
        }

        // Recupera appuntamenti del terapista
        $appointments = $this->getTherapistAppointmentsOptimized($therapistId, $date);

        // Formatta i dati per l'app mobile
        $formattedAppointments = [];
        foreach ($appointments as $appointment) {
            $datetime = new \DateTime($appointment->appointment_datetime);
            
            // Determina il tipo di trattamento
            $treatmentName = 'Terapia';
            $treatmentCode = null;
            
            if ($appointment->appointment_type === 'private') {
                $treatmentName = 'Appuntamento Privato';
            } elseif ($appointment->planTherapy && $appointment->planTherapy->treatmentType) {
                $treatmentName = $appointment->planTherapy->treatmentType->name;
                $treatmentCode = $appointment->planTherapy->treatmentType->code;
            }
            
            $formattedAppointments[] = [
                'id' => $appointment->id,
                'date' => $date,
                'time' => $datetime->format('H:i'),
                'datetime' => $appointment->appointment_datetime,
                'duration_minutes' => $appointment->duration_minutes,
                'status' => $this->mapStatusToApp($appointment->status),
                'type' => $treatmentName,
                'appointment_type' => $appointment->appointment_type,
                'treatment_code' => $treatmentCode,
                'notes' => $appointment->notes,
                'location' => 'Centro Terapeutico',
                'patient' => [
                    'id' => $appointment->patient->id,
                    'name' => $appointment->patient->first_name . ' ' . $appointment->patient->last_name,
                    'first_name' => $appointment->patient->first_name,
                    'last_name' => $appointment->patient->last_name,
                    'phone' => $appointment->patient->phone ?? null,
                    'avatar' => $this->getPatientAvatar($appointment->patient->id),
                ],
                'therapist' => [
                    'id' => $appointment->therapist->id,
                    'name' => $appointment->therapist->user->profile->first_name . ' ' . $appointment->therapist->user->profile->last_name,
                    'first_name' => $appointment->therapist->user->profile->first_name,
                    'last_name' => $appointment->therapist->user->profile->last_name,
                    'specialization' => $appointment->therapist->specialization->name ?? null,
                ]
            ];
        }

        return [
            'success' => true,
            'data' => $formattedAppointments,
            'meta' => [
                'date' => $date,
                'count' => count($formattedAppointments),
                'therapist_id' => $therapistId
            ]
        ];

    } catch (\Exception $e) {
        Yii::error('Errore recupero appuntamenti terapista: ' . $e->getMessage(), __METHOD__);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

    /**
     * POST /api/calendar/therapist-marked-dates
     * Recupera i giorni del mese che hanno appuntamenti per il terapista autenticato
     * 
     * Body:
     * {
     *   "month": "2024-01"
     * }
     */
    public function actionTherapistMarkedDates()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();
        
        $month = $data['month'] ?? null;

        if (!$month) {
            throw new BadRequestHttpException('Parametro month è obbligatorio');
        }

        // Recupera il terapista dall'utente autenticato
        $therapistId = $this->getAuthenticatedTherapistId();
        if (!$therapistId) {
            throw new BadRequestHttpException('Utente non associato a nessun terapista');
        }

        try {
            // Verifica formato mese (YYYY-MM)
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                throw new BadRequestHttpException('Formato month non valido. Utilizzare YYYY-MM');
            }

            // Calcola primo e ultimo giorno del mese
            $firstDay = $month . '-01';
            $lastDay = date('Y-m-t', strtotime($firstDay));

            // Recupera date marcate del terapista
            $markedDates = $this->getTherapistMarkedDatesOptimized($therapistId, $firstDay, $lastDay);

            return [
                'success' => true,
                'data' => $markedDates,
                'meta' => [
                    'month' => $month,
                    'total_days_with_appointments' => count($markedDates),
                    'therapist_id' => $therapistId // Per debug, ma non necessario
                ]
            ];

        } catch (\Exception $e) {
            Yii::error('Errore recupero date marcate terapista: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Mappa gli stati del database agli stati dell'app
     */
    private function mapStatusToApp($dbStatus)
    {
        $statusMap = [
            Appointment::STATUS_SCHEDULED => 'confermato',
            Appointment::STATUS_COMPLETED => 'completato',
            Appointment::STATUS_CANCELLED => 'annullato',
            Appointment::STATUS_ABSENT_JUSTIFIED => 'assente_giustificato',
            Appointment::STATUS_ABSENT_NOT_JUSTIFIED => 'assente_non_giustificato',
        ];

        return $statusMap[$dbStatus] ?? $dbStatus;
    }

    /**
     * Genera URL avatar placeholder per terapista
     */
    private function getTherapistAvatar($therapistId)
    {
        // Per ora usa un placeholder, in futuro potresti avere avatar reali
        return "https://ui-avatars.com/api/?name=Therapist&background=E3F2FD&color=1976D2&size=128";
    }

    /**
     * Metodo ottimizzato per recuperare appuntamenti del paziente usando ActiveRecord relationships
     */
    private function getPatientAppointmentsOptimized($patientId, $date)
    {
        return Appointment::find()
            ->joinWith([
                'planTherapy.therapeuticPlan.patient',
                'planTherapy.treatmentType',
                'therapist.user.profile',
                'therapist.specialization'
            ])
            ->where(['patients.id' => $patientId])
            ->andWhere(['DATE(appointments.appointment_datetime)' => $date])
            ->andWhere(['!=', 'appointments.status', Appointment::STATUS_CANCELLED])
            ->andWhere(['IS NOT', 'patients.first_name', null])
            ->andWhere(['IS NOT', 'patients.last_name', null])
            ->andWhere(['IS NOT', 'user_profiles.first_name', null])
            ->andWhere(['IS NOT', 'user_profiles.last_name', null])
            ->orderBy('appointments.appointment_datetime ASC')
            ->all();
    }

    /**
     * Metodo ottimizzato per recuperare date marcate del paziente
     */
    private function getPatientMarkedDatesOptimized($patientId, $firstDay, $lastDay)
    {
        $results = Appointment::find()
            ->select([
                'DATE(appointments.appointment_datetime) as appointment_date',
                'COUNT(*) as appointment_count'
            ])
            ->joinWith('planTherapy.therapeuticPlan.patient')
            ->where(['patients.id' => $patientId])
            ->andWhere(['between', 'DATE(appointments.appointment_datetime)', $firstDay, $lastDay])
            ->andWhere(['!=', 'appointments.status', Appointment::STATUS_CANCELLED])
            ->groupBy('DATE(appointments.appointment_datetime)')
            ->orderBy('appointment_date ASC')
            ->asArray()
            ->all();

        // Formatta per react-native-calendars
        $markedDates = [];
        foreach ($results as $result) {
            $date = $result['appointment_date'];
            $count = $result['appointment_count'];
            
            $markedDates[$date] = [
                'marked' => true,
                'dotColor' => '#007AFF', // Colore del punto
                'activeOpacity' => 0.5,
                'appointment_count' => $count,
                'customStyles' => [
                    'container' => [
                        'backgroundColor' => '#E3F2FD',
                        'borderRadius' => 8
                    ],
                    'text' => [
                        'color' => '#1976D2',
                        'fontWeight' => 'bold'
                    ]
                ]
            ];
        }

        return $markedDates;
    }

    /**
     * Metodo ottimizzato per recuperare appuntamenti del terapista usando ActiveRecord relationships
     */
    private function getTherapistAppointmentsOptimized($therapistId, $date)
    {
        return Appointment::find()
            ->joinWith([
                'planTherapy.therapeuticPlan.patient',
                'planTherapy.treatmentType',
                'therapist.user.profile',
                'therapist.specialization'
            ])
            ->where(['appointments.therapist_id' => $therapistId])
            ->andWhere(['DATE(appointments.appointment_datetime)' => $date])
            ->andWhere(['!=', 'appointments.status', Appointment::STATUS_CANCELLED])
            ->orderBy('appointments.appointment_datetime ASC')
            ->all();
    }

    /**
     * Metodo ottimizzato per recuperare date marcate del terapista
     */
    private function getTherapistMarkedDatesOptimized($therapistId, $firstDay, $lastDay)
    {
        $results = Appointment::find()
            ->select([
                'DATE(appointments.appointment_datetime) as appointment_date',
                'COUNT(*) as appointment_count'
            ])
            ->where(['appointments.therapist_id' => $therapistId])
            ->andWhere(['between', 'DATE(appointments.appointment_datetime)', $firstDay, $lastDay])
            ->andWhere(['!=', 'appointments.status', Appointment::STATUS_CANCELLED])
            ->groupBy('DATE(appointments.appointment_datetime)')
            ->orderBy('appointment_date ASC')
            ->asArray()
            ->all();

        // Formatta per react-native-calendars
        $markedDates = [];
        foreach ($results as $result) {
            $date = $result['appointment_date'];
            $count = $result['appointment_count'];
            
            $markedDates[$date] = [
                'marked' => true,
                'dotColor' => '#007AFF', // Colore del punto
                'activeOpacity' => 0.5,
                'appointment_count' => $count,
                'customStyles' => [
                    'container' => [
                        'backgroundColor' => '#E3F2FD',
                        'borderRadius' => 8
                    ],
                    'text' => [
                        'color' => '#1976D2',
                        'fontWeight' => 'bold'
                    ]
                ]
            ];
        }

        return $markedDates;
    }

    /**
     * Genera URL avatar placeholder per paziente
     */
    private function getPatientAvatar($patientId)
    {
        // Per ora usa un placeholder, in futuro potresti avere avatar reali
        return "https://ui-avatars.com/api/?name=Patient&background=FFF3E0&color=F57C00&size=128";
    }

    /**
     * Recupera l'ID del terapista dall'utente autenticato
     */
    private function getAuthenticatedTherapistId()
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return null;
        }

        // Cerca il terapista associato all'utente
        $therapist = Therapist::findOne(['user_id' => $user->id]);
        return $therapist ? $therapist->id : null;
    }

    /**
     * POST /api/calendar/mark-patient-absent
     * Segna un paziente come assente da parte del terapista
     * 
     * Body:
     * {
     *   "appointment_id": 123,
     *   "absence_type": "justified|not_justified",
     *   "reason": "Motivo dell'assenza",
     *   "notes": "Note aggiuntive (opzionale)"
     * }
     */
    public function actionMarkPatientAbsent()
    {
        $request = Yii::$app->request;
        $data = $request->getBodyParams();
        
        $appointmentId = $data['appointment_id'] ?? null;
        $absenceType = $data['absence_type'] ?? null;
        $reason = $data['reason'] ?? null;
        $notes = $data['notes'] ?? '';

        if (!$appointmentId || !$absenceType || !$reason) {
            throw new BadRequestHttpException('Parametri appointment_id, absence_type e reason sono obbligatori');
        }

        if (!in_array($absenceType, ['justified', 'not_justified'])) {
            throw new BadRequestHttpException('absence_type deve essere "justified" o "not_justified"');
        }

        // Recupera il terapista dall'utente autenticato
        $therapistId = $this->getAuthenticatedTherapistId();
        if (!$therapistId) {
            throw new BadRequestHttpException('Utente non associato a nessun terapista');
        }

        try {
            // Trova l'appuntamento
            $appointment = Appointment::findOne($appointmentId);
            if (!$appointment) {
                throw new NotFoundHttpException('Appuntamento non trovato');
            }

            // Verifica che l'appuntamento appartenga al terapista autenticato
            if ($appointment->therapist_id != $therapistId) {
                throw new BadRequestHttpException('Non sei autorizzato a modificare questo appuntamento');
            }

            // Verifica che l'appuntamento possa essere segnato come assente
            if ($appointment->status !== Appointment::STATUS_SCHEDULED) {
                throw new BadRequestHttpException('Solo gli appuntamenti confermati possono essere segnati come assenti');
            }

            // Verifica che l'appuntamento non sia nel futuro lontano
            $appointmentDateTime = new \DateTime($appointment->appointment_datetime);
            $now = new \DateTime();
            if ($appointmentDateTime > $now->modify('+1 day')) {
                throw new BadRequestHttpException('Non è possibile segnare come assente un appuntamento troppo in anticipo');
            }

            $transaction = Yii::$app->db->beginTransaction();

            try {
                // Aggiorna lo stato dell'appuntamento
                $appointment->status = $absenceType === 'justified' ? 
                    Appointment::STATUS_ABSENT_JUSTIFIED : 
                    Appointment::STATUS_ABSENT_NOT_JUSTIFIED;
                
                // Aggiungi le note sull'assenza
                $absenceNote = "ASSENZA SEGNALATA DAL TERAPISTA - Tipo: " . 
                    ($absenceType === 'justified' ? 'Giustificata' : 'Non Giustificata') . 
                    " - Motivo: {$reason}";
                if (!empty($notes)) {
                    $absenceNote .= " - Note: {$notes}";
                }
                $absenceNote .= " - Data segnalazione: " . date('Y-m-d H:i:s');
                
                // Aggiungi la nota di assenza alle note esistenti
                if (!empty($appointment->notes)) {
                    $appointment->notes .= "\n\n" . $absenceNote;
                } else {
                    $appointment->notes = $absenceNote;
                }

                if (!$appointment->save()) {
                    throw new \Exception('Errore nel salvataggio dell\'appuntamento: ' . json_encode($appointment->errors));
                }

                // Invia notifiche di assenza
                $this->sendAbsenceNotifications($appointment, $absenceType, $reason, $notes);

                $transaction->commit();

                Yii::info("Appuntamento {$appointmentId} segnato come assente dal terapista {$therapistId}. Tipo: {$absenceType}, Motivo: {$reason}", __METHOD__);

                return [
                    'success' => true,
                    'message' => 'Paziente segnato come assente con successo',
                    'data' => [
                        'appointment_id' => $appointment->id,
                        'new_status' => $this->mapStatusToApp($appointment->status),
                        'absence_type' => $absenceType,
                        'reason' => $reason,
                        'notes' => $notes,
                        'marked_at' => date('Y-m-d H:i:s')
                    ]
                ];

            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Yii::error('Errore segnalazione assenza: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Invia notifiche di cancellazione al manager e al terapista
     * 
     * @param Appointment $appointment
     * @param string $reason
     * @param string $notes
     */
    private function sendCancellationNotifications($appointment, $reason, $notes)
    {
        try {
            // Carica le relazioni necessarie
            $patient = $appointment->planTherapy->therapeuticPlan->patient;
            $therapist = $appointment->therapist;
            $appointmentDateTime = new \DateTime($appointment->appointment_datetime);
            
            // Prepara i dati comuni per le notifiche
            $patientName = $patient->first_name . ' ' . $patient->last_name;
            $therapistName = $therapist->user->profile->first_name . ' ' . $therapist->user->profile->last_name;
            $appointmentDate = $appointmentDateTime->format('d/m/Y');
            $appointmentTime = $appointmentDateTime->format('H:i');
            
            // Notifica al manager
            $managerTitle = "Appuntamento Cancellato dal Paziente";
            $managerMessage = "Il paziente {$patientName} ha cancellato l'appuntamento del {$appointmentDate} alle {$appointmentTime} con il terapista {$therapistName}.\n\n";
            $managerMessage .= "Motivo: {$reason}";
            if (!empty($notes)) {
                $managerMessage .= "\nNote: {$notes}";
            }
            
                         NotificationHelper::sendToManagers(
                $managerTitle,
                $managerMessage,
                Notification::TYPE_INFO,
                [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $patient->id,
                    'therapist_id' => $therapist->id,
                    'cancellation_reason' => $reason,
                    'appointment_date' => $appointmentDate,
                    'appointment_time' => $appointmentTime,
                    'type' => 'appointment_cancellation'
                ]
            );

            // Notifica al terapista
            $therapistTitle = "Appuntamento Cancellato";
            $therapistMessage = "Il tuo appuntamento con {$patientName} del {$appointmentDate} alle {$appointmentTime} è stato cancellato dal paziente.\n\n";
            $therapistMessage .= "Motivo: {$reason}";
            if (!empty($notes)) {
                $therapistMessage .= "\nNote: {$notes}";
            }
            
                         NotificationHelper::sendToUsers(
                [$therapist->user_id],
                $therapistTitle,
                $therapistMessage,
                Notification::TYPE_INFO,
                [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $patient->id,
                    'patient_name' => $patientName,
                    'cancellation_reason' => $reason,
                    'appointment_date' => $appointmentDate,
                    'appointment_time' => $appointmentTime,
                    'type' => 'appointment_cancellation'
                ]
            );

            Yii::info("Notifiche di cancellazione inviate per appuntamento {$appointment->id}", __METHOD__);

        } catch (\Exception $e) {
            // Non bloccare l'operazione se l'invio delle notifiche fallisce
            Yii::error("Errore invio notifiche cancellazione appuntamento {$appointment->id}: " . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Invia notifiche di assenza al manager e al paziente
     * 
     * @param Appointment $appointment
     * @param string $absenceType
     * @param string $reason
     * @param string $notes
     */
    private function sendAbsenceNotifications($appointment, $absenceType, $reason, $notes)
    {
        try {
            // Carica le relazioni necessarie
            $patient = $appointment->planTherapy->therapeuticPlan->patient;
            $therapist = $appointment->therapist;
            $appointmentDateTime = new \DateTime($appointment->appointment_datetime);
            
            // Prepara i dati comuni per le notifiche
            $patientName = $patient->first_name . ' ' . $patient->last_name;
            $therapistName = $therapist->user->profile->first_name . ' ' . $therapist->user->profile->last_name;
            $appointmentDate = $appointmentDateTime->format('d/m/Y');
            $appointmentTime = $appointmentDateTime->format('H:i');
            $absenceTypeLabel = $absenceType === 'justified' ? 'Giustificata' : 'Non Giustificata';
            
            // Notifica al manager
            $managerTitle = "Paziente Segnalato Come Assente";
            $managerMessage = "Il terapista {$therapistName} ha segnalato il paziente {$patientName} come assente per l'appuntamento del {$appointmentDate} alle {$appointmentTime}.\n\n";
            $managerMessage .= "Tipo di assenza: {$absenceTypeLabel}\n";
            $managerMessage .= "Motivo: {$reason}";
            if (!empty($notes)) {
                $managerMessage .= "\nNote: {$notes}";
            }
            
                         NotificationHelper::sendToManagers(
                $managerTitle,
                $managerMessage,
                Notification::TYPE_INFO,
                [
                    'appointment_id' => $appointment->id,
                    'patient_id' => $patient->id,
                    'therapist_id' => $therapist->id,
                    'absence_type' => $absenceType,
                    'absence_reason' => $reason,
                    'appointment_date' => $appointmentDate,
                    'appointment_time' => $appointmentTime,
                    'type' => 'patient_absence_reported'
                ]
            );

            // Notifica al paziente (trova l'utente associato al paziente)
            $patientUsers = \common\models\User::find()
                ->joinWith('accountPatients')
                ->where(['account_patients.patient_id' => $patient->id])
                ->andWhere(['users.status' => \common\models\User::STATUS_ACTIVE])
                ->all();

            if (!empty($patientUsers)) {
                $patientTitle = "Assenza Segnalata";
                $patientMessage = "È stata segnalata un'assenza per il tuo appuntamento del {$appointmentDate} alle {$appointmentTime} con il terapista {$therapistName}.\n\n";
                $patientMessage .= "Tipo di assenza: {$absenceTypeLabel}\n";
                $patientMessage .= "Motivo: {$reason}";
                if (!empty($notes)) {
                    $patientMessage .= "\nNote: {$notes}";
                }
                $patientMessage .= "\n\nSe hai domande o contestazioni, contatta il centro.";
                
                // Invia notifica a tutti gli utenti associati al paziente
                $patientUserIds = [];
                foreach ($patientUsers as $patientUser) {
                    $patientUserIds[] = $patientUser->id;
                }
                
                             NotificationHelper::sendToUsers(
                    $patientUserIds,
                    $patientTitle,
                    $patientMessage,
                    Notification::TYPE_INFO,
                    [
                        'appointment_id' => $appointment->id,
                        'therapist_id' => $therapist->id,
                        'therapist_name' => $therapistName,
                        'absence_type' => $absenceType,
                        'absence_reason' => $reason,
                        'appointment_date' => $appointmentDate,
                        'appointment_time' => $appointmentTime,
                        'type' => 'patient_absence_reported'
                    ]
                );
            }

            Yii::info("Notifiche di assenza inviate per appuntamento {$appointment->id}", __METHOD__);

        } catch (\Exception $e) {
            // Non bloccare l'operazione se l'invio delle notifiche fallisce
            Yii::error("Errore invio notifiche assenza appuntamento {$appointment->id}: " . $e->getMessage(), __METHOD__);
        }
    }

    /* 
     * ========================================
     * FUNZIONI PRECEDENTI COMMENTATE
     * ========================================
     */

    /*
    /**
     * GET /api/calendar/appointments
     * Recupera appuntamenti in un range di date
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
    */

    /**
 * POST /api/calendar/complete-appointment
 * Segna un appuntamento come completato da parte del terapista
 * 
 * Body:
 * {
 *   "appointment_id": 123
 * }
 */
public function actionCompleteAppointment()
{
    $request = Yii::$app->request;
    $data = $request->getBodyParams();
    
    $appointmentId = $data['appointment_id'] ?? null;

    if (!$appointmentId) {
        throw new BadRequestHttpException('Parametro appointment_id è obbligatorio');
    }

    // Recupera il terapista dall'utente autenticato
    $therapistId = $this->getAuthenticatedTherapistId();
    if (!$therapistId) {
        throw new BadRequestHttpException('Utente non associato a nessun terapista');
    }

    try {
        // Trova l'appuntamento
        $appointment = Appointment::findOne($appointmentId);
        if (!$appointment) {
            throw new NotFoundHttpException('Appuntamento non trovato');
        }

        // Verifica che l'appuntamento appartenga al terapista autenticato
        if ($appointment->therapist_id != $therapistId) {
            throw new BadRequestHttpException('Non sei autorizzato a modificare questo appuntamento');
        }

        // Verifica che l'appuntamento sia in stato scheduled
        if ($appointment->status !== Appointment::STATUS_SCHEDULED) {
            throw new BadRequestHttpException('Solo gli appuntamenti confermati possono essere completati');
        }

        // Verifica che l'appuntamento non sia troppo nel passato
        $appointmentEndTime = new \DateTime($appointment->appointment_datetime);
        $appointmentEndTime->modify('+' . $appointment->duration_minutes . ' minutes');
        $now = new \DateTime();
        
        // Se sono passati più di 15 minuti dalla fine dell'appuntamento, non può essere completato manualmente
        $fifteenMinutesAfterEnd = clone $appointmentEndTime;
        $fifteenMinutesAfterEnd->modify('+15 minutes');
        
        if ($now > $fifteenMinutesAfterEnd) {
            throw new BadRequestHttpException('Non è possibile completare un appuntamento terminato da più di 15 minuti');
        }

        // Verifica che l'appuntamento sia già iniziato
        $appointmentStartTime = new \DateTime($appointment->appointment_datetime);
        if ($now < $appointmentStartTime) {
            throw new BadRequestHttpException('Non è possibile completare un appuntamento non ancora iniziato');
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Aggiorna lo stato dell'appuntamento
            $appointment->status = Appointment::STATUS_COMPLETED;
            
            // Aggiungi una nota di completamento
            $completionNote = "COMPLETATO MANUALMENTE DAL TERAPISTA - Data completamento: " . date('Y-m-d H:i:s');
            
            if (!empty($appointment->notes)) {
                $appointment->notes .= "\n\n" . $completionNote;
            } else {
                $appointment->notes = $completionNote;
            }

            if (!$appointment->save()) {
                throw new \Exception('Errore nel salvataggio dell\'appuntamento: ' . json_encode($appointment->errors));
            }

            $transaction->commit();

            Yii::info("Appuntamento {$appointmentId} completato manualmente dal terapista {$therapistId}", __METHOD__);

            return [
                'success' => true,
                'message' => 'Appuntamento completato con successo',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'new_status' => $this->mapStatusToApp($appointment->status),
                    'completed_at' => date('Y-m-d H:i:s')
                ]
            ];

        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

    } catch (\Exception $e) {
        Yii::error('Errore completamento appuntamento: ' . $e->getMessage(), __METHOD__);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
} 
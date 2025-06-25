<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "document_requests".
 *
 * @property int $id
 * @property int $patient_id
 * @property int $request_type_id
 * @property int $requested_by_account_patient_id
 * @property string $status
 * @property string|null $reason
 * @property string|null $notes
 * @property string|null $date_from
 * @property string|null $date_to
 * @property string $estimated_completion
 * @property string|null $completed_at
 * @property string|null $delivered_at
 * @property string|null $rejected_at
 * @property string|null $rejection_reason
 * @property string|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Patient $patient
 * @property RequestType $requestType
 * @property AccountPatient $requestedByAccountPatient
 */
class DocumentRequest extends ActiveRecord
{
    // Status constants per nuovo workflow
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_PROCESSING = 'processing';
    const STATUS_READY = 'ready';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%document_requests}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => function() {
                    return (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['patient_id', 'request_type_id', 'requested_by_account_patient_id'], 'required'],
            [['patient_id', 'request_type_id', 'requested_by_account_patient_id'], 'integer'],
            [['reason', 'notes', 'rejection_reason', 'cancellation_reason'], 'string'],
            [['reason'], 'string', 'max' => 1000],
            [['notes'], 'string', 'max' => 2000],
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
            [['estimated_completion', 'completed_at', 'delivered_at', 'rejected_at', 'cancelled_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['estimated_completion'], 'required'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING, 
                self::STATUS_REJECTED, 
                self::STATUS_ACCEPTED, 
                self::STATUS_PROCESSING, 
                self::STATUS_READY, 
                self::STATUS_DELIVERED, 
                self::STATUS_CANCELLED
            ]],
            [['patient_id'], 'exist', 'skipOnError' => true, 'targetClass' => Patient::class, 'targetAttribute' => ['patient_id' => 'id']],
            [['request_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => RequestType::class, 'targetAttribute' => ['request_type_id' => 'id']],
            [['requested_by_account_patient_id'], 'exist', 'skipOnError' => true, 'targetClass' => AccountPatient::class, 'targetAttribute' => ['requested_by_account_patient_id' => 'id']],
            
            // Validazione date range
            ['date_to', 'validateDateRange'],
        ];
    }

    /**
     * Validazione range date
     */
    public function validateDateRange($attribute, $params)
    {
        if (!empty($this->date_from) && !empty($this->date_to)) {
            $dateFrom = \DateTime::createFromFormat('Y-m-d', $this->date_from, new \DateTimeZone('UTC'));
            $dateTo = \DateTime::createFromFormat('Y-m-d', $this->date_to, new \DateTimeZone('UTC'));
            
            if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
                $this->addError($attribute, 'La data di fine deve essere successiva alla data di inizio.');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'patient_id' => 'Paziente',
            'request_type_id' => 'Tipo Richiesta',
            'requested_by_account_patient_id' => 'Richiesto da',
            'status' => 'Stato',
            'reason' => 'Motivo',
            'notes' => 'Note',
            'date_from' => 'Data Inizio',
            'date_to' => 'Data Fine',
            'estimated_completion' => 'Completamento Stimato',
            'completed_at' => 'Completato il',
            'delivered_at' => 'Consegnato il',
            'rejected_at' => 'Rifiutato il',
            'rejection_reason' => 'Motivo Rifiuto',
            'cancelled_at' => 'Cancellato il',
            'cancellation_reason' => 'Motivo Cancellazione',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * Gets query for [[Patient]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPatient()
    {
        return $this->hasOne(Patient::class, ['id' => 'patient_id']);
    }

    /**
     * Gets query for [[RequestType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequestType()
    {
        return $this->hasOne(RequestType::class, ['id' => 'request_type_id']);
    }

    /**
     * Gets query for [[RequestedByAccountPatient]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequestedByAccountPatient()
    {
        return $this->hasOne(AccountPatient::class, ['id' => 'requested_by_account_patient_id']);
    }

    /**
     * Gets status labels
     *
     * @return array
     */
    public static function getStatusLabels()
    {
        return [
            self::STATUS_PENDING => 'In Attesa',
            self::STATUS_REJECTED => 'Rifiutata',
            self::STATUS_ACCEPTED => 'Accettata',
            self::STATUS_PROCESSING => 'In Elaborazione',
            self::STATUS_READY => 'Pronta',
            self::STATUS_DELIVERED => 'Consegnata',
            self::STATUS_CANCELLED => 'Cancellata',
        ];
    }

    /**
     * Gets status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = static::getStatusLabels();
        return $labels[$this->status] ?? $this->status;
    }

    // ===== METODI DI STATO =====

    /**
     * Checks if request is pending
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Checks if request is rejected
     *
     * @return bool
     */
    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Checks if request is accepted
     *
     * @return bool
     */
    public function isAccepted()
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Checks if request is processing
     *
     * @return bool
     */
    public function isProcessing()
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Checks if request is ready
     *
     * @return bool
     */
    public function isReady()
    {
        return $this->status === self::STATUS_READY;
    }

    /**
     * Checks if request is delivered
     *
     * @return bool
     */
    public function isDelivered()
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Checks if request is cancelled
     *
     * @return bool
     */
    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Checks if request can be cancelled (only if pending)
     *
     * @return bool
     */
    public function canBeCancelled()
    {
        return $this->isPending();
    }

    // ===== METODI DI TRANSIZIONE STATO =====

    /**
     * Rejects request
     *
     * @param string $reason
     * @return bool
     */
    public function reject($reason)
    {
        $this->status = self::STATUS_REJECTED;
        $this->rejection_reason = $reason;
        $this->rejected_at = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        
        return $this->save();
    }

    /**
     * Accepts request (takes it in charge)
     *
     * @return bool
     */
    public function accept()
    {
        $this->status = self::STATUS_ACCEPTED;
        
        return $this->save();
    }

    /**
     * Marks request as processing
     *
     * @return bool
     */
    public function markAsProcessing()
    {
        $this->status = self::STATUS_PROCESSING;
        
        return $this->save();
    }

    /**
     * Marks request as ready (completed)
     *
     * @return bool
     */
    public function markAsReady()
    {
        $this->status = self::STATUS_READY;
        $this->completed_at = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        
        return $this->save();
    }

    /**
     * Marks request as delivered
     *
     * @return bool
     */
    public function markAsDelivered()
    {
        $this->status = self::STATUS_DELIVERED;
        $this->delivered_at = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        
        return $this->save();
    }

    /**
     * Cancels request
     *
     * @param string $reason
     * @return bool
     */
    public function cancel($reason = null)
    {
        if (!$this->canBeCancelled()) {
            return false;
        }
        
        $this->status = self::STATUS_CANCELLED;
        $this->cancellation_reason = $reason;
        $this->cancelled_at = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        
        return $this->save();
    }

    // ===== METODI QUERY =====

    /**
     * Scope per trovare richieste attive (non consegnate, rifiutate o cancellate)
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findActive()
    {
        return static::find()->where(['not in', 'status', [
            self::STATUS_DELIVERED, 
            self::STATUS_REJECTED, 
            self::STATUS_CANCELLED
        ]]);
    }

    /**
     * Gets pending requests
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findPending()
    {
        return static::find()
            ->where(['status' => self::STATUS_PENDING])
            ->orderBy(['created_at' => SORT_ASC]);
    }

    /**
     * Gets requests by patient
     *
     * @param int $patientId
     * @return \yii\db\ActiveQuery
     */
    public static function findByPatient($patientId)
    {
        return static::find()
            ->where(['patient_id' => $patientId])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Gets requests by account patient (richiedente)
     *
     * @param int $accountPatientId
     * @return \yii\db\ActiveQuery
     */
    public static function findByAccountPatient($accountPatientId)
    {
        return static::find()
            ->where(['requested_by_account_patient_id' => $accountPatientId])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Gets overdue requests (estimated_completion passed)
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findOverdue()
    {
        return static::findActive()
            ->andWhere(['<', 'estimated_completion', (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')]);
    }

    // ===== METODI PER API =====

    /**
     * Calcola estimated_completion basato su request_type estimated_days
     * Aggiunge solo giorni lavorativi (lunedì-venerdì)
     *
     * @return string
     */
    public function calculateEstimatedCompletion()
    {
        // Carica il RequestType se non già caricato
        if (!$this->requestType) {
            $this->refresh();
        }
        
        $days = $this->requestType ? $this->requestType->estimated_days : 7; // Default 7 giorni
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $addedDays = 0;

        // Aggiungi solo giorni lavorativi
        while ($addedDays < $days) {
            $date->add(new \DateInterval('P1D')); // Aggiungi un giorno
            
            // Se è un giorno lavorativo (lunedì-venerdì), conta
            if ($date->format('N') <= 5) {
                $addedDays++;
            }
        }

        // Imposta l'ora a fine giornata lavorativa (18:00 UTC)
        $date->setTime(18, 0, 0);
        
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Formatta per API response
     *
     * @return array
     */
    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'request_type_id' => $this->request_type_id,
            'request_type_name' => $this->requestType ? $this->requestType->name : null,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'reason' => $this->reason,
            'notes' => $this->notes,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'estimated_completion' => $this->estimated_completion ? (new \DateTime($this->estimated_completion, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z') : null,
            'completed_at' => $this->completed_at ? (new \DateTime($this->completed_at, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z') : null,
            'delivered_at' => $this->delivered_at ? (new \DateTime($this->delivered_at, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z') : null,
            'rejected_at' => $this->rejected_at ? (new \DateTime($this->rejected_at, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z') : null,
            'rejection_reason' => $this->rejection_reason,
            'cancelled_at' => $this->cancelled_at ? (new \DateTime($this->cancelled_at, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z') : null,
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => (new \DateTime($this->created_at, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
            'updated_at' => (new \DateTime($this->updated_at, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
            'can_be_cancelled' => $this->canBeCancelled(),
        ];
    }
} 
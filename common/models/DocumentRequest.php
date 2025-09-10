<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "document_requests".
 *
 * @property int $id
 * @property int $account_patient_id
 * @property int $patient_id
 * @property int $request_type_id
 * @property int|null $therapeutic_plan_id
 * @property int|null $therapy_id
 * @property string|null $notes
 * @property int $status
 * @property string $created_at
 *
 * @property AccountPatient $accountPatient
 * @property Patient $patient
 * @property RequestType $requestType
 * @property TherapeuticPlan $therapeuticPlan
 * @property PlanTherapy $therapy
 * @property RequestStatus $requestStatus
 */
class DocumentRequest extends ActiveRecord
{
    // Status constants basati sulla tabella request_statuses
    const STATUS_INVIATA = 1;         // "Inviata"
    const STATUS_PRESA_IN_CARICO = 2; // "Presa in carico"
    const STATUS_STAMPATO = 3;        // "Stampato" 
    const STATUS_CONSEGNATO = 4;      // "Consegnato"

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
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false, // Non esiste updated_at nella nuova tabella
                'value' => function() {
                    return (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
                },
            ],
            [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => ['created_at'],
                'entityNameCallback' => function($model) {
                    return 'RichiestaDocumento';
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
            [['account_patient_id', 'patient_id', 'request_type_id', 'status'], 'required'],
            [['account_patient_id', 'patient_id', 'request_type_id', 'therapeutic_plan_id', 'therapy_id', 'status'], 'integer'],
            [['notes'], 'string', 'max' => 2000],
            [['status'], 'in', 'range' => [
                self::STATUS_INVIATA, 
                self::STATUS_PRESA_IN_CARICO, 
                self::STATUS_STAMPATO, 
                self::STATUS_CONSEGNATO
            ]],
            [['account_patient_id'], 'exist', 'skipOnError' => true, 'targetClass' => AccountPatient::class, 'targetAttribute' => ['account_patient_id' => 'id']],
            [['patient_id'], 'exist', 'skipOnError' => true, 'targetClass' => Patient::class, 'targetAttribute' => ['patient_id' => 'id']],
            [['request_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => RequestType::class, 'targetAttribute' => ['request_type_id' => 'id']],
            [['therapeutic_plan_id'], 'exist', 'skipOnError' => true, 'targetClass' => TherapeuticPlan::class, 'targetAttribute' => ['therapeutic_plan_id' => 'id']],
            [['therapy_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlanTherapy::class, 'targetAttribute' => ['therapy_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_patient_id' => 'Account Paziente',
            'patient_id' => 'Paziente',
            'request_type_id' => 'Tipo Richiesta',
            'therapeutic_plan_id' => 'Piano Terapeutico',
            'therapy_id' => 'Terapia',
            'notes' => 'Note',
            'status' => 'Stato',
            'created_at' => 'Creato il',
        ];
    }

    /**
     * Gets query for [[AccountPatient]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAccountPatient()
    {
        return $this->hasOne(AccountPatient::class, ['id' => 'account_patient_id']);
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
     * Gets query for [[TherapeuticPlan]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTherapeuticPlan()
    {
        return $this->hasOne(TherapeuticPlan::class, ['id' => 'therapeutic_plan_id']);
    }

    /**
     * Gets query for [[Therapy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTherapy()
    {
        return $this->hasOne(PlanTherapy::class, ['id' => 'therapy_id']);
    }

    /**
     * Gets query for [[RequestStatus]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequestStatus()
    {
        return $this->hasOne(RequestStatus::class, ['id' => 'status']);
    }

    /**
     * Gets query for [[StatusHistory]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStatusHistory()
    {
        return $this->hasMany(DocumentRequestStatusHistory::class, ['document_request_id' => 'id'])
                    ->orderBy(['created_at' => SORT_ASC]);
    }

    /**
     * Legacy alias for compatibility - gets query for [[AccountPatient]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequestedByAccountPatient()
    {
        return $this->getAccountPatient();
    }

    /**
     * Gets status labels
     *
     * @return array
     */
    public static function getStatusLabels()
    {
        return [
            self::STATUS_INVIATA => 'Inviata',
            self::STATUS_PRESA_IN_CARICO => 'Presa in carico',
            self::STATUS_STAMPATO => 'Stampato',
            self::STATUS_CONSEGNATO => 'Consegnato',
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
        return $labels[$this->status] ?? 'Sconosciuto';
    }

    /**
     * Checks if request is delivered (completed)
     *
     * @return bool
     */
    public function isDelivered()
    {
        return $this->status === self::STATUS_CONSEGNATO;
    }

    /**
     * Checks if request can be cancelled (only if not delivered)
     *
     * @return bool
     */
    public function canBeCancelled()
    {
        return !$this->isDelivered();
    }

    /**
     * Trova richieste duplicate per paziente e tipo (per controllo allow_multiple_requests)
     *
     * @param int $patientId
     * @param int $requestTypeId
     * @param bool $allowMultiple
     * @return DocumentRequest|null
     */
    public static function findDuplicateForPatientAndType($patientId, $requestTypeId, $allowMultiple = false)
    {
        if ($allowMultiple) {
            return null; // Se sono permesse richieste multiple, non c'è duplicato
        }
        
        return static::find()
            ->where([
                'patient_id' => $patientId,
                'request_type_id' => $requestTypeId
            ])
            ->andWhere(['in', 'status', [
                self::STATUS_INVIATA,
                self::STATUS_PRESA_IN_CARICO,
                self::STATUS_STAMPATO
            ]]) // Solo richieste attive (non consegnate)
            ->orderBy(['created_at' => SORT_DESC])
            ->one();
    }

    /**
     * Scope per richieste attive (non consegnate)
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findActive()
    {
        return static::find()->where(['!=', 'status', self::STATUS_CONSEGNATO]);
    }

    /**
     * Trova richieste per paziente
     *
     * @param int $patientId
     * @return \yii\db\ActiveQuery
     */
    public static function findByPatient($patientId)
    {
        return static::find()
            ->with(['requestType', 'accountPatient.user.profile', 'requestStatus'])
            ->where(['patient_id' => $patientId])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Trova richieste per account paziente
     *
     * @param int $accountPatientId
     * @return \yii\db\ActiveQuery
     */
    public static function findByAccountPatient($accountPatientId)
    {
        return static::find()
            ->with(['requestType', 'patient', 'requestStatus'])
            ->where(['account_patient_id' => $accountPatientId])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Restituisce un array per API
     *
     * @return array
     */
    public function toApiArray()
    {
        $createdBy = [
            'id' => $this->accountPatient->id,
            'user_id' => $this->accountPatient->user_id,
            'first_name' => $this->accountPatient->user->profile ? 
                           $this->accountPatient->user->profile->first_name : 'N/A',
            'last_name' => $this->accountPatient->user->profile ? 
                          $this->accountPatient->user->profile->last_name : 'N/A',
            'relationship_type' => $this->accountPatient->relationship_type
        ];

        return [
            'id' => $this->id,
            'account_patient_id' => $this->account_patient_id,
            'patient_id' => $this->patient_id,
            'request_type_id' => $this->request_type_id,
            'request_type' => $this->requestType ? [
                'id' => $this->requestType->id,
                'name' => $this->requestType->name,
                'therapeutic_plan_rule' => $this->requestType->therapeutic_plan_rule,
                'require_therapy_assignment' => (bool) $this->requestType->require_therapy_assignment,
                'require_notes' => (bool) $this->requestType->require_notes,
            ] : null,
            'therapeutic_plan_id' => $this->therapeutic_plan_id,
            'therapy_id' => $this->therapy_id,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'notes' => $this->notes,
            'created_at' => (new \DateTime($this->created_at, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
            'created_by' => $createdBy,
            'can_be_cancelled' => $this->canBeCancelled(),
        ];
    }

    /**
     * Cambia lo stato della richiesta e registra lo storico
     *
     * @param int $newStatus
     * @param int|null $changedByUserId
     * @return bool
     */
    public function changeStatus($newStatus, $changedByUserId = null)
    {
        $oldStatus = $this->status;
        
        // Non fare nulla se lo status è già quello richiesto
        if ($oldStatus === $newStatus) {
            return true;
        }
        
        $this->status = $newStatus;
        
        if ($this->save()) {
            // Crea record nello storico
            DocumentRequestStatusHistory::createStatusChange(
                $this->id,
                $oldStatus,
                $newStatus,
                $changedByUserId
            );
            
            Yii::info("DocumentRequest {$this->id} status changed from {$oldStatus} to {$newStatus} by user {$changedByUserId}", __METHOD__);
            return true;
        }
        
        return false;
    }

    /**
     * Segna come presa in carico
     *
     * @param int|null $userId
     * @return bool
     */
    public function markAsPresoInCarico($userId = null)
    {
        return $this->changeStatus(self::STATUS_PRESA_IN_CARICO, $userId);
    }

    /**
     * Segna come stampato
     *
     * @param int|null $userId
     * @return bool
     */
    public function markAsStampato($userId = null)
    {
        return $this->changeStatus(self::STATUS_STAMPATO, $userId);
    }

    /**
     * Segna come consegnato
     *
     * @param int|null $userId
     * @return bool
     */
    public function markAsConsegnato($userId = null)
    {
        return $this->changeStatus(self::STATUS_CONSEGNATO, $userId);
    }
} 
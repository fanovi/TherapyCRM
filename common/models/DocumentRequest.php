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
 * @property int $requested_by
 * @property string $document_type
 * @property string|null $purpose
 * @property string $status
 * @property string|null $notes
 * @property string|null $file_path
 * @property int|null $processed_by
 * @property string|null $processed_at
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Patient $patient
 * @property User $requestedBy
 * @property User $processedBy
 */
class DocumentRequest extends ActiveRecord
{
    const TYPE_MEDICAL_CERTIFICATE = 'medical_certificate';
    const TYPE_THERAPY_REPORT = 'therapy_report';
    const TYPE_ATTENDANCE_CERTIFICATE = 'attendance_certificate';
    const TYPE_INVOICE = 'invoice';
    const TYPE_OTHER = 'other';

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';
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
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['patient_id', 'requested_by', 'document_type'], 'required'],
            [['patient_id', 'requested_by', 'processed_by'], 'integer'],
            [['purpose', 'notes'], 'string'],
            [['processed_at'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['document_type'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 20],
            [['file_path'], 'string', 'max' => 500],
            [['document_type'], 'in', 'range' => [self::TYPE_MEDICAL_CERTIFICATE, self::TYPE_THERAPY_REPORT, self::TYPE_ATTENDANCE_CERTIFICATE, self::TYPE_INVOICE, self::TYPE_OTHER]],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED, self::STATUS_REJECTED, self::STATUS_CANCELLED]],
            [['patient_id'], 'exist', 'skipOnError' => true, 'targetClass' => Patient::class, 'targetAttribute' => ['patient_id' => 'id']],
            [['requested_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['requested_by' => 'id']],
            [['processed_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['processed_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'patient_id' => 'Paziente',
            'requested_by' => 'Richiesto da',
            'document_type' => 'Tipo Documento',
            'purpose' => 'Scopo',
            'status' => 'Stato',
            'notes' => 'Note',
            'file_path' => 'File',
            'processed_by' => 'Elaborato da',
            'processed_at' => 'Elaborato il',
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
     * Gets query for [[RequestedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequestedBy()
    {
        return $this->hasOne(User::class, ['id' => 'requested_by']);
    }

    /**
     * Gets query for [[ProcessedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProcessedBy()
    {
        return $this->hasOne(User::class, ['id' => 'processed_by']);
    }

    /**
     * Gets document type labels
     *
     * @return array
     */
    public static function getDocumentTypeLabels()
    {
        return [
            self::TYPE_MEDICAL_CERTIFICATE => 'Certificato Medico',
            self::TYPE_THERAPY_REPORT => 'Relazione Terapeutica',
            self::TYPE_ATTENDANCE_CERTIFICATE => 'Certificato di Frequenza',
            self::TYPE_INVOICE => 'Fattura',
            self::TYPE_OTHER => 'Altro',
        ];
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
            self::STATUS_IN_PROGRESS => 'In Elaborazione',
            self::STATUS_COMPLETED => 'Completato',
            self::STATUS_REJECTED => 'Rifiutato',
            self::STATUS_CANCELLED => 'Annullato',
        ];
    }

    /**
     * Gets document type label
     *
     * @return string
     */
    public function getDocumentTypeLabel()
    {
        $labels = static::getDocumentTypeLabels();
        return $labels[$this->document_type] ?? $this->document_type;
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

    /**
     * Checks if request is completed
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

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
     * Marks request as in progress
     *
     * @param int $processedBy
     * @return bool
     */
    public function markInProgress($processedBy)
    {
        $this->status = self::STATUS_IN_PROGRESS;
        $this->processed_by = $processedBy;
        $this->processed_at = date('Y-m-d H:i:s');
        
        return $this->save();
    }

    /**
     * Marks request as completed
     *
     * @param string|null $filePath
     * @return bool
     */
    public function markCompleted($filePath = null)
    {
        $this->status = self::STATUS_COMPLETED;
        if ($filePath) {
            $this->file_path = $filePath;
        }
        
        return $this->save();
    }

    /**
     * Rejects request
     *
     * @param string|null $reason
     * @return bool
     */
    public function reject($reason = null)
    {
        $this->status = self::STATUS_REJECTED;
        if ($reason) {
            $this->notes = $reason;
        }
        
        return $this->save();
    }

    /**
     * Gets pending requests
     *
     * @return static[]
     */
    public static function findPending()
    {
        return static::find()
            ->where(['status' => self::STATUS_PENDING])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
    }

    /**
     * Gets requests by patient
     *
     * @param int $patientId
     * @return static[]
     */
    public static function findByPatient($patientId)
    {
        return static::find()
            ->where(['patient_id' => $patientId])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }
} 
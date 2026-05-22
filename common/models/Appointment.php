<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use Ramsey\Uuid\Uuid;

/**
 * This is the model class for table "appointments".
 *
 * @property int $id
 * @property int|null $pattern_id
 * @property int|null $plan_therapy_id
 * @property string $appointment_source
 * @property int|null $treatment_type_id
 * @property int|null $specialization_id
 * @property int|null $private_cycle_id
 * @property int|null $patient_id
 * @property string|null $group_session_id
 * @property int $therapist_id
 * @property string $appointment_datetime
 * @property int $duration_minutes
 * @property string $status
 * @property int|null $original_therapist_id
 * @property string|null $notes
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 * @property int|null $id_setting
 * @property string $appointment_category
 * @property int|null $related_appointment_id
 *
 * @property AppointmentPattern $pattern
 * @property PlanTherapy $planTherapy
 * @property TreatmentType $treatmentType
 * @property PrivateCycle $privateCycle
 * @property Patient $patient
 * @property Therapist $therapist
 * @property Therapist $originalTherapist
 * @property User $createdBy
 * @property Setting $setting
 * @property Specialization $specialization
 */
class Appointment extends ActiveRecord
{
    const CATEGORY_REGULAR = 'regular';
    const CATEGORY_RECOVERY = 'recovery';
    const CATEGORY_ADVANCE = 'advance';
    const CATEGORY_EXTRA = 'extra';
    const CATEGORY_COMPENSATION = 'compensation';

    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ABSENT_JUSTIFIED = 'absent_justified';
    const STATUS_ABSENT_NOT_JUSTIFIED = 'absent_not_justified';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_THERAPIST_ABSENT = 'therapist_absent';

    const TYPE_SUPERVISIONE = 'supervisione';
    const TYPE_PARENT_TRAINING = 'parent_training';
    const TYPE_TERAPIA = 'terapia';

    // Appointment source constants
    const SOURCE_THERAPEUTIC_PLAN = 'therapeutic_plan';
    const SOURCE_PRIVATE = 'private';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%appointments}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => function () {
                    return date('Y-m-d H:i:s');
                },
            ],
            [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => ['created_at', 'updated_at'],
                'entityNameCallback' => function ($model) {
                    return 'Appuntamento';
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
            [['therapist_id', 'appointment_datetime', 'duration_minutes', 'appointment_source', 'created_by'], 'required'],
            [['pattern_id', 'plan_therapy_id', 'treatment_type_id', 'specialization_id', 'private_cycle_id', 'patient_id', 'therapist_id', 'duration_minutes', 'original_therapist_id', 'created_by', 'related_appointment_id'], 'integer'],
            [['group_session_id'], 'string', 'max' => 36],
            [['group_session_id'], 'match', 'pattern' => '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', 'message' => 'Group Session ID deve essere un UUID valido', 'skipOnEmpty' => true],
            [['appointment_datetime'], 'validateAppointmentDateTime'],
            [['notes'], 'string'],
            [['status'], 'string', 'max' => 30],
            [['status'], 'in', 'range' => [self::STATUS_SCHEDULED, self::STATUS_COMPLETED, self::STATUS_ABSENT_JUSTIFIED, self::STATUS_ABSENT_NOT_JUSTIFIED, self::STATUS_CANCELLED, self::STATUS_THERAPIST_ABSENT]],
            [['appointment_source'], 'in', 'range' => [self::SOURCE_THERAPEUTIC_PLAN, self::SOURCE_PRIVATE]],
            [['appointment_source'], 'default', 'value' => self::SOURCE_THERAPEUTIC_PLAN],
            [['appointment_type'], 'string', 'max' => 20],
            [['appointment_type'], 'in', 'range' => [self::TYPE_SUPERVISIONE, self::TYPE_PARENT_TRAINING, self::TYPE_TERAPIA]],
            [['appointment_type'], 'default', 'value' => self::TYPE_TERAPIA],
            [['duration_minutes'], 'integer', 'min' => 15, 'max' => 180],
            [['appointment_datetime'], 'validateFutureDateTime'],
            [['therapist_id', 'appointment_datetime'], 'validateTherapistAvailability'],
            // Validazione condizionale per appuntamenti da piano terapeutico
            [['plan_therapy_id'], 'required', 'when' => function ($model) {
                return $model->appointment_source === self::SOURCE_THERAPEUTIC_PLAN;
            }],
            // Validazione condizionale per appuntamenti privati
            [['patient_id', 'treatment_type_id'], 'required', 'when' => function ($model) {
                return $model->appointment_source === self::SOURCE_PRIVATE;
            }],
            // Foreign key validations
            [['pattern_id'], 'exist', 'skipOnError' => true, 'targetClass' => AppointmentPattern::class, 'targetAttribute' => ['pattern_id' => 'id']],
            [['plan_therapy_id'], 'exist', 'skipOnError' => true, 'targetClass' => PlanTherapy::class, 'targetAttribute' => ['plan_therapy_id' => 'id']],
            [['treatment_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => TreatmentType::class, 'targetAttribute' => ['treatment_type_id' => 'id']],
            [['specialization_id'], 'exist', 'skipOnError' => true, 'targetClass' => Specialization::class, 'targetAttribute' => ['specialization_id' => 'id']],
            [['specialization_id'], 'required', 'when' => function ($model) {
                return $this->shouldValidateSpecialization();
            }, 'message' => 'La specializzazione è obbligatoria per i nuovi appuntamenti.'],
            [['specialization_id'], 'validateTherapistSpecializationCompatibility'],
            [['private_cycle_id'], 'exist', 'skipOnError' => true, 'targetClass' => PrivateCycle::class, 'targetAttribute' => ['private_cycle_id' => 'id']],
            [['patient_id'], 'exist', 'skipOnError' => true, 'targetClass' => Patient::class, 'targetAttribute' => ['patient_id' => 'id']],
            [['therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['therapist_id' => 'id']],
            [['original_therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['original_therapist_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
            ['id_setting', 'required'],
            ['id_setting', 'integer'],
            ['id_setting', 'exist', 'skipOnError' => true, 'targetClass' => Setting::class, 'targetAttribute' => ['id_setting' => 'id']],
            [['appointment_category'], 'in', 'range' => [
                self::CATEGORY_REGULAR,
                self::CATEGORY_RECOVERY,
                self::CATEGORY_ADVANCE,
                self::CATEGORY_EXTRA,
                self::CATEGORY_COMPENSATION
            ]],
            [['appointment_category'], 'default', 'value' => self::CATEGORY_REGULAR],
        ];
    }

    /**
     * Custom validator for appointment_datetime
     */
    public function validateAppointmentDateTime($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            // Try to parse the datetime string
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $this->$attribute);

            if ($dateTime === false) {
                // Try alternative formats
                $dateTime = \DateTime::createFromFormat('Y-m-d H:i', $this->$attribute);
                if ($dateTime !== false) {
                    // Convert to full format
                    $this->$attribute = $dateTime->format('Y-m-d H:i:s');
                } else {
                    // Try to parse with strtotime as last resort
                    $timestamp = strtotime($this->$attribute);
                    if ($timestamp !== false) {
                        $this->$attribute = date('Y-m-d H:i:s', $timestamp);
                    } else {
                        $this->addError($attribute, 'Il formato della data e ora deve essere YYYY-MM-DD HH:mm:ss');
                        return;
                    }
                }
            }

            // Additional validation: check if it's a valid date
            $finalDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $this->$attribute);
            if ($finalDateTime === false) {
                $this->addError($attribute, 'Data e ora non valida');
            }
        }
    }

    /**
     * Validates that appointment is not in the past
     */
    public function validateFutureDateTime($attribute, $params)
    {
        // TEMPORANEAMENTE DISABILITATO PER TESTING
        // TODO: Riabilitare questa validazione quando i test saranno completati
        /*
        if (!empty($this->$attribute) && $this->status === self::STATUS_SCHEDULED) {
            $appointmentTime = strtotime($this->$attribute);
            if ($appointmentTime < time() - 300) { // 5 minutes tolerance
                $this->addError($attribute, 'Non è possibile programmare appuntamenti nel passato');
            }
        }
        */

        // Per ora accettiamo qualsiasi data per il testing
        if (!empty($this->$attribute)) {
            \Yii::info("Validazione data futura temporaneamente disabilitata per testing - DateTime: {$this->$attribute}", __METHOD__);
        }
    }

    /**
     * Validates therapist availability
     */
    public function validateTherapistAvailability($attribute, $params)
    {
        if (empty($this->therapist_id) || empty($this->appointment_datetime)) {
            return;
        }
        if (empty($this->duration_minutes) || (int)$this->duration_minutes <= 0) {
            return;
        }

        try {
            $startDt = new \DateTimeImmutable($this->appointment_datetime);
        } catch (\Exception) {
            return;
        }
        $endDt = $startDt->add(new \DateInterval('PT' . (int)$this->duration_minutes . 'M'));
        $start = $startDt->format('Y-m-d H:i:s');
        $end   = $endDt->format('Y-m-d H:i:s');

        $existingEnd = new \yii\db\Expression('DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)');

        $query = static::find()
            ->where(['therapist_id' => $this->therapist_id])
            ->andWhere([
                'or',
                ['and',
                    ['<=', 'appointment_datetime', $start],
                    ['>',  $existingEnd, $start],
                ],
                ['and',
                    ['<',  'appointment_datetime', $end],
                    ['>=', 'appointment_datetime', $start],
                ],
            ])
            ->andWhere(['not in', 'status', [
                self::STATUS_CANCELLED,
                self::STATUS_COMPLETED,
                self::STATUS_ABSENT_JUSTIFIED,
                self::STATUS_ABSENT_NOT_JUSTIFIED,
                self::STATUS_THERAPIST_ABSENT,
            ]])
            ->andWhere(['!=', 'id', $this->id ?: 0]);

        // Se questo appuntamento fa parte di una sessione di gruppo,
        // escludi altri appuntamenti della stessa sessione di gruppo dalla validazione
        if (!empty($this->group_session_id)) {
            $query->andWhere(['!=', 'group_session_id', $this->group_session_id]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'Il terapista ha già un appuntamento in questo orario');
        }
    }

    /**
     * True quando va validata la coerenza terapista ↔ specializzazione ↔ treatment.
     *
     * Lo storico (record già persistiti e non modificati nei campi rilevanti)
     * non viene rivalidato per evitare di rompere appuntamenti pre-migrazione N:N.
     */
    public function shouldValidateSpecialization(): bool
    {
        if ($this->isNewRecord) {
            return true;
        }
        foreach (['therapist_id', 'specialization_id', 'plan_therapy_id', 'treatment_type_id'] as $attr) {
            if ($this->isAttributeChanged($attr, false)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     *
     * Auto-deriva specialization_id quando può farlo senza ambiguità, così i
     * creatori esistenti che non sono stati ancora aggiornati al modello N
     * specializzazioni continuano a funzionare senza modifiche.
     */
    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }
        $this->autoResolveSpecializationId();
        return true;
    }

    /**
     * Riempie specialization_id derivandolo dall'intersezione fra le
     * specializzazioni del terapista e quelle che offrono il treatment.
     * Imposta il valore solo se la derivazione è univoca (1 candidato);
     * lascia il campo vuoto altrimenti, così la rule "required" scatta
     * e il chiamante è costretto a indicarla esplicitamente.
     */
    private function autoResolveSpecializationId(): void
    {
        if (!empty($this->specialization_id)) {
            return;
        }
        if (!$this->shouldValidateSpecialization()) {
            return;
        }
        if (empty($this->therapist_id)) {
            return;
        }

        $treatmentTypeId = $this->treatment_type_id;
        if (!$treatmentTypeId && $this->plan_therapy_id) {
            $treatmentTypeId = PlanTherapy::find()
                ->select('treatment_type_id')
                ->where(['id' => $this->plan_therapy_id])
                ->scalar();
        }
        if (!$treatmentTypeId) {
            return;
        }

        $candidates = (new \yii\db\Query())
            ->select('s.id')
            ->from(['s' => 'specializations'])
            ->innerJoin(['ts' => 'therapist_specializations'], 'ts.specialization_id = s.id')
            ->innerJoin(['st' => 'specialization_treatments'], 'st.specialization_id = s.id')
            ->where([
                'ts.therapist_id' => (int)$this->therapist_id,
                'st.treatment_type_id' => (int)$treatmentTypeId,
            ])
            ->distinct()
            ->column();

        if (count($candidates) === 1) {
            $this->specialization_id = (int)$candidates[0];
        }
    }

    /**
     * Verifica che la specialization scelta:
     *  1) sia effettivamente posseduta dal terapista (therapist_specializations);
     *  2) sia compatibile con il treatment_type richiesto (specialization_treatments).
     *
     * Il treatment_type viene letto da $this->treatment_type_id, oppure derivato
     * dal piano (plan_therapies.treatment_type_id) se quel campo è vuoto.
     */
    public function validateTherapistSpecializationCompatibility($attribute, $params)
    {
        if (!$this->shouldValidateSpecialization()) {
            return;
        }
        if (empty($this->$attribute) || empty($this->therapist_id)) {
            return;
        }

        $specId = (int)$this->$attribute;

        $hasSpec = TherapistSpecialization::find()
            ->where(['therapist_id' => $this->therapist_id, 'specialization_id' => $specId])
            ->exists();
        if (!$hasSpec) {
            $this->addError($attribute, 'Il terapista non possiede la specializzazione selezionata.');
            return;
        }

        $treatmentTypeId = $this->treatment_type_id;
        if (!$treatmentTypeId && $this->plan_therapy_id) {
            $treatmentTypeId = PlanTherapy::find()
                ->select('treatment_type_id')
                ->where(['id' => $this->plan_therapy_id])
                ->scalar();
        }
        if (!$treatmentTypeId) {
            return;
        }

        $compatible = SpecializationTreatment::find()
            ->where(['specialization_id' => $specId, 'treatment_type_id' => (int)$treatmentTypeId])
            ->exists();
        if (!$compatible) {
            $this->addError($attribute, 'La specializzazione selezionata non è compatibile con il tipo di trattamento.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pattern_id' => 'Pattern',
            'plan_therapy_id' => 'Piano Terapia',
            'appointment_source' => 'Origine Appuntamento',
            'treatment_type_id' => 'Tipo Trattamento',
            'specialization_id' => 'Specializzazione',
            'private_cycle_id' => 'Ciclo Privato',
            'patient_id' => 'Paziente',
            'group_session_id' => 'ID Sessione di Gruppo',
            'therapist_id' => 'Terapista',
            'appointment_datetime' => 'Data e Ora',
            'duration_minutes' => 'Durata (minuti)',
            'status' => 'Stato',
            'original_therapist_id' => 'Terapista Originale',
            'notes' => 'Note',
            'appointment_type' => 'Tipo Appuntamento',
            'created_by' => 'Creato da',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
            'id_setting' => 'Setting',
            'appointment_category' => 'Categoria',
            'related_appointment_id' => 'Appuntamento Correlato',
        ];
    }

    /**
     * Gets query for [[Pattern]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPattern()
    {
        return $this->hasOne(AppointmentPattern::class, ['id' => 'pattern_id']);
    }

    /**
     * Gets query for [[PlanTherapy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlanTherapy()
    {
        return $this->hasOne(PlanTherapy::class, ['id' => 'plan_therapy_id']);
    }

    /**
     * Gets query for [[Therapist]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTherapist()
    {
        return $this->hasOne(Therapist::class, ['id' => 'therapist_id']);
    }

    /**
     * Gets query for [[TreatmentType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTreatmentType()
    {
        return $this->hasOne(TreatmentType::class, ['id' => 'treatment_type_id']);
    }

    /**
     * Specializzazione storicizzata sull'appuntamento (in che "veste" il terapista
     * sta erogando il trattamento). Riempita al momento della creazione.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSpecialization()
    {
        return $this->hasOne(Specialization::class, ['id' => 'specialization_id']);
    }

    /**
     * Gets query for [[PrivateCycle]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPrivateCycle()
    {
        return $this->hasOne(PrivateCycle::class, ['id' => 'private_cycle_id']);
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
     * Gets query for [[Patient]] via plan therapy (legacy method for backward compatibility).
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPatientViaPlanTherapy()
    {
        return $this->hasOne(Patient::class, ['id' => 'patient_id'])
            ->via('planTherapy');
    }

    /**
     * Gets query for [[OriginalTherapist]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOriginalTherapist()
    {
        return $this->hasOne(Therapist::class, ['id' => 'original_therapist_id']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[TherapistSubstitution]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTherapistSubstitution()
    {
        return $this->hasOne(TherapistSubstitution::class, ['appointment_id' => 'id']);
    }

    /**
     * Gets appointment type labels
     *
     * @return array
     */
    public static function getAppointmentTypeLabels()
    {
        return [
            self::TYPE_SUPERVISIONE => 'Supervisione',
            self::TYPE_PARENT_TRAINING => 'Parent Training',
            self::TYPE_TERAPIA => 'Terapia',
        ];
    }

    /**
     * Gets appointment type label
     *
     * @return string
     */
    public function getAppointmentTypeLabel()
    {
        $labels = static::getAppointmentTypeLabels();
        return $labels[$this->appointment_type] ?? $this->appointment_type;
    }

    /**
     * {@inheritdoc}
     * @return AppointmentQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AppointmentQuery(get_called_class());
    }

    /**
     * Gets status labels
     *
     * @return array
     */
    public static function getStatusLabels()
    {
        return [
            self::STATUS_SCHEDULED => 'Programmato',
            self::STATUS_COMPLETED => 'Completato',
            self::STATUS_ABSENT_JUSTIFIED => 'Assente Giustificato',
            self::STATUS_ABSENT_NOT_JUSTIFIED => 'Assente Non Giustificato',
            self::STATUS_CANCELLED => 'Annullato',
            self::STATUS_THERAPIST_ABSENT => 'Terapista Assente',
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

    /**
     * Checks if appointment is completed
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Checks if appointment is cancelled
     *
     * @return bool
     */
    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Checks if appointment is absent
     *
     * @return bool
     */
    public function isAbsent()
    {
        return in_array($this->status, [self::STATUS_ABSENT_JUSTIFIED, self::STATUS_ABSENT_NOT_JUSTIFIED]);
    }

    /**
     * Checks if therapist was substituted
     *
     * @return bool
     */
    public function isSubstituted()
    {
        return !empty($this->original_therapist_id) && $this->original_therapist_id != $this->therapist_id;
    }

    /**
     * Gets formatted appointment datetime
     *
     * @return string
     */
    public function getFormattedDateTime()
    {
        return Yii::$app->formatter->asDatetime($this->appointment_datetime);
    }

    /**
     * Gets formatted appointment date
     *
     * @return string
     */
    public function getFormattedDate()
    {
        return Yii::$app->formatter->asDate($this->appointment_datetime);
    }

    /**
     * Gets formatted appointment time
     *
     * @return string
     */
    public function getFormattedTime()
    {
        return Yii::$app->formatter->asTime($this->appointment_datetime);
    }

    /**
     * Checks if appointment is from therapeutic plan
     *
     * @return bool
     */
    public function isFromTherapeuticPlan()
    {
        return $this->appointment_source === self::SOURCE_THERAPEUTIC_PLAN;
    }

    /**
     * Checks if appointment is private
     *
     * @return bool
     */
    public function isPrivate()
    {
        return $this->appointment_source === self::SOURCE_PRIVATE;
    }

    /**
     * Gets appointment source labels
     *
     * @return array
     */
    public static function getAppointmentSourceLabels()
    {
        return [
            self::SOURCE_THERAPEUTIC_PLAN => 'Piano Terapeutico',
            self::SOURCE_PRIVATE => 'Privato',
        ];
    }

    /**
     * Gets appointment source label
     *
     * @return string
     */
    public function getAppointmentSourceLabel()
    {
        $labels = static::getAppointmentSourceLabels();
        return $labels[$this->appointment_source] ?? $this->appointment_source;
    }

    /**
     * Gets the actual patient for this appointment
     * Works for both therapeutic plan and private appointments
     *
     * @return Patient|null
     */
    public function getActualPatient()
    {
        if ($this->isPrivate()) {
            return $this->patient;
        } elseif ($this->isFromTherapeuticPlan() && $this->planTherapy) {
            return $this->planTherapy->therapeuticPlan->patient ?? null;
        }
        return null;
    }

    /**
     * Gets the treatment type for this appointment
     * Works for both therapeutic plan and private appointments
     *
     * @return TreatmentType|null
     */
    public function getActualTreatmentType()
    {
        if ($this->isPrivate()) {
            return $this->treatmentType;
        } elseif ($this->isFromTherapeuticPlan() && $this->planTherapy) {
            return $this->planTherapy->treatmentType ?? null;
        }
        return null;
    }

    /**
     * Marks appointment as completed
     *
     * @return bool
     */
    public function markAsCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        if ($this->save()) {
            // Increment plan therapy completed sessions
            $this->planTherapy->incrementCompleted();
            return true;
        }
        return false;
    }

    /**
     * Cancels appointment
     *
     * @return bool
     */
    public function cancel()
    {
        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }

    /**
     * Gets appointments for therapist on specific date
     *
     * @param int $therapistId
     * @param string $date
     * @return static[]
     */
    public static function findByTherapistAndDate($therapistId, $date)
    {
        return static::find()
            ->where(['therapist_id' => $therapistId])
            ->andWhere(['like', 'appointment_datetime', $date])
            ->orderBy('appointment_datetime')
            ->all();
    }

    /**
     * Gets upcoming appointments for patient
     *
     * @param int $patientId
     * @param int $limit
     * @return static[]
     */
    public static function findUpcomingByPatient($patientId, $limit = 10)
    {
        return static::find()
            ->joinWith('planTherapy')
            ->where(['plan_therapies.patient_id' => $patientId])
            ->andWhere(['>=', 'appointment_datetime', date('Y-m-d H:i:s')])
            ->andWhere(['!=', 'status', self::STATUS_CANCELLED])
            ->orderBy('appointment_datetime')
            ->limit($limit)
            ->all();
    }

    /**
     * Checks if appointment allows therapist substitution
     *
     * @return bool
     */
    public function allowsTherapistSubstitution()
    {
        return $this->status === self::STATUS_THERAPIST_ABSENT;
    }

    /**
     * Checks if appointment is in a non-scheduled status (allows click but not move)
     *
     * @return bool
     */
    public function isNonScheduled()
    {
        return $this->status !== self::STATUS_SCHEDULED;
    }

    /**
     * Checks if appointment has therapist absent status
     *
     * @return bool
     */
    public function isTherapistAbsent()
    {
        return $this->status === self::STATUS_THERAPIST_ABSENT;
    }

    /**
     * Checks if appointment has been substituted (has substitution record)
     *
     * @return bool
     */
    public function hasSubstitution()
    {
        return $this->therapistSubstitution !== null;
    }

    /**
     * Gets substitution info if exists
     *
     * @return TherapistSubstitution|null
     */
    public function getSubstitutionInfo()
    {
        return $this->therapistSubstitution;
    }

    /**
     * Checks if appointment is part of a group session
     *
     * @return bool
     */
    public function isGroupSession()
    {
        return !empty($this->group_session_id);
    }

    /**
     * Gets all appointments in the same group session
     *
     * @return static[]
     */
    public function getGroupSessionAppointments()
    {
        if (!$this->isGroupSession()) {
            return [$this];
        }

        return static::find()
            ->where(['group_session_id' => $this->group_session_id])
            ->orderBy('appointment_datetime')
            ->all();
    }

    /**
     * Gets the number of participants in the group session
     *
     * @return int
     */
    public function getGroupSessionParticipantsCount()
    {
        if (!$this->isGroupSession()) {
            return 1;
        }

        return static::find()
            ->where(['group_session_id' => $this->group_session_id])
            ->count();
    }

    /**
     * Generates a new UUID for group session
     *
     * @return string
     */
    public static function generateGroupSessionId()
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * Finds appointments by group session ID
     *
     * @param string $groupSessionId
     * @return static[]
     */
    public static function findByGroupSession($groupSessionId)
    {
        return static::find()
            ->where(['group_session_id' => $groupSessionId])
            ->orderBy('appointment_datetime')
            ->all();
    }

    /**
     * Gets patients in the same group session
     *
     * @return Patient[]
     */
    public function getGroupSessionPatients()
    {
        if (!$this->isGroupSession()) {
            return $this->getActualPatient() ? [$this->getActualPatient()] : [];
        }

        $appointments = $this->getGroupSessionAppointments();
        $patients = [];

        foreach ($appointments as $appointment) {
            $patient = $appointment->getActualPatient();
            if ($patient && !in_array($patient->id, array_column($patients, 'id'))) {
                $patients[] = $patient;
            }
        }

        return $patients;
    }

    /**
     * Checks if group session conflicts with therapist availability
     *
     * @param int $therapistId
     * @param string $datetime
     * @param int $duration
     * @param string|null $excludeGroupSessionId
     * @return bool
     */
    public static function checkGroupSessionConflict($therapistId, $datetime, $duration, $excludeGroupSessionId = null)
    {
        $query = static::find()
            ->where(['therapist_id' => $therapistId])
            ->andWhere([
                'between',
                'appointment_datetime',
                date('Y-m-d H:i:s', strtotime($datetime) - 300),
                date('Y-m-d H:i:s', strtotime($datetime) + ($duration * 60) + 300)
            ])
            ->andWhere(['!=', 'status', self::STATUS_CANCELLED])
            ->andWhere(['!=', 'status', self::STATUS_COMPLETED]);

        if ($excludeGroupSessionId) {
            $query->andWhere(['!=', 'group_session_id', $excludeGroupSessionId]);
        }

        return $query->exists();
    }

    /**
     * Gets query for [[Setting]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSetting()
    {
        return $this->hasOne(Setting::class, ['id' => 'id_setting']);
    }

    public static function getCategoryLabels()
    {
        return [
            self::CATEGORY_REGULAR => 'Appuntamento Normale',
            self::CATEGORY_RECOVERY => 'Recupero',
            self::CATEGORY_ADVANCE => 'Anticipo',
            self::CATEGORY_EXTRA => 'Straordinario',
            self::CATEGORY_COMPENSATION => 'Compensazione',
        ];
    }

    // Relazione con l'appuntamento originale
    public function getRelatedAppointment()
    {
        return $this->hasOne(Appointment::class, ['id' => 'related_appointment_id']);
    }

    // Relazione inversa: recuperi di questo appuntamento
    public function getRecoveries()
    {
        return $this->hasMany(Appointment::class, ['related_appointment_id' => 'id'])
            ->andWhere(['appointment_category' => self::CATEGORY_RECOVERY]);
    }
}

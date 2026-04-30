<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * This is the model class for table "therapeutic_plans".
 *
 * @property int $id
 * @property int $patient_id
 * @property string $start_date
 * @property int $duration_days
 * @property string $end_date (generated column)
 * @property int $regime_id
 * @property string|null $approval_date
 * @property string|null $protocol_number
 * @property int|null $district_id
 * @property string|null $notes
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 * @property string $status
 * @property string|null $suspension_date
 * @property string|null $suspension_reason
 * @property string|null $termination_date
 * @property string|null $termination_reason
 *
 * @property Patient $patient
 * @property User $createdBy
 * @property Regime $regime
 * @property District $district
 * @property PlanTherapy[] $planTherapies
 */
class TherapeuticPlan extends ActiveRecord
{
    /**
     * @var array temporary storage for therapies during validation
     */
    private $_therapies = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%therapeutic_plans}}';
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
                    return 'TherapeuticPlan';
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
            [['patient_id', 'start_date', 'duration_days', 'regime_id', 'created_by'], 'required'],
            [['patient_id', 'duration_days', 'regime_id', 'created_by'], 'integer'],
            [['duration_days'], 'integer', 'min' => 1, 'max' => 1095],  // Max 3 years
            [['start_date', 'approval_date'], 'date', 'format' => 'php:Y-m-d'],
            [['notes'], 'string'],
            [['protocol_number'], 'string', 'max' => 50],
            [['district_id'], 'integer'],
            [['district_id'], 'default', 'value' => null],
            [
                ['protocol_number'], 
                'unique', 
                'targetAttribute' => ['protocol_number', 'district_id', 'regime_id'],
                'message' => 'Questa combinazione di numero protocollo, distretto e regime è già in uso.'
            ],
            [['approval_date', 'protocol_number'], 'default', 'value' => null],
            [['patient_id'], 'exist', 'skipOnError' => true, 'targetClass' => Patient::class, 'targetAttribute' => ['patient_id' => 'id']],
            [['regime_id'], 'exist', 'skipOnError' => true, 'targetClass' => Regime::class, 'targetAttribute' => ['regime_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
            [['district_id'], 'exist', 'skipOnError' => true, 'targetClass' => District::class, 'targetAttribute' => ['district_id' => 'id']],
            // Custom validation for ABA requirements
            ['regime_id', 'validateABARequirements'],
            [['status'], 'string'],
            // Default 'active'; se data fine già passata -> 'expired'.
            [['status'], 'default', 'value' => function ($model) {
                if ($model->start_date && $model->duration_days) {
                    $endDate = date('Y-m-d', strtotime($model->start_date . ' + ' . ($model->duration_days - 1) . ' days'));
                    if ($endDate < date('Y-m-d')) {
                        return 'expired';
                    }
                }
                return 'active';
            }],
            [['status'], 'in', 'range' => ['draft', 'pending', 'active', 'suspended', 'completed', 'terminated', 'expired']],
            [['suspension_date'], 'date', 'format' => 'php:Y-m-d'],
            [['suspension_reason'], 'string'],
            [['suspension_date', 'suspension_reason'], 'default', 'value' => null],
            // Validazione: suspension_date e suspension_reason solo se status = suspended
            ['suspension_date', 'required', 'when' => function($model) {
                return $model->status === 'suspended';
            }, 'whenClient' => "function (attribute, value) {
                return $('#status').val() === 'suspended';
            }"],
            [['termination_date'], 'date', 'format' => 'php:Y-m-d'],
            [['termination_reason'], 'string'],
            [['termination_date', 'termination_reason'], 'default', 'value' => null],
            // Validazione: termination_date e termination_reason solo se status = terminated
            ['termination_date', 'required', 'when' => function($model) {
                return $model->status === 'terminated';
            }, 'whenClient' => "function (attribute, value) {
                return $('#status').val() === 'terminated';
            }"],
        ];
    }

    /**
     * Validates ABA regime requirements
     * @param string $attribute
     * @param array $params
     */
    public function validateABARequirements($attribute, $params)
    {
        // Only validate if regime is set and it's ABA
        if (!$this->regime_id) {
            return;
        }

        $regime = Regime::findOne($this->regime_id);
        if (!$regime || stripos($regime->nome, 'ABA') === false) {
            return;  // Not ABA regime, no special validation needed
        }

        // Check if therapies are set (for validation during save)
        $therapies = $this->_therapies;

        // If we're updating, also check existing therapies
        if (!$this->isNewRecord && empty($therapies)) {
            $therapies = [];
            foreach ($this->planTherapies as $therapy) {
                $therapies[] = [
                    'treatment_type_id' => $therapy->treatment_type_id,
                    'weekly_hours' => $therapy->weekly_hours,
                ];
            }
        }

        if (empty($therapies)) {
            $this->addError($attribute, 'Per il regime ABA è necessario definire le terapie.');
            return;
        }

        // Verifica età del paziente per determinare i controlli obbligatori
        $patientAge = null;
        if ($this->patient) {
            $patientAge = $this->patient->getAge();
        }

        $isUnder14 = ($patientAge !== null && $patientAge < 14);

        $hasSupervision = false;
        $hasParentTraining = false;
        $otherTherapiesCount = 0;

        foreach ($therapies as $therapy) {
            if (empty($therapy['treatment_type_id'])) {
                continue;
            }

            $treatmentType = TreatmentType::findOne($therapy['treatment_type_id']);
            if (!$treatmentType) {
                continue;
            }

            $treatmentName = strtoupper($treatmentType->name);

            if (strpos($treatmentName, 'SUPERVIS') !== false) {
                $hasSupervision = true;
            } elseif (strpos($treatmentName, 'PARENT') !== false || strpos($treatmentName, 'TRAINING') !== false) {
                $hasParentTraining = true;
            } else {
                // Conta le terapie che non sono supervisione né parent training
                $otherTherapiesCount++;
            }
        }

        // Controlli obbligatori solo per pazienti sotto i 14 anni
        if ($isUnder14) {
            if (!$hasSupervision) {
                $this->addError($attribute, 'Per il regime ABA di pazienti sotto i 14 anni è obbligatorio inserire ore di SUPERVISIONE.');
            }

            if (!$hasParentTraining) {
                $this->addError($attribute, 'Per il regime ABA di pazienti sotto i 14 anni è obbligatorio inserire ore di PARENT TRAINING.');
            }
        }

        // La terapia principale è sempre obbligatoria indipendentemente dall'età
        if ($otherTherapiesCount === 0) {
            $this->addError($attribute, 'Per il regime ABA è obbligatorio inserire almeno una terapia oltre alla supervisione e al parent training.');
        }
    }

    /**
     * Sets therapies for validation
     * @param array $therapies
     */
    public function setTherapiesForValidation($therapies)
    {
        $this->_therapies = $therapies;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'patient_id' => 'Paziente',
            'start_date' => 'Data Inizio',
            'duration_days' => 'Durata (giorni)',
            'end_date' => 'Data Fine',
            'regime_id' => 'Regime',
            'notes' => 'Note',
            'created_by' => 'Creato da',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
            'approval_date' => 'Data Approvazione',
            'protocol_number' => 'Numero Protocollo',
            'district_id' => 'Distretto',
            'status' => 'Stato',
            'suspension_date' => 'Data Sospensione',
            'suspension_reason' => 'Motivo Sospensione',
            'termination_date' => 'Data Interruzione',
            'termination_reason' => 'Motivo Interruzione',
        ];
    }

    /**
     * Check if this plan is ABA regime
     * @return bool
     */
    public function isABARegime()
    {
        if (!$this->regime) {
            return false;
        }

        return stripos($this->regime->nome, 'ABA') !== false;
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
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[Regime]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegime()
    {
        return $this->hasOne(Regime::class, ['id' => 'regime_id']);
    }

    /**
     * Gets query for [[District]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDistrict()
    {
        return $this->hasOne(District::class, ['id' => 'district_id']);
    }

    /**
     * Gets query for [[PlanTherapies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlanTherapies()
    {
        return $this->hasMany(PlanTherapy::class, ['therapeutic_plan_id' => 'id']);
    }

    /**
     * Checks if plan is expired based on end date
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->end_date && $this->end_date < date('Y-m-d');
    }

    /**
     * Gets calculated end date
     * Note: end_date is a generated column in the database
     *
     * @return string|null
     */
    public function getCalculatedEndDate()
    {
        if (!$this->start_date || !$this->duration_days) {
            return null;
        }

        return date('Y-m-d', strtotime($this->start_date . ' + ' . ($this->duration_days - 1) . ' days'));
    }

    /**
     * Gets remaining days
     *
     * @return int
     */
    public function getRemainingDays()
    {
        $endDate = $this->end_date ?: $this->getCalculatedEndDate();
        if (!$endDate) {
            return 0;
        }

        $end = new \DateTime($endDate);
        $now = new \DateTime();

        $diff = $end->diff($now);
        return $diff->invert ? $diff->days : -$diff->days;
    }

    /**
     * Gets elapsed days
     *
     * @return int
     */
    public function getElapsedDays()
    {
        if (!$this->start_date) {
            return 0;
        }

        $start = new \DateTime($this->start_date);
        $now = new \DateTime();

        $diff = $now->diff($start);
        return $diff->invert ? $diff->days : 0;
    }

    /**
     * Gets progress percentage
     *
     * @return float
     */
    public function getProgressPercentage()
    {
        if (!$this->duration_days) {
            return 0;
        }

        $elapsed = $this->getElapsedDays();
        return min(100, ($elapsed / $this->duration_days) * 100);
    }

    /**
     * Gets total weekly hours from all therapies
     * For ABA regime, returns monthly hours
     *
     * @return float
     */
    public function getTotalWeeklyHours()
    {
        return $this
            ->getPlanTherapies()
            ->sum('weekly_hours') ?: 0;
    }

    /**
     * Gets the regime name for this plan
     *
     * @return string|null
     */
    public function getRegimeName()
    {
        return $this->regime ? $this->regime->nome : null;
    }

    /**
     * Gets formatted duration
     *
     * @return string
     */
    public function getFormattedDuration()
    {
        if (!$this->duration_days) {
            return '';
        }

        return $this->duration_days . ' giorni';
    }

    /**
     * Creates a new plan by renewing this one
     *
     * @param int $newDurationDays
     * @return TherapeuticPlan|null
     */
    public function renew($newDurationDays)
    {
        if (!$this->isExpired()) {
            return null;
        }

        $newPlan = new static();
        $newPlan->patient_id = $this->patient_id;
        $newPlan->regime_id = $this->regime_id;
        $newPlan->start_date = date('Y-m-d');
        $newPlan->duration_days = $newDurationDays;
        $newPlan->notes = $this->notes;
        $newPlan->created_by = Yii::$app->user->id;

        if ($newPlan->save()) {
            // Copy therapies from old plan
            foreach ($this->planTherapies as $oldTherapy) {
                $newTherapy = new PlanTherapy();
                $newTherapy->therapeutic_plan_id = $newPlan->id;
                $newTherapy->treatment_type_id = $oldTherapy->treatment_type_id;
                $newTherapy->weekly_hours = $oldTherapy->weekly_hours;
                $newTherapy->is_group = $oldTherapy->is_group;
                $newTherapy->setting_id = $oldTherapy->setting_id;
                $newTherapy->save();
            }

            return $newPlan;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     * @return TherapeuticPlanQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TherapeuticPlanQuery(get_called_class());
    }
    
    /**
     * Checks if plan is active
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Checks if plan is suspended
     * @return bool
     */
    public function isSuspended()
    {
        return $this->status === 'suspended';
    }

    /**
     * Gets status label with color
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            'draft' => '<span class="badge badge-secondary">Bozza</span>',
            'pending' => '<span class="badge badge-info">In Attesa</span>',
            'active' => '<span class="badge badge-success">Attivo</span>',
            'suspended' => '<span class="badge badge-warning">Sospeso</span>',
            'completed' => '<span class="badge badge-primary">Completato</span>',
            'terminated' => '<span class="badge badge-danger">Interrotto</span>',
            'expired' => '<span class="badge badge-dark">Scaduto</span>',
        ];
        return $labels[$this->status] ?? $this->status;
    }
}

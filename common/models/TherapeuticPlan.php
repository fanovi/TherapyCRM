<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "therapeutic_plans".
 *
 * @property int $id
 * @property int $patient_id
 * @property string $start_date
 * @property int $duration_days
 * @property string $end_date (generated column)
 * @property int $regime_id
 * @property string|null $notes
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Patient $patient
 * @property User $createdBy
 * @property Regime $regime
 * @property PlanTherapy[] $planTherapies
 */
class TherapeuticPlan extends ActiveRecord
{

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
                'value' => function() {
                    return date('Y-m-d H:i:s');
                },
            ],
            [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => ['created_at', 'updated_at'],
                'entityNameCallback' => function($model) {
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
            [['duration_days'], 'integer', 'min' => 1, 'max' => 1095], // Max 3 years
            [['start_date'], 'date', 'format' => 'php:Y-m-d'],
            [['notes'], 'string'],
            [['patient_id'], 'exist', 'skipOnError' => true, 'targetClass' => Patient::class, 'targetAttribute' => ['patient_id' => 'id']],
            [['regime_id'], 'exist', 'skipOnError' => true, 'targetClass' => Regime::class, 'targetAttribute' => ['regime_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
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
            'start_date' => 'Data Inizio',
            'duration_days' => 'Durata (giorni)',
            'end_date' => 'Data Fine',
            'regime_id' => 'Regime',
            'notes' => 'Note',
            'created_by' => 'Creato da',
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
        
        return date('Y-m-d', strtotime($this->start_date . ' + ' . $this->duration_days . ' days'));
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
     *
     * @return float
     */
    public function getTotalWeeklyHours()
    {
        return $this->getPlanTherapies()
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
} 
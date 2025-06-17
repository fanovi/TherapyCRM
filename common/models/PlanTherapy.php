<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "plan_therapies".
 *
 * @property int $id
 * @property int $therapeutic_plan_id
 * @property int $treatment_type_id
 * @property float $weekly_hours
 * @property bool $is_group
 * @property string $health_regime
 * @property string|null $notes
 * @property string $created_at
 * @property string $updated_at
 *
 * @property TherapeuticPlan $therapeuticPlan
 * @property TreatmentType $treatmentType
 * @property Appointment[] $appointments
 */
class PlanTherapy extends ActiveRecord
{
    // Health regime constants
    const HEALTH_REGIME_L11 = 'L11';
    const HEALTH_REGIME_L11DOM = 'L11DOM';
    const HEALTH_REGIME_L11PG = 'L11PG';
    const HEALTH_REGIME_L11SEM = 'L11SEM';
    const HEALTH_REGIME_ABA = 'ABA';
    const HEALTH_REGIME_FKT = 'FKT';
    const HEALTH_REGIME_PRIVATE = 'Private';
    const HEALTH_REGIME_PDOM = 'PDOM';
    const HEALTH_REGIME_OTHER = 'Other';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%plan_therapies}}';
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
            [['therapeutic_plan_id', 'treatment_type_id', 'weekly_hours'], 'required'],
            [['therapeutic_plan_id', 'treatment_type_id'], 'integer'],
            [['weekly_hours'], 'number', 'min' => 0.5, 'max' => 50],
            [['is_group'], 'boolean'],
            [['notes'], 'string'],
            [['health_regime'], 'string', 'max' => 20],
            [['health_regime'], 'in', 'range' => [
                self::HEALTH_REGIME_L11, self::HEALTH_REGIME_L11DOM, self::HEALTH_REGIME_L11PG,
                self::HEALTH_REGIME_L11SEM, self::HEALTH_REGIME_ABA, self::HEALTH_REGIME_FKT,
                self::HEALTH_REGIME_PRIVATE, self::HEALTH_REGIME_PDOM, self::HEALTH_REGIME_OTHER
            ]],
            [['therapeutic_plan_id'], 'exist', 'skipOnError' => true, 'targetClass' => TherapeuticPlan::class, 'targetAttribute' => ['therapeutic_plan_id' => 'id']],
            [['treatment_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => TreatmentType::class, 'targetAttribute' => ['treatment_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'therapeutic_plan_id' => 'Piano Terapeutico',
            'treatment_type_id' => 'Tipo Trattamento',
            'weekly_hours' => 'Ore Settimanali',
            'is_group' => 'Terapia di Gruppo',
            'health_regime' => 'Regime Sanitario',
            'notes' => 'Note',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
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
     * Gets query for [[TreatmentType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTreatmentType()
    {
        return $this->hasOne(TreatmentType::class, ['id' => 'treatment_type_id']);
    }

    /**
     * Gets query for [[Appointments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAppointments()
    {
        return $this->hasMany(Appointment::class, ['plan_therapy_id' => 'id']);
    }

    /**
     * Gets health regime labels
     *
     * @return array
     */
    public static function getHealthRegimeLabels()
    {
        return [
            self::HEALTH_REGIME_L11 => 'L11 - Standard',
            self::HEALTH_REGIME_L11DOM => 'L11DOM - Domiciliare',
            self::HEALTH_REGIME_L11PG => 'L11PG - Psicologia',
            self::HEALTH_REGIME_L11SEM => 'L11SEM - Semiresidenziale',
            self::HEALTH_REGIME_ABA => 'ABA - Applied Behavior Analysis',
            self::HEALTH_REGIME_FKT => 'FKT - Fisioterapia',
            self::HEALTH_REGIME_PRIVATE => 'Privato',
            self::HEALTH_REGIME_PDOM => 'PDOM - Prestazioni Domiciliari',
            self::HEALTH_REGIME_OTHER => 'Altro',
        ];
    }

    /**
     * Gets health regime label
     *
     * @return string
     */
    public function getHealthRegimeLabel()
    {
        $labels = static::getHealthRegimeLabels();
        return $labels[$this->health_regime] ?? $this->health_regime;
    }

    /**
     * Checks if therapy is group therapy
     *
     * @return bool
     */
    public function isGroupTherapy()
    {
        return (bool) $this->is_group;
    }

    /**
     * Gets monthly hours (weekly_hours * 4.33)
     *
     * @return float
     */
    public function getMonthlyHours()
    {
        return $this->weekly_hours * 4.33; // Approx weeks per month
    }

    /**
     * Gets total planned sessions based on plan duration and weekly hours
     *
     * @return int
     */
    public function getPlannedSessions()
    {
        if (!$this->therapeuticPlan || !$this->therapeuticPlan->duration_days) {
            return 0;
        }

        $weeks = $this->therapeuticPlan->duration_days / 7;
        $sessionsPerWeek = $this->weekly_hours; // Assuming 1 hour per session
        
        return round($weeks * $sessionsPerWeek);
    }

    /**
     * Gets completed sessions count
     *
     * @return int
     */
    public function getCompletedSessions()
    {
        return $this->getAppointments()
            ->where(['status' => Appointment::STATUS_COMPLETED])
            ->count();
    }

    /**
     * Gets completion percentage
     *
     * @return float
     */
    public function getCompletionPercentage()
    {
        $planned = $this->getPlannedSessions();
        if ($planned == 0) {
            return 0;
        }
        
        return ($this->getCompletedSessions() / $planned) * 100;
    }

    /**
     * Check if therapy uses L11 regime (any variant)
     *
     * @return bool
     */
    public function isL11Regime()
    {
        return in_array($this->health_regime, [
            self::HEALTH_REGIME_L11,
            self::HEALTH_REGIME_L11DOM,
            self::HEALTH_REGIME_L11PG,
            self::HEALTH_REGIME_L11SEM,
        ]);
    }

    /**
     * Check if therapy is domiciliary
     *
     * @return bool
     */
    public function isDomiciliary()
    {
        return in_array($this->health_regime, [
            self::HEALTH_REGIME_L11DOM,
            self::HEALTH_REGIME_PDOM,
        ]);
    }

    /**
     * Check if therapy is private
     *
     * @return bool
     */
    public function isPrivate()
    {
        return $this->health_regime === self::HEALTH_REGIME_PRIVATE;
    }

    /**
     * {@inheritdoc}
     * @return PlanTherapyQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PlanTherapyQuery(get_called_class());
    }
} 
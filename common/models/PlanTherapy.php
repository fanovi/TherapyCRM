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
 * @property int $setting_id
 * @property string|null $notes
 * @property string $created_at
 * @property string $updated_at
 *
 * @property TherapeuticPlan $therapeuticPlan
 * @property TreatmentType $treatmentType
 * @property Setting $setting
 * @property Appointment[] $appointments
 */
class PlanTherapy extends ActiveRecord
{

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
                    return 'TerapiaPiano';
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
            [['therapeutic_plan_id', 'treatment_type_id', 'weekly_hours', 'setting_id'], 'required'],
            [['therapeutic_plan_id', 'treatment_type_id', 'setting_id'], 'integer'],
            [['weekly_hours'], 'number', 'min' => 0.5, 'max' => 999.99],
            [['is_group'], 'boolean'],
            [['notes'], 'string'],
            [['therapeutic_plan_id'], 'exist', 'skipOnError' => true, 'targetClass' => TherapeuticPlan::class, 'targetAttribute' => ['therapeutic_plan_id' => 'id']],
            [['treatment_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => TreatmentType::class, 'targetAttribute' => ['treatment_type_id' => 'id']],
            [['setting_id'], 'exist', 'skipOnError' => true, 'targetClass' => Setting::class, 'targetAttribute' => ['setting_id' => 'id']],
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
            'setting_id' => 'Setting',
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
     * Gets query for [[Setting]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSetting()
    {
        return $this->hasOne(Setting::class, ['id' => 'setting_id']);
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
     * Gets setting name
     *
     * @return string|null
     */
    public function getSettingName()
    {
        return $this->setting ? $this->setting->nome : null;
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
     * Check if setting is domiciliary
     *
     * @return bool
     */
    public function isDomiciliary()
    {
        return $this->setting && $this->setting->nome === 'Domiciliare';
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
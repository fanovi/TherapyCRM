<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "absence_counters".
 *
 * @property int $id
 * @property int $therapist_id
 * @property int $year
 * @property int $vacation_days_total
 * @property int $vacation_days_used
 * @property int $sick_days_used
 * @property int $personal_days_used
 * @property int $training_days_used
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Therapist $therapist
 */
class AbsenceCounter extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%absence_counters}}';
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
                    return 'Contatore Assenze';
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
            [['therapist_id', 'year'], 'required'],
            [['therapist_id', 'year', 'vacation_days_total', 'vacation_days_used', 'sick_days_used', 'personal_days_used', 'training_days_used'], 'integer'],
            [['year'], 'integer', 'min' => 2020, 'max' => 2050],
            [['vacation_days_total'], 'integer', 'min' => 0, 'max' => 365],
            [['vacation_days_used', 'sick_days_used', 'personal_days_used', 'training_days_used'], 'integer', 'min' => 0],
            [['vacation_days_used'], 'validateVacationDaysUsed'],
            [['therapist_id', 'year'], 'unique', 'targetAttribute' => ['therapist_id', 'year']],
            [['therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['therapist_id' => 'id']],
        ];
    }

    /**
     * Validates vacation days used
     */
    public function validateVacationDaysUsed($attribute, $params)
    {
        if (!empty($this->$attribute) && !empty($this->vacation_days_total)) {
            if ($this->$attribute > $this->vacation_days_total) {
                $this->addError($attribute, 'I giorni di ferie utilizzati non possono superare il totale disponibile');
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
            'therapist_id' => 'Terapista',
            'year' => 'Anno',
            'vacation_days_total' => 'Giorni Ferie Totali',
            'vacation_days_used' => 'Giorni Ferie Utilizzati',
            'sick_days_used' => 'Giorni Malattia Utilizzati',
            'personal_days_used' => 'Giorni Personali Utilizzati',
            'training_days_used' => 'Giorni Formazione Utilizzati',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
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
     * Gets remaining vacation days
     *
     * @return int
     */
    public function getRemainingVacationDays()
    {
        return max(0, $this->vacation_days_total - $this->vacation_days_used);
    }

    /**
     * Gets total days used
     *
     * @return int
     */
    public function getTotalDaysUsed()
    {
        return $this->vacation_days_used + $this->sick_days_used + $this->personal_days_used + $this->training_days_used;
    }

    /**
     * Adds vacation days
     *
     * @param int $days
     * @return bool
     */
    public function addVacationDays($days)
    {
        if ($this->vacation_days_used + $days <= $this->vacation_days_total) {
            $this->vacation_days_used += $days;
            return $this->save(false);
        }
        return false;
    }

    /**
     * Adds sick days
     *
     * @param int $days
     * @return bool
     */
    public function addSickDays($days)
    {
        $this->sick_days_used += $days;
        return $this->save(false);
    }

    /**
     * Adds personal days
     *
     * @param int $days
     * @return bool
     */
    public function addPersonalDays($days)
    {
        $this->personal_days_used += $days;
        return $this->save(false);
    }

    /**
     * Adds training days
     *
     * @param int $days
     * @return bool
     */
    public function addTrainingDays($days)
    {
        $this->training_days_used += $days;
        return $this->save(false);
    }

    /**
     * Finds or creates counter for therapist and year
     *
     * @param int $therapistId
     * @param int $year
     * @param int $vacationDaysTotal
     * @return static
     */
    public static function findOrCreate($therapistId, $year, $vacationDaysTotal = 30)
    {
        $counter = static::findOne(['therapist_id' => $therapistId, 'year' => $year]);
        
        if (!$counter) {
            $counter = new static([
                'therapist_id' => $therapistId,
                'year' => $year,
                'vacation_days_total' => $vacationDaysTotal,
                'vacation_days_used' => 0,
                'sick_days_used' => 0,
                'personal_days_used' => 0,
                'training_days_used' => 0,
            ]);
            $counter->save();
        }
        
        return $counter;
    }
} 
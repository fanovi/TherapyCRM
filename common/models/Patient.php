<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "patients".
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $birth_date
 * @property string|null $fiscal_code
 * @property int|null $district_id
 * @property string|null $notes
 * @property string $created_at
 * @property string $updated_at
 *
 * @property District $district
 * @property AccountPatient[] $accountPatients
 * @property TherapeuticPlan[] $therapeuticPlans
 * @property Appointment[] $appointments
 */
class Patient extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%patients}}';
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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['create'] = ['first_name', 'last_name', 'birth_date', 'fiscal_code', 'district_id', 'notes'];
        $scenarios['update'] = ['first_name', 'last_name', 'birth_date', 'fiscal_code', 'district_id', 'notes'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['first_name', 'last_name', 'birth_date'], 'required'],
            [['birth_date'], 'date', 'format' => 'php:Y-m-d'],
            [['birth_date'], 'validateBirthDate'],
            [['district_id'], 'integer'],
            [['notes'], 'string'],
            [['first_name', 'last_name'], 'string', 'max' => 100],
            [['first_name', 'last_name'], 'match', 'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/u', 'message' => 'Il nome può contenere solo lettere, spazi, apostrofi e trattini'],
            [['fiscal_code'], 'string', 'max' => 16],
            [['fiscal_code'], 'match', 'pattern' => '/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/', 'message' => 'Codice fiscale non valido'],
            [['fiscal_code'], 'unique'],
            [['district_id'], 'exist', 'skipOnError' => true, 'targetClass' => District::class, 'targetAttribute' => ['district_id' => 'id']],
        ];
    }

    /**
     * Validates birth date
     */
    public function validateBirthDate($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            $birthDate = \DateTime::createFromFormat('Y-m-d', $this->$attribute);
            $today = new \DateTime();
            
            if ($birthDate > $today) {
                $this->addError($attribute, 'La data di nascita non può essere nel futuro');
            } elseif ($birthDate < $today->modify('-120 years')) {
                $this->addError($attribute, 'La data di nascita non può essere anteriore a 120 anni fa');
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
            'first_name' => 'Nome',
            'last_name' => 'Cognome',
            'birth_date' => 'Data di Nascita',
            'fiscal_code' => 'Codice Fiscale',
            'district_id' => 'Distretto',
            'notes' => 'Note',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
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
     * Gets query for [[AccountPatients]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAccountPatients()
    {
        return $this->hasMany(AccountPatient::class, ['patient_id' => 'id']);
    }

    /**
     * Gets query for [[TherapeuticPlans]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTherapeuticPlans()
    {
        return $this->hasMany(TherapeuticPlan::class, ['patient_id' => 'id']);
    }

    /**
     * Gets query for [[Appointments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAppointments()
    {
        return $this->hasMany(Appointment::class, ['patient_id' => 'id']);
    }

    /**
     * Gets full name
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Gets age
     *
     * @return int|null
     */
    public function getAge()
    {
        if (!$this->birth_date) {
            return null;
        }

        $birthDate = new \DateTime($this->birth_date);
        $today = new \DateTime();
        
        return $today->diff($birthDate)->y;
    }

    /**
     * Gets formatted birth date
     *
     * @return string
     */
    public function getFormattedBirthDate()
    {
        return $this->birth_date ? Yii::$app->formatter->asDate($this->birth_date) : '';
    }

    /**
     * Gets all patients for dropdown
     *
     * @return array
     */
    public static function getDropdownData()
    {
        return ArrayHelper::map(
            static::find()->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])->all(),
            'id',
            'fullName'
        );
    }

    /**
     * Searches patients by name or fiscal code
     *
     * @param string $query
     * @return \yii\db\ActiveQuery
     */
    public static function search($query)
    {
        return static::find()
            ->where(['like', 'first_name', $query])
            ->orWhere(['like', 'last_name', $query])
            ->orWhere(['like', 'fiscal_code', $query])
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC]);
    }

    /**
     * Gets active therapeutic plan
     *
     * @return TherapeuticPlan|null
     */
    public function getActiveTherapeuticPlan()
    {
        return $this->getTherapeuticPlans()
            ->where(['status' => 'active'])
            ->one();
    }

    /**
     * {@inheritdoc}
     * @return PatientQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PatientQuery(get_called_class());
    }
} 
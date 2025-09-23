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
 * @property string|null $birth_city
 * @property string|null $birth_province_name
 * @property string|null $birth_province_code
 * @property int $born_in_italy
 * @property string|null $residence_address
 * @property string|null $residence_city
 * @property string|null $residence_province_name
 * @property string|null $residence_province_code
 * @property string|null $residence_postal_code
 * @property string|null $phone_number
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
                'value' => function () {
                    return date('Y-m-d H:i:s');
                },
            ],

            // Activity Logging
            [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => [
                    'created_at',
                    'updated_at',
                ],
                'entityNameCallback' => function ($model) {
                    return 'Paziente';
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
        $scenarios['create'] = [
            'first_name', 'last_name', 'birth_date', 'birth_city', 'birth_province_name', 'birth_province_code', 'born_in_italy',
            'residence_address', 'residence_city', 'residence_province_name', 'residence_province_code', 'residence_postal_code',
            'phone_number', 'fiscal_code', 'district_id', 'notes'
        ];
        $scenarios['update'] = [
            'first_name', 'last_name', 'birth_date', 'birth_city', 'birth_province_name', 'birth_province_code', 'born_in_italy',
            'residence_address', 'residence_city', 'residence_province_name', 'residence_province_code', 'residence_postal_code',
            'phone_number', 'fiscal_code', 'district_id', 'notes'
        ];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['first_name', 'last_name', 'birth_date'], 'required', 'message' => '{attribute} è obbligatorio.'],
            [['birth_date'], 'date', 'format' => 'php:Y-m-d', 'message' => 'La data di nascita deve essere in formato valido.'],
            [['birth_date'], 'validateBirthDate'],
            [['district_id', 'born_in_italy'], 'integer', 'message' => '{attribute} deve essere un numero valido.'],
            [['born_in_italy'], 'in', 'range' => [0, 1], 'message' => 'Valore non valido per {attribute}.'],
            [['notes'], 'string'],
            [['first_name', 'last_name'], 'string', 'max' => 100, 'message' => '{attribute} non può superare i 100 caratteri.'],
            [['first_name', 'last_name'], 'match', 'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/u', 'message' => 'Il nome può contenere solo lettere, spazi, apostrofi e trattini'],
            [['birth_city', 'birth_province_name', 'residence_city', 'residence_province_name'], 'string', 'max' => 100, 'message' => '{attribute} non può superare i 100 caratteri.'],
            [['birth_province_code', 'residence_province_code'], 'string', 'max' => 2, 'message' => '{attribute} deve essere di 2 caratteri.'],
            [['birth_province_code', 'residence_province_code'], 'match', 'pattern' => '/^[A-Z]{2}$/', 'message' => '{attribute} deve contenere solo 2 lettere maiuscole.'],
            [['residence_address'], 'string', 'max' => 255, 'message' => '{attribute} non può superare i 255 caratteri.'],
            [['residence_postal_code'], 'string', 'max' => 5, 'message' => '{attribute} deve essere di 5 cifre.'],
            [['residence_postal_code'], 'match', 'pattern' => '/^[0-9]{5}$/', 'message' => '{attribute} deve contenere solo 5 cifre numeriche.'],
            [['phone_number'], 'string', 'max' => 20, 'message' => '{attribute} non può superare i 20 caratteri.'],
            [['phone_number'], 'match', 'pattern' => '/^[+]?[0-9\s\-\.\(\)]+$/', 'message' => '{attribute} contiene caratteri non validi.'],
            [['fiscal_code'], 'string', 'max' => 20, 'message' => 'Il codice fiscale non può superare i 20 caratteri.'],
            [
                ['fiscal_code'],
                'match',
                'pattern' => '/^[A-Z0-9]{5,20}$/',
                'message' => 'Codice fiscale non valido (solo lettere maiuscole e numeri, lunghezza 5-20 caratteri)'
            ],
            [['fiscal_code'], 'unique', 'message' => 'Questo codice fiscale è già stato registrato.'],
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
            'birth_city' => 'Comune di Nascita',
            'birth_province_name' => 'Provincia di Nascita',
            'birth_province_code' => 'Sigla Provincia di Nascita',
            'born_in_italy' => 'Nato in Italia',
            'residence_address' => 'Indirizzo di Residenza',
            'residence_city' => 'Comune di Residenza',
            'residence_province_name' => 'Provincia di Residenza',
            'residence_province_code' => 'Sigla Provincia di Residenza',
            'residence_postal_code' => 'CAP',
            'phone_number' => 'Numero di Telefono',
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
     * Gets formatted birth location
     *
     * @return string
     */
    public function getBirthLocation()
    {
        if ($this->born_in_italy) {
            if ($this->birth_city && $this->birth_province_code) {
                return $this->birth_city . ' (' . $this->birth_province_code . ')';
            } elseif ($this->birth_city) {
                return $this->birth_city;
            }
            return 'Italia';
        }
        return 'Estero';
    }

    /**
     * Gets formatted residence address
     *
     * @return string
     */
    public function getFullResidenceAddress()
    {
        $parts = [];
        
        if ($this->residence_address) {
            $parts[] = $this->residence_address;
        }
        
        if ($this->residence_city) {
            $cityPart = $this->residence_city;
            if ($this->residence_province_code) {
                $cityPart .= ' (' . $this->residence_province_code . ')';
            }
            if ($this->residence_postal_code) {
                $cityPart = $this->residence_postal_code . ' ' . $cityPart;
            }
            $parts[] = $cityPart;
        }
        
        return implode(', ', $parts);
    }

    /**
     * Gets all patients for dropdown
     *
     * @return array
     */
    public static function getListData()
    {
        return ArrayHelper::map(
            static::find()->orderBy('last_name, first_name')->all(),
            'id',
            function ($model) {
                return $model->last_name . ' ' . $model->first_name;
            }
        );
    }

    /**
     * Gets linked users (accounts) for this patient
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinkedUsers()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->viaTable('{{%account_patients}}', ['patient_id' => 'id'])
            ->where(['users.status' => User::STATUS_ACTIVE]);
    }

    /**
     * Gets IDs of all linked users for this patient
     *
     * @return array
     */
    public function getLinkedUserIds()
    {
        return $this->getLinkedUsers()->select('id')->column();
    }

    /**
     * Gets all linked users for multiple patients
     *
     * @param array $patientIds
     * @return array Array of user IDs
     */
    public static function getLinkedUsersForPatients($patientIds)
    {
        return AccountPatient::find()
            ->select('user_id')
            ->distinct()
            ->where(['patient_id' => $patientIds])
            ->innerJoin('users', 'users.id = account_patients.user_id')
            ->andWhere(['users.status' => User::STATUS_ACTIVE])
            ->column();
    }

    /**
     * Search patients by query string
     *
     * @param string $query
     * @return array
     */
    public static function search($query)
    {
        return static::find()
            ->where(['like', 'first_name', $query])
            ->orWhere(['like', 'last_name', $query])
            ->orWhere(['like', 'fiscal_code', $query])
            ->orWhere(['like', 'birth_city', $query])
            ->orWhere(['like', 'residence_city', $query])
            ->orWhere(['like', 'phone_number', $query])
            ->limit(20)
            ->all();
    }

    /**
     * Gets active therapeutic plan for this patient
     *
     * @return TherapeuticPlan|null
     */
    public function getActiveTherapeuticPlan()
    {
        return $this->getTherapeuticPlans()
            ->where(['>=', 'end_date', date('Y-m-d')])
            ->orderBy(['created_at' => SORT_DESC])
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

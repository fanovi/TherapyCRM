<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "specialization_treatments".
 *
 * @property int $id
 * @property int $specialization_id
 * @property int $treatment_type_id
 *
 * @property Specialization $specialization
 * @property TreatmentType $treatmentType
 */
class SpecializationTreatment extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%specialization_treatments}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['specialization_id', 'treatment_type_id'], 'required'],
            [['specialization_id', 'treatment_type_id'], 'integer'],
            [['specialization_id', 'treatment_type_id'], 'unique', 'targetAttribute' => ['specialization_id', 'treatment_type_id']],
            [['specialization_id'], 'exist', 'skipOnError' => true, 'targetClass' => Specialization::class, 'targetAttribute' => ['specialization_id' => 'id']],
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
            'specialization_id' => 'Specializzazione',
            'treatment_type_id' => 'Tipo Trattamento',
        ];
    }

    /**
     * Gets query for [[Specialization]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSpecialization()
    {
        return $this->hasOne(Specialization::class, ['id' => 'specialization_id']);
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
     * Gets formatted combination name
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->specialization->name . ' - ' . $this->treatmentType->name;
    }

    /**
     * Gets all combinations for dropdown
     *
     * @return array
     */
    public static function getDropdownData()
    {
        $combinations = static::find()
            ->joinWith(['specialization', 'treatmentType'])
            ->orderBy(['specializations.name' => SORT_ASC, 'treatment_types.name' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($combinations as $combination) {
            $result[$combination->id] = $combination->getFullName();
        }

        return $result;
    }

    /**
     * Gets combinations by specialization
     *
     * @param int $specializationId
     * @return static[]
     */
    public static function findBySpecialization($specializationId)
    {
        return static::find()
            ->where(['specialization_id' => $specializationId])
            ->joinWith('treatmentType')
            ->orderBy('treatment_types.name')
            ->all();
    }

    /**
     * Gets default duration for treatment
     *
     * @return int
     */
    public function getDefaultDuration()
    {
        return $this->duration_minutes ?: 45; // Default 45 minutes
    }

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
                    return 'Associazione Specializzazione-Trattamento';
                },
            ],
        ];
    }
} 
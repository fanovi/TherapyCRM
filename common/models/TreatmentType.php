<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "treatment_types".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 *
 * @property SpecializationTreatment[] $specializationTreatments
 * @property PlanTherapy[] $planTherapies
 */
class TreatmentType extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%treatment_types}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'name'], 'required'],
            [['description'], 'string'],
            [['code'], 'string', 'max' => 50],
            [['name'], 'string', 'max' => 100],
            [['code'], 'unique'],
            [['code'], 'match', 'pattern' => '/^[A-Z_]+$/', 'message' => 'Il codice deve contenere solo lettere maiuscole e underscore'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Codice',
            'name' => 'Nome',
            'description' => 'Descrizione',
        ];
    }

    /**
     * Gets query for [[SpecializationTreatments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSpecializationTreatments()
    {
        return $this->hasMany(SpecializationTreatment::class, ['treatment_type_id' => 'id']);
    }

    /**
     * Gets query for [[PlanTherapies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlanTherapies()
    {
        return $this->hasMany(PlanTherapy::class, ['treatment_type_id' => 'id']);
    }

    /**
     * Gets query for [[Specializations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSpecializations()
    {
        return $this->hasMany(Specialization::class, ['id' => 'specialization_id'])
            ->viaTable('{{%specialization_treatments}}', ['treatment_type_id' => 'id']);
    }

    /**
     * Finds treatment type by code
     *
     * @param string $code
     * @return static|null
     */
    public static function findByCode($code)
    {
        return static::findOne(['code' => $code]);
    }

    /**
     * Gets formatted name with code
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->code . ' - ' . $this->name;
    }

    /**
     * Gets all treatment types for dropdown
     *
     * @return array
     */
    public static function getDropdownData()
    {
        return ArrayHelper::map(
            static::find()->orderBy('name')->all(),
            'id',
            'name'
        );
    }

    /**
     * Gets treatment types grouped by specialization
     *
     * @return array
     */
    public static function getGroupedBySpecialization()
    {
        $result = [];
        $treatmentTypes = static::find()
            ->joinWith('specializations')
            ->orderBy(['specializations.name' => SORT_ASC, 'treatment_types.name' => SORT_ASC])
            ->all();

        foreach ($treatmentTypes as $treatmentType) {
            foreach ($treatmentType->specializations as $specialization) {
                $result[$specialization->name][$treatmentType->id] = $treatmentType->name;
            }
        }

        return $result;
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
                    return 'Tipo Trattamento';
                },
            ],
        ];
    }
} 
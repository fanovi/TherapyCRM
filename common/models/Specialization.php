<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Specialization model
 *
 * @property integer $id
 * @property string $code
 * @property string $name
 * @property string $description
 *
 * @property Therapist[] $therapists
 */
class Specialization extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%specializations}}';
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
     * Terapisti che possiedono questa specializzazione (N:N via tabella ponte).
     * @return \yii\db\ActiveQuery
     */
    public function getTherapists()
    {
        return $this->hasMany(Therapist::class, ['id' => 'therapist_id'])
            ->viaTable('{{%therapist_specializations}}', ['specialization_id' => 'id']);
    }

    /**
     * Gets query for [[SpecializationTreatments]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSpecializationTreatments()
    {
        return $this->hasMany(SpecializationTreatment::class, ['specialization_id' => 'id']);
    }

    /**
     * Gets query for [[TreatmentTypes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTreatmentTypes()
    {
        return $this->hasMany(TreatmentType::class, ['id' => 'treatment_type_id'])
            ->viaTable('{{%specialization_treatments}}', ['specialization_id' => 'id']);
    }

    /**
     * Restituisce tutte le specializzazioni come array key-value per dropdown
     * @return array
     */
    public static function getDropdownList()
    {
        return static::find()
            ->select(['name', 'id'])
            ->indexBy('id')
            ->column();
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
                    return 'Specializzazione';
                },
            ],
        ];
    }
} 
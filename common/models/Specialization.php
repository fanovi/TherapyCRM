<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

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
     * @return \yii\db\ActiveQuery
     */
    public function getTherapists()
    {
        return $this->hasMany(Therapist::class, ['specialization_id' => 'id']);
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
} 
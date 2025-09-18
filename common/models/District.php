<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "districts".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $asl_reference
 *
 * @property Patient[] $patients
 */
class District extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%districts}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'name'], 'required'],
            [['code'], 'string', 'max' => 10],
            [['name', 'asl_reference'], 'string', 'max' => 100],
            [['code'], 'unique'],
            [['code'], 'match', 'pattern' => '/^[A-Z0-9]+$/', 'message' => 'Il codice deve contenere solo lettere maiuscole e numeri'],
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
            'asl_reference' => 'Riferimento ASL',
        ];
    }

    /**
     * Gets query for [[Patients]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPatients()
    {
        return $this->hasMany(Patient::class, ['district_id' => 'id']);
    }

    /**
     * Finds district by code
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
     * Gets all districts for dropdown
     *
     * @return array
     */
    public static function getDropdownData()
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
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => [],
                'entityNameCallback' => function($model) {
                    return 'Distretto';
                },
            ],
        ];
    }
} 
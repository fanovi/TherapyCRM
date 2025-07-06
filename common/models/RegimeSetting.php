<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "regime_setting".
 *
 * @property int $id
 * @property int $regime_id
 * @property int $setting_id
 *
 * @property Regime $regime
 * @property Setting $setting
 */
class RegimeSetting extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%regime_setting}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['regime_id', 'setting_id'], 'required'],
            [['regime_id', 'setting_id'], 'integer'],
            [['regime_id'], 'exist', 'skipOnError' => true, 'targetClass' => Regime::class, 'targetAttribute' => ['regime_id' => 'id']],
            [['setting_id'], 'exist', 'skipOnError' => true, 'targetClass' => Setting::class, 'targetAttribute' => ['setting_id' => 'id']],
            [['regime_id', 'setting_id'], 'unique', 'targetAttribute' => ['regime_id', 'setting_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'regime_id' => 'Regime',
            'setting_id' => 'Setting',
        ];
    }

    /**
     * Gets query for [[Regime]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegime()
    {
        return $this->hasOne(Regime::class, ['id' => 'regime_id']);
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
} 
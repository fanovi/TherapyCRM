<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "setting".
 *
 * @property int $id
 * @property string $nome
 *
 * @property PlanTherapy[] $planTherapies
 * @property RegimeSetting[] $regimeSettings
 * @property Regime[] $regimes
 */
class Setting extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%setting}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nome'], 'required'],
            [['nome'], 'string', 'max' => 255],
            [['nome'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nome' => 'Nome',
        ];
    }

    /**
     * Gets query for [[PlanTherapies]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPlanTherapies()
    {
        return $this->hasMany(PlanTherapy::class, ['setting_id' => 'id']);
    }

    /**
     * Gets query for [[RegimeSettings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegimeSettings()
    {
        return $this->hasMany(RegimeSetting::class, ['setting_id' => 'id']);
    }

    /**
     * Gets query for [[Regimes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegimes()
    {
        return $this->hasMany(Regime::class, ['id' => 'regime_id'])
            ->viaTable('{{%regime_setting}}', ['setting_id' => 'id']);
    }

    /**
     * Get all settings for dropdown
     *
     * @return array
     */
    public static function getDropdownOptions()
    {
        return ArrayHelper::map(static::find()->all(), 'id', 'nome');
    }

    /**
     * Get settings available for a specific regime
     *
     * @param int $regimeId
     * @return array
     */
    public static function getByRegime($regimeId)
    {
        return static::find()
            ->joinWith('regimes')
            ->where(['regime.id' => $regimeId])
            ->all();
    }

    /**
     * Get settings dropdown for a specific regime
     *
     * @param int $regimeId
     * @return array
     */
    public static function getDropdownByRegime($regimeId)
    {
        return ArrayHelper::map(static::getByRegime($regimeId), 'id', 'nome');
    }
} 
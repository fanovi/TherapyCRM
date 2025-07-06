<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "regime".
 *
 * @property int $id
 * @property string $nome
 * @property string|null $descrizione
 *
 * @property TherapeuticPlan[] $therapeuticPlans
 * @property RegimeSetting[] $regimeSettings
 * @property Setting[] $settings
 */
class Regime extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%regime}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nome'], 'required'],
            [['descrizione'], 'string'],
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
            'descrizione' => 'Descrizione',
        ];
    }

    /**
     * Gets query for [[TherapeuticPlans]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTherapeuticPlans()
    {
        return $this->hasMany(TherapeuticPlan::class, ['regime_id' => 'id']);
    }

    /**
     * Gets query for [[RegimeSettings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegimeSettings()
    {
        return $this->hasMany(RegimeSetting::class, ['regime_id' => 'id']);
    }

    /**
     * Gets query for [[Settings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSettings()
    {
        return $this->hasMany(Setting::class, ['id' => 'setting_id'])
            ->viaTable('{{%regime_setting}}', ['regime_id' => 'id']);
    }

    /**
     * Get all regimes for dropdown
     *
     * @return array
     */
    public static function getDropdownOptions()
    {
        return ArrayHelper::map(static::find()->all(), 'id', 'nome');
    }

    /**
     * Get settings available for this regime
     *
     * @return array
     */
    public function getAvailableSettings()
    {
        return $this->getSettings()->all();
    }

    /**
     * Get settings dropdown for this regime
     *
     * @return array
     */
    public function getSettingsDropdown()
    {
        return ArrayHelper::map($this->getAvailableSettings(), 'id', 'nome');
    }
} 
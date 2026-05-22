<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Tabella ponte therapist_specializations (N specializzazioni per terapista).
 *
 * @property int $id
 * @property int $therapist_id
 * @property int $specialization_id
 * @property int|null $is_primary  // 1 = specializzazione principale; NULL altrimenti
 * @property string $created_at
 *
 * @property Therapist $therapist
 * @property Specialization $specialization
 */
class TherapistSpecialization extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%therapist_specializations}}';
    }

    public function rules()
    {
        return [
            [['therapist_id', 'specialization_id'], 'required'],
            [['therapist_id', 'specialization_id', 'is_primary'], 'integer'],
            [['created_at'], 'safe'],
            [['therapist_id', 'specialization_id'], 'unique', 'targetAttribute' => ['therapist_id', 'specialization_id']],
            [['therapist_id'], 'exist', 'skipOnError' => true, 'targetClass' => Therapist::class, 'targetAttribute' => ['therapist_id' => 'id']],
            [['specialization_id'], 'exist', 'skipOnError' => true, 'targetClass' => Specialization::class, 'targetAttribute' => ['specialization_id' => 'id']],
        ];
    }

    public function getTherapist()
    {
        return $this->hasOne(Therapist::class, ['id' => 'therapist_id']);
    }

    public function getSpecialization()
    {
        return $this->hasOne(Specialization::class, ['id' => 'specialization_id']);
    }
}

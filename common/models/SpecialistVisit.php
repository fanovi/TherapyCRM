<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "specialist_visits".
 *
 * @property int $id
 * @property int $patient_id
 * @property string $visit_date
 * @property string $specialist_name
 * @property string|null $specialist_type
 * @property string|null $diagnosis
 * @property string|null $recommendations
 * @property string|null $notes
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Patient $patient
 */
class SpecialistVisit extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%specialist_visits}}';
    }

    /**
     * {@inheritdoc}
     */
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
                    return 'Visita Specialistica';
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['patient_id', 'visit_date', 'specialist_name'], 'required'],
            [['patient_id'], 'integer'],
            [['visit_date'], 'date', 'format' => 'php:Y-m-d'],
            [['diagnosis', 'recommendations', 'notes'], 'string'],
            [['specialist_name'], 'string', 'max' => 255],
            [['specialist_type'], 'string', 'max' => 100],
            [['patient_id'], 'exist', 'skipOnError' => true, 'targetClass' => Patient::class, 'targetAttribute' => ['patient_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'patient_id' => 'Paziente',
            'visit_date' => 'Data Visita',
            'specialist_name' => 'Nome Specialista',
            'specialist_type' => 'Tipo Specialista',
            'diagnosis' => 'Diagnosi',
            'recommendations' => 'Raccomandazioni',
            'notes' => 'Note',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * Gets query for [[Patient]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPatient()
    {
        return $this->hasOne(Patient::class, ['id' => 'patient_id']);
    }

    /**
     * Gets formatted visit date
     *
     * @return string
     */
    public function getFormattedVisitDate()
    {
        return Yii::$app->formatter->asDate($this->visit_date);
    }

    /**
     * Gets visits for specific patient
     *
     * @param int $patientId
     * @return static[]
     */
    public static function findByPatient($patientId)
    {
        return static::find()
            ->where(['patient_id' => $patientId])
            ->orderBy(['visit_date' => SORT_DESC])
            ->all();
    }
} 
<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "complaint".
 *
 * @property int $id ID del reclamo
 * @property int $account_id ID dell'account che ha creato il reclamo (FK a users)
 * @property int $patient_id ID del paziente correlato al reclamo (FK a patients)
 * @property string $title Titolo del reclamo
 * @property string $description Descrizione dettagliata del reclamo
 * @property string|null $created_at Data e ora di creazione del reclamo
 *
 * @property Users $account
 * @property Patients $patient
 */
class Complaint extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'complaint';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['account_id', 'patient_id', 'title', 'description'], 'required'],
            [['account_id', 'patient_id'], 'integer'],
            [['description'], 'string'],
            [['created_at'], 'safe'],
            [['title'], 'string', 'max' => 200],
            [['account_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['account_id' => 'id']],
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
            'account_id' => 'Account ID',
            'patient_id' => 'Patient ID',
            'title' => 'Title',
            'description' => 'Description',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Account]].
     *
     * @return \yii\db\ActiveQuery|UserQuery
     */
    public function getAccount()
    {
        return $this->hasOne(User::class, ['id' => 'account_id']);
    }

    /**
     * Gets query for [[Patient]].
     *
     * @return \yii\db\ActiveQuery|PatientQuery
     */
    public function getPatient()
    {
        return $this->hasOne(Patient::class, ['id' => 'patient_id']);
    }

    /**
     * {@inheritdoc}
     * @return ComplaintQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ComplaintQuery(get_called_class());
    }

}

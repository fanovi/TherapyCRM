<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Therapist model
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $specialization_id
 * @property integer $weekly_hours_contract
 * @property string $calendar_color
 * @property boolean $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 * @property Specialization $specialization
 */
class Therapist extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%therapists}}';
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
             // Activity Logging
             [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => [
                    'created_at',
                    'updated_at',
                ],
                'entityNameCallback' => function($model) {
                    return 'Terapista';
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
            [['user_id', 'specialization_id', 'weekly_hours_contract'], 'required', 'message' => '{attribute} è obbligatorio.'],
            [['user_id', 'specialization_id', 'weekly_hours_contract'], 'integer', 'message' => '{attribute} deve essere un numero valido.'],
            [['weekly_hours_contract'], 'integer', 'min' => 1, 'max' => 40, 'message' => 'Le ore settimanali devono essere comprese tra 1 e 40.'],
            [['is_active'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
            [['calendar_color'], 'string', 'max' => 7, 'message' => 'Il colore del calendario non è valido.'],
            [['calendar_color'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/', 'message' => 'Il colore deve essere in formato esadecimale (es. #FF0000).'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id'], 'message' => 'Utente non valido.'],
            [['specialization_id'], 'exist', 'skipOnError' => true, 'targetClass' => Specialization::class, 'targetAttribute' => ['specialization_id' => 'id'], 'message' => 'Specializzazione non valida.'],
            [['user_id'], 'unique', 'message' => 'Questo utente è già associato a un altro terapista.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Utente',
            'specialization_id' => 'Specializzazione',
            'weekly_hours_contract' => 'Ore Settimanali Contratto',
            'calendar_color' => 'Colore Calendario',
            'is_active' => 'Attivo',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * Relazione con l'utente
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Relazione con la specializzazione
     * @return \yii\db\ActiveQuery
     */
    public function getSpecialization()
    {
        return $this->hasOne(Specialization::class, ['id' => 'specialization_id']);
    }

    /**
     * Scope per terapisti attivi
     * @return \yii\db\ActiveQuery
     */
    public static function findActive()
    {
        return static::find()->where(['is_active' => true]);
    }

    /**
     * Trova terapista per user_id
     * @param int $userId
     * @return static|null
     */
    public static function findByUserId($userId)
    {
        return static::findOne(['user_id' => $userId]);
    }

    /**
     * Genera un colore casuale per il calendario
     * @return string
     */
    public function generateRandomColor()
    {
        $colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FECA57',
            '#FF9FF3', '#54A0FF', '#5F27CD', '#00D2D3', '#FF9F43',
            '#10AC84', '#EE5A52', '#0ABDE3', '#006BA6', '#F79F1F'
        ];
        
        return $colors[array_rand($colors)];
    }

    /**
     * Hook prima del salvataggio
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if ($insert && empty($this->calendar_color)) {
            $this->calendar_color = $this->generateRandomColor();
        }
        
        return parent::beforeSave($insert);
    }

    /**
     * {@inheritdoc}
     * @return TherapistQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TherapistQuery(get_called_class());
    }
} 
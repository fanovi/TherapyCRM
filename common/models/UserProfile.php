<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * UserProfile model
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $fiscal_code
 * @property string $phone
 * @property string $address
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 */
class UserProfile extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_profiles}}';
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
                    return 'ProfiloUtente';
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
            [['user_id', 'first_name', 'last_name'], 'required', 'message' => '{attribute} è obbligatorio.'],
            [['user_id'], 'integer', 'message' => 'L\'ID utente deve essere un numero valido.'],
            [['address', 'phone'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['first_name', 'last_name'], 'string', 'max' => 100, 'message' => '{attribute} non può superare i 100 caratteri.'],
            [['fiscal_code'], 'string', 'max' => 16, 'message' => 'Il codice fiscale non può superare i 16 caratteri.'],
            [['fiscal_code'], 'unique', 'message' => 'Questo codice fiscale è già stato registrato.'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id'], 'message' => 'Utente non valido.'],
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
            'first_name' => 'Nome',
            'last_name' => 'Cognome',
            'fiscal_code' => 'Codice Fiscale',
            'phone' => 'Telefono',
            'address' => 'Indirizzo',
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
     * Restituisce il nome completo
     * @return string
     */
    public function getFullName()
    {
        return $this->last_name . ' ' . $this->first_name;
    }
} 
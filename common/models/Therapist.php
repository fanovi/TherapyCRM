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
 * @property boolean $is_internal
 * @property boolean $can_supervise
 * @property boolean $can_parental_training
 * @property boolean $is_aba
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 * @property Specialization $specialization
 * @property Specialization[] $specializations
 * @property Specialization|null $primarySpecialization
 * @property TherapistSpecialization[] $therapistSpecializations
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
            [['is_active', 'is_internal', 'can_supervise', 'can_parental_training', 'is_aba'], 'boolean'],
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
            'is_internal' => 'Dipendente',
            'can_supervise' => 'Supervisione',
            'can_parental_training' => 'Parental Training',
            'is_aba' => 'ABA',
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
     * Relazione con la specializzazione "principale" (legacy).
     *
     * Punta a therapists.specialization_id, mantenuta per backward-compat
     * con il codice che si aspetta una sola specializzazione per terapista
     * (es. app mobile via AuthController::buildTherapistData). La sorgente
     * autoritativa di "specializzazione principale" è la riga della tabella
     * ponte con is_primary = 1 — vedi getPrimarySpecialization().
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSpecialization()
    {
        return $this->hasOne(Specialization::class, ['id' => 'specialization_id']);
    }

    /**
     * Relazione 1:N con le righe della tabella ponte therapist_specializations.
     * @return \yii\db\ActiveQuery
     */
    public function getTherapistSpecializations()
    {
        return $this->hasMany(TherapistSpecialization::class, ['therapist_id' => 'id']);
    }

    /**
     * Tutte le specializzazioni del terapista (N:N via therapist_specializations).
     * @return \yii\db\ActiveQuery
     */
    public function getSpecializations()
    {
        return $this->hasMany(Specialization::class, ['id' => 'specialization_id'])
            ->viaTable('{{%therapist_specializations}}', ['therapist_id' => 'id']);
    }

    /**
     * Specializzazione marcata is_primary = 1 nella tabella ponte (se presente).
     * @return \yii\db\ActiveQuery
     */
    public function getPrimarySpecialization()
    {
        return $this->hasOne(Specialization::class, ['id' => 'specialization_id'])
            ->viaTable('{{%therapist_specializations}}', ['therapist_id' => 'id'], function ($q) {
                $q->andWhere(['is_primary' => 1]);
            });
    }

    /**
     * @return int[] ID delle specializzazioni del terapista
     */
    public function getSpecializationIds()
    {
        return TherapistSpecialization::find()
            ->select('specialization_id')
            ->where(['therapist_id' => $this->id])
            ->column();
    }

    /**
     * @param int $specializationId
     * @return bool true se il terapista possiede la specializzazione data
     */
    public function hasSpecialization($specializationId)
    {
        return TherapistSpecialization::find()
            ->where([
                'therapist_id' => $this->id,
                'specialization_id' => (int)$specializationId,
            ])
            ->exists();
    }

    /**
     * Sincronizza le specializzazioni del terapista con la lista fornita
     * (insert/delete sulla tabella ponte) e imposta la specializzazione primaria.
     *
     * Aggiorna anche therapists.specialization_id (legacy) per mantenere
     * coerente la backward-compat finché la colonna non viene rimossa.
     *
     * @param int[] $specializationIds
     * @param int   $primaryId  Deve essere uno degli ID in $specializationIds
     * @throws \yii\db\Exception se la primary non è tra gli ID forniti
     */
    public function syncSpecializations(array $specializationIds, int $primaryId): void
    {
        $specializationIds = array_values(array_unique(array_map('intval', $specializationIds)));
        if (empty($specializationIds)) {
            throw new \yii\db\Exception('Almeno una specializzazione è obbligatoria.');
        }
        if (!in_array($primaryId, $specializationIds, true)) {
            throw new \yii\db\Exception('La specializzazione principale deve essere tra quelle selezionate.');
        }

        $db = static::getDb();
        $tx = $db->beginTransaction();
        try {
            // Cancella tutte e reinserisce: semplice, e i volumi sono piccoli
            // (poche specializzazioni per terapista).
            $db->createCommand()
                ->delete('{{%therapist_specializations}}', ['therapist_id' => $this->id])
                ->execute();

            foreach ($specializationIds as $specId) {
                $db->createCommand()->insert('{{%therapist_specializations}}', [
                    'therapist_id' => $this->id,
                    'specialization_id' => $specId,
                    'is_primary' => $specId === $primaryId ? 1 : null,
                    'created_at' => date('Y-m-d H:i:s'),
                ])->execute();
            }

            // Allinea la colonna legacy.
            if ((int)$this->specialization_id !== $primaryId) {
                $db->createCommand()
                    ->update('{{%therapists}}', ['specialization_id' => $primaryId], ['id' => $this->id])
                    ->execute();
                $this->specialization_id = $primaryId;
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    /**
     * Relazione con le assegnazioni ai gruppi
     * @return \yii\db\ActiveQuery
     */
    public function getGroupTherapists()
    {
        return $this->hasMany(GroupTherapist::class, ['therapist_id' => 'id']);
    }

    /**
     * Relazione con i gruppi attivi
     * @return \yii\db\ActiveQuery
     */
    public function getActiveGroups()
    {
        return $this->hasMany(CoordinatorGroup::class, ['id' => 'group_id'])
            ->viaTable('group_therapists', ['therapist_id' => 'id'], function($query) {
                $query->andWhere(['IS', 'assigned_to', null]);
            });
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
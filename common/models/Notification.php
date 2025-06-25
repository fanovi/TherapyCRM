<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "notifications".
 *
 * @property int $id
 * @property int $recipient_user_id
 * @property int|null $sender_user_id
 * @property string|null $notification_type
 * @property string $title
 * @property string|null $message
 * @property bool $requires_read_confirmation
 * @property string|null $read_at
 * @property string|null $viewed_at
 * @property string|null $scheduled_for
 * @property string|null $sent_at
 * @property string $created_at
 *
 * @property User $recipientUser
 * @property User $senderUser
 */
class Notification extends ActiveRecord
{
    const TYPE_INFO = 'info';
    const TYPE_REMINDER = 'reminder';
    const TYPE_DEADLINE = 'deadline';
    const TYPE_MANDATORY_READ = 'mandatory_read';
    const TYPE_INTERNAL_COMMUNICATION = 'internal_communication';

    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_READ = 'read';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%notifications}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
                'value' => function() {
                    return date('Y-m-d H:i:s');
                }
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['recipient_user_id', 'title'], 'required'],
            [['recipient_user_id', 'sender_user_id'], 'integer'],
            [['message'], 'string'],
            [['requires_read_confirmation'], 'boolean'],
            [['read_at', 'viewed_at', 'scheduled_for', 'sent_at', 'created_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['notification_type'], 'in', 'range' => [self::TYPE_INFO, self::TYPE_REMINDER, self::TYPE_DEADLINE, self::TYPE_MANDATORY_READ, self::TYPE_INTERNAL_COMMUNICATION]],
            [['recipient_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['recipient_user_id' => 'id']],
            [['sender_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['sender_user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'recipient_user_id' => 'Destinatario',
            'sender_user_id' => 'Mittente',
            'notification_type' => 'Tipo Notifica',
            'title' => 'Titolo',
            'message' => 'Messaggio',
            'requires_read_confirmation' => 'Richiede Conferma Lettura',
            'read_at' => 'Letto Il',
            'viewed_at' => 'Visualizzato Il',
            'scheduled_for' => 'Programmato Per',
            'sent_at' => 'Inviato Il',
            'created_at' => 'Creato Il',
        ];
    }

    /**
     * Gets query for [[RecipientUser]].
     */
    public function getRecipientUser()
    {
        return $this->hasOne(User::class, ['id' => 'recipient_user_id']);
    }

    /**
     * Gets query for [[SenderUser]].
     */
    public function getSenderUser()
    {
        return $this->hasOne(User::class, ['id' => 'sender_user_id']);
    }

    /**
     * Segna la notifica come letta
     */
    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->read_at = date('Y-m-d H:i:s');
            $this->save(false);
        }
    }

    /**
     * Segna la notifica come visualizzata
     */
    public function markAsViewed()
    {
        if (!$this->viewed_at) {
            $this->viewed_at = date('Y-m-d H:i:s');
            $this->save(false);
        }
    }

    /**
     * Segna la notifica come inviata
     */
    public function markAsSent()
    {
        if (!$this->sent_at) {
            $this->sent_at = date('Y-m-d H:i:s');
            $this->save(false);
        }
    }

    /**
     * Verifica se la notifica è stata letta
     * 
     * @return bool
     */
    public function isRead()
    {
        return $this->read_at !== null;
    }

    /**
     * Verifica se la notifica è stata visualizzata
     * 
     * @return bool
     */
    public function isViewed()
    {
        return $this->viewed_at !== null;
    }

    /**
     * Verifica se la notifica è stata inviata
     * 
     * @return bool
     */
    public function isSent()
    {
        return $this->sent_at !== null;
    }

    /**
     * Verifica se la notifica è programmata
     * 
     * @return bool
     */
    public function isScheduled()
    {
        return $this->scheduled_for !== null;
    }

    /**
     * Restituisce lo status della notifica
     * 
     * @return string
     */
    public function getStatus()
    {
        if ($this->isRead()) {
            return self::STATUS_READ;
        }
        if ($this->isSent()) {
            return self::STATUS_SENT;
        }
        return self::STATUS_PENDING;
    }

    /**
     * Scope per notifiche non lette
     * 
     * @return \yii\db\ActiveQuery
     */
    public static function findUnread()
    {
        return static::find()->where(['read_at' => null]);
    }

    /**
     * Scope per notifiche da inviare (non inviate e non programmate o programmate per adesso)
     * 
     * @return \yii\db\ActiveQuery
     */
    public static function findPendingToSend()
    {
        return static::find()->where([
            'and',
            ['sent_at' => null],
            [
                'or',
                ['scheduled_for' => null],
                ['<=', 'scheduled_for', date('Y-m-d H:i:s')]
            ]
        ]);
    }

    /**
     * Scope per notifiche di un utente
     * 
     * @param int $userId
     * @return \yii\db\ActiveQuery
     */
    public static function findByUser($userId)
    {
        return static::find()->where(['recipient_user_id' => $userId]);
    }

    /**
     * Restituisce tutte le opzioni di tipo
     * 
     * @return array
     */
    public static function getTypeOptions()
    {
        return [
            self::TYPE_INFO => 'Informativa',
            self::TYPE_REMINDER => 'Promemoria',
            self::TYPE_DEADLINE => 'Scadenza',
            self::TYPE_MANDATORY_READ => 'Lettura Obbligatoria',
            self::TYPE_INTERNAL_COMMUNICATION => 'Comunicazione Interna',
        ];
    }

    /**
     * Scope per comunicazioni interne del gestionale
     * 
     * @return \yii\db\ActiveQuery
     */
    public static function findInternalCommunications()
    {
        return static::find()->where(['notification_type' => self::TYPE_INTERNAL_COMMUNICATION]);
    }

    /**
     * Verifica se è una comunicazione interna
     * 
     * @return bool
     */
    public function isInternalCommunication()
    {
        return $this->notification_type === self::TYPE_INTERNAL_COMMUNICATION;
    }
} 
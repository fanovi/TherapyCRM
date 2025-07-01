<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "request_statuses".
 *
 * @property int $id
 * @property string $name
 * @property int $display_order
 * @property string $created_at
 *
 * @property DocumentRequest[] $documentRequests
 */
class RequestStatus extends ActiveRecord
{
    // Costanti per gli stati (corrispondenti agli ID nella tabella)
    const STATUS_INVIATA = 1;
    const STATUS_PRESA_IN_CARICO = 2;
    const STATUS_STAMPATO = 3;
    const STATUS_CONSEGNATO = 4;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%request_statuses}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
                'value' => function() {
                    return date('Y-m-d H:i:s');
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
            [['name'], 'required'],
            [['display_order'], 'integer'],
            [['display_order'], 'default', 'value' => 0],
            [['created_at'], 'safe'],
            [['name'], 'string', 'max' => 50],
            [['name'], 'unique'],
            [['display_order'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Nome',
            'display_order' => 'Ordine',
            'created_at' => 'Creato il',
        ];
    }

    /**
     * Gets query for [[DocumentRequests]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentRequests()
    {
        return $this->hasMany(DocumentRequest::class, ['status' => 'id']);
    }

    /**
     * Restituisce tutti gli stati ordinati per display_order
     *
     * @return RequestStatus[]
     */
    public static function getAllOrdered()
    {
        return static::find()
            ->orderBy(['display_order' => SORT_ASC])
            ->all();
    }

    /**
     * Restituisce una mappa id => nome per uso in dropdown
     *
     * @return array
     */
    public static function getDropdownOptions()
    {
        $statuses = static::getAllOrdered();
        $options = [];
        
        foreach ($statuses as $status) {
            $options[$status->id] = $status->name;
        }
        
        return $options;
    }

    /**
     * Trova uno stato per ID
     *
     * @param int $id
     * @return RequestStatus|null
     */
    public static function findById($id)
    {
        return static::findOne(['id' => $id]);
    }

    /**
     * Trova lo stato "Inviata" (stato iniziale)
     *
     * @return RequestStatus|null
     */
    public static function findInviata()
    {
        return static::findById(self::STATUS_INVIATA);
    }

    /**
     * Trova lo stato "Consegnato" (stato finale)
     *
     * @return RequestStatus|null
     */
    public static function findConsegnato()
    {
        return static::findById(self::STATUS_CONSEGNATO);
    }

    /**
     * Controlla se questo è lo stato iniziale
     *
     * @return bool
     */
    public function isInitialStatus()
    {
        return $this->id === self::STATUS_INVIATA;
    }

    /**
     * Controlla se questo è lo stato finale
     *
     * @return bool
     */
    public function isFinalStatus()
    {
        return $this->id === self::STATUS_CONSEGNATO;
    }

    /**
     * Restituisce il prossimo stato nella sequenza (se esiste)
     *
     * @return RequestStatus|null
     */
    public function getNextStatus()
    {
        return static::find()
            ->where(['>', 'display_order', $this->display_order])
            ->orderBy(['display_order' => SORT_ASC])
            ->one();
    }

    /**
     * Restituisce lo stato precedente nella sequenza (se esiste)
     *
     * @return RequestStatus|null
     */
    public function getPreviousStatus()
    {
        return static::find()
            ->where(['<', 'display_order', $this->display_order])
            ->orderBy(['display_order' => SORT_DESC])
            ->one();
    }

    /**
     * Controlla se è possibile passare a un altro stato
     *
     * @param int $targetStatusId
     * @return bool
     */
    public function canTransitionTo($targetStatusId)
    {
        // Per ora permettiamo transizioni solo sequenziali
        // In futuro si possono aggiungere regole più complesse
        $targetStatus = static::findById($targetStatusId);
        
        if (!$targetStatus) {
            return false;
        }
        
        // Permetti solo avanzamento sequenziale o ritorno allo stato precedente
        $nextStatus = $this->getNextStatus();
        $prevStatus = $this->getPreviousStatus();
        
        return ($nextStatus && $nextStatus->id === $targetStatusId) || 
               ($prevStatus && $prevStatus->id === $targetStatusId);
    }

    /**
     * Restituisce un array per API
     *
     * @return array
     */
    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_order' => $this->display_order,
            'is_initial' => $this->isInitialStatus(),
            'is_final' => $this->isFinalStatus(),
        ];
    }
} 
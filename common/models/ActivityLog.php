<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "activity_log".
 *
 * @property int $id
 * @property int $user_id ID utente che ha eseguito l'azione
 * @property string $action Tipo di azione eseguita
 * @property string $entity_name Nome della tabella/entità
 * @property int $entity_id ID del record modificato
 * @property string|null $old_values Valori precedenti in formato JSON
 * @property string|null $new_values Nuovi valori in formato JSON
 * @property string|null $ip_address Indirizzo IP dell'utente
 * @property string|null $user_agent User Agent del browser
 * @property string $created_at Data e ora dell'azione
 *
 * @property User $user
 */
class ActivityLog extends ActiveRecord
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%activity_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'action', 'entity_name', 'entity_id'], 'required'],
            [['user_id', 'entity_id'], 'integer'],
            [['user_id', 'entity_id'], 'integer', 'min' => 1],
            [['old_values', 'new_values', 'user_agent'], 'string'],
            [['created_at'], 'safe'],
            [['action'], 'in', 'range' => [self::ACTION_CREATE, self::ACTION_UPDATE, self::ACTION_DELETE]],
            [['entity_name'], 'string', 'max' => 100],
            [['entity_name'], 'match', 'pattern' => '/^[a-zA-Z_][a-zA-Z0-9_]*$/', 'message' => 'Nome entità non valido'],
            [['ip_address'], 'string', 'max' => 45],
            [['ip_address'], 'ip'],
            [['old_values', 'new_values'], 'validateJson'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * Validazione custom per i campi JSON
     */
    public function validateJson($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            Json::decode($this->$attribute);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError($attribute, 'Il campo deve contenere JSON valido.');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Utente',
            'action' => 'Azione',
            'entity_name' => 'Entità',
            'entity_id' => 'ID Entità',
            'old_values' => 'Valori Precedenti',
            'new_values' => 'Nuovi Valori',
            'ip_address' => 'Indirizzo IP',
            'user_agent' => 'User Agent',
            'created_at' => 'Data Creazione',
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
     * Decodifica i valori precedenti dal JSON
     * @return array
     */
    public function getOldValuesArray()
    {
        if (empty($this->old_values)) {
            return [];
        }

        try {
            return Json::decode($this->old_values);
        } catch (\Exception $e) {
            Yii::error("Errore decodifica old_values per activity_log {$this->id}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Decodifica i nuovi valori dal JSON
     * @return array
     */
    public function getNewValuesArray()
    {
        if (empty($this->new_values)) {
            return [];
        }

        try {
            return Json::decode($this->new_values);
        } catch (\Exception $e) {
            Yii::error("Errore decodifica new_values per activity_log {$this->id}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ritorna la descrizione dell'azione in italiano
     * @return string
     */
    public function getActionDescription()
    {
        $descriptions = [
            self::ACTION_CREATE => 'Creato',
            self::ACTION_UPDATE => 'Modificato',
            self::ACTION_DELETE => 'Eliminato',
        ];

        return ArrayHelper::getValue($descriptions, $this->action, 'Sconosciuto');
    }

    /**
     * Ritorna il nome dell'utente che ha eseguito l'azione
     * @return string
     */
    public function getUserName()
    {
        return $this->user ? $this->user->username : 'Utente Sconosciuto';
    }

    /**
     * Ritorna un riassunto dell'attività
     * @return string
     */
    public function getSummary()
    {
        return sprintf(
            '%s ha %s %s #%d',
            $this->getUserName(),
            strtolower($this->getActionDescription()),
            $this->entity_name,
            $this->entity_id
        );
    }

    /**
     * Scope per filtrare per utente
     * @param int $userId
     * @return \yii\db\ActiveQuery
     */
    public static function findByUser($userId)
    {
        return static::find()->where(['user_id' => $userId]);
    }

    /**
     * Scope per filtrare per entità
     * @param string $entityName
     * @param int|null $entityId
     * @return \yii\db\ActiveQuery
     */
    public static function findByEntity($entityName, $entityId = null)
    {
        $query = static::find()->where(['entity_name' => $entityName]);
        
        if ($entityId !== null) {
            $query->andWhere(['entity_id' => $entityId]);
        }

        return $query;
    }

    /**
     * Scope per filtrare per azione
     * @param string $action
     * @return \yii\db\ActiveQuery
     */
    public static function findByAction($action)
    {
        return static::find()->where(['action' => $action]);
    }

    /**
     * Scope per filtrare per range di date
     * @param string $dateFrom
     * @param string $dateTo
     * @return \yii\db\ActiveQuery
     */
    public static function findByDateRange($dateFrom, $dateTo)
    {
        return static::find()->where(['between', 'created_at', $dateFrom, $dateTo]);
    }

    /**
     * Ritorna gli ultimi N log per una specifica entità
     * @param string $entityName
     * @param int $entityId
     * @param int $limit
     * @return ActivityLog[]
     */
    public static function getRecentByEntity($entityName, $entityId, $limit = 10)
    {
        return static::findByEntity($entityName, $entityId)
            ->with('user')
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Conta le attività per utente in un periodo
     * @param int $userId
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public static function getActivityCountByUser($userId, $dateFrom, $dateTo)
    {
        return static::find()
            ->select(['action', 'COUNT(*) as count'])
            ->where(['user_id' => $userId])
            ->andWhere(['between', 'created_at', $dateFrom, $dateTo])
            ->groupBy('action')
            ->asArray()
            ->all();
    }
} 
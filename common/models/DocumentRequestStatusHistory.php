<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "document_request_status_history".
 *
 * @property int $id
 * @property int $document_request_id
 * @property int|null $from_status_id
 * @property int $to_status_id
 * @property int|null $changed_by_user_id
 * @property string $created_at
 *
 * @property DocumentRequest $documentRequest
 * @property RequestStatus $fromStatus
 * @property RequestStatus $toStatus
 * @property User $changedByUser
 */
class DocumentRequestStatusHistory extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%document_request_status_history}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false, // Readonly table - no updates
                'value' => function() {
                    return (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
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
            [['document_request_id', 'to_status_id'], 'required'],
            [['document_request_id', 'from_status_id', 'to_status_id', 'changed_by_user_id'], 'integer'],
            [['created_at'], 'safe'],
            [['document_request_id'], 'exist', 'skipOnError' => true, 'targetClass' => DocumentRequest::class, 'targetAttribute' => ['document_request_id' => 'id']],
            [['from_status_id'], 'exist', 'skipOnError' => true, 'targetClass' => RequestStatus::class, 'targetAttribute' => ['from_status_id' => 'id']],
            [['to_status_id'], 'exist', 'skipOnError' => true, 'targetClass' => RequestStatus::class, 'targetAttribute' => ['to_status_id' => 'id']],
            [['changed_by_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['changed_by_user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'document_request_id' => 'Richiesta Documento',
            'from_status_id' => 'Stato Precedente',
            'to_status_id' => 'Nuovo Stato',
            'changed_by_user_id' => 'Modificato Da',
            'created_at' => 'Creato il',
        ];
    }

    /**
     * Gets query for [[DocumentRequest]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentRequest()
    {
        return $this->hasOne(DocumentRequest::class, ['id' => 'document_request_id']);
    }

    /**
     * Gets query for [[FromStatus]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFromStatus()
    {
        return $this->hasOne(RequestStatus::class, ['id' => 'from_status_id']);
    }

    /**
     * Gets query for [[ToStatus]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getToStatus()
    {
        return $this->hasOne(RequestStatus::class, ['id' => 'to_status_id']);
    }

    /**
     * Gets query for [[ChangedByUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChangedByUser()
    {
        return $this->hasOne(User::class, ['id' => 'changed_by_user_id']);
    }

    /**
     * Creates initial status history entry (when request is created)
     *
     * @param int $documentRequestId
     * @param int $toStatusId
     * @param int|null $userId
     * @return DocumentRequestStatusHistory
     */
    public static function createInitialEntry($documentRequestId, $toStatusId, $userId = null)
    {
        $history = new static();
        $history->document_request_id = $documentRequestId;
        $history->from_status_id = null; // Initial creation has no previous status
        $history->to_status_id = $toStatusId;
        $history->changed_by_user_id = $userId;
        
        if (!$history->save()) {
            Yii::error("Failed to create initial status history entry: " . json_encode($history->getFirstErrors()), __METHOD__);
        }
        
        return $history;
    }

    /**
     * Creates status change history entry
     *
     * @param int $documentRequestId
     * @param int $fromStatusId
     * @param int $toStatusId
     * @param int|null $userId
     * @return DocumentRequestStatusHistory
     */
    public static function createStatusChange($documentRequestId, $fromStatusId, $toStatusId, $userId = null)
    {
        $history = new static();
        $history->document_request_id = $documentRequestId;
        $history->from_status_id = $fromStatusId;
        $history->to_status_id = $toStatusId;
        $history->changed_by_user_id = $userId;
        
        if (!$history->save()) {
            Yii::error("Failed to create status change history entry: " . json_encode($history->getFirstErrors()), __METHOD__);
        }
        
        return $history;
    }

    /**
     * Get average completion time for document requests (from creation to delivered)
     *
     * @param int|null $requestTypeId Filter by request type
     * @return float|null Average hours to completion
     */
    public static function getAverageCompletionTime($requestTypeId = null)
    {
        $query = static::find()
            ->alias('h1')
            ->select(['AVG(TIMESTAMPDIFF(HOUR, h1.created_at, h2.created_at)) as avg_hours'])
            ->innerJoin(['h2' => static::tableName()], 'h1.document_request_id = h2.document_request_id')
            ->where(['h1.from_status_id' => null]) // Initial creation
            ->andWhere(['h2.to_status_id' => RequestStatus::STATUS_CONSEGNATO]); // Final delivery

        if ($requestTypeId) {
            $query->innerJoin(DocumentRequest::tableName() . ' dr', 'h1.document_request_id = dr.id')
                  ->andWhere(['dr.request_type_id' => $requestTypeId]);
        }

        $result = $query->scalar();
        return $result !== false ? (float)$result : null;
    }

    /**
     * Get requests stuck in a status for more than specified days
     *
     * @param int $days Number of days to consider "stuck"
     * @param int|null $statusId Filter by specific status
     * @return array
     */
    public static function getStuckRequests($days = 7, $statusId = null)
    {
        $query = static::find()
            ->with(['documentRequest.requestType', 'toStatus'])
            ->where(['<=', 'created_at', (new \DateTime("-{$days} days", new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')])
            ->andWhere(['not in', 'to_status_id', [RequestStatus::STATUS_CONSEGNATO]]); // Exclude delivered

        if ($statusId) {
            $query->andWhere(['to_status_id' => $statusId]);
        }

        // Get only the latest status change for each request
        $subQuery = static::find()
            ->select(['document_request_id', 'MAX(created_at) as latest_change'])
            ->groupBy('document_request_id');

        $query->innerJoin(['latest' => $subQuery], 
            'latest.document_request_id = ' . static::tableName() . '.document_request_id AND latest.latest_change = ' . static::tableName() . '.created_at');

        return $query->all();
    }

    /**
     * Get status history for a document request
     *
     * @param int $documentRequestId
     * @return DocumentRequestStatusHistory[]
     */
    public static function getHistoryForRequest($documentRequestId)
    {
        return static::find()
            ->with(['fromStatus', 'toStatus', 'changedByUser.profile'])
            ->where(['document_request_id' => $documentRequestId])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
    }

    /**
     * Get formatted history for API
     *
     * @param int $documentRequestId
     * @return array
     */
    public static function getHistoryForApi($documentRequestId)
    {
        $history = static::getHistoryForRequest($documentRequestId);
        $result = [];

        foreach ($history as $entry) {
            $result[] = [
                'id' => $entry->id,
                'from_status' => $entry->fromStatus ? [
                    'id' => $entry->fromStatus->id,
                    'name' => $entry->fromStatus->name
                ] : null,
                'to_status' => [
                    'id' => $entry->toStatus->id,
                    'name' => $entry->toStatus->name
                ],
                'changed_by' => $entry->changedByUser ? [
                    'id' => $entry->changedByUser->id,
                    'name' => $entry->changedByUser->profile ? 
                             $entry->changedByUser->profile->last_name . ' ' . $entry->changedByUser->profile->first_name :
                             'N/A'
                ] : null,
                'created_at' => (new \DateTime($entry->created_at, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z')
            ];
        }

        return $result;
    }
} 
<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "request_types".
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $category
 * @property int $estimated_days
 * @property bool $requires_reason
 * @property bool $requires_date_range
 * @property bool $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property DocumentRequest[] $documentRequests
 */
class RequestType extends ActiveRecord
{
    const CATEGORY_MEDICAL = 'medical';
    const CATEGORY_THERAPY = 'therapy';
    const CATEGORY_FITNESS = 'fitness';
    const CATEGORY_APPOINTMENT = 'appointment';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%request_types}}';
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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'category'], 'required'],
            [['description'], 'string'],
            [['estimated_days'], 'integer', 'min' => 1, 'max' => 30],
            [['requires_reason', 'requires_date_range', 'is_active'], 'boolean'],
            [['name'], 'string', 'max' => 255],
            [['category'], 'string', 'max' => 50],
            [['created_at', 'updated_at'], 'safe'],
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
            'description' => 'Descrizione',
            'category' => 'Categoria',
            'estimated_days' => 'Giorni Stimati',
            'requires_reason' => 'Richiede Motivo',
            'requires_date_range' => 'Richiede Range Date',
            'is_active' => 'Attivo',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * Gets query for [[DocumentRequests]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDocumentRequests()
    {
        return $this->hasMany(DocumentRequest::class, ['type_id' => 'id']);
    }

    /**
     * Gets available category options (dinamiche dal database)
     *
     * @return array
     */
    public function getCategoryOptions()
    {
        // Restituisce le categorie attualmente in uso nel database
        return static::find()
            ->select('category')
            ->distinct()
            ->where(['is_active' => true])
            ->orderBy('category')
            ->column();
    }

    /**
     * Gets category labels
     *
     * @return array
     */
    public static function getCategoryLabels()
    {
        return [
            self::CATEGORY_MEDICAL => 'Medico',
            self::CATEGORY_THERAPY => 'Terapeutico',
            self::CATEGORY_FITNESS => 'Fitness',
            self::CATEGORY_APPOINTMENT => 'Appuntamenti',
        ];
    }

    /**
     * Gets category label
     *
     * @return string
     */
    public function getCategoryLabel()
    {
        $labels = static::getCategoryLabels();
        return $labels[$this->category] ?? $this->category;
    }

    /**
     * Scope: only active request types
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findActive()
    {
        return static::find()->where(['is_active' => true]);
    }

    /**
     * Scope: filter by category
     *
     * @param string $category
     * @return \yii\db\ActiveQuery
     */
    public static function findByCategory($category)
    {
        return static::find()->where(['category' => $category]);
    }

    /**
     * Get all active request types for API
     *
     * @return array
     */
    public static function getForApi()
    {
        $requestTypes = static::findActive()
            ->orderBy(['category' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($requestTypes as $type) {
            $result[] = [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'category' => $type->category,
                'estimated_days' => $type->estimated_days,
                'requires_reason' => (bool) $type->requires_reason,
                'requires_date_range' => (bool) $type->requires_date_range,
                'is_active' => (bool) $type->is_active,
            ];
        }

        return $result;
    }

    /**
     * Get categories for API meta
     *
     * @return array
     */
    public static function getActiveCategories()
    {
        return static::findActive()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->column();
    }

    /**
     * Find active request type by ID
     *
     * @param int $id
     * @return static|null
     */
    public static function findActiveById($id)
    {
        return static::findActive()->where(['id' => $id])->one();
    }

    /**
     * Check if request type requires reason
     *
     * @return bool
     */
    public function requiresReason()
    {
        return (bool) $this->requires_reason;
    }

    /**
     * Check if request type requires date range
     *
     * @return bool
     */
    public function requiresDateRange()
    {
        return (bool) $this->requires_date_range;
    }

    /**
     * {@inheritdoc}
     * @return RequestTypeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new RequestTypeQuery(get_called_class());
    }
} 
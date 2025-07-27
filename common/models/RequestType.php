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
 * @property int $therapeutic_plan_rule
 * @property int $allow_multiple_requests
 * @property int $require_therapy_assignment
 * @property int $require_notes
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 *
 * @property DocumentRequest[] $documentRequests
 */
class RequestType extends ActiveRecord
{
    // Costanti per therapeutic_plan_rule
    const PLAN_OPTIONAL = 1;          // Si può associare, ma non è obbligatorio
    const PLAN_NOT_ALLOWED = 2;       // Non si può associare
    const PLAN_REQUIRED = 3;          // Si deve associare obbligatoriamente

    // Costanti per campi boolean
    const BOOLEAN_FALSE = 0;
    const BOOLEAN_TRUE = 1;

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
            [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => ['created_at', 'updated_at'],
                'entityNameCallback' => function($model) {
                    return 'Tipo Richiesta';
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
            [['therapeutic_plan_rule'], 'integer', 'min' => 1, 'max' => 3],
            [['allow_multiple_requests', 'require_therapy_assignment', 'require_notes', 'is_active'], 'integer', 'min' => 0, 'max' => 1],
            [['name'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'safe'],
            [['therapeutic_plan_rule'], 'in', 'range' => [self::PLAN_OPTIONAL, self::PLAN_NOT_ALLOWED, self::PLAN_REQUIRED]],
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
            'therapeutic_plan_rule' => 'Regola Piano Terapeutico',
            'allow_multiple_requests' => 'Permette Richieste Multiple',
            'require_therapy_assignment' => 'Richiede Assegnazione Terapia',
            'require_notes' => 'Richiede Note',
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
     * Get therapeutic plan rule options
     *
     * @return array
     */
    public static function getTherapeuticPlanRuleOptions()
    {
        return [
            self::PLAN_OPTIONAL => 'Opzionale',
            self::PLAN_NOT_ALLOWED => 'Non Associabile',
            self::PLAN_REQUIRED => 'Obbligatorio',
        ];
    }

    /**
     * Get therapeutic plan rule label
     *
     * @return string
     */
    public function getTherapeuticPlanRuleLabel()
    {
        $options = self::getTherapeuticPlanRuleOptions();
        return $options[$this->therapeutic_plan_rule] ?? 'Sconosciuto';
    }

    /**
     * Check if therapeutic plan is required
     *
     * @return bool
     */
    public function isTherapeuticPlanRequired()
    {
        return $this->therapeutic_plan_rule === self::PLAN_REQUIRED;
    }

    /**
     * Check if therapeutic plan is optional
     *
     * @return bool
     */
    public function isTherapeuticPlanOptional()
    {
        return $this->therapeutic_plan_rule === self::PLAN_OPTIONAL;
    }

    /**
     * Check if therapeutic plan is not allowed
     *
     * @return bool
     */
    public function isTherapeuticPlanNotAllowed()
    {
        return $this->therapeutic_plan_rule === self::PLAN_NOT_ALLOWED;
    }

    /**
     * Check if multiple requests are allowed
     *
     * @return bool
     */
    public function allowsMultipleRequests()
    {
        return $this->allow_multiple_requests === self::BOOLEAN_TRUE;
    }

    /**
     * Check if therapy assignment is required
     *
     * @return bool
     */
    public function requiresTherapyAssignment()
    {
        return $this->require_therapy_assignment === self::BOOLEAN_TRUE;
    }

    /**
     * Check if notes are required
     *
     * @return bool
     */
    public function requiresNotes()
    {
        return $this->require_notes === self::BOOLEAN_TRUE;
    }

    /**
     * Check if request type is active
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->is_active === self::BOOLEAN_TRUE;
    }

    /**
     * Get all request types for API
     *
     * @return array
     */
    public static function getForApi()
    {
        $requestTypes = static::find()
            ->where(['is_active' => self::BOOLEAN_TRUE])
            ->orderBy(['display_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($requestTypes as $type) {
            $result[] = [
                'id' => $type->id,
                'name' => $type->name,
                'therapeutic_plan_rule' => $type->therapeutic_plan_rule,
                'therapeutic_plan_rule_label' => $type->getTherapeuticPlanRuleLabel(),
                'allow_multiple_requests' => (bool) $type->allow_multiple_requests,
                'require_therapy_assignment' => (bool) $type->require_therapy_assignment,
                'require_notes' => (bool) $type->require_notes,
                'is_active' => (bool) $type->is_active,
                'is_therapeutic_plan_required' => $type->isTherapeuticPlanRequired(),
                'is_therapeutic_plan_optional' => $type->isTherapeuticPlanOptional(),
                'is_therapeutic_plan_not_allowed' => $type->isTherapeuticPlanNotAllowed(),
                'display_order' => $type->display_order,
            ];
        }

        return $result;
    }

    /**
     * Scope: only active request types
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findActive()
    {
        return static::find()->where(['is_active' => self::BOOLEAN_TRUE]);
    }

    /**
     * Find request type by ID
     *
     * @param int $id
     * @return static|null
     */
    public static function findById($id)
    {
        return static::find()->where(['id' => $id])->one();
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
     * Get all request types as options for dropdown
     *
     * @return array
     */
    public static function getDropdownOptions()
    {
        return static::find()
            ->select(['name', 'id'])
            ->orderBy(['name' => SORT_ASC])
            ->indexBy('id')
            ->column();
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
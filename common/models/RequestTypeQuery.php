<?php

namespace common\models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[RequestType]].
 *
 * @see RequestType
 */
class RequestTypeQuery extends ActiveQuery
{
    /**
     * Filter only active request types
     *
     * @return $this
     */
    public function active()
    {
        return $this->andWhere(['is_active' => RequestType::BOOLEAN_TRUE]);
    }

    /**
     * Filter by therapeutic plan rule
     *
     * @param int $rule
     * @return $this
     */
    public function byTherapeuticPlanRule($rule)
    {
        return $this->andWhere(['therapeutic_plan_rule' => $rule]);
    }

    /**
     * Filter request types that require therapeutic plan
     *
     * @return $this
     */
    public function requiresTherapeuticPlan()
    {
        return $this->andWhere(['therapeutic_plan_rule' => RequestType::PLAN_REQUIRED]);
    }

    /**
     * Filter request types that allow optional therapeutic plan
     *
     * @return $this
     */
    public function optionalTherapeuticPlan()
    {
        return $this->andWhere(['therapeutic_plan_rule' => RequestType::PLAN_OPTIONAL]);
    }

    /**
     * Filter request types that don't allow therapeutic plan
     *
     * @return $this
     */
    public function noTherapeuticPlan()
    {
        return $this->andWhere(['therapeutic_plan_rule' => RequestType::PLAN_NOT_ALLOWED]);
    }

    /**
     * Filter request types that allow multiple requests
     *
     * @return $this
     */
    public function allowsMultiple()
    {
        return $this->andWhere(['allow_multiple_requests' => RequestType::BOOLEAN_TRUE]);
    }

    /**
     * Filter request types that don't allow multiple requests
     *
     * @return $this
     */
    public function singleOnly()
    {
        return $this->andWhere(['allow_multiple_requests' => RequestType::BOOLEAN_FALSE]);
    }

    /**
     * Filter request types that require therapy assignment
     *
     * @return $this
     */
    public function requiresTherapy()
    {
        return $this->andWhere(['require_therapy_assignment' => RequestType::BOOLEAN_TRUE]);
    }

    /**
     * Filter request types that require notes
     *
     * @return $this
     */
    public function requiresNotes()
    {
        return $this->andWhere(['require_notes' => RequestType::BOOLEAN_TRUE]);
    }

    /**
     * Order by name
     *
     * @return $this
     */
    public function ordered()
    {
        return $this->orderBy(['name' => SORT_ASC]);
    }

    /**
     * {@inheritdoc}
     * @return RequestType[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return RequestType|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
} 
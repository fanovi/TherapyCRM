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
        return $this->andWhere(['is_active' => true]);
    }

    /**
     * Filter by category
     *
     * @param string $category
     * @return $this
     */
    public function category($category)
    {
        return $this->andWhere(['category' => $category]);
    }

    /**
     * Filter request types that require reason
     *
     * @return $this
     */
    public function requiresReason()
    {
        return $this->andWhere(['requires_reason' => true]);
    }

    /**
     * Filter request types that require date range
     *
     * @return $this
     */
    public function requiresDateRange()
    {
        return $this->andWhere(['requires_date_range' => true]);
    }

    /**
     * Order by category and name
     *
     * @return $this
     */
    public function ordered()
    {
        return $this->orderBy(['category' => SORT_ASC, 'name' => SORT_ASC]);
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
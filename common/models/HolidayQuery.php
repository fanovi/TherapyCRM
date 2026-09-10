<?php

namespace common\models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Holiday]].
 *
 * @see Holiday
 */
class HolidayQuery extends ActiveQuery
{
    /**
     * Filter active closures only
     *
     * @return $this
     */
    public function active()
    {
        return $this->andWhere(['is_active' => 1]);
    }
}

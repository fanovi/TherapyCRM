<?php

namespace common\models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Absence]].
 *
 * @see Absence
 */
class AbsenceQuery extends ActiveQuery
{
    /**
     * Filter by status
     *
     * @param string|array $status
     * @return $this
     */
    public function status($status)
    {
        return $this->andWhere(['status' => $status]);
    }

    /**
     * Filter pending absences
     *
     * @return $this
     */
    public function pending()
    {
        return $this->status(Absence::STATUS_PENDING);
    }

    /**
     * Filter approved absences
     *
     * @return $this
     */
    public function approved()
    {
        return $this->status(Absence::STATUS_APPROVED);
    }

    /**
     * Filter rejected absences
     *
     * @return $this
     */
    public function rejected()
    {
        return $this->status(Absence::STATUS_REJECTED);
    }

    /**
     * Filter by therapist
     *
     * @param int $therapistId
     * @return $this
     */
    public function byTherapist($therapistId)
    {
        return $this->andWhere(['therapist_id' => $therapistId]);
    }

    /**
     * Filter by absence type
     *
     * @param string $type
     * @return $this
     */
    public function byType($type)
    {
        return $this->andWhere(['type' => $type]);
    }

    /**
     * Filter vacation absences
     *
     * @return $this
     */
    public function vacation()
    {
        return $this->byType(Absence::TYPE_VACATION);
    }

    /**
     * Filter sick leave absences
     *
     * @return $this
     */
    public function sickLeave()
    {
        return $this->byType(Absence::TYPE_SICK_LEAVE);
    }

    /**
     * Filter by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return $this
     */
    public function dateRange($startDate, $endDate)
    {
        return $this->andWhere(['between', 'start_date', $startDate, $endDate])
               ->orWhere(['between', 'end_date', $startDate, $endDate])
               ->orWhere(['and', ['<=', 'start_date', $startDate], ['>=', 'end_date', $endDate]]);
    }

    /**
     * Filter absences that overlap with specific date
     *
     * @param string $date
     * @return $this
     */
    public function onDate($date)
    {
        return $this->andWhere(['<=', 'start_date', $date])
               ->andWhere(['>=', 'end_date', $date]);
    }

    /**
     * Filter current absences (active today)
     *
     * @return $this
     */
    public function current()
    {
        return $this->onDate(date('Y-m-d'))
               ->approved();
    }

    /**
     * Filter future absences
     *
     * @return $this
     */
    public function future()
    {
        return $this->andWhere(['>', 'start_date', date('Y-m-d')]);
    }

    /**
     * Filter past absences
     *
     * @return $this
     */
    public function past()
    {
        return $this->andWhere(['<', 'end_date', date('Y-m-d')]);
    }

    /**
     * Filter by year
     *
     * @param int $year
     * @return $this
     */
    public function inYear($year)
    {
        return $this->dateRange($year . '-01-01', $year . '-12-31');
    }

    /**
     * Filter by month
     *
     * @param int $year
     * @param int $month
     * @return $this
     */
    public function inMonth($year, $month)
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        
        return $this->dateRange($startDate, $endDate);
    }

    /**
     * Filter absences that need approval
     *
     * @return $this
     */
    public function needingApproval()
    {
        return $this->pending()
               ->orderBy(['created_at' => SORT_ASC]);
    }

    /**
     * Filter long absences (more than X days)
     *
     * @param int $days
     * @return $this
     */
    public function longAbsences($days = 7)
    {
        return $this->andWhere(['>', 'DATEDIFF(end_date, start_date)', $days - 1]);
    }

    /**
     * Order by start date
     *
     * @param string $direction
     * @return $this
     */
    public function orderByDate($direction = 'ASC')
    {
        return $this->orderBy(['start_date' => $direction === 'ASC' ? SORT_ASC : SORT_DESC]);
    }

    /**
     * Include related models for optimization
     *
     * @return $this
     */
    public function withRelations()
    {
        return $this->with(['therapist', 'approvedBy', 'createdBy']);
    }

    /**
     * Get absence statistics by type
     *
     * @return $this
     */
    public function statisticsByType()
    {
        return $this->select(['type', 'COUNT(*) as count', 'SUM(DATEDIFF(end_date, start_date) + 1) as total_days'])
               ->groupBy('type');
    }

    /**
     * Get absence statistics by therapist
     *
     * @return $this
     */
    public function statisticsByTherapist()
    {
        return $this->select(['therapist_id', 'COUNT(*) as absence_count', 'SUM(DATEDIFF(end_date, start_date) + 1) as total_days'])
               ->groupBy('therapist_id')
               ->with('therapist');
    }

    /**
     * Get monthly absence trends
     *
     * @return $this
     */
    public function monthlyTrends()
    {
        return $this->select([
            'DATE_FORMAT(start_date, "%Y-%m") as month',
            'COUNT(*) as absence_count',
            'SUM(DATEDIFF(end_date, start_date) + 1) as total_days'
        ])
        ->groupBy('month')
        ->orderBy('month');
    }
} 
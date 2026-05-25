<?php

namespace common\models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[TherapeuticPlan]].
 *
 * @see TherapeuticPlan
 */
class TherapeuticPlanQuery extends ActiveQuery
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
     * Filter active plans only
     *
     * @return $this
     */
    public function active()
    {
        return $this->status(TherapeuticPlan::STATUS_ACTIVE);
    }

    /**
     * Filter plans that are active AND valid at the given date:
     * status='active' AND start_date <= $date AND end_date >= $date.
     *
     * Esclude piani in stato terminated/expired/suspended/completed/draft/pending
     * anche se hanno end_date futura, e piani 'active' che non sono ancora
     * iniziati o sono gia' scaduti.
     *
     * @param string|null $date Data Y-m-d. Null = oggi.
     * @return $this
     */
    public function activeAtDate($date = null)
    {
        $date = $date ?: date('Y-m-d');
        return $this->andWhere(['status' => TherapeuticPlan::STATUS_ACTIVE])
            ->andWhere(['<=', 'start_date', $date])
            ->andWhere(['>=', 'end_date', $date]);
    }

    /**
     * Filter draft plans
     *
     * @return $this
     */
    public function draft()
    {
        return $this->status(TherapeuticPlan::STATUS_DRAFT);
    }

    /**
     * Filter expired plans
     *
     * @return $this
     */
    public function expired()
    {
        return $this->status(TherapeuticPlan::STATUS_EXPIRED);
    }

    /**
     * Filter renewed plans
     *
     * @return $this
     */
    public function renewed()
    {
        return $this->status(TherapeuticPlan::STATUS_RENEWED);
    }

    /**
     * Filter by patient
     *
     * @param int $patientId
     * @return $this
     */
    public function byPatient($patientId)
    {
        return $this->andWhere(['patient_id' => $patientId]);
    }

    /**
     * Filter by created by user
     *
     * @param int $userId
     * @return $this
     */
    public function createdBy($userId)
    {
        return $this->andWhere(['created_by' => $userId]);
    }

    /**
     * Filter by start date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return $this
     */
    public function startDateRange($startDate, $endDate)
    {
        return $this->andWhere(['between', 'start_date', $startDate, $endDate]);
    }

    /**
     * Filter by end date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return $this
     */
    public function endDateRange($startDate, $endDate)
    {
        return $this->andWhere(['between', 'end_date', $startDate, $endDate]);
    }

    /**
     * Filter plans starting this month
     *
     * @return $this
     */
    public function startingThisMonth()
    {
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');
        
        return $this->startDateRange($startOfMonth, $endOfMonth);
    }

    /**
     * Filter plans ending this month
     *
     * @return $this
     */
    public function endingThisMonth()
    {
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');
        
        return $this->endDateRange($startOfMonth, $endOfMonth);
    }

    /**
     * Filter plans expiring soon
     *
     * @param int $days
     * @return $this
     */
    public function expiringSoon($days = 30)
    {
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+$days days"));
        
        return $this->active()
               ->andWhere(['between', 'end_date', $startDate, $endDate]);
    }

    /**
     * Filter by duration range (in days)
     *
     * @param int $minDays
     * @param int|null $maxDays
     * @return $this
     */
    public function durationRange($minDays, $maxDays = null)
    {
        if ($maxDays !== null) {
            return $this->andWhere(['between', 'duration_days', $minDays, $maxDays]);
        }
        
        return $this->andWhere(['>=', 'duration_days', $minDays]);
    }

    /**
     * Filter short-term plans (< 3 months)
     *
     * @return $this
     */
    public function shortTerm()
    {
        return $this->durationRange(1, 89);
    }

    /**
     * Filter medium-term plans (3-12 months)
     *
     * @return $this
     */
    public function mediumTerm()
    {
        return $this->durationRange(90, 365);
    }

    /**
     * Filter long-term plans (> 1 year)
     *
     * @return $this
     */
    public function longTerm()
    {
        return $this->durationRange(366);
    }

    /**
     * Filter plans with specific health regime
     *
     * @param string|array $healthRegime
     * @return $this
     */
    public function withHealthRegime($healthRegime)
    {
        return $this->joinWith('planTherapies')
               ->andWhere(['plan_therapies.health_regime' => $healthRegime]);
    }

    /**
     * Filter plans with L11 regimes
     *
     * @return $this
     */
    public function withL11Regimes()
    {
        return $this->joinWith('planTherapies')
               ->andWhere(['plan_therapies.health_regime' => [
                   PlanTherapy::HEALTH_REGIME_L11,
                   PlanTherapy::HEALTH_REGIME_L11DOM,
                   PlanTherapy::HEALTH_REGIME_L11PG,
                   PlanTherapy::HEALTH_REGIME_L11SEM,
               ]]);
    }

    /**
     * Filter plans with group therapies
     *
     * @return $this
     */
    public function withGroupTherapies()
    {
        return $this->joinWith('planTherapies')
               ->andWhere(['plan_therapies.is_group' => true]);
    }

    /**
     * Filter plans by minimum total weekly hours
     *
     * @param float $minHours
     * @return $this
     */
    public function minTotalWeeklyHours($minHours)
    {
        return $this->joinWith('planTherapies')
               ->groupBy('therapeutic_plans.id')
               ->having(['>=', 'SUM(plan_therapies.weekly_hours)', $minHours]);
    }

    /**
     * Order by start date
     *
     * @param string $direction
     * @return $this
     */
    public function orderByStartDate($direction = 'DESC')
    {
        return $this->orderBy(['start_date' => $direction === 'ASC' ? SORT_ASC : SORT_DESC]);
    }

    /**
     * Order by end date
     *
     * @param string $direction
     * @return $this
     */
    public function orderByEndDate($direction = 'DESC')
    {
        return $this->orderBy(['end_date' => $direction === 'ASC' ? SORT_ASC : SORT_DESC]);
    }

    /**
     * Order by duration
     *
     * @param string $direction
     * @return $this
     */
    public function orderByDuration($direction = 'DESC')
    {
        return $this->orderBy(['duration_days' => $direction === 'ASC' ? SORT_ASC : SORT_DESC]);
    }

    /**
     * Include related data
     *
     * @return $this
     */
    public function withRelations()
    {
        return $this->with(['patient', 'createdBy', 'planTherapies']);
    }

    /**
     * Get statistics by status
     *
     * @return $this
     */
    public function statisticsByStatus()
    {
        return $this->select(['status', 'COUNT(*) as plan_count'])
               ->groupBy('status');
    }

    /**
     * Get statistics by duration range
     *
     * @return $this
     */
    public function statisticsByDuration()
    {
        return $this->select([
            'CASE 
                WHEN duration_days < 90 THEN "short-term"
                WHEN duration_days < 365 THEN "medium-term"
                ELSE "long-term"
            END as duration_category',
            'COUNT(*) as plan_count',
            'AVG(duration_days) as avg_duration'
        ])->groupBy('duration_category');
    }

    /**
     * Get monthly creation trends
     *
     * @return $this
     */
    public function monthlyCreationTrends()
    {
        return $this->select([
            'DATE_FORMAT(created_at, "%Y-%m") as month',
            'COUNT(*) as plan_count'
        ])
        ->groupBy('month')
        ->orderBy('month');
    }

    /**
     * Get plans with completion rates
     *
     * @return $this
     */
    public function withCompletionRates()
    {
        return $this->select([
            'therapeutic_plans.*',
            'COUNT(appointments.id) as total_appointments',
            'SUM(CASE WHEN appointments.status = "completed" THEN 1 ELSE 0 END) as completed_appointments'
        ])
        ->leftJoin('plan_therapies', 'plan_therapies.therapeutic_plan_id = therapeutic_plans.id')
        ->leftJoin('appointments', 'appointments.plan_therapy_id = plan_therapies.id')
        ->groupBy('therapeutic_plans.id');
    }
} 
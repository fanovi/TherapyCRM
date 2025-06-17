<?php

namespace common\models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[PlanTherapy]].
 *
 * @see PlanTherapy
 */
class PlanTherapyQuery extends ActiveQuery
{
    /**
     * Filter by therapeutic plan
     *
     * @param int $planId
     * @return $this
     */
    public function byPlan($planId)
    {
        return $this->andWhere(['therapeutic_plan_id' => $planId]);
    }

    /**
     * Filter by treatment type
     *
     * @param int $treatmentTypeId
     * @return $this
     */
    public function byTreatmentType($treatmentTypeId)
    {
        return $this->andWhere(['treatment_type_id' => $treatmentTypeId]);
    }

    /**
     * Filter by health regime
     *
     * @param string|array $healthRegime
     * @return $this
     */
    public function byHealthRegime($healthRegime)
    {
        return $this->andWhere(['health_regime' => $healthRegime]);
    }

    /**
     * Filter L11 regimes (all variants)
     *
     * @return $this
     */
    public function l11Regimes()
    {
        return $this->andWhere(['health_regime' => [
            PlanTherapy::HEALTH_REGIME_L11,
            PlanTherapy::HEALTH_REGIME_L11DOM,
            PlanTherapy::HEALTH_REGIME_L11PG,
            PlanTherapy::HEALTH_REGIME_L11SEM,
        ]]);
    }

    /**
     * Filter domiciliary therapies
     *
     * @return $this
     */
    public function domiciliary()
    {
        return $this->andWhere(['health_regime' => [
            PlanTherapy::HEALTH_REGIME_L11DOM,
            PlanTherapy::HEALTH_REGIME_PDOM,
        ]]);
    }

    /**
     * Filter private therapies
     *
     * @return $this
     */
    public function private()
    {
        return $this->byHealthRegime(PlanTherapy::HEALTH_REGIME_PRIVATE);
    }

    /**
     * Filter group therapies
     *
     * @return $this
     */
    public function groupTherapies()
    {
        return $this->andWhere(['is_group' => true]);
    }

    /**
     * Filter individual therapies
     *
     * @return $this
     */
    public function individualTherapies()
    {
        return $this->andWhere(['is_group' => false]);
    }

    /**
     * Filter by minimum weekly hours
     *
     * @param float $minHours
     * @return $this
     */
    public function minWeeklyHours($minHours)
    {
        return $this->andWhere(['>=', 'weekly_hours', $minHours]);
    }

    /**
     * Filter by weekly hours range
     *
     * @param float $minHours
     * @param float $maxHours
     * @return $this
     */
    public function weeklyHoursRange($minHours, $maxHours)
    {
        return $this->andWhere(['between', 'weekly_hours', $minHours, $maxHours]);
    }

    /**
     * Filter intensive therapies (>10 hours per week)
     *
     * @return $this
     */
    public function intensive()
    {
        return $this->minWeeklyHours(10);
    }

    /**
     * Filter light therapies (<=5 hours per week)
     *
     * @return $this
     */
    public function light()
    {
        return $this->andWhere(['<=', 'weekly_hours', 5]);
    }

    /**
     * Include related data for optimization
     *
     * @return $this
     */
    public function withRelations()
    {
        return $this->with(['therapeuticPlan', 'treatmentType']);
    }

    /**
     * Order by weekly hours
     *
     * @param string $direction
     * @return $this
     */
    public function orderByHours($direction = 'DESC')
    {
        return $this->orderBy(['weekly_hours' => $direction === 'ASC' ? SORT_ASC : SORT_DESC]);
    }

    /**
     * Get statistics by health regime
     *
     * @return $this
     */
    public function statisticsByHealthRegime()
    {
        return $this->select([
            'health_regime',
            'COUNT(*) as therapy_count',
            'SUM(weekly_hours) as total_hours',
            'AVG(weekly_hours) as avg_hours'
        ])->groupBy('health_regime');
    }

    /**
     * Get statistics by treatment type
     *
     * @return $this
     */
    public function statisticsByTreatmentType()
    {
        return $this->select([
            'treatment_type_id',
            'COUNT(*) as therapy_count',
            'SUM(weekly_hours) as total_hours',
            'AVG(weekly_hours) as avg_hours'
        ])
        ->groupBy('treatment_type_id')
        ->with('treatmentType');
    }

    /**
     * Get group vs individual statistics
     *
     * @return $this
     */
    public function groupIndividualStatistics()
    {
        return $this->select([
            'is_group',
            'COUNT(*) as therapy_count',
            'SUM(weekly_hours) as total_hours',
            'AVG(weekly_hours) as avg_hours'
        ])->groupBy('is_group');
    }

    /**
     * Filter by active therapeutic plans
     *
     * @return $this
     */
    public function fromActivePlans()
    {
        return $this->joinWith('therapeuticPlan')
               ->andWhere(['therapeutic_plans.status' => TherapeuticPlan::STATUS_ACTIVE]);
    }

    /**
     * Filter by patient
     *
     * @param int $patientId
     * @return $this
     */
    public function byPatient($patientId)
    {
        return $this->joinWith('therapeuticPlan')
               ->andWhere(['therapeutic_plans.patient_id' => $patientId]);
    }
} 
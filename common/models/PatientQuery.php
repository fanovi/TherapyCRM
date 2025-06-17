<?php

namespace common\models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Patient]].
 *
 * @see Patient
 */
class PatientQuery extends ActiveQuery
{
    /**
     * Filter by district
     *
     * @param int $districtId
     * @return $this
     */
    public function byDistrict($districtId)
    {
        return $this->andWhere(['district_id' => $districtId]);
    }

    /**
     * Filter by age range
     *
     * @param int $minAge
     * @param int|null $maxAge
     * @return $this
     */
    public function ageRange($minAge, $maxAge = null)
    {
        $maxDate = date('Y-m-d', strtotime("-$minAge years"));
        
        if ($maxAge !== null) {
            $minDate = date('Y-m-d', strtotime("-$maxAge years"));
            return $this->andWhere(['between', 'birth_date', $minDate, $maxDate]);
        }
        
        return $this->andWhere(['<=', 'birth_date', $maxDate]);
    }

    /**
     * Filter minors (under 18)
     *
     * @return $this
     */
    public function minors()
    {
        return $this->ageRange(0, 17);
    }

    /**
     * Filter adults (18 and over)
     *
     * @return $this
     */
    public function adults()
    {
        return $this->ageRange(18);
    }

    /**
     * Search by name or fiscal code
     *
     * @param string $query
     * @return $this
     */
    public function search($query)
    {
        return $this->andWhere(['or',
            ['like', 'first_name', $query],
            ['like', 'last_name', $query],
            ['like', 'fiscal_code', $query]
        ]);
    }

    /**
     * Filter by name
     *
     * @param string $firstName
     * @param string|null $lastName
     * @return $this
     */
    public function byName($firstName, $lastName = null)
    {
        $this->andWhere(['like', 'first_name', $firstName]);
        
        if ($lastName !== null) {
            $this->andWhere(['like', 'last_name', $lastName]);
        }
        
        return $this;
    }

    /**
     * Filter by fiscal code
     *
     * @param string $fiscalCode
     * @return $this
     */
    public function byFiscalCode($fiscalCode)
    {
        return $this->andWhere(['fiscal_code' => $fiscalCode]);
    }

    /**
     * Filter patients with active therapeutic plans
     *
     * @return $this
     */
    public function withActiveTherapeuticPlan()
    {
        return $this->joinWith('therapeuticPlans')
               ->andWhere(['therapeutic_plans.status' => TherapeuticPlan::STATUS_ACTIVE]);
    }

    /**
     * Filter patients without active therapeutic plans
     *
     * @return $this
     */
    public function withoutActiveTherapeuticPlan()
    {
        return $this->leftJoin('therapeutic_plans', 
                    'therapeutic_plans.patient_id = patients.id AND therapeutic_plans.status = "' . TherapeuticPlan::STATUS_ACTIVE . '"')
               ->andWhere(['therapeutic_plans.id' => null]);
    }

    /**
     * Filter patients with upcoming appointments
     *
     * @return $this
     */
    public function withUpcomingAppointments()
    {
        return $this->joinWith('appointments')
               ->andWhere(['>=', 'appointments.appointment_datetime', date('Y-m-d H:i:s')])
               ->andWhere(['!=', 'appointments.status', Appointment::STATUS_CANCELLED]);
    }

    /**
     * Filter by birth year
     *
     * @param int $year
     * @return $this
     */
    public function bornInYear($year)
    {
        return $this->andWhere(['like', 'birth_date', $year . '%', false]);
    }

    /**
     * Filter by birth month
     *
     * @param int $month
     * @return $this
     */
    public function bornInMonth($month)
    {
        $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
        return $this->andWhere(['like', 'birth_date', '%-' . $monthStr . '-%', false]);
    }

    /**
     * Order by name
     *
     * @param string $direction
     * @return $this
     */
    public function orderByName($direction = 'ASC')
    {
        $sort = $direction === 'ASC' ? SORT_ASC : SORT_DESC;
        return $this->orderBy(['last_name' => $sort, 'first_name' => $sort]);
    }

    /**
     * Order by age
     *
     * @param string $direction
     * @return $this
     */
    public function orderByAge($direction = 'ASC')
    {
        // Birth date DESC = age ASC (younger first)
        $sort = $direction === 'ASC' ? SORT_DESC : SORT_ASC;
        return $this->orderBy(['birth_date' => $sort]);
    }

    /**
     * Include related data for optimization
     *
     * @return $this
     */
    public function withRelations()
    {
        return $this->with(['district', 'therapeuticPlans']);
    }

    /**
     * Get patients statistics by district
     *
     * @return $this
     */
    public function statisticsByDistrict()
    {
        return $this->select(['district_id', 'COUNT(*) as patient_count'])
               ->groupBy('district_id')
               ->with('district');
    }

    /**
     * Get patients statistics by age group
     *
     * @return $this
     */
    public function statisticsByAgeGroup()
    {
        return $this->select([
            'CASE 
                WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 18 THEN "0-17"
                WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 30 THEN "18-29"
                WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 50 THEN "30-49"
                WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 65 THEN "50-64"
                ELSE "65+"
            END as age_group',
            'COUNT(*) as patient_count'
        ])->groupBy('age_group');
    }
} 
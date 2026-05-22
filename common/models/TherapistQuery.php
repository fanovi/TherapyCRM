<?php

namespace common\models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Therapist]].
 *
 * @see Therapist
 */
class TherapistQuery extends ActiveQuery
{
    /**
     * Filter active therapists only
     *
     * @return $this
     */
    public function active()
    {
        return $this->andWhere(['is_active' => 1]);
    }

    /**
     * Filter inactive therapists
     *
     * @return $this
     */
    public function inactive()
    {
        return $this->andWhere(['is_active' => 0]);
    }

    /**
     * Filter by specialization (matching ANY of the therapist's specializations).
     *
     * @param int $specializationId
     * @return $this
     */
    public function bySpecialization($specializationId)
    {
        return $this->andWhere([
            'EXISTS',
            (new \yii\db\Query())
                ->from(['ts_bs' => 'therapist_specializations'])
                ->where('ts_bs.therapist_id = therapists.id')
                ->andWhere(['ts_bs.specialization_id' => (int)$specializationId]),
        ]);
    }

    /**
     * Filter by multiple specializations (therapist has at least one of them).
     *
     * @param array $specializationIds
     * @return $this
     */
    public function bySpecializations($specializationIds)
    {
        $ids = array_map('intval', (array)$specializationIds);
        return $this->andWhere([
            'EXISTS',
            (new \yii\db\Query())
                ->from(['ts_bss' => 'therapist_specializations'])
                ->where('ts_bss.therapist_id = therapists.id')
                ->andWhere(['ts_bss.specialization_id' => $ids]),
        ]);
    }

    /**
     * Filter by coordinator group
     *
     * @param int $groupId
     * @return $this
     */
    public function byCoordinatorGroup($groupId)
    {
        return $this->joinWith('groupTherapists')
               ->andWhere(['group_therapists.group_id' => $groupId, 'group_therapists.is_active' => 1]);
    }

    /**
     * Filter therapists available on specific date
     *
     * @param string $date
     * @return $this
     */
    public function availableOnDate($date)
    {
        return $this->active()
               ->andWhere(['not exists', 
                   Absence::find()
                       ->where('absences.therapist_id = therapists.id')
                       ->andWhere(['<=', 'start_date', $date])
                       ->andWhere(['>=', 'end_date', $date])
                       ->andWhere(['status' => Absence::STATUS_APPROVED])
               ]);
    }

    /**
     * Filter therapists available in time slot
     *
     * @param string $date
     * @param string $startTime
     * @param string $endTime
     * @return $this
     */
    public function availableInTimeSlot($date, $startTime, $endTime)
    {
        $datetime = $date . ' ' . $startTime;
        
        return $this->availableOnDate($date)
               ->andWhere(['not exists',
                   Appointment::find()
                       ->where('appointments.therapist_id = therapists.id')
                       ->andWhere(['between', 'appointment_datetime', $datetime, $date . ' ' . $endTime])
                       ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
               ])
               ->andWhere(['not exists',
                   TherapistBusySlot::find()
                       ->where('therapist_busy_slots.therapist_id = therapists.id')
                       ->andWhere(['date' => $date])
                       ->andWhere(['<=', 'start_time', $startTime])
                       ->andWhere(['>=', 'end_time', $endTime])
               ]);
    }

    /**
     * Filter by employee type
     *
     * @param string $employeeType
     * @return $this
     */
    public function byEmployeeType($employeeType)
    {
        return $this->andWhere(['employee_type' => $employeeType]);
    }

    /**
     * Filter external therapists
     *
     * @return $this
     */
    public function external()
    {
        return $this->byEmployeeType('external');
    }

    /**
     * Filter internal therapists
     *
     * @return $this
     */
    public function internal()
    {
        return $this->byEmployeeType('employee');
    }

    /**
     * Search by name or registration number
     *
     * @param string $query
     * @return $this
     */
    public function search($query)
    {
        return $this->andWhere(['or',
            ['like', 'first_name', $query],
            ['like', 'last_name', $query],
            ['like', 'registration_number', $query]
        ]);
    }

    /**
     * Filter by workload capacity
     *
     * @param int $minHours
     * @param int|null $maxHours
     * @return $this
     */
    public function workloadCapacity($minHours, $maxHours = null)
    {
        if ($maxHours !== null) {
            return $this->andWhere(['between', 'weekly_hours', $minHours, $maxHours]);
        }
        
        return $this->andWhere(['>=', 'weekly_hours', $minHours]);
    }

    /**
     * Filter part-time therapists
     *
     * @return $this
     */
    public function partTime()
    {
        return $this->andWhere(['<', 'weekly_hours', 40]);
    }

    /**
     * Filter full-time therapists
     *
     * @return $this
     */
    public function fullTime()
    {
        return $this->andWhere(['>=', 'weekly_hours', 40]);
    }

    /**
     * Filter therapists with upcoming appointments
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
     * Filter supervisors
     *
     * @return $this
     */
    public function supervisors()
    {
        return $this->joinWith('groupTherapists')
               ->andWhere(['group_therapists.role' => GroupTherapist::ROLE_SUPERVISOR]);
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
     * Order by experience (hire date)
     *
     * @param string $direction
     * @return $this
     */
    public function orderByExperience($direction = 'DESC')
    {
        $sort = $direction === 'ASC' ? SORT_ASC : SORT_DESC;
        return $this->orderBy(['hire_date' => $sort]);
    }

    /**
     * Include related data for optimization
     *
     * @return $this
     */
    public function withRelations()
    {
        return $this->with(['specialization', 'user']);
    }

    /**
     * Get therapists statistics by specialization
     *
     * @return $this
     */
    public function statisticsBySpecialization()
    {
        return $this->select(['specialization_id', 'COUNT(*) as therapist_count'])
               ->groupBy('specialization_id')
               ->with('specialization');
    }

    /**
     * Get therapists workload statistics
     *
     * @return $this
     */
    public function workloadStatistics()
    {
        return $this->select([
            'CASE 
                WHEN weekly_hours < 20 THEN "part-time-low"
                WHEN weekly_hours < 40 THEN "part-time-high"
                ELSE "full-time"
            END as workload_category',
            'COUNT(*) as therapist_count',
            'AVG(weekly_hours) as avg_hours'
        ])->groupBy('workload_category');
    }

    /**
     * Get active therapists count by month (for trends)
     *
     * @return $this
     */
    public function activeCountByMonth()
    {
        return $this->select([
            'DATE_FORMAT(hire_date, "%Y-%m") as month',
            'COUNT(*) as count'
        ])
        ->where(['is_active' => 1])
        ->groupBy('month')
        ->orderBy('month');
    }
} 
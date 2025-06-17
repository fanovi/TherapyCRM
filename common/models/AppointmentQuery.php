<?php

namespace common\models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Appointment]].
 *
 * @see Appointment
 */
class AppointmentQuery extends ActiveQuery
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
     * Filter by scheduled appointments only
     *
     * @return $this
     */
    public function scheduled()
    {
        return $this->andWhere(['status' => Appointment::STATUS_SCHEDULED]);
    }

    /**
     * Filter by completed appointments only
     *
     * @return $this
     */
    public function completed()
    {
        return $this->andWhere(['status' => Appointment::STATUS_COMPLETED]);
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
     * Filter by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return $this
     */
    public function dateRange($startDate, $endDate)
    {
        return $this->andWhere(['between', 'DATE(appointment_datetime)', $startDate, $endDate]);
    }

    /**
     * Filter by specific date
     *
     * @param string $date
     * @return $this
     */
    public function onDate($date)
    {
        return $this->andWhere(['DATE(appointment_datetime)' => $date]);
    }

    /**
     * Filter by upcoming appointments (from now)
     *
     * @return $this
     */
    public function upcoming()
    {
        return $this->andWhere(['>=', 'appointment_datetime', date('Y-m-d H:i:s')])
               ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED]);
    }

    /**
     * Filter by past appointments
     *
     * @return $this
     */
    public function past()
    {
        return $this->andWhere(['<', 'appointment_datetime', date('Y-m-d H:i:s')]);
    }

    /**
     * Filter by today's appointments
     *
     * @return $this
     */
    public function today()
    {
        return $this->onDate(date('Y-m-d'));
    }

    /**
     * Filter by this week's appointments
     *
     * @return $this
     */
    public function thisWeek()
    {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        
        return $this->dateRange($startOfWeek, $endOfWeek);
    }

    /**
     * Filter by this month's appointments
     *
     * @return $this
     */
    public function thisMonth()
    {
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');
        
        return $this->dateRange($startOfMonth, $endOfMonth);
    }

    /**
     * Filter by location type
     *
     * @param string $locationType
     * @return $this
     */
    public function atLocation($locationType)
    {
        return $this->andWhere(['location_type' => $locationType]);
    }

    /**
     * Filter appointments at office
     *
     * @return $this
     */
    public function atOffice()
    {
        return $this->atLocation(Appointment::LOCATION_OFFICE);
    }

    /**
     * Filter appointments at home
     *
     * @return $this
     */
    public function atHome()
    {
        return $this->atLocation(Appointment::LOCATION_HOME);
    }

    /**
     * Filter by duration
     *
     * @param int $minMinutes
     * @param int|null $maxMinutes
     * @return $this
     */
    public function duration($minMinutes, $maxMinutes = null)
    {
        if ($maxMinutes === null) {
            return $this->andWhere(['duration_minutes' => $minMinutes]);
        }
        
        return $this->andWhere(['between', 'duration_minutes', $minMinutes, $maxMinutes]);
    }

    /**
     * Filter substituted appointments
     *
     * @return $this
     */
    public function substituted()
    {
        return $this->andWhere(['IS NOT', 'original_therapist_id', null])
               ->andWhere('original_therapist_id != therapist_id');
    }

    /**
     * Include related models for optimization
     *
     * @return $this
     */
    public function withRelations()
    {
        return $this->with(['therapist', 'patient', 'planTherapy']);
    }

    /**
     * Order by appointment datetime
     *
     * @param string $direction
     * @return $this
     */
    public function orderByDateTime($direction = 'ASC')
    {
        return $this->orderBy(['appointment_datetime' => $direction === 'ASC' ? SORT_ASC : SORT_DESC]);
    }

    /**
     * Group by date for statistics
     *
     * @return $this
     */
    public function groupByDate()
    {
        return $this->select(['DATE(appointment_datetime) as date', 'COUNT(*) as count'])
               ->groupBy('DATE(appointment_datetime)');
    }

    /**
     * Get appointments count by status
     *
     * @return $this
     */
    public function countByStatus()
    {
        return $this->select(['status', 'COUNT(*) as count'])
               ->groupBy('status');
    }
} 
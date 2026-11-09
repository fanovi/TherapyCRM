<?php

namespace common\services;

use DateTime;
use common\models\Appointment;

/**
 * Recuperi delle ore di trattamento per il regime L11.
 *
 * In L11 il monte ore e' settimanale: l'assenza del paziente libera l'ora nella
 * sua settimana, ma il recupero puo' cadere in qualunque giorno dello stesso
 * mese di calendario (mai nel mese precedente o successivo).
 *
 * Ogni recupero (appointment_category = recovery) viene collegato a una
 * specifica assenza del paziente tramite related_appointment_id. L'ora
 * recuperata viene conteggiata nella settimana dell'assenza, non in quella in
 * cui cade il recupero: cosi' la settimana dell'assenza non mostra piu' l'ora
 * libera e quella del recupero non va oltre il limite.
 *
 * Definizioni:
 * - assenza recuperabile: appuntamento non-recupero della stessa terapia, in
 *   stato absent_justified/absent_not_justified, senza recupero attivo collegato;
 * - recupero attivo: recupero non annullato e non a sua volta in assenza
 *   (se il recupero salta, l'ora torna recuperabile).
 */
class RecoveryCreditService
{
    const L11_REGIME_NAME = 'L11';

    /** Stati che non consumano il monte ore del piano. */
    const NOT_COUNTED_STATUSES = [
        Appointment::STATUS_CANCELLED,
        Appointment::STATUS_THERAPIST_ABSENT,
        Appointment::STATUS_ABSENT_JUSTIFIED,
        Appointment::STATUS_ABSENT_NOT_JUSTIFIED,
    ];

    /** Assenze del paziente: le uniche che danno diritto al recupero. */
    const PATIENT_ABSENT_STATUSES = [
        Appointment::STATUS_ABSENT_JUSTIFIED,
        Appointment::STATUS_ABSENT_NOT_JUSTIFIED,
    ];

    /**
     * Il piano terapeutico e' in regime L11 (riconosciuto per nome, non per id).
     *
     * @param \common\models\TherapeuticPlan|null $therapeuticPlan
     */
    public static function isL11($therapeuticPlan): bool
    {
        return $therapeuticPlan
            && $therapeuticPlan->regime
            && strcasecmp(trim((string) $therapeuticPlan->regime->nome), self::L11_REGIME_NAME) === 0;
    }

    /**
     * Limite settimanale della terapia in minuti.
     *
     * @param \common\models\PlanTherapy $planTherapy
     */
    public static function weeklyCapMinutes($planTherapy): int
    {
        return (int) round((float) $planTherapy->weekly_hours * 60);
    }

    /**
     * @return string[] [inizio, fine] della settimana (lun-dom) in 'Y-m-d H:i:s'
     */
    public static function weekBounds(string $datetime): array
    {
        $start = new DateTime($datetime);
        $start->modify('monday this week')->setTime(0, 0, 0);
        $end = clone $start;
        $end->modify('sunday this week')->setTime(23, 59, 59);

        return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
    }

    /**
     * @return string[] [inizio, fine] del mese di calendario in 'Y-m-d H:i:s'
     */
    public static function monthBounds(string $datetime): array
    {
        $start = new DateTime($datetime);
        $start->modify('first day of this month')->setTime(0, 0, 0);
        $end = clone $start;
        $end->modify('last day of this month')->setTime(23, 59, 59);

        return [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')];
    }

    /**
     * Minuti consumati nella settimana della data indicata:
     * appuntamenti non-recupero effettivi + ore delle assenze recuperate altrove.
     * I recuperi non contano nella settimana in cui cadono.
     */
    public static function weeklyUsedMinutes(int $planTherapyId, string $datetime, ?int $excludeAppointmentId = null): int
    {
        [$from, $to] = self::weekBounds($datetime);

        $regularQuery = Appointment::find()
            ->where(['plan_therapy_id' => $planTherapyId])
            ->andWhere(['between', 'appointment_datetime', $from, $to])
            ->andWhere(['not in', 'status', self::NOT_COUNTED_STATUSES])
            ->andWhere(['!=', 'appointment_category', Appointment::CATEGORY_RECOVERY]);
        if ($excludeAppointmentId) {
            $regularQuery->andWhere(['!=', 'id', $excludeAppointmentId]);
        }
        $regularMinutes = (int) ($regularQuery->sum('duration_minutes') ?: 0);

        $absences = self::absenceQuery($planTherapyId, $from, $to)
            ->select(['id', 'duration_minutes'])
            ->asArray()
            ->all();

        $linked = self::linkedActiveMinutes(array_column($absences, 'id'), $excludeAppointmentId);

        $recoveredMinutes = 0;
        foreach ($absences as $absence) {
            $recoveredMinutes += min((int) $absence['duration_minutes'], $linked[$absence['id']] ?? 0);
        }

        return $regularMinutes + $recoveredMinutes;
    }

    /**
     * Cerca l'assenza del paziente da collegare a un recupero: stessa terapia,
     * stesso mese di calendario del recupero, senza recupero attivo e con la
     * settimana dell'assenza che ha ancora spazio (se l'ora e' gia' stata
     * riprogrammata nella stessa settimana non e' recuperabile altrove).
     * A parita' si sceglie la piu' vecchia.
     *
     * @param int|null $excludeAppointmentId il recupero stesso, in modifica
     * @param int|null $preferredAbsenceId   collegamento attuale, tenuto se ancora valido
     */
    public static function findRecoverableAbsence(
        int $planTherapyId,
        int $weeklyCapMinutes,
        string $recoveryDatetime,
        int $durationMinutes,
        ?int $excludeAppointmentId = null,
        ?int $preferredAbsenceId = null
    ): ?Appointment {
        [$from, $to] = self::monthBounds($recoveryDatetime);

        /** @var Appointment[] $candidates */
        $candidates = self::absenceQuery($planTherapyId, $from, $to)
            ->orderBy(['appointment_datetime' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        if ($preferredAbsenceId) {
            usort($candidates, function ($a, $b) use ($preferredAbsenceId) {
                return ((int) $b->id === $preferredAbsenceId) <=> ((int) $a->id === $preferredAbsenceId);
            });
        }

        $linked = self::linkedActiveMinutes(array_map(function ($a) {
            return $a->id;
        }, $candidates), $excludeAppointmentId);

        foreach ($candidates as $absence) {
            if (($linked[$absence->id] ?? 0) > 0) {
                continue;
            }

            $needed = min($durationMinutes, (int) $absence->duration_minutes);
            $free = $weeklyCapMinutes - self::weeklyUsedMinutes(
                $planTherapyId,
                $absence->appointment_datetime,
                $excludeAppointmentId
            );

            if ($free >= $needed) {
                return $absence;
            }
        }

        return null;
    }

    /**
     * Riepilogo dei recuperi del mese per il contatore ore.
     *
     * @return array{mese: string, minuti_da_recuperare: int, minuti_recuperati: int, minuti_non_collegati: int}
     */
    public static function monthlySummary(int $planTherapyId, int $weeklyCapMinutes, string $datetime): array
    {
        [$from, $to] = self::monthBounds($datetime);

        $absences = self::absenceQuery($planTherapyId, $from, $to)
            ->select(['id', 'duration_minutes', 'appointment_datetime'])
            ->asArray()
            ->all();

        $linked = self::linkedActiveMinutes(array_column($absences, 'id'));

        // Assenze ancora da recuperare, raggruppate per settimana: lo spazio
        // libero della settimana e' condiviso fra le sue assenze.
        $unrecoveredByWeek = [];
        foreach ($absences as $absence) {
            if (($linked[$absence['id']] ?? 0) > 0) {
                continue;
            }
            $weekStart = self::weekBounds($absence['appointment_datetime'])[0];
            $unrecoveredByWeek[$weekStart] = ($unrecoveredByWeek[$weekStart] ?? 0) + (int) $absence['duration_minutes'];
        }

        $toRecover = 0;
        foreach ($unrecoveredByWeek as $weekStart => $minutes) {
            $free = max(0, $weeklyCapMinutes - self::weeklyUsedMinutes($planTherapyId, $weekStart));
            $toRecover += min($minutes, $free);
        }

        // Recuperi attivi del mese: collegati a un'assenza valida oppure no
        // (assenza revocata, recuperi precedenti al collegamento).
        $recoveries = Appointment::find()
            ->select(['id', 'duration_minutes', 'related_appointment_id'])
            ->where(['plan_therapy_id' => $planTherapyId])
            ->andWhere(['appointment_category' => Appointment::CATEGORY_RECOVERY])
            ->andWhere(['between', 'appointment_datetime', $from, $to])
            ->andWhere(['not in', 'status', self::NOT_COUNTED_STATUSES])
            ->asArray()
            ->all();

        $relatedIds = array_filter(array_column($recoveries, 'related_appointment_id'));
        $validRelatedIds = $relatedIds
            ? array_map('intval', Appointment::find()
                ->select('id')
                ->where(['id' => $relatedIds, 'status' => self::PATIENT_ABSENT_STATUSES])
                ->column())
            : [];

        $recovered = 0;
        $notLinked = 0;
        foreach ($recoveries as $recovery) {
            if ($recovery['related_appointment_id'] && in_array((int) $recovery['related_appointment_id'], $validRelatedIds, true)) {
                $recovered += (int) $recovery['duration_minutes'];
            } else {
                $notLinked += (int) $recovery['duration_minutes'];
            }
        }

        return [
            'mese' => substr($from, 0, 7),
            'minuti_da_recuperare' => $toRecover,
            'minuti_recuperati' => $recovered,
            'minuti_non_collegati' => $notLinked,
        ];
    }

    /**
     * Stacca i recuperi da un'assenza che non e' piu' tale (revocata o cambiata
     * di stato): restano come recuperi non collegati.
     */
    public static function detachRecoveries(int $absenceAppointmentId): int
    {
        return Appointment::updateAll(
            ['related_appointment_id' => null],
            [
                'related_appointment_id' => $absenceAppointmentId,
                'appointment_category' => Appointment::CATEGORY_RECOVERY,
            ]
        );
    }

    /**
     * Assenze del paziente (non-recupero) di una terapia in un intervallo.
     */
    private static function absenceQuery(int $planTherapyId, string $from, string $to)
    {
        return Appointment::find()
            ->where(['plan_therapy_id' => $planTherapyId])
            ->andWhere(['status' => self::PATIENT_ABSENT_STATUSES])
            ->andWhere(['!=', 'appointment_category', Appointment::CATEGORY_RECOVERY])
            ->andWhere(['between', 'appointment_datetime', $from, $to]);
    }

    /**
     * Minuti dei recuperi attivi collegati a ciascuna assenza.
     *
     * @param int[] $absenceIds
     * @return array<int, int> absenceId => minuti
     */
    private static function linkedActiveMinutes(array $absenceIds, ?int $excludeAppointmentId = null): array
    {
        if (!$absenceIds) {
            return [];
        }

        $query = Appointment::find()
            ->select(['related_appointment_id', 'minutes' => 'SUM(duration_minutes)'])
            ->where(['related_appointment_id' => $absenceIds])
            ->andWhere(['appointment_category' => Appointment::CATEGORY_RECOVERY])
            ->andWhere(['not in', 'status', self::NOT_COUNTED_STATUSES])
            ->groupBy('related_appointment_id');
        if ($excludeAppointmentId) {
            $query->andWhere(['!=', 'id', $excludeAppointmentId]);
        }

        $map = [];
        foreach ($query->asArray()->all() as $row) {
            $map[(int) $row['related_appointment_id']] = (int) $row['minutes'];
        }

        return $map;
    }
}

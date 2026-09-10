<?php

namespace common\services;

use Yii;
use yii\caching\TagDependency;
use common\models\Holiday;

/**
 * Service per i giorni di chiusura della struttura (tabella holidays).
 *
 * Le righe della tabella sono REGOLE, non date: "ogni 1 gennaio", "26 aprile
 * 2027", "Pasqua", "ogni domenica". Questo service le espande in date concrete
 * e risponde alla domanda "la struttura e' chiusa in questo giorno?".
 *
 * Due cache distinte, entrambe invalidate dal tag CACHE_TAG:
 * - getDatesForYear(): festivita' a data (fisse, una tantum, calcolate)
 *   espanse per anno. Una query per anno, poi solo cache.
 * - getClosedWeekdays(): chiusure settimanali, tenute come regola e mai
 *   espanse in date (sarebbero ~52 righe l'anno di rumore).
 *
 * Nota: la cache e' un FileCache sotto @runtime, quindi per-applicazione.
 * L'invalidazione fatta dal gestionale (frontend) vale per il frontend, che e'
 * anche l'unica app che crea appuntamenti; console e api vedono le modifiche
 * entro CACHE_DURATION.
 */
class HolidayService
{
    const CACHE_TAG = 'holidays';
    const CACHE_DURATION = 86400; // 24 ore

    /**
     * Memo in-process: evita di rileggere il FileCache a ogni giorno quando si
     * genera un pattern annuale (~365 chiamate a getClosureName()).
     *
     * @var array
     */
    private static array $memo = [];

    /**
     * Festivita' a data dell'anno richiesto (chiusure settimanali escluse).
     *
     * @param int $year
     * @return array ['Y-m-d' => nome], ordinato per data
     */
    public static function getDatesForYear(int $year): array
    {
        $key = 'dates_' . $year;
        if (!isset(self::$memo[$key])) {
            self::$memo[$key] = self::cached(['holidays_dates', $year], function () use ($year) {
                $rows = Holiday::find()
                    ->active()
                    ->andWhere(['!=', 'rule', Holiday::RULE_WEEKDAY])
                    ->andWhere(['year' => [0, $year]])
                    ->orderBy(['month' => SORT_ASC, 'day' => SORT_ASC, 'id' => SORT_ASC])
                    ->asArray()
                    ->all();

                return self::expandForYear($rows, $year);
            });
        }

        return self::$memo[$key];
    }

    /**
     * Giorni della settimana di chiusura, in numerazione ISO 8601.
     *
     * @return array [isoDay => nome], es. [7 => 'Domenica']
     */
    public static function getClosedWeekdays(): array
    {
        if (!isset(self::$memo['weekdays'])) {
            self::$memo['weekdays'] = self::cached('holidays_weekdays', function () {
                $rows = Holiday::find()
                    ->active()
                    ->andWhere(['rule' => Holiday::RULE_WEEKDAY])
                    ->orderBy(['day' => SORT_ASC])
                    ->asArray()
                    ->all();

                $weekdays = [];
                foreach ($rows as $row) {
                    $weekdays[(int)$row['day']] = $row['name'];
                }
                return $weekdays;
            });
        }

        return self::$memo['weekdays'];
    }

    /**
     * Festivita' a data comprese nell'intervallo (estremi inclusi).
     * Le chiusure settimanali non sono incluse: vanno lette da getClosedWeekdays().
     *
     * @param string $from Y-m-d
     * @param string $to Y-m-d
     * @return array ['Y-m-d' => nome], ordinato per data
     */
    public static function getDatesInRange(string $from, string $to): array
    {
        $fromDate = self::toDate($from);
        $toDate = self::toDate($to);
        if ($fromDate === null || $toDate === null || $fromDate > $toDate) {
            return [];
        }

        $dates = [];
        for ($year = (int)substr($fromDate, 0, 4); $year <= (int)substr($toDate, 0, 4); $year++) {
            foreach (self::getDatesForYear($year) as $date => $name) {
                if ($date >= $fromDate && $date <= $toDate) {
                    $dates[$date] = $name;
                }
            }
        }

        return $dates;
    }

    /**
     * @param string $datetime Y-m-d, Y-m-d H:i o Y-m-d H:i:s
     * @return bool
     */
    public static function isClosed(string $datetime): bool
    {
        return self::getClosureName($datetime) !== null;
    }

    /**
     * Nome della chiusura che cade nel giorno indicato.
     * Controlla prima il giorno della settimana, poi le festivita' a data.
     *
     * @param string $datetime Y-m-d, Y-m-d H:i o Y-m-d H:i:s
     * @return string|null null se il giorno e' lavorativo o la data non e' valida
     */
    public static function getClosureName(string $datetime): ?string
    {
        $date = self::toDate($datetime);
        if ($date === null) {
            return null;
        }

        $isoDay = (int)(new \DateTimeImmutable($date))->format('N');
        $weekdays = self::getClosedWeekdays();
        if (isset($weekdays[$isoDay])) {
            return $weekdays[$isoDay];
        }

        return self::getDatesForYear((int)substr($date, 0, 4))[$date] ?? null;
    }

    /**
     * Data della Pasqua gregoriana (algoritmo di Meeus/Jones/Butcher).
     *
     * Implementato a mano invece di easter_date(): quella richiede l'estensione
     * calendar e sulle build a 32 bit e' limitata al range 1970-2037.
     *
     * @param int $year
     * @return \DateTimeImmutable
     */
    public static function computeEaster(int $year): \DateTimeImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
    }

    /**
     * Data in cui una regola cade nell'anno richiesto.
     * Unica implementazione della semantica di rule/month/day/year: la usa
     * anche Holiday::resolveDate().
     *
     * @param string $rule Holiday::RULE_*
     * @param int $month
     * @param int $day
     * @param int $year 0 = ricorrente
     * @param int $targetYear
     * @return string|null Y-m-d; null per le chiusure settimanali, per le
     *                     una tantum di un altro anno e per il 29/02 negli anni non bisestili
     */
    public static function resolveOccurrence(string $rule, int $month, int $day, int $year, int $targetYear): ?string
    {
        if ($year !== 0 && $year !== $targetYear) {
            return null;
        }

        switch ($rule) {
            case Holiday::RULE_NONE:
                return checkdate($month, $day, $targetYear)
                    ? sprintf('%04d-%02d-%02d', $targetYear, $month, $day)
                    : null;
            case Holiday::RULE_EASTER:
                return self::computeEaster($targetYear)->format('Y-m-d');
            case Holiday::RULE_EASTER_MONDAY:
                return self::computeEaster($targetYear)->modify('+1 day')->format('Y-m-d');
            default:
                // RULE_WEEKDAY: nessuna data singola
                return null;
        }
    }

    /**
     * Espande le righe della tabella nelle date concrete di un anno.
     * Se due regole cadono lo stesso giorno i nomi vengono uniti.
     *
     * @param array $rows righe holidays (array con rule, month, day, year, name)
     * @param int $year
     * @return array ['Y-m-d' => nome], ordinato per data
     */
    public static function expandForYear(array $rows, int $year): array
    {
        $dates = [];
        foreach ($rows as $row) {
            $date = self::resolveOccurrence(
                (string)$row['rule'],
                (int)$row['month'],
                (int)$row['day'],
                (int)$row['year'],
                $year
            );
            if ($date === null) {
                continue;
            }
            $dates[$date] = isset($dates[$date]) ? $dates[$date] . ' / ' . $row['name'] : $row['name'];
        }
        ksort($dates);

        return $dates;
    }

    /**
     * Invalida la cache delle chiusure (chiamato da Holiday dopo save/delete).
     */
    public static function invalidateCache(): void
    {
        self::$memo = [];
        TagDependency::invalidate(Yii::$app->cache, self::CACHE_TAG);
    }

    /**
     * getOrSet() con il tag delle chiusure.
     *
     * Se la tabella non e' ancora stata migrata (finestra fra git pull e
     * ./yii migrate) non ci sono chiusure: si registra l'errore e non si mette
     * nulla in cache, invece di far fallire ogni salvataggio di appuntamento.
     *
     * @param mixed $key
     * @param callable $loader
     * @return array
     */
    private static function cached($key, callable $loader): array
    {
        try {
            return Yii::$app->cache->getOrSet($key, $loader, self::CACHE_DURATION, new TagDependency(['tags' => self::CACHE_TAG]));
        } catch (\yii\db\Exception $e) {
            Yii::error('Giorni di chiusura non disponibili: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }

    /**
     * Normalizza una data/datetime in Y-m-d, senza tagliare la stringa grezza.
     *
     * @param string $datetime
     * @return string|null
     */
    private static function toDate(string $datetime): ?string
    {
        $datetime = trim($datetime);
        // new DateTimeImmutable('') restituirebbe "adesso"
        if ($datetime === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($datetime))->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }
}

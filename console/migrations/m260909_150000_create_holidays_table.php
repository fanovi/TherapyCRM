<?php

use yii\db\Expression;
use yii\db\Migration;
use yii\db\Query;
use common\services\HolidayService;

/**
 * Modulo giorni festivi (docs/PLAN_GIORNI_FESTIVI_2026-09-09.md).
 *
 * Tabella holidays: un'unica anagrafica per tutti i giorni di chiusura della
 * struttura — festivita' a data fissa, chiusure una tantum, festivita'
 * calcolate (Pasqua, Lunedi' dell'Angelo) e chiusure settimanali (la domenica).
 *
 * I campi month/day/year sono NOT NULL con 0 come "non pertinente": con i NULL
 * l'indice UNIQUE (rule, month, day, year) non impedirebbe i duplicati.
 *
 * Seed idempotente delle 12 festivita' nazionali + chiusura domenicale,
 * inserito via query senza passare dalla validazione del modello: il modello
 * rifiuta una chiusura se il giorno ha appuntamenti 'scheduled', e la migration
 * non sarebbe installabile su un DB che ha gia' appuntamenti domenicali.
 *
 * In coda stampa quanti appuntamenti 'scheduled' da oggi in avanti cadono in
 * giorni ora chiusi. Sola lettura: vanno spostati o annullati a mano.
 */
class m260909_150000_create_holidays_table extends Migration
{
    private const TABLE = '{{%holidays}}';

    /** [name, rule, month, day] — tutte ricorrenti (year = 0) */
    private const SEED = [
        ['Capodanno', '', 1, 1],
        ['Epifania', '', 1, 6],
        ['Pasqua', 'easter', 0, 0],
        ["Lunedì dell'Angelo", 'easter_monday', 0, 0],
        ['Festa della Liberazione', '', 4, 25],
        ['Festa del Lavoro', '', 5, 1],
        ['Festa della Repubblica', '', 6, 2],
        ['Ferragosto', '', 8, 15],
        ['Ognissanti', '', 11, 1],
        ['Immacolata Concezione', '', 12, 8],
        ['Natale', '', 12, 25],
        ['Santo Stefano', '', 12, 26],
        ['Domenica', 'weekday', 0, 7],
    ];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Il DDL MySQL fa commit implicito: se una esecuzione precedente si e'
        // interrotta dopo il createTable, la tabella esiste gia' e si riparte dal seed.
        if ($this->db->getTableSchema(self::TABLE, true) === null) {
            $this->createTable(self::TABLE, [
                'id' => $this->primaryKey(),
                'name' => $this->string(100)->notNull(),
                'rule' => $this->string(30)->notNull()->defaultValue('')->comment('vuoto = data esplicita, easter, easter_monday, weekday'),
                'month' => $this->tinyInteger()->unsigned()->notNull()->defaultValue(0)->comment('1-12, 0 se non pertinente'),
                'day' => $this->tinyInteger()->unsigned()->notNull()->defaultValue(0)->comment('1-31 per le date, 1-7 ISO (7 = domenica) per weekday, 0 se non pertinente'),
                'year' => $this->smallInteger()->unsigned()->notNull()->defaultValue(0)->comment('0 = ricorrente ogni anno'),
                'is_active' => $this->boolean()->notNull()->defaultValue(1),
                'is_national' => $this->boolean()->notNull()->defaultValue(0)->comment('Riga del seed nazionale, solo etichetta'),
                'notes' => $this->text()->null(),
                'created_by' => $this->integer()->null(),
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
                'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            ]);

            $this->addForeignKey(
                'fk-holidays-created_by',
                self::TABLE,
                'created_by',
                '{{%users}}',
                'id',
                'SET NULL',
                'CASCADE'
            );

            $this->createIndex('uniq-holiday_occurrence', self::TABLE, ['rule', 'month', 'day', 'year'], true);
            $this->createIndex('idx-holiday_active', self::TABLE, 'is_active');
        } else {
            echo "  - Tabella holidays gia' esistente\n";
        }

        echo "\n📅 Seed festività nazionali e chiusura domenicale...\n";
        foreach (self::SEED as [$name, $rule, $month, $day]) {
            $exists = (new Query())
                ->from(self::TABLE)
                ->where(['rule' => $rule, 'month' => $month, 'day' => $day, 'year' => 0])
                ->exists($this->db);
            if ($exists) {
                echo "  - {$name} gia' presente\n";
                continue;
            }

            $this->insert(self::TABLE, [
                'name' => $name,
                'rule' => $rule,
                'month' => $month,
                'day' => $day,
                'year' => 0,
                'is_active' => 1,
                'is_national' => 1,
            ]);
        }

        $this->reportScheduledOnClosedDays();

        echo "\n✅ Migrazione completata\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-holidays-created_by', self::TABLE);
        $this->dropTable(self::TABLE);

        echo "\n✅ Rollback completato\n";

        return true;
    }

    /**
     * Conta gli appuntamenti 'scheduled' da oggi in avanti che cadono in un
     * giorno ora chiuso. Non modifica nulla e non fa fallire la migration.
     */
    private function reportScheduledOnClosedDays(): void
    {
        try {
            $today = date('Y-m-d');
            $rows = (new Query())->from(self::TABLE)->where(['is_active' => 1])->all($this->db);

            $scheduled = (new Query())
                ->from('{{%appointments}}')
                ->where(['status' => 'scheduled'])
                ->andWhere(['>=', 'appointment_datetime', $today . ' 00:00:00']);

            // Chiusure settimanali. WEEKDAY() + 1 coincide con l'ISO 8601 (7 = domenica);
            // DAYOFWEEK() usa 1 = domenica e sposterebbe il conteggio di un giorno.
            $weekdayCounts = [];
            $closedIsoDays = [];
            foreach ($rows as $row) {
                if ($row['rule'] !== 'weekday') {
                    continue;
                }
                $closedIsoDays[] = (int)$row['day'];
                $weekdayCounts[$row['name']] = (int)(clone $scheduled)
                    ->andWhere(new Expression('WEEKDAY(appointment_datetime) + 1 = :isoDay', [':isoDay' => (int)$row['day']]))
                    ->count('*', $this->db);
            }

            // Festivita' a data: occorrenze da oggi fino all'ultimo appuntamento
            // programmato. Si escludono i giorni gia' contati come chiusura
            // settimanale (Pasqua cade sempre di domenica).
            $dateRows = array_filter($rows, fn($row) => $row['rule'] !== 'weekday');
            $dateCounts = [];
            $maxDatetime = (clone $scheduled)->max('appointment_datetime', $this->db);
            if ($maxDatetime !== null && !empty($dateRows)) {
                $dates = [];
                $lastYear = (int)(new \DateTimeImmutable($maxDatetime))->format('Y');
                for ($year = (int)date('Y'); $year <= $lastYear; $year++) {
                    foreach (HolidayService::expandForYear($dateRows, $year) as $date => $name) {
                        if ($date >= $today) {
                            $dates[$date] = $name;
                        }
                    }
                }

                if (!empty($dates)) {
                    $query = (clone $scheduled)
                        ->select(['day' => new Expression('DATE(appointment_datetime)'), 'cnt' => new Expression('COUNT(*)')])
                        ->andWhere(['in', 'DATE(appointment_datetime)', array_keys($dates)])
                        ->groupBy(new Expression('DATE(appointment_datetime)'))
                        ->orderBy(new Expression('DATE(appointment_datetime)'));
                    if (!empty($closedIsoDays)) {
                        $query->andWhere(['not in', 'WEEKDAY(appointment_datetime) + 1', $closedIsoDays]);
                    }
                    foreach ($query->all($this->db) as $row) {
                        $dateCounts[$row['day']] = [$dates[$row['day']], (int)$row['cnt']];
                    }
                }
            }

            $total = array_sum($weekdayCounts) + array_sum(array_column($dateCounts, 1));
            if ($total === 0) {
                echo "\n✓ Nessun appuntamento 'scheduled' in giorni di chiusura da oggi in avanti.\n";
                return;
            }

            echo "\n⚠️  Appuntamenti 'scheduled' in giorni ora chiusi (da oggi in avanti):\n";
            foreach ($weekdayCounts as $name => $count) {
                printf("    %s %s %d appuntamenti\n", $name, str_repeat('.', max(3, 24 - mb_strlen($name))), $count);
            }
            $holidayTotal = array_sum(array_column($dateCounts, 1));
            printf("    Festività %s %d appuntamenti\n", str_repeat('.', 15), $holidayTotal);
            foreach ($dateCounts as $date => [$name, $count]) {
                printf("      - %s %s: %d\n", \DateTimeImmutable::createFromFormat('Y-m-d', $date)->format('d/m/Y'), $name, $count);
            }
            echo "    Vanno spostati o annullati a mano dal calendario.\n";
        } catch (\Throwable $e) {
            echo "\n⚠️  Report appuntamenti non disponibile: {$e->getMessage()}\n";
        }
    }
}

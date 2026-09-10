<?php

namespace common\models;

use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\db\Query;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use common\services\HolidayService;

/**
 * This is the model class for table "holidays".
 *
 * Ogni riga e' una REGOLA di chiusura della struttura, non una data:
 *
 * | Caso                            | rule          | month | day  | year |
 * |---------------------------------|---------------|-------|------|------|
 * | Ricorrente fissa (Capodanno)    | ''            | 1     | 1    | 0    |
 * | Una tantum (ponte 2027)         | ''            | 4     | 26   | 2027 |
 * | Calcolata ricorrente (Pasquetta)| easter_monday | 0     | 0    | 0    |
 * | Chiusura settimanale (domenica) | weekday       | 0     | 7    | 0    |
 *
 * I campi non pertinenti valgono 0 e non NULL: e' cio' che rende efficace
 * l'indice UNIQUE (rule, month, day, year), perche' MySQL ammette piu' NULL
 * dentro un UNIQUE.
 *
 * @property int $id
 * @property string $name
 * @property string $rule
 * @property int $month
 * @property int $day 1-31 per le date, 1-7 (ISO 8601, 7 = domenica) per le chiusure settimanali
 * @property int $year 0 = ricorrente ogni anno
 * @property int $is_active
 * @property int $is_national
 * @property string|null $notes
 * @property int|null $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $createdBy
 */
class Holiday extends ActiveRecord
{
    const RULE_NONE = '';
    const RULE_EASTER = 'easter';
    const RULE_EASTER_MONDAY = 'easter_monday';
    const RULE_WEEKDAY = 'weekday';

    /** Tipi mostrati nel form: ognuno corrisponde a una combinazione di rule/month/day/year */
    const KIND_RECURRING = 'recurring';
    const KIND_ONE_OFF = 'one_off';
    const KIND_COMPUTED = 'computed';
    const KIND_WEEKDAY = 'weekday';

    const YEAR_MIN = 2000;
    const YEAR_MAX = 2100;

    /** Giorni elencati al massimo nel messaggio di conflitto con gli appuntamenti */
    const MAX_LISTED_CONFLICTS = 10;

    /**
     * Campi virtuali del form. Quando $kind e' valorizzato, applyKind() li
     * traduce in rule/month/day/year, cosi' l'utente non deve comporre a mano
     * le combinazioni della tabella. Senza $kind (seed, console, codice) si
     * lavora direttamente sulle colonne.
     *
     * @var string|null uno dei KIND_*
     */
    public $kind;
    /** @var string|null Y-m-d della chiusura una tantum */
    public $date;
    /** @var int|string|null giorno ISO 8601 della chiusura settimanale */
    public $weekday;

    /**
     * Appuntamenti 'scheduled' che hanno impedito il salvataggio, valorizzato
     * da validateNoExistingAppointments(): ['Y-m-d' => conteggio].
     * Serve alla view per mostrare cosa va spostato o annullato.
     *
     * @var array
     */
    public $appointmentConflicts = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%holidays}}';
    }

    /**
     * {@inheritdoc}
     * @return HolidayQuery
     */
    public static function find()
    {
        return new HolidayQuery(get_called_class());
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => function () {
                    return date('Y-m-d H:i:s');
                },
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => false,
            ],
            [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => ['created_at', 'updated_at'],
                'entityNameCallback' => function ($model) {
                    return 'GiornoFestivo';
                },
            ],
        ];
    }

    /**
     * L'ordine conta: prima il tipo scelto nel form viene tradotto nelle
     * colonne, poi le colonne vengono normalizzate e solo dopo validate.
     *
     * is_national e created_by non sono assegnabili da form: il primo marca
     * solo le righe del seed, il secondo lo valorizza BlameableBehavior.
     *
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['kind'], 'in', 'range' => array_keys(self::getKindOptions())],
            [['kind'], 'applyKind'],
            [['date', 'weekday'], 'safe'],
            [['name'], 'required'],
            [['name', 'notes'], 'trim'],
            [['name'], 'string', 'max' => 100],
            [['notes'], 'string'],
            [['notes'], 'default', 'value' => null],
            [['rule'], 'default', 'value' => self::RULE_NONE],
            [['rule'], 'in', 'range' => array_keys(self::getRuleOptions())],
            // skipOnEmpty => false: RULE_NONE e' la stringa vuota
            [['rule'], 'normalizeOccurrence', 'skipOnEmpty' => false],
            [['month', 'day', 'year'], 'default', 'value' => 0],
            [['month', 'day', 'year'], 'integer'],
            [['is_active'], 'default', 'value' => 1],
            [['is_active'], 'boolean'],
            [['rule'], 'validateOccurrence', 'skipOnEmpty' => false],
            [['rule'], 'unique', 'targetAttribute' => ['rule', 'month', 'day', 'year'], 'skipOnEmpty' => false,
                'message' => 'Esiste già un giorno di chiusura con questa ricorrenza.'],
            [['rule'], 'validateNoExistingAppointments', 'skipOnEmpty' => false],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Nome',
            'rule' => 'Tipo',
            'month' => 'Mese',
            'day' => 'Giorno',
            'year' => 'Anno',
            'is_active' => 'Attivo',
            'is_national' => 'Festività nazionale',
            'notes' => 'Note',
            'created_by' => 'Creato da',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
            'kind' => 'Tipo',
            'date' => 'Data',
            'weekday' => 'Giorno della settimana',
        ];
    }

    /**
     * Valorizza i campi virtuali del form a partire dalle colonne.
     * Da chiamare prima di mostrare il form di modifica; non viene fatto in
     * afterFind() perche' un $kind sempre valorizzato riscriverebbe le colonne
     * a ogni save() fatto da codice.
     */
    public function initFormFields(): void
    {
        $this->kind = $this->getKind();
        $this->date = $this->kind === self::KIND_ONE_OFF ? $this->resolveDate((int)$this->year) : null;
        $this->weekday = $this->getClosedWeekday();
    }

    /**
     * Traduce il tipo scelto nel form nelle colonne rule/month/day/year.
     */
    public function applyKind($attribute, $params)
    {
        switch ($this->kind) {
            case self::KIND_RECURRING:
                $this->rule = self::RULE_NONE;
                $this->year = 0;
                break;

            case self::KIND_ONE_OFF:
                $date = trim((string)$this->date);
                $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                if ($dt === false || $dt->format('Y-m-d') !== $date) {
                    $this->addError('date', $date === '' ? 'Indica la data della chiusura.' : 'Inserisci una data valida.');
                    return;
                }
                $this->rule = self::RULE_NONE;
                $this->year = (int)$dt->format('Y');
                $this->month = (int)$dt->format('n');
                $this->day = (int)$dt->format('j');
                break;

            case self::KIND_COMPUTED:
                if (!$this->isComputedRule()) {
                    $this->addError('rule', 'Seleziona quale festività calcolata.');
                }
                break;

            case self::KIND_WEEKDAY:
                $this->rule = self::RULE_WEEKDAY;
                $this->day = (int)$this->weekday;
                break;
        }
    }

    /**
     * Azzera i campi che la regola scelta non usa, cosi' l'indice
     * uniq-holiday_occurrence confronta solo cio' che conta.
     */
    public function normalizeOccurrence($attribute, $params)
    {
        if ($this->rule === self::RULE_WEEKDAY) {
            $this->month = 0;
            $this->year = 0;
        } elseif ($this->isComputedRule()) {
            $this->month = 0;
            $this->day = 0;
            $this->year = 0;
        }
    }

    /**
     * Coerenza fra rule, month, day e year.
     */
    public function validateOccurrence($attribute, $params)
    {
        foreach (['kind', 'rule', 'date', 'month', 'day', 'year'] as $name) {
            if ($this->hasErrors($name)) {
                return;
            }
        }

        $month = (int)$this->month;
        $day = (int)$this->day;
        $year = (int)$this->year;

        if ($this->rule === self::RULE_WEEKDAY) {
            if ($day < 1 || $day > 7) {
                $this->addOccurrenceError('day', 'Seleziona un giorno della settimana valido.');
            }
            return;
        }

        if ($this->rule !== self::RULE_NONE) {
            return;
        }

        if ($month < 1 || $month > 12) {
            $this->addOccurrenceError('month', 'Seleziona un mese valido.');
            return;
        }
        if ($year !== 0 && ($year < self::YEAR_MIN || $year > self::YEAR_MAX)) {
            $this->addOccurrenceError('year', "L'anno deve essere compreso tra " . self::YEAR_MIN . ' e ' . self::YEAR_MAX . '.');
            return;
        }
        // Le ricorrenti si verificano su un anno bisestile: il 29/02 e' ammesso
        // e semplicemente non cade negli anni non bisestili.
        if (!checkdate($month, $day, $year ?: 2000)) {
            $this->addOccurrenceError('day', 'La data indicata non esiste.');
        }
    }

    /**
     * Nel form la data una tantum e il giorno della settimana hanno un campo
     * proprio: l'errore va mostrato li', non sulla colonna sottostante.
     */
    private function addOccurrenceError(string $attribute, string $message): void
    {
        $formField = [self::KIND_ONE_OFF => 'date', self::KIND_WEEKDAY => 'weekday'][$this->kind] ?? $attribute;
        $this->addError($formField, $message);
    }

    /**
     * Impedisce di chiudere un giorno che ha ancora appuntamenti 'scheduled':
     * vanno prima spostati o annullati. Scatta su inserimento, riattivazione e
     * modifica della ricorrenza; rinominare o annotare una chiusura esistente
     * non viene bloccato.
     *
     * Il seed della migration inserisce via query e non passa di qui: e' voluto,
     * altrimenti non sarebbe installabile su un DB con appuntamenti domenicali.
     */
    public function validateNoExistingAppointments($attribute, $params)
    {
        if ($this->hasErrors() || !$this->is_active) {
            return;
        }

        if (!$this->isNewRecord) {
            $changed = false;
            foreach (['rule', 'month', 'day', 'year', 'is_active'] as $name) {
                if ($this->isAttributeChanged($name, false)) {
                    $changed = true;
                    break;
                }
            }
            if (!$changed) {
                return;
            }
        }

        $conflicts = $this->findAppointmentConflicts(date('Y-m-d'));
        if (empty($conflicts)) {
            return;
        }

        $this->appointmentConflicts = $conflicts;

        $listed = [];
        foreach (array_slice($conflicts, 0, self::MAX_LISTED_CONFLICTS, true) as $date => $count) {
            $listed[] = \DateTimeImmutable::createFromFormat('Y-m-d', $date)->format('d/m/Y') . " ({$count})";
        }
        $others = count($conflicts) - count($listed);

        $this->addError($attribute, sprintf(
            'Impossibile salvare: ci sono %d appuntamenti programmati nei giorni interessati: %s%s. Spostali o annullali dal calendario, poi riprova.',
            array_sum($conflicts),
            implode(', ', $listed),
            $others > 0 ? " e in altri {$others} giorni" : ''
        ));
    }

    /**
     * Appuntamenti 'scheduled' da $fromDate in avanti che cadono nei giorni
     * chiusi da questa regola. Gli altri stati (completati, annullati, assenze)
     * sono storia e non contano.
     *
     * @param string $fromDate Y-m-d
     * @return array ['Y-m-d' => conteggio], ordinato per data
     */
    public function findAppointmentConflicts(string $fromDate): array
    {
        $dayExpr = new Expression('DATE(appointment_datetime)');

        $query = (new Query())
            ->select(['day' => $dayExpr, 'cnt' => new Expression('COUNT(*)')])
            ->from(Appointment::tableName())
            ->where(['status' => Appointment::STATUS_SCHEDULED])
            ->andWhere(['>=', 'appointment_datetime', $fromDate . ' 00:00:00'])
            ->groupBy($dayExpr)
            ->orderBy($dayExpr);

        if ($this->rule === self::RULE_WEEKDAY) {
            // WEEKDAY() restituisce 0 = lunedi' ... 6 = domenica, quindi +1 coincide
            // con l'ISO 8601 di PHP (format('N')). DAYOFWEEK() usa 1 = domenica e
            // sposterebbe silenziosamente la chiusura di un giorno.
            $query->andWhere(new Expression('WEEKDAY(appointment_datetime) + 1 = :isoDay', [':isoDay' => (int)$this->day]));
        } else {
            // Le occorrenze si enumerano fino all'ultimo appuntamento programmato:
            // oltre non c'e' nulla da controllare.
            $maxDatetime = (new Query())
                ->from(Appointment::tableName())
                ->where(['status' => Appointment::STATUS_SCHEDULED])
                ->max('appointment_datetime');
            if ($maxDatetime === null) {
                return [];
            }

            $dates = $this->getOccurrencesBetween($fromDate, (new \DateTimeImmutable($maxDatetime))->format('Y-m-d'));
            if (empty($dates)) {
                return [];
            }
            $query->andWhere(['in', 'DATE(appointment_datetime)', $dates]);
        }

        $conflicts = [];
        foreach ($query->all() as $row) {
            $conflicts[$row['day']] = (int)$row['cnt'];
        }

        return $conflicts;
    }

    /**
     * Date in cui la regola cade nell'intervallo (estremi inclusi).
     * Vuoto per le chiusure settimanali, che non hanno occorrenze discrete.
     *
     * @param string $from Y-m-d
     * @param string $to Y-m-d
     * @return string[] Y-m-d, in ordine
     */
    public function getOccurrencesBetween(string $from, string $to): array
    {
        $dates = [];
        for ($year = (int)substr($from, 0, 4); $year <= (int)substr($to, 0, 4); $year++) {
            $date = $this->resolveDate($year);
            if ($date !== null && $date >= $from && $date <= $to) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    /**
     * Data in cui la regola cade nell'anno richiesto.
     *
     * @param int $year
     * @return string|null Y-m-d; null se e' una tantum di un altro anno o una chiusura settimanale
     */
    public function resolveDate(int $year): ?string
    {
        return HolidayService::resolveOccurrence(
            (string)$this->rule,
            (int)$this->month,
            (int)$this->day,
            (int)$this->year,
            $year
        );
    }

    /**
     * Prima data da oggi in avanti in cui la regola cade.
     *
     * @param string|null $today Y-m-d, default oggi
     * @return string|null Y-m-d; null per le chiusure settimanali e le una tantum passate
     */
    public function getNextOccurrence(?string $today = null): ?string
    {
        $today = $today ?? date('Y-m-d');
        $year = (int)substr($today, 0, 4);
        // 8 anni: il 29/02 ricorrente puo' saltare fino a 7 anni (es. 2097 -> 2104)
        for ($y = $year; $y <= $year + 8; $y++) {
            $date = $this->resolveDate($y);
            if ($date !== null && $date >= $today) {
                return $date;
            }
        }

        return null;
    }

    /**
     * @return int|null giorno ISO 8601 (7 = domenica) se e' una chiusura settimanale
     */
    public function getClosedWeekday(): ?int
    {
        return $this->rule === self::RULE_WEEKDAY ? (int)$this->day : null;
    }

    /**
     * Tipo della regola, nella classificazione usata dal form.
     *
     * @return string uno dei KIND_*
     */
    public function getKind(): string
    {
        if ($this->rule === self::RULE_WEEKDAY) {
            return self::KIND_WEEKDAY;
        }
        if ($this->isComputedRule()) {
            return self::KIND_COMPUTED;
        }

        return (int)$this->year === 0 ? self::KIND_RECURRING : self::KIND_ONE_OFF;
    }

    /**
     * Descrizione leggibile della ricorrenza: "ogni 1 gennaio", "26 aprile 2027",
     * "Lunedì dell'Angelo (calcolata)", "ogni domenica".
     *
     * @return string
     */
    public function getOccurrenceLabel(): string
    {
        switch ($this->rule) {
            case self::RULE_EASTER:
                return 'Pasqua (calcolata)';
            case self::RULE_EASTER_MONDAY:
                return "Lunedì dell'Angelo (calcolata)";
            case self::RULE_WEEKDAY:
                return 'ogni ' . mb_strtolower(self::getWeekdayOptions()[(int)$this->day] ?? '?');
        }

        $label = (int)$this->day . ' ' . mb_strtolower(self::getMonthOptions()[(int)$this->month] ?? '?');

        return (int)$this->year === 0 ? 'ogni ' . $label : $label . ' ' . (int)$this->year;
    }

    /**
     * @return bool true per Pasqua e Lunedi' dell'Angelo
     */
    public function isComputedRule(): bool
    {
        return in_array($this->rule, [self::RULE_EASTER, self::RULE_EASTER_MONDAY], true);
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        HolidayService::invalidateCache();
    }

    /**
     * {@inheritdoc}
     */
    public function afterDelete()
    {
        parent::afterDelete();
        HolidayService::invalidateCache();
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * @return array
     */
    public static function getRuleOptions(): array
    {
        return [
            self::RULE_NONE => 'Data',
            self::RULE_EASTER => 'Pasqua',
            self::RULE_EASTER_MONDAY => "Lunedì dell'Angelo",
            self::RULE_WEEKDAY => 'Chiusura settimanale',
        ];
    }

    /**
     * @return array
     */
    public static function getKindOptions(): array
    {
        return [
            self::KIND_RECURRING => 'Ricorrente ogni anno',
            self::KIND_ONE_OFF => 'Una tantum',
            self::KIND_COMPUTED => 'Calcolata',
            self::KIND_WEEKDAY => 'Chiusura settimanale',
        ];
    }

    /**
     * Regole selezionabili per il tipo "Calcolata".
     *
     * @return array
     */
    public static function getComputedRuleOptions(): array
    {
        return [
            self::RULE_EASTER => 'Pasqua',
            self::RULE_EASTER_MONDAY => "Lunedì dell'Angelo",
        ];
    }

    /**
     * @return array [1 => 'Gennaio', ...]
     */
    public static function getMonthOptions(): array
    {
        return [
            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
        ];
    }

    /**
     * Giorni della settimana in numerazione ISO 8601 (1 = lunedi', 7 = domenica).
     *
     * @return array
     */
    public static function getWeekdayOptions(): array
    {
        return [
            1 => 'Lunedì', 2 => 'Martedì', 3 => 'Mercoledì', 4 => 'Giovedì',
            5 => 'Venerdì', 6 => 'Sabato', 7 => 'Domenica',
        ];
    }
}

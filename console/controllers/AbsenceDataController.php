<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use common\models\Absence;
use common\models\Appointment;
use common\models\Therapist;
use common\models\Patient;
use common\models\User;

/**
 * Controller console per generare dati di assenza di esempio per l'anno corrente
 * 
 * Esempi di utilizzo:
 * ./yii absence-data/generate-year - Genera assenze per tutto l'anno corrente
 * ./yii absence-data/generate-month 3 - Genera assenze per marzo dell'anno corrente
 * ./yii absence-data/clear-all - Rimuove tutte le assenze generate
 * ./yii absence-data/stats - Mostra statistiche assenze correnti
 */
class AbsenceDataController extends Controller
{
    public $defaultAction = 'help';

    // Configurazioni per la generazione realistica
    private $therapistAbsenceTypes = [
        Absence::TYPE_VACATION => ['weight' => 40, 'avg_days' => 7, 'reasons' => ['Ferie estive', 'Ferie natalizie', 'Ferie pasquali', 'Ferie personali']],
        Absence::TYPE_SICK_LEAVE => ['weight' => 30, 'avg_days' => 3, 'reasons' => ['Influenza', 'Mal di schiena', 'Febbre', 'Gastroenterite', 'Covid-19']],
        Absence::TYPE_PERSONAL => ['weight' => 15, 'avg_days' => 1, 'reasons' => ['Motivi familiari', 'Visita medica', 'Problemi personali']],
        Absence::TYPE_TRAINING => ['weight' => 10, 'avg_days' => 2, 'reasons' => ['Corso di aggiornamento', 'Convegno', 'Formazione obbligatoria', 'Workshop']],
        Absence::TYPE_OTHER => ['weight' => 5, 'avg_days' => 1, 'reasons' => ['Permesso studio', 'Lutto', 'Altro']]
    ];

    private $patientAbsenceReasons = [
        'justified' => ['Malattia', 'Visita medica', 'Impegno familiare', 'Trasporto non disponibile', 'Condizioni meteorologiche', 'Emergenza'],
        'not_justified' => ['', '', ''] // Assenze non giustificate spesso non hanno motivo specificato
    ];

    /**
     * Mostra l'help dei comandi disponibili
     */
    public function actionHelp()
    {
        $this->stdout("=== GENERATORE DATI ASSENZE ===\n\n", Console::BOLD);
        
        $this->stdout("Comandi disponibili:\n", Console::FG_GREEN);
        $this->stdout("  generate-year                 Genera assenze per tutto l'anno corrente\n");
        $this->stdout("  generate-month [mese]         Genera assenze per un mese specifico (1-12)\n");
        $this->stdout("  generate-period [data_inizio] [data_fine]  Genera assenze per un periodo\n");
        $this->stdout("  clear-all                     Rimuove tutte le assenze generate\n");
        $this->stdout("  clear-period [data_inizio] [data_fine]     Rimuove assenze in un periodo\n");
        $this->stdout("  stats                         Mostra statistiche assenze correnti\n");
        $this->stdout("  preview                       Anteprima di cosa verrà generato\n\n");
        
        $this->stdout("Esempi di utilizzo:\n", Console::FG_YELLOW);
        $this->stdout("  ./yii absence-data/generate-year\n");
        $this->stdout("  ./yii absence-data/generate-month 3\n");
        $this->stdout("  ./yii absence-data/generate-period 2024-06-01 2024-08-31\n");
        $this->stdout("  ./yii absence-data/clear-all\n");
        $this->stdout("  ./yii absence-data/stats\n\n");
        
        $this->stdout("ATTENZIONE: ", Console::FG_RED);
        $this->stdout("I comandi di generazione creano dati di esempio.\n");
        $this->stdout("Utilizzare solo in ambienti di sviluppo/test.\n\n");
        
        return ExitCode::OK;
    }

    /**
     * Genera assenze per tutto l'anno corrente
     */
    public function actionGenerateYear()
    {
        $year = date('Y');
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";

        $this->stdout("🚀 Generazione assenze per l'anno {$year}...\n\n", Console::BOLD);
        
        return $this->generateAbsencesForPeriod($startDate, $endDate);
    }

    /**
     * Genera assenze per un mese specifico
     */
    public function actionGenerateMonth($month = null)
    {
        if ($month === null) {
            $month = $this->prompt('Inserisci il mese (1-12):');
        }

        $month = (int)$month;
        if ($month < 1 || $month > 12) {
            $this->stderr("❌ Mese non valido. Inserire un valore tra 1 e 12.\n");
            return ExitCode::DATAERR;
        }

        $year = date('Y');
        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $this->stdout("🚀 Generazione assenze per {$monthName} {$year}...\n\n", Console::BOLD);
        
        return $this->generateAbsencesForPeriod($startDate, $endDate);
    }

    /**
     * Genera assenze per un periodo specifico
     */
    public function actionGeneratePeriod($startDate = null, $endDate = null)
    {
        if ($startDate === null) {
            $startDate = $this->prompt('Data inizio (YYYY-MM-DD):');
        }
        if ($endDate === null) {
            $endDate = $this->prompt('Data fine (YYYY-MM-DD):');
        }

        // Validazione date
        if (!$this->validateDate($startDate) || !$this->validateDate($endDate)) {
            $this->stderr("❌ Formato date non valido. Utilizzare YYYY-MM-DD.\n");
            return ExitCode::DATAERR;
        }

        if ($startDate > $endDate) {
            $this->stderr("❌ La data di inizio deve essere precedente alla data di fine.\n");
            return ExitCode::DATAERR;
        }

        $this->stdout("🚀 Generazione assenze dal {$startDate} al {$endDate}...\n\n", Console::BOLD);
        
        return $this->generateAbsencesForPeriod($startDate, $endDate);
    }

    /**
     * Mostra anteprima di cosa verrà generato
     */
    public function actionPreview()
    {
        $this->stdout("📋 ANTEPRIMA GENERAZIONE ASSENZE\n", Console::BOLD);
        $this->stdout("================================\n\n");

        // Conta terapisti attivi
        $therapistCount = Therapist::find()->where(['is_active' => 1])->count();
        
        // Conta pazienti con appuntamenti
        $patientCount = Patient::find()
            ->joinWith('appointments')
            ->where(['!=', 'appointments.status', Appointment::STATUS_CANCELLED])
            ->distinct()
            ->count();

        // Conta appuntamenti totali anno corrente
        $year = date('Y');
        $appointmentCount = Appointment::find()
            ->where(['between', 'DATE(appointment_datetime)', "{$year}-01-01", "{$year}-12-31"])
            ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
            ->count();

        $this->stdout("👥 Terapisti attivi: {$therapistCount}\n");
        $this->stdout("🧑‍⚕️ Pazienti con appuntamenti: {$patientCount}\n");
        $this->stdout("📅 Appuntamenti anno {$year}: {$appointmentCount}\n\n");

        // Stima assenze terapisti
        $estimatedTherapistAbsences = $therapistCount * 15; // Media 15 assenze per terapista all'anno
        $this->stdout("📊 Assenze terapisti stimate: ~{$estimatedTherapistAbsences}\n");

        // Stima assenze pazienti (5% degli appuntamenti)
        $estimatedPatientAbsences = round($appointmentCount * 0.05);
        $this->stdout("🏥 Assenze pazienti stimate: ~{$estimatedPatientAbsences} (5% degli appuntamenti)\n\n");

        $this->stdout("Tipi di assenze terapisti:\n", Console::FG_CYAN);
        foreach ($this->therapistAbsenceTypes as $type => $config) {
            $percentage = $config['weight'];
            $this->stdout("  • {$type}: {$percentage}% (media {$config['avg_days']} giorni)\n");
        }

        $this->stdout("\n");
        return ExitCode::OK;
    }

    /**
     * Logica principale per generare assenze in un periodo
     */
    protected function generateAbsencesForPeriod($startDate, $endDate)
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // Controlla se esistono già dati nel periodo
            $existingData = $this->checkExistingData($startDate, $endDate);
            if ($existingData['total'] > 0) {
                $this->stdout("⚠️  Trovate {$existingData['total']} assenze esistenti nel periodo.\n", Console::FG_YELLOW);
                $this->stdout("   - Assenze terapisti: {$existingData['therapist_absences']}\n");
                $this->stdout("   - Assenze pazienti: {$existingData['patient_absences']}\n");
                
                $continue = $this->confirm('Continuare comunque?', false);
                if (!$continue) {
                    $transaction->rollBack();
                    return ExitCode::OK;
                }
            }

            // Genera assenze terapisti
            $this->stdout("1️⃣ Generazione assenze terapisti...\n");
            $therapistResults = $this->generateTherapistAbsences($startDate, $endDate);
            $this->stdout("   ✅ Generate {$therapistResults['count']} assenze per {$therapistResults['therapists']} terapisti\n\n");

            // Genera assenze pazienti
            $this->stdout("2️⃣ Generazione assenze pazienti...\n");
            $patientResults = $this->generatePatientAbsences($startDate, $endDate);
            $this->stdout("   ✅ Modificati {$patientResults['count']} appuntamenti per {$patientResults['patients']} pazienti\n\n");

            $transaction->commit();

            // Riepilogo finale
            $this->stdout("🎉 GENERAZIONE COMPLETATA\n", Console::BOLD | Console::FG_GREEN);
            $this->stdout("========================\n");
            $this->stdout("📊 Periodo: {$startDate} - {$endDate}\n");
            $this->stdout("👨‍⚕️ Assenze terapisti: {$therapistResults['count']}\n");
            $this->stdout("🧑‍⚕️ Assenze pazienti: {$patientResults['count']}\n");
            $this->stdout("📈 Totale: " . ($therapistResults['count'] + $patientResults['count']) . "\n\n");

            return ExitCode::OK;

        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stderr("❌ Errore durante la generazione: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Genera assenze realistiche per i terapisti
     */
    protected function generateTherapistAbsences($startDate, $endDate)
    {
        $therapists = Therapist::find()
            ->where(['is_active' => 1])
            ->with('user.profile')
            ->all();

        $generatedCount = 0;
        $adminUserId = $this->getAdminUserId();

        foreach ($therapists as $therapist) {
            // Numero casuale di assenze per terapista (8-25 all'anno, proporzionale al periodo)
            $periodDays = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
            $yearFraction = $periodDays / 365;
            $baseAbsences = rand(8, 25);
            $targetAbsences = max(1, round($baseAbsences * $yearFraction));

            for ($i = 0; $i < $targetAbsences; $i++) {
                $absence = new Absence();
                
                // Seleziona tipo di assenza basato sui pesi
                $type = $this->selectRandomAbsenceType();
                $config = $this->therapistAbsenceTypes[$type];
                
                // Genera durata realistica
                $duration = $this->generateAbsenceDuration($config['avg_days']);
                
                // Genera date casuali nel periodo
                $absenceStart = $this->generateRandomDate($startDate, $endDate, $duration);
                $absenceEnd = date('Y-m-d', strtotime($absenceStart . " +{$duration} days"));
                
                // Assicurati che la fine non ecceda il periodo
                if ($absenceEnd > $endDate) {
                    $absenceEnd = $endDate;
                }

                $absence->therapist_id = $therapist->id;
                $absence->start_date = $absenceStart;
                $absence->end_date = $absenceEnd;
                $absence->type = $type;
                $absence->reason = $config['reasons'][array_rand($config['reasons'])];
                $absence->status = Absence::STATUS_APPROVED; // Auto-approva per dati di esempio
                $absence->approved_by = $adminUserId;
                $absence->approved_at = date('Y-m-d H:i:s');
                $absence->created_by = $adminUserId;

                if ($absence->save()) {
                    $generatedCount++;
                } else {
                    $this->stderr("⚠️  Errore salvando assenza per terapista {$therapist->id}: " . implode(', ', $absence->getFirstErrors()) . "\n");
                }
            }
        }

        return [
            'count' => $generatedCount,
            'therapists' => count($therapists)
        ];
    }

    /**
     * Genera assenze realistiche per i pazienti modificando gli appuntamenti
     */
    protected function generatePatientAbsences($startDate, $endDate)
    {
        // Trova tutti gli appuntamenti nel periodo che non sono già assenti o cancellati
        $appointments = Appointment::find()
            ->where(['between', 'DATE(appointment_datetime)', $startDate, $endDate])
            ->andWhere(['status' => [Appointment::STATUS_SCHEDULED, Appointment::STATUS_COMPLETED]])
            ->with(['planTherapy.therapeuticPlan.patient', 'patient'])
            ->all();

        $modifiedCount = 0;
        $affectedPatients = [];

        // Modifica circa il 5% degli appuntamenti in assenze
        $targetAbsenceRate = 0.05;
        $targetAbsences = round(count($appointments) * $targetAbsenceRate);

        // Mescola gli appuntamenti per selezione casuale
        shuffle($appointments);

        for ($i = 0; $i < min($targetAbsences, count($appointments)); $i++) {
            $appointment = $appointments[$i];
            
            // 70% giustificate, 30% non giustificate
            $isJustified = rand(1, 100) <= 70;
            $newStatus = $isJustified ? Appointment::STATUS_ABSENT_JUSTIFIED : Appointment::STATUS_ABSENT_NOT_JUSTIFIED;
            
            // Seleziona motivo appropriato
            $reasonType = $isJustified ? 'justified' : 'not_justified';
            $reasons = $this->patientAbsenceReasons[$reasonType];
            $reason = $reasons[array_rand($reasons)];
            
            // Aggiorna l'appuntamento
            $appointment->status = $newStatus;
            if (!empty($reason)) {
                $appointment->notes = 'Assenza: ' . $reason;
            }

            if ($appointment->save()) {
                $modifiedCount++;
                
                // Traccia pazienti coinvolti
                $patientId = $appointment->planTherapy 
                    ? $appointment->planTherapy->therapeuticPlan->patient_id 
                    : $appointment->patient_id;
                    
                if ($patientId && !in_array($patientId, $affectedPatients)) {
                    $affectedPatients[] = $patientId;
                }
            }
        }

        return [
            'count' => $modifiedCount,
            'patients' => count($affectedPatients)
        ];
    }

    /**
     * Seleziona un tipo di assenza casuale basato sui pesi
     */
    protected function selectRandomAbsenceType()
    {
        $rand = rand(1, 100);
        $cumulative = 0;
        
        foreach ($this->therapistAbsenceTypes as $type => $config) {
            $cumulative += $config['weight'];
            if ($rand <= $cumulative) {
                return $type;
            }
        }
        
        // Fallback
        return array_keys($this->therapistAbsenceTypes)[0];
    }

    /**
     * Genera durata realistica per un'assenza
     */
    protected function generateAbsenceDuration($avgDays)
    {
        // Variazione casuale intorno alla media
        $variation = rand(-2, 3);
        $duration = max(1, $avgDays + $variation);
        
        // Limita durate eccessive
        return min($duration, 21); // Max 3 settimane
    }

    /**
     * Genera una data casuale nel periodo, considerando la durata
     */
    protected function generateRandomDate($startDate, $endDate, $duration)
    {
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate . " -{$duration} days");
        
        if ($endTimestamp <= $startTimestamp) {
            return $startDate;
        }
        
        $randomTimestamp = rand($startTimestamp, $endTimestamp);
        return date('Y-m-d', $randomTimestamp);
    }

    /**
     * Ottiene l'ID di un utente admin per le operazioni automatiche
     */
    protected function getAdminUserId()
    {
        $admin = User::find()
            ->where(['status' => User::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_ASC])
            ->one();
            
        return $admin ? $admin->id : 1; // Fallback a ID 1
    }

    /**
     * Controlla se esistono già dati nel periodo
     */
    protected function checkExistingData($startDate, $endDate)
    {
        // Conta assenze terapisti
        $therapistAbsences = Absence::find()
            ->where(['between', 'start_date', $startDate, $endDate])
            ->orWhere(['between', 'end_date', $startDate, $endDate])
            ->count();

        // Conta assenze pazienti (appuntamenti con status assente)
        $patientAbsences = Appointment::find()
            ->where(['between', 'DATE(appointment_datetime)', $startDate, $endDate])
            ->andWhere(['status' => [Appointment::STATUS_ABSENT_JUSTIFIED, Appointment::STATUS_ABSENT_NOT_JUSTIFIED]])
            ->count();

        return [
            'therapist_absences' => $therapistAbsences,
            'patient_absences' => $patientAbsences,
            'total' => $therapistAbsences + $patientAbsences
        ];
    }

    /**
     * Mostra statistiche assenze correnti
     */
    public function actionStats()
    {
        $this->stdout("📊 STATISTICHE ASSENZE CORRENTI\n", Console::BOLD);
        $this->stdout("===============================\n\n");

        $year = date('Y');
        
        // Statistiche terapisti
        $this->stdout("👨‍⚕️ ASSENZE TERAPISTI ({$year})\n", Console::FG_CYAN);
        $therapistStats = $this->getTherapistAbsenceStats($year);
        foreach ($therapistStats as $stat) {
            $this->stdout("   {$stat['type']}: {$stat['count']} ({$stat['days']} giorni totali)\n");
        }
        
        $totalTherapistAbsences = array_sum(array_column($therapistStats, 'count'));
        $totalTherapistDays = array_sum(array_column($therapistStats, 'days'));
        $this->stdout("   TOTALE: {$totalTherapistAbsences} assenze, {$totalTherapistDays} giorni\n\n");

        // Statistiche pazienti
        $this->stdout("🧑‍⚕️ ASSENZE PAZIENTI ({$year})\n", Console::FG_CYAN);
        $patientStats = $this->getPatientAbsenceStats($year);
        $this->stdout("   Giustificate: {$patientStats['justified']}\n");
        $this->stdout("   Non giustificate: {$patientStats['not_justified']}\n");
        $this->stdout("   TOTALE: {$patientStats['total']}\n\n");

        // Percentuale assenze
        $totalAppointments = Appointment::find()
            ->where(['between', 'DATE(appointment_datetime)', "{$year}-01-01", "{$year}-12-31"])
            ->count();
            
        if ($totalAppointments > 0) {
            $absenceRate = round(($patientStats['total'] / $totalAppointments) * 100, 2);
            $this->stdout("📈 Tasso assenze pazienti: {$absenceRate}%\n");
        }

        return ExitCode::OK;
    }

    /**
     * Ottiene statistiche assenze terapisti per anno
     */
    protected function getTherapistAbsenceStats($year)
    {
        $stats = [];
        $types = array_keys($this->therapistAbsenceTypes);
        
        foreach ($types as $type) {
            $query = Absence::find()
                ->where(['type' => $type])
                ->andWhere(['between', 'start_date', "{$year}-01-01", "{$year}-12-31"]);
                
            $count = $query->count();
            $absences = $query->all();
            
            $totalDays = 0;
            foreach ($absences as $absence) {
                $totalDays += $absence->getDurationDays();
            }
            
            $stats[] = [
                'type' => ucfirst(str_replace('_', ' ', $type)),
                'count' => $count,
                'days' => $totalDays
            ];
        }
        
        return $stats;
    }

    /**
     * Ottiene statistiche assenze pazienti per anno
     */
    protected function getPatientAbsenceStats($year)
    {
        $justified = Appointment::find()
            ->where(['status' => Appointment::STATUS_ABSENT_JUSTIFIED])
            ->andWhere(['between', 'DATE(appointment_datetime)', "{$year}-01-01", "{$year}-12-31"])
            ->count();
            
        $notJustified = Appointment::find()
            ->where(['status' => Appointment::STATUS_ABSENT_NOT_JUSTIFIED])
            ->andWhere(['between', 'DATE(appointment_datetime)', "{$year}-01-01", "{$year}-12-31"])
            ->count();

        return [
            'justified' => $justified,
            'not_justified' => $notJustified,
            'total' => $justified + $notJustified
        ];
    }

    /**
     * Rimuove tutte le assenze generate
     */
    public function actionClearAll()
    {
        $this->stdout("🗑️  RIMOZIONE TUTTE LE ASSENZE\n", Console::BOLD | Console::FG_RED);
        $this->stdout("============================\n\n");
        
        $this->stdout("⚠️  ATTENZIONE: Questa operazione rimuoverà TUTTE le assenze dal sistema!\n", Console::FG_RED);
        $confirm = $this->confirm('Sei sicuro di voler continuare?', false);
        
        if (!$confirm) {
            $this->stdout("Operazione annullata.\n");
            return ExitCode::OK;
        }

        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // Rimuovi assenze terapisti
            $therapistAbsences = Absence::deleteAll();
            
            // Ripristina status appuntamenti pazienti
            $patientAbsences = Appointment::updateAll(
                ['status' => Appointment::STATUS_SCHEDULED],
                ['status' => [Appointment::STATUS_ABSENT_JUSTIFIED, Appointment::STATUS_ABSENT_NOT_JUSTIFIED]]
            );
            
            $transaction->commit();
            
            $this->stdout("✅ Rimozione completata:\n", Console::FG_GREEN);
            $this->stdout("   - Assenze terapisti rimosse: {$therapistAbsences}\n");
            $this->stdout("   - Appuntamenti ripristinati: {$patientAbsences}\n");
            
            return ExitCode::OK;
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stderr("❌ Errore durante la rimozione: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Rimuove assenze in un periodo specifico
     */
    public function actionClearPeriod($startDate = null, $endDate = null)
    {
        if ($startDate === null) {
            $startDate = $this->prompt('Data inizio (YYYY-MM-DD):');
        }
        if ($endDate === null) {
            $endDate = $this->prompt('Data fine (YYYY-MM-DD):');
        }

        if (!$this->validateDate($startDate) || !$this->validateDate($endDate)) {
            $this->stderr("❌ Formato date non valido. Utilizzare YYYY-MM-DD.\n");
            return ExitCode::DATAERR;
        }

        $this->stdout("🗑️  Rimozione assenze dal {$startDate} al {$endDate}...\n", Console::FG_YELLOW);
        
        $existing = $this->checkExistingData($startDate, $endDate);
        if ($existing['total'] == 0) {
            $this->stdout("ℹ️  Nessuna assenza trovata nel periodo specificato.\n");
            return ExitCode::OK;
        }

        $confirm = $this->confirm("Rimuovere {$existing['total']} assenze?", false);
        if (!$confirm) {
            return ExitCode::OK;
        }

        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // Rimuovi assenze terapisti nel periodo
            $therapistDeleted = Absence::deleteAll([
                'and',
                ['<=', 'start_date', $endDate],
                ['>=', 'end_date', $startDate]
            ]);
            
            // Ripristina appuntamenti pazienti nel periodo
            $patientRestored = Appointment::updateAll(
                ['status' => Appointment::STATUS_SCHEDULED],
                [
                    'and',
                    ['between', 'DATE(appointment_datetime)', $startDate, $endDate],
                    ['status' => [Appointment::STATUS_ABSENT_JUSTIFIED, Appointment::STATUS_ABSENT_NOT_JUSTIFIED]]
                ]
            );
            
            $transaction->commit();
            
            $this->stdout("✅ Rimozione completata:\n", Console::FG_GREEN);
            $this->stdout("   - Assenze terapisti: {$therapistDeleted}\n");
            $this->stdout("   - Appuntamenti ripristinati: {$patientRestored}\n");
            
            return ExitCode::OK;
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stderr("❌ Errore: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Valida formato data
     */
    protected function validateDate($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
} 
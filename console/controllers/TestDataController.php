<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use common\models\User;
use common\models\UserProfile;
use common\models\Therapist;
use common\models\Patient;
use common\models\TherapeuticPlan;
use common\models\PlanTherapy;
use common\models\Appointment;
use common\models\AppointmentPattern;
use common\models\AbsenceCounter;
use common\models\Absence;
use common\models\District;
use common\models\Specialization;
use common\models\TreatmentType;
use common\models\SpecializationTreatment;
use yii\db\Query;

/**
 * Genera dati di test per il sistema calendario
 * 
 * Usage:
 * yii test-data/generate-all   # Genera tutti i dati
 * yii test-data/clear-all      # Pulisce tutti i dati
 */
class TestDataController extends Controller
{
    /**
     * Genera tutti i dati di test
     */
    public function actionGenerateAll()
    {
        $this->stdout("🚀 Generazione dati di test iniziata...\n\n");
        
        // Controlla se i dati base esistono già
        if (District::find()->count() == 0) {
            $this->stdout("📍 Generazione dati base (distretti, specializzazioni, trattamenti)...\n");
            $this->generateBaseData();
        }

        $this->stdout("👥 Generazione utenti e terapisti...\n");
        $this->generateUsersAndTherapists();

        $this->stdout("🏥 Generazione pazienti...\n");
        $this->generatePatients();

        $this->stdout("📋 Generazione piani terapeutici...\n");
        $this->generateTherapeuticPlans();

        $this->stdout("📅 Generazione appuntamenti...\n");
        $appointmentsCount = $this->generateAppointments();

        $this->stdout("📊 Aggiornamento contatori assenze...\n");
        $this->updateAbsenceCounters();

        $this->stdout("\n✅ Generazione completata!\n");
        $this->stdout("📈 Statistiche finali:\n");
        $this->showStats();

        return ExitCode::OK;
    }

    /**
     * Pulisce tutti i dati di test
     */ 
    public function actionClearAll()
    {
        $this->stdout("🗑️  Pulizia dati di test...\n");
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Disabilita controlli foreign key
            Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
            
            // Pulisce in ordine inverso delle dipendenze
            Absence::deleteAll();
            AbsenceCounter::deleteAll();
            Appointment::deleteAll();
            AppointmentPattern::deleteAll();
            PlanTherapy::deleteAll();
            TherapeuticPlan::deleteAll();
            Therapist::deleteAll();
            Patient::deleteAll();
            UserProfile::deleteAll();
            User::deleteAll();
            
            // Riabilita controlli foreign key
            Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
            
            $transaction->commit();
            $this->stdout("✅ Pulizia completata!\n");
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stdout("❌ Errore durante la pulizia: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Genera solo appuntamenti per oggi e prossimi giorni (per test)
     */
    public function actionGenerateUpcomingAppointments()
    {
        $this->stdout("📅 Generazione appuntamenti prossimi giorni...\n");
        
        $count = 0;
        $therapists = Therapist::find()->where(['is_active' => true])->all();
        $patients = Patient::find()->limit(10)->all();
        
        // Genera appuntamenti per i prossimi 7 giorni
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            
            foreach ($therapists as $therapist) {
                // 30% probabilità di avere appuntamento
                if (rand(1, 100) <= 30) {
                    $patient = $patients[array_rand($patients)];
                    $hour = rand(8, 18);
                    $minute = rand(0, 1) * 30; // 0 o 30 minuti
                    
                    $appointment = new Appointment();
                    $appointment->therapist_id = $therapist->id;
                    $appointment->patient_id = $patient->id;
                    $appointment->appointment_datetime = "$date $hour:$minute:00";
                    $appointment->duration_minutes = [45, 60][rand(0, 1)];
                    $appointment->location_type = 'office';
                    $appointment->status = 'scheduled';
                    $appointment->created_by = 1; // Admin
                    
                    if ($appointment->save()) {
                        $count++;
                    }
                }
            }
        }
        
        $this->stdout("✅ Generati $count appuntamenti per i prossimi giorni\n");
        return ExitCode::OK;
    }

    /**
     * Genera dati di base (distretti, specializzazioni, trattamenti)
     */
    private function generateBaseData()
    {
        // Distretti
        $districts = [
            ['DST01', 'Distretto Centro', 'ASL Napoli 1'],
            ['DST02', 'Distretto Nord', 'ASL Napoli 2'],
            ['DST03', 'Distretto Sud', 'ASL Napoli 3'],
        ];

        foreach ($districts as $d) {
            if (!District::find()->where(['code' => $d[0]])->exists()) {
                $district = new District();
                $district->code = $d[0];
                $district->name = $d[1];
                $district->asl_reference = $d[2];
                $district->save();
            }
        }

        // Specializzazioni
        $specializations = [
            ['LOG', 'Logopedia', 'Terapia del linguaggio'],
            ['PSI', 'Psicomotricità', 'Terapia psicomotoria'],
            ['FIS', 'Fisioterapia', 'Terapia fisica e riabilitazione'],
            ['PSY', 'Psicologia', 'Supporto psicologico'],
            ['NPI', 'Neuropsichiatria', 'Valutazione neuropsichiatrica'],
        ];

        foreach ($specializations as $s) {
            if (!Specialization::find()->where(['code' => $s[0]])->exists()) {
                $spec = new Specialization();
                $spec->code = $s[0];
                $spec->name = $s[1];
                $spec->description = $s[2];
                $spec->save();
            }
        }

        // Tipi di trattamento
        $treatments = [
            ['LOG_IND', 'Logopedia Individuale', 'Seduta logopedica individuale'],
            ['LOG_GRP', 'Logopedia Gruppo', 'Seduta logopedica di gruppo'],
            ['PSM_IND', 'Psicomotricità Individuale', 'Seduta psicomotoria individuale'],
            ['PSM_GRP', 'Psicomotricità Gruppo', 'Seduta psicomotoria di gruppo'],
            ['FIS_IND', 'Fisioterapia Individuale', 'Seduta fisioterapica individuale'],
            ['PSY_IND', 'Supporto Psicologico', 'Seduta psicologica individuale'],
            ['NPI_VAL', 'Valutazione NPI', 'Valutazione neuropsichiatrica'],
        ];

        foreach ($treatments as $t) {
            if (!TreatmentType::find()->where(['code' => $t[0]])->exists()) {
                $treatment = new TreatmentType();
                $treatment->code = $t[0];
                $treatment->name = $t[1];
                $treatment->description = $t[2];
                $treatment->save();
            }
        }

        // Relazioni specializzazione-trattamenti
        $relations = [
            ['LOG', ['LOG_IND', 'LOG_GRP']],
            ['PSI', ['PSM_IND', 'PSM_GRP']],
            ['FIS', ['FIS_IND']],
            ['PSY', ['PSY_IND']],
            ['NPI', ['NPI_VAL']],
        ];

        foreach ($relations as $rel) {
            $spec = Specialization::find()->where(['code' => $rel[0]])->one();
            foreach ($rel[1] as $treatmentCode) {
                $treatment = TreatmentType::find()->where(['code' => $treatmentCode])->one();
                if ($spec && $treatment) {
                    if (!SpecializationTreatment::find()->where([
                        'specialization_id' => $spec->id,
                        'treatment_type_id' => $treatment->id
                    ])->exists()) {
                        $st = new SpecializationTreatment();
                        $st->specialization_id = $spec->id;
                        $st->treatment_type_id = $treatment->id;
                        $st->save();
                    }
                }
            }
        }
    }

    /**
     * Genera utenti e terapisti
     */
    private function generateUsersAndTherapists()
    {
        $colors = [
            '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
            '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'
        ];

        // Admin
        if (!User::find()->where(['email' => 'admin@therapy.com'])->exists()) {
            $this->createUser('admin@therapy.com', 'Admin', 'Sistema');
        }

        // Manager Sharon
        if (!User::find()->where(['email' => 'sharon@therapy.com'])->exists()) {
            $this->createUser('sharon@therapy.com', 'Sharon', 'Manager');
        }

        // Terapisti
        $specializations = Specialization::find()->all();
        for ($i = 1; $i <= 20; $i++) {
            $email = "terapista$i@therapy.com";
            
            if (!User::find()->where(['email' => $email])->exists()) {
                $user = $this->createUser($email, "Nome$i", "Terapista$i");
                
                // Crea profilo terapista
                $therapist = new Therapist();
                $therapist->user_id = $user->id;
                $therapist->specialization_id = $specializations[($i - 1) % count($specializations)]->id;
                $therapist->weekly_hours_contract = [20, 30, 38][($i - 1) % 3];
                $therapist->calendar_color = $colors[($i - 1) % count($colors)];
                $therapist->is_active = true;
                $therapist->save();
            }
        }
    }

    /**
     * Genera pazienti
     */
    private function generatePatients()
    {
        $districts = District::find()->all();
        
        for ($i = 1; $i <= 50; $i++) {
            if (!Patient::find()->where(['fiscal_code' => "PZNT" . sprintf('%02d', $i) . "C00C000C"])->exists()) {
                $patient = new Patient();
                $patient->first_name = "Bambino$i";
                $patient->last_name = "Paziente$i";
                $patient->birth_date = date('Y-m-d', strtotime("-" . rand(3, 15) . " years"));
                $patient->fiscal_code = "PZNT" . sprintf('%02d', $i) . "C00C000C";
                $patient->district_id = $districts[array_rand($districts)]->id;
                $patient->notes = rand(1, 100) <= 30 ? 'Necessita supporto costante' : null;
                $patient->save();
            }
        }
    }

    /**
     * Genera piani terapeutici
     */
    private function generateTherapeuticPlans()
    {
        $patients = Patient::find()->limit(30)->all(); // 30 piani attivi
        $treatments = TreatmentType::find()->all();
        $regimes = ['L11', 'L11DOM', 'ABA', 'Private'];

        foreach ($patients as $patient) {
            if (!TherapeuticPlan::find()->where(['patient_id' => $patient->id])->exists()) {
                $plan = new TherapeuticPlan();
                $plan->patient_id = $patient->id;
                $plan->start_date = date('Y-m-d', strtotime('-' . rand(0, 90) . ' days'));
                $plan->duration_days = 180;
                $plan->health_regime = $regimes[array_rand($regimes)];
                $plan->status = 'active';
                $plan->created_by = 1; // Admin
                $plan->save();

                // Aggiungi 2-3 terapie per piano
                $therapyCount = rand(2, 3);
                for ($j = 0; $j < $therapyCount; $j++) {
                    $planTherapy = new PlanTherapy();
                    $planTherapy->therapeutic_plan_id = $plan->id;
                    $planTherapy->treatment_type_id = $treatments[array_rand($treatments)]->id;
                    $planTherapy->weekly_hours = rand(1, 3);
                    $planTherapy->is_group = rand(1, 100) <= 30; // 30% gruppi
                    $planTherapy->save();
                }

                // Inizializza contatore assenze
                $counter = new AbsenceCounter();
                $counter->patient_id = $patient->id;
                $counter->therapeutic_plan_id = $plan->id;
                $counter->save();
            }
        }
    }

    /**
     * Genera appuntamenti
     */
    private function generateAppointments()
    {
        $count = 0;
        $planTherapies = PlanTherapy::find()->with(['therapeuticPlan', 'treatmentType'])->all();
        
        foreach ($planTherapies as $planTherapy) {
            // Trova terapista compatibile
            $therapist = $this->findCompatibleTherapist($planTherapy->treatment_type_id);
            if (!$therapist) continue;

            // Crea pattern per questa terapia (1-2 sessioni a settimana)
            $sessionsPerWeek = rand(1, 2);
            for ($s = 0; $s < $sessionsPerWeek; $s++) {
                $pattern = new AppointmentPattern();
                $pattern->plan_therapy_id = $planTherapy->id;
                $pattern->therapist_id = $therapist->id;
                $pattern->day_of_week = rand(1, 5); // Lun-Ven
                $pattern->start_time = sprintf('%02d:%02d:00', rand(8, 17), [0, 30][rand(0, 1)]);
                $pattern->duration_minutes = [45, 60][rand(0, 1)];
                $pattern->location_type = rand(1, 100) <= 90 ? 'office' : 'home';
                $pattern->valid_from = $planTherapy->therapeuticPlan->start_date;
                $pattern->valid_to = date('Y-m-d', strtotime($planTherapy->therapeuticPlan->start_date . ' +180 days'));
                $pattern->created_by = 2; // Manager
                $pattern->save();

                // Genera appuntamenti basati sul pattern
                $count += $this->generateAppointmentsFromPattern($pattern);
            }
        }

        return $count;
    }

    /**
     * Genera appuntamenti da un pattern
     */
    private function generateAppointmentsFromPattern($pattern)
    {
        $count = 0;
        $currentDate = new \DateTime($pattern->valid_from);
        $endDate = new \DateTime($pattern->valid_to);
        $today = new \DateTime();
        $maxDate = clone $today;
        $maxDate->add(new \DateInterval('P30D')); // Solo prossimi 30 giorni

        if ($endDate > $maxDate) {
            $endDate = $maxDate;
        }

        while ($currentDate <= $endDate) {
            // Controlla se è il giorno giusto della settimana
            if ($currentDate->format('N') == $pattern->day_of_week) {
                $appointmentDateTime = $currentDate->format('Y-m-d') . ' ' . $pattern->start_time;
                
                // Determina status
                $appointmentTime = new \DateTime($appointmentDateTime);
                if ($appointmentTime < $today->sub(new \DateInterval('PT2H'))) {
                    // Appuntamento passato
                    $rand = rand(1, 100);
                    if ($rand <= 85) {
                        $status = 'completed';
                    } elseif ($rand <= 92) {
                        $status = 'absent_justified';
                    } else {
                        $status = 'absent_not_justified';
                    }
                } else {
                    $status = 'scheduled';
                }

                // Verifica che non ci siano conflitti
                if (!$this->hasConflict($pattern->therapist_id, $appointmentDateTime, $pattern->duration_minutes)) {
                    $appointment = new Appointment();
                    $appointment->pattern_id = $pattern->id;
                    $appointment->plan_therapy_id = $pattern->plan_therapy_id;
                    $appointment->therapist_id = $pattern->therapist_id;
                    $appointment->patient_id = $pattern->planTherapy->therapeuticPlan->patient_id;
                    $appointment->appointment_datetime = $appointmentDateTime;
                    $appointment->duration_minutes = $pattern->duration_minutes;
                    $appointment->location_type = $pattern->location_type;
                    $appointment->status = $status;
                    $appointment->created_by = 2; // Manager
                    
                    if ($appointment->save()) {
                        $count++;
                        
                        // Se assente, crea record assenza
                        if (in_array($status, ['absent_justified', 'absent_not_justified'])) {
                            $absence = new Absence();
                            $absence->appointment_id = $appointment->id;
                            $absence->patient_id = $appointment->patient_id;
                            $absence->absence_date = $appointmentDateTime;
                            $absence->reason = ['health', 'family', 'other'][rand(0, 2)];
                            $absence->is_justified = ($status === 'absent_justified');
                            $absence->is_communicated = true;
                            $absence->communicated_by = 2;
                            $absence->communicated_at = $appointmentDateTime;
                            $absence->save();
                        }
                    }
                }
            }
            
            $currentDate->add(new \DateInterval('P1D'));
        }

        return $count;
    }

    /**
     * Trova terapista compatibile per un tipo di trattamento
     */
    private function findCompatibleTherapist($treatmentTypeId)
    {
        $therapist = Therapist::find()
            ->joinWith(['specialization.specializationTreatments'])
            ->where(['specialization_treatments.treatment_type_id' => $treatmentTypeId])
            ->andWhere(['therapists.is_active' => true])
            ->orderBy('RAND()')
            ->one();

        return $therapist;
    }

    /**
     * Controlla conflitti di orario
     */
    private function hasConflict($therapistId, $datetime, $duration)
    {
        $endTime = date('Y-m-d H:i:s', strtotime($datetime . ' +' . $duration . ' minutes'));
        
        return Appointment::find()
            ->where(['therapist_id' => $therapistId])
            ->andWhere(['status' => ['scheduled', 'completed']])
            ->andWhere([
                'or',
                ['between', 'appointment_datetime', $datetime, $endTime],
                ['between', 'DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)', $datetime, $endTime]
            ])
            ->exists();
    }

    /**
     * Aggiorna contatori assenze
     */
    private function updateAbsenceCounters()
    {
        $sql = "
            UPDATE absence_counters ac
            SET 
                total_appointments = (
                    SELECT COUNT(*) 
                    FROM appointments a
                    JOIN plan_therapies pt ON a.plan_therapy_id = pt.id
                    WHERE pt.therapeutic_plan_id = ac.therapeutic_plan_id
                    AND a.patient_id = ac.patient_id
                ),
                total_absences = (
                    SELECT COUNT(*) 
                    FROM appointments a
                    JOIN plan_therapies pt ON a.plan_therapy_id = pt.id
                    WHERE pt.therapeutic_plan_id = ac.therapeutic_plan_id
                    AND a.patient_id = ac.patient_id
                    AND a.status IN ('absent_justified', 'absent_not_justified')
                ),
                justified_absences = (
                    SELECT COUNT(*) 
                    FROM appointments a
                    JOIN plan_therapies pt ON a.plan_therapy_id = pt.id
                    WHERE pt.therapeutic_plan_id = ac.therapeutic_plan_id
                    AND a.patient_id = ac.patient_id
                    AND a.status = 'absent_justified'
                ),
                unjustified_absences = (
                    SELECT COUNT(*) 
                    FROM appointments a
                    JOIN plan_therapies pt ON a.plan_therapy_id = pt.id
                    WHERE pt.therapeutic_plan_id = ac.therapeutic_plan_id
                    AND a.patient_id = ac.patient_id
                    AND a.status = 'absent_not_justified'
                )
        ";
        
        Yii::$app->db->createCommand($sql)->execute();
    }

    /**
     * Crea un utente con profilo
     */
    private function createUser($email, $firstName, $lastName)
    {
        $user = new User();
        $user->email = $email;
        $user->setPassword('password123');
        $user->generateAuthKey();
        $user->status = User::STATUS_ACTIVE;
        $user->save();

        $profile = new UserProfile();
        $profile->user_id = $user->id;
        $profile->first_name = $firstName;
        $profile->last_name = $lastName;
        $profile->fiscal_code = strtoupper(substr($firstName, 0, 3) . substr($lastName, 0, 3)) . '00A00A000A';
        $profile->phone = '333' . sprintf('%07d', rand(1000000, 9999999));
        $profile->save();

        return $user;
    }

    /**
     * Mostra statistiche finali
     */
    private function showStats()
    {
        $stats = [
            'Utenti' => User::find()->count(),
            'Terapisti' => Therapist::find()->count(),
            'Pazienti' => Patient::find()->count(),
            'Piani terapeutici' => TherapeuticPlan::find()->count(),
            'Appuntamenti totali' => Appointment::find()->count(),
        ];

        foreach ($stats as $label => $count) {
            $this->stdout("   $label: $count\n");
        }

        // Statistiche appuntamenti per status
        $this->stdout("\n📊 Appuntamenti per status:\n");
        $statusStats = (new Query())
            ->select(['status', 'COUNT(*) as count'])
            ->from('appointments')
            ->groupBy('status')
            ->all();

        foreach ($statusStats as $stat) {
            $this->stdout("   {$stat['status']}: {$stat['count']}\n");
        }
    }
} 
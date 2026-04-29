<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use common\models\User;
use common\models\UserProfile;
use common\models\Therapist;
use common\models\Patient;
use common\models\AccountPatient;
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
use common\models\CoordinatorGroup;
use common\models\GroupTherapist;
use common\models\Regime;
use common\models\RegimeSetting;
use common\models\Setting;
use yii\db\Query;

/**
 * Genera dati di test per il sistema calendario
 *
 * Usage:
 * yii test-data/generate-all      # Genera tutti i dati (legacy)
 * yii test-data/clear-all         # Pulisce tutti i dati
 * yii test-data/generate-complete yes
 *                                 # Wipe completo + reseed con roster definito
 *                                 # (1 super_admin, 5 admin, 3 coordinator,
 *                                 #  20 therapist, 50 paziente con piano + 20 appuntamenti)
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

        foreach ($patients as $patient) {
            if (!TherapeuticPlan::find()->where(['patient_id' => $patient->id])->exists()) {
                $plan = new TherapeuticPlan();
                $plan->patient_id = $patient->id;
                $plan->start_date = date('Y-m-d', strtotime('-' . rand(0, 90) . ' days'));
                $plan->duration_days = 180;
                $plan->status = 'active';
                $plan->diagnosis = 'Diagnosi di test per ' . $patient->first_name;
                $plan->objectives = 'Obiettivi terapeutici personalizzati';
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
        $user->username = $email; // Usa la mail anche come username
        $user->email = $email;
        $user->password_hash = Yii::$app->security->generatePasswordHash('12345678');
        $user->auth_key = Yii::$app->security->generateRandomString();
        $user->status = User::STATUS_ACTIVE;
        $user->save();

        $profile = new UserProfile();
        $profile->user_id = $user->id;
        $profile->first_name = $firstName;
        $profile->last_name = $lastName;
        $profile->fiscal_code = strtoupper(substr($firstName, 0, 3) . substr($lastName, 0, 3)) . '00A00A000A';
        $profile->phone = base64_encode(Yii::$app->security->encryptByKey('3331234567', Yii::$app->params['encryptionKey']));
        $profile->address = base64_encode(Yii::$app->security->encryptByKey('Via Roma 123, 80100 Napoli (NA)', Yii::$app->params['encryptionKey']));
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

    /**
     * Crea un utente terapista specifico per test
     */
    public function actionCreateTherapistUser()
    {
        $this->stdout("👨‍⚕️ Creazione utente terapista di test...\n");
        
        $email = 'terapista@test.it';
        
        // Controlla se l'utente esiste già
        if (User::find()->where(['email' => $email])->exists()) {
            $this->stdout("⚠️  L'utente terapista con email '$email' esiste già!\n");
            return ExitCode::OK;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Crea l'utente
            $user = new User();
            $user->username = $email; // Usa la mail anche come username
            $user->email = $email;
            $user->password_hash = Yii::$app->security->generatePasswordHash('12345678');
            $user->auth_key = Yii::$app->security->generateRandomString();
            $user->status = User::STATUS_ACTIVE;
            
            if (!$user->save()) {
                throw new \Exception('Errore nella creazione utente: ' . implode(', ', $user->getFirstErrors()));
            }

            // Crea il profilo utente
            $profile = new UserProfile();
            $profile->user_id = $user->id;
            $profile->first_name = 'Mario';
            $profile->last_name = 'Rossi';
            $profile->fiscal_code = 'RSSMRA80A01H501T';
            $profile->phone = base64_encode(Yii::$app->security->encryptByKey('3331234567', Yii::$app->params['encryptionKey']));
            $profile->address = base64_encode(Yii::$app->security->encryptByKey('Via Roma 123, 80100 Napoli (NA)', Yii::$app->params['encryptionKey']));
            
            if (!$profile->save()) {
                throw new \Exception('Errore nella creazione profilo: ' . implode(', ', $profile->getFirstErrors()));
            }

            // Crea il profilo terapista
            $specializations = Specialization::find()->all();
            if (empty($specializations)) {
                throw new \Exception('Nessuna specializzazione trovata. Eseguire prima generate-all per creare i dati base.');
            }

            $therapist = new Therapist();
            $therapist->user_id = $user->id;
            $therapist->specialization_id = $specializations[0]->id; // Prima specializzazione disponibile
            $therapist->weekly_hours_contract = 38;
            $therapist->calendar_color = '#3b82f6';
            $therapist->is_active = true;
            
            if (!$therapist->save()) {
                throw new \Exception('Errore nella creazione terapista: ' . implode(', ', $therapist->getFirstErrors()));
            }

            $transaction->commit();
            
            // Assegna il ruolo di terapista
            $auth = Yii::$app->authManager;
            $therapistRole = $auth->getRole('therapist');
            if ($therapistRole) {
                $auth->assign($therapistRole, $user->id);
            }

            $this->stdout("✅ Utente terapista creato con successo!\n");
            $this->stdout("   Email: $email\n");
            $this->stdout("   Password: 12345678\n");
            $this->stdout("   Nome: {$profile->first_name} {$profile->last_name}\n");
            $this->stdout("   Ruolo: Therapist\n");
            $this->stdout("   Specializzazione: {$specializations[0]->name}\n");
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stdout("❌ Errore durante la creazione: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Crea un paziente e il relativo account utente per test
     */
    public function actionCreatePatientUser()
    {
        $this->stdout("👶 Creazione paziente e utente collegato di test...\n");
        
        $email = 'paziente@test.it';
        
        // Controlla se l'utente esiste già
        if (User::find()->where(['email' => $email])->exists()) {
            $this->stdout("⚠️  L'utente paziente con email '$email' esiste già!\n");
            return ExitCode::OK;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Controlla che esistano i distretti
            $districts = District::find()->all();
            if (empty($districts)) {
                throw new \Exception('Nessun distretto trovato. Eseguire prima generate-all per creare i dati base.');
            }

            // Crea prima il paziente
            $patient = new Patient();
            $patient->first_name = 'Giulia';
            $patient->last_name = 'Bianchi';
            $patient->birth_date = '2015-03-15';
            $patient->fiscal_code = 'BNCGLI15C55F205R';
            $patient->district_id = $districts[0]->id;
            $patient->notes = 'Paziente di test creato automaticamente. Genitore: Anna Bianchi (Madre)';
            
            if (!$patient->save()) {
                throw new \Exception('Errore nella creazione paziente: ' . implode(', ', $patient->getFirstErrors()));
            }

            // Crea l'utente collegato al paziente
            $user = new User();
            $user->username = $email; // Usa la mail anche come username
            $user->email = $email;
            $user->password_hash = Yii::$app->security->generatePasswordHash('12345678');
            $user->auth_key = Yii::$app->security->generateRandomString();
            $user->status = User::STATUS_ACTIVE;
            
            if (!$user->save()) {
                throw new \Exception('Errore nella creazione utente: ' . implode(', ', $user->getFirstErrors()));
            }

            // Crea il profilo utente (del genitore/tutore)
            $profile = new UserProfile();
            $profile->user_id = $user->id;
            $profile->first_name = 'Anna';
            $profile->last_name = 'Bianchi';
            $profile->fiscal_code = 'BNCNNA75D48F205X';
            $profile->phone = base64_encode(Yii::$app->security->encryptByKey('3339876543', Yii::$app->params['encryptionKey']));
            $profile->address = base64_encode(Yii::$app->security->encryptByKey('Via Garibaldi 45, 20100 Milano (MI)', Yii::$app->params['encryptionKey']));
            
            if (!$profile->save()) {
                throw new \Exception('Errore nella creazione profilo: ' . implode(', ', $profile->getFirstErrors()));
            }

            // Crea il collegamento utente-paziente
            $accountPatient = new AccountPatient();
            $accountPatient->user_id = $user->id;
            $accountPatient->patient_id = $patient->id;
            $accountPatient->relationship_type = 'parent';
            $accountPatient->has_parental_authority = true;
            
            if (!$accountPatient->save()) {
                throw new \Exception('Errore nella creazione collegamento paziente: ' . implode(', ', $accountPatient->getFirstErrors()));
            }

            // Assegna il ruolo di paziente
            $auth = Yii::$app->authManager;
            $patientRole = $auth->getRole('patient_family');
            if ($patientRole) {
                $auth->assign($patientRole, $user->id);
            }

            $transaction->commit();

            $this->stdout("✅ Paziente e utente collegato creati con successo!\n");
            $this->stdout("   PAZIENTE:\n");
            $this->stdout("     Nome: {$patient->first_name} {$patient->last_name}\n");
            $this->stdout("     Data nascita: {$patient->birth_date}\n");
            $this->stdout("     Codice fiscale: {$patient->fiscal_code}\n");
            $this->stdout("   ACCOUNT GENITORE:\n");
            $this->stdout("     Email: $email\n");
            $this->stdout("     Password: 12345678\n");
            $this->stdout("     Nome: {$profile->first_name} {$profile->last_name}\n");
            $this->stdout("     Ruolo: Patient\n");
            $this->stdout("     Relazione: Madre del paziente\n");
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stdout("❌ Errore durante la creazione: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Crea un utente manager per test
     */
    public function actionCreateManagerUser()
    {
        $this->stdout("👨‍💼 Creazione utente manager di test...\n");
        
        $email = 'manager@test.it';
        
        // Controlla se l'utente esiste già
        if (User::find()->where(['email' => $email])->exists()) {
            $this->stdout("⚠️  L'utente manager con email '$email' esiste già!\n");
            return ExitCode::OK;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Crea l'utente
            $user = new User();
            $user->username = $email; // Usa la mail anche come username
            $user->email = $email;
            $user->password_hash = Yii::$app->security->generatePasswordHash('12345678');
            $user->auth_key = Yii::$app->security->generateRandomString();
            $user->status = User::STATUS_ACTIVE;
            
            if (!$user->save()) {
                throw new \Exception('Errore nella creazione utente: ' . implode(', ', $user->getFirstErrors()));
            }

            // Crea il profilo utente
            $profile = new UserProfile();
            $profile->user_id = $user->id;
            $profile->first_name = 'Antonio';
            $profile->last_name = 'Verdi';
            $profile->fiscal_code = 'VRDNTN80A01H501Z';
            $profile->phone = base64_encode(Yii::$app->security->encryptByKey('3337654321', Yii::$app->params['encryptionKey']));
            $profile->address = base64_encode(Yii::$app->security->encryptByKey('Via Milano 87, 80100 Napoli (NA)', Yii::$app->params['encryptionKey']));
            
            if (!$profile->save()) {
                throw new \Exception('Errore nella creazione profilo: ' . implode(', ', $profile->getFirstErrors()));
            }

            // Assegna il ruolo di manager
            $auth = Yii::$app->authManager;
            $managerRole = $auth->getRole('manager');
            if ($managerRole) {
                $auth->assign($managerRole, $user->id);
            }

            $transaction->commit();
            
            $this->stdout("✅ Utente manager creato con successo!\n");
            $this->stdout("   Email: $email\n");
            $this->stdout("   Password: 12345678\n");
            $this->stdout("   Nome: {$profile->first_name} {$profile->last_name}\n");
            $this->stdout("   Ruolo: Manager\n");
            $this->stdout("   Codice fiscale: {$profile->fiscal_code}\n");
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stdout("❌ Errore durante la creazione: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Crea un utente amministratore per test
     */
    public function actionCreateAdminUser()
    {
        $this->stdout("👑 Creazione utente amministratore di test...\n");
        
        $email = 'admin@test.it';
        
        // Controlla se l'utente esiste già
        if (User::find()->where(['email' => $email])->exists()) {
            $this->stdout("⚠️  L'utente admin con email '$email' esiste già!\n");
            return ExitCode::OK;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Crea l'utente
            $user = new User();
            $user->username = $email; // Usa la mail anche come username
            $user->email = $email;
            $user->password_hash = Yii::$app->security->generatePasswordHash('admin123');
            $user->auth_key = Yii::$app->security->generateRandomString();
            $user->status = User::STATUS_ACTIVE;
            
            if (!$user->save()) {
                throw new \Exception('Errore nella creazione utente: ' . implode(', ', $user->getFirstErrors()));
            }

            // Crea il profilo utente
            $profile = new UserProfile();
            $profile->user_id = $user->id;
            $profile->first_name = 'Super';
            $profile->last_name = 'Admin';
            $profile->fiscal_code = 'DMNSPR70A01H501A';
            $profile->phone = base64_encode(Yii::$app->security->encryptByKey('3331111111', Yii::$app->params['encryptionKey']));
            $profile->address = base64_encode(Yii::$app->security->encryptByKey('Via Amministrazione 1, 80100 Napoli (NA)', Yii::$app->params['encryptionKey']));
            
            if (!$profile->save()) {
                throw new \Exception('Errore nella creazione profilo: ' . implode(', ', $profile->getFirstErrors()));
            }

            // Assegna il ruolo di amministratore
            $auth = Yii::$app->authManager;
            $adminRole = $auth->getRole('admin');
            if ($adminRole) {
                $auth->assign($adminRole, $user->id);
            }

            $transaction->commit();
            
            $this->stdout("✅ Utente amministratore creato con successo!\n");
            $this->stdout("   Email: $email\n");
            $this->stdout("   Password: admin123\n");
            $this->stdout("   Nome: {$profile->first_name} {$profile->last_name}\n");
            $this->stdout("   Ruolo: Admin (accesso completo al sistema)\n");
            $this->stdout("   Codice fiscale: {$profile->fiscal_code}\n");
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stdout("❌ Errore durante la creazione: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Crea un utente coordinatore per test
     */
    public function actionCreateCoordinatorUser()
    {
        $this->stdout("👨‍💼 Creazione utente coordinatore di test...\n");
        
        $email = 'coordinatore@test.it';
        
        // Controlla se l'utente esiste già
        if (User::find()->where(['email' => $email])->exists()) {
            $this->stdout("⚠️  L'utente coordinatore con email '$email' esiste già!\n");
            return ExitCode::OK;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Crea l'utente
            $user = new User();
            $user->username = $email; // Usa la mail anche come username
            $user->email = $email;
            $user->password_hash = Yii::$app->security->generatePasswordHash('12345678');
            $user->auth_key = Yii::$app->security->generateRandomString();
            $user->status = User::STATUS_ACTIVE;
            
            if (!$user->save()) {
                throw new \Exception('Errore nella creazione utente: ' . implode(', ', $user->getFirstErrors()));
            }

            // Crea il profilo utente
            $profile = new UserProfile();
            $profile->user_id = $user->id;
            $profile->first_name = 'Laura';
            $profile->last_name = 'Neri';
            $profile->fiscal_code = 'NRELRA85T55F205B';
            $profile->phone = base64_encode(Yii::$app->security->encryptByKey('3332222222', Yii::$app->params['encryptionKey']));
            $profile->address = base64_encode(Yii::$app->security->encryptByKey('Via Coordinamento 15, 80100 Napoli (NA)', Yii::$app->params['encryptionKey']));
            
            if (!$profile->save()) {
                throw new \Exception('Errore nella creazione profilo: ' . implode(', ', $profile->getFirstErrors()));
            }

            // Assegna il ruolo di coordinatore
            $auth = Yii::$app->authManager;
            $coordinatorRole = $auth->getRole('coordinator');
            if ($coordinatorRole) {
                $auth->assign($coordinatorRole, $user->id);
            }

            $transaction->commit();
            
            $this->stdout("✅ Utente coordinatore creato con successo!\n");
            $this->stdout("   Email: $email\n");
            $this->stdout("   Password: 12345678\n");
            $this->stdout("   Nome: {$profile->first_name} {$profile->last_name}\n");
            $this->stdout("   Ruolo: Coordinator\n");
            $this->stdout("   Codice fiscale: {$profile->fiscal_code}\n");
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stdout("❌ Errore durante la creazione: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Crea un utente paziente con più pazienti collegati per testare lo switch
     */
    public function actionCreatePatientUserMultiple()
    {
        $this->stdout("👨‍👩‍👧‍👦 Creazione utente con più pazienti collegati...\n");
        
        $email = 'genitore@test.it';
        
        // Controlla se l'utente esiste già
        if (User::find()->where(['email' => $email])->exists()) {
            $this->stdout("⚠️  L'utente con email '$email' esiste già!\n");
            return ExitCode::OK;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Controlla che esistano i distretti
            $districts = District::find()->all();
            if (empty($districts)) {
                throw new \Exception('Nessun distretto trovato. Eseguire prima generate-all per creare i dati base.');
            }

            // Crea il primo paziente (figlio)
            $patient1 = new Patient();
            $patient1->first_name = 'Marco';
            $patient1->last_name = 'Rossi';
            $patient1->birth_date = '2012-08-22';
            $patient1->fiscal_code = 'RSSMRC12M22F205L';
            $patient1->district_id = $districts[0]->id;
            $patient1->notes = 'Primo figlio - paziente di test per sistema multi-paziente';
            
            if (!$patient1->save()) {
                throw new \Exception('Errore nella creazione primo paziente: ' . implode(', ', $patient1->getFirstErrors()));
            }

            // Crea il secondo paziente (figlia)
            $patient2 = new Patient();
            $patient2->first_name = 'Sofia';
            $patient2->last_name = 'Rossi';
            $patient2->birth_date = '2016-04-10';
            $patient2->fiscal_code = 'RSSSFO16D50F205P';
            $patient2->district_id = $districts[0]->id;
            $patient2->notes = 'Seconda figlia - paziente di test per sistema multi-paziente';
            
            if (!$patient2->save()) {
                throw new \Exception('Errore nella creazione secondo paziente: ' . implode(', ', $patient2->getFirstErrors()));
            }

            // Crea l'utente genitore
            $user = new User();
            $user->username = $email;
            $user->email = $email;
            $user->password_hash = Yii::$app->security->generatePasswordHash('12345678');
            $user->auth_key = Yii::$app->security->generateRandomString();
            $user->status = User::STATUS_ACTIVE;
            
            if (!$user->save()) {
                throw new \Exception('Errore nella creazione utente: ' . implode(', ', $user->getFirstErrors()));
            }

            // Crea il profilo utente genitore
            $profile = new UserProfile();
            $profile->user_id = $user->id;
            $profile->first_name = 'Maria';
            $profile->last_name = 'Rossi';
            $profile->fiscal_code = 'RSSMRA80E45F205T';
            $profile->phone = base64_encode(Yii::$app->security->encryptByKey('3334567890', Yii::$app->params['encryptionKey']));
            $profile->address = base64_encode(Yii::$app->security->encryptByKey('Via Roma 123, 20121 Milano (MI)', Yii::$app->params['encryptionKey']));
            
            if (!$profile->save()) {
                throw new \Exception('Errore nella creazione profilo: ' . implode(', ', $profile->getFirstErrors()));
            }

            // Crea il collegamento con il primo paziente
            $accountPatient1 = new AccountPatient();
            $accountPatient1->user_id = $user->id;
            $accountPatient1->patient_id = $patient1->id;
            $accountPatient1->relationship_type = 'parent';
            $accountPatient1->has_parental_authority = true;
            
            if (!$accountPatient1->save()) {
                throw new \Exception('Errore nella creazione collegamento primo paziente: ' . implode(', ', $accountPatient1->getFirstErrors()));
            }

            // Crea il collegamento con il secondo paziente
            $accountPatient2 = new AccountPatient();
            $accountPatient2->user_id = $user->id;
            $accountPatient2->patient_id = $patient2->id;
            $accountPatient2->relationship_type = 'parent';
            $accountPatient2->has_parental_authority = true;
            
            if (!$accountPatient2->save()) {
                throw new \Exception('Errore nella creazione collegamento secondo paziente: ' . implode(', ', $accountPatient2->getFirstErrors()));
            }

            // Assegna il ruolo di paziente
            $auth = Yii::$app->authManager;
            $patientRole = $auth->getRole('patient_family');
            if ($patientRole) {
                $auth->assign($patientRole, $user->id);
            }

            $transaction->commit();

            $this->stdout("✅ Utente genitore con 2 pazienti creato con successo!\n");
            $this->stdout("   ACCOUNT GENITORE:\n");
            $this->stdout("     Email: $email\n");
            $this->stdout("     Password: 12345678\n");
            $this->stdout("     Nome: {$profile->first_name} {$profile->last_name}\n");
            $this->stdout("     Ruolo: Patient\n");
            $this->stdout("     CF: {$profile->fiscal_code}\n");
            $this->stdout("   PRIMO PAZIENTE (FIGLIO):\n");
            $this->stdout("     Nome: {$patient1->first_name} {$patient1->last_name}\n");
            $this->stdout("     Data nascita: {$patient1->birth_date}\n");
            $this->stdout("     CF: {$patient1->fiscal_code}\n");
            $this->stdout("   SECONDO PAZIENTE (FIGLIA):\n");
            $this->stdout("     Nome: {$patient2->first_name} {$patient2->last_name}\n");
            $this->stdout("     Data nascita: {$patient2->birth_date}\n");
            $this->stdout("     CF: {$patient2->fiscal_code}\n");
            $this->stdout("   🔄 Questo account permetterà di testare lo switch tra pazienti!\n");
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stdout("❌ Errore durante la creazione: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Crea un utente genitore con 3 pazienti per test avanzati
     */
    public function actionCreatePatientUserFamily()
    {
        $this->stdout("👨‍👩‍👧‍👧‍👦 Creazione famiglia numerosa (3 pazienti)...\n");
        
        $email = 'famiglia@test.it';
        
        // Controlla se l'utente esiste già
        if (User::find()->where(['email' => $email])->exists()) {
            $this->stdout("⚠️  L'utente con email '$email' esiste già!\n");
            return ExitCode::OK;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Controlla che esistano i distretti
            $districts = District::find()->all();
            if (empty($districts)) {
                throw new \Exception('Nessun distretto trovato. Eseguire prima generate-all per creare i dati base.');
            }

            // Array di pazienti da creare
            $patientsData = [
                [
                    'first_name' => 'Alessandro',
                    'last_name' => 'Verdi',
                    'birth_date' => '2010-01-15',
                    'fiscal_code' => 'VRDLSN10A15F205M',
                    'notes' => 'Figlio maggiore - test famiglia numerosa'
                ],
                [
                    'first_name' => 'Francesca',
                    'last_name' => 'Verdi',
                    'birth_date' => '2013-06-08',
                    'fiscal_code' => 'VRDFNC13H48F205Q',
                    'notes' => 'Figlia di mezzo - test famiglia numerosa'
                ],
                [
                    'first_name' => 'Matteo',
                    'last_name' => 'Verdi',
                    'birth_date' => '2017-11-22',
                    'fiscal_code' => 'VRDMTT17S22F205W',
                    'notes' => 'Figlio minore - test famiglia numerosa'
                ]
            ];

            $patients = [];
            
            // Crea tutti i pazienti
            foreach ($patientsData as $patientData) {
                $patient = new Patient();
                $patient->first_name = $patientData['first_name'];
                $patient->last_name = $patientData['last_name'];
                $patient->birth_date = $patientData['birth_date'];
                $patient->fiscal_code = $patientData['fiscal_code'];
                $patient->district_id = $districts[0]->id;
                $patient->notes = $patientData['notes'];
                
                if (!$patient->save()) {
                    throw new \Exception("Errore nella creazione paziente {$patientData['first_name']}: " . implode(', ', $patient->getFirstErrors()));
                }
                
                $patients[] = $patient;
            }

            // Crea l'utente genitore
            $user = new User();
            $user->username = $email;
            $user->email = $email;
            $user->password_hash = Yii::$app->security->generatePasswordHash('12345678');
            $user->auth_key = Yii::$app->security->generateRandomString();
            $user->status = User::STATUS_ACTIVE;
            
            if (!$user->save()) {
                throw new \Exception('Errore nella creazione utente: ' . implode(', ', $user->getFirstErrors()));
            }

            // Crea il profilo utente genitore
            $profile = new UserProfile();
            $profile->user_id = $user->id;
            $profile->first_name = 'Carla';
            $profile->last_name = 'Verdi';
            $profile->fiscal_code = 'VRDCRL78M42F205K';
            $profile->phone = base64_encode(Yii::$app->security->encryptByKey('3335678901', Yii::$app->params['encryptionKey']));
            $profile->address = base64_encode(Yii::$app->security->encryptByKey('Corso Buenos Aires 45, 20124 Milano (MI)', Yii::$app->params['encryptionKey']));
            
            if (!$profile->save()) {
                throw new \Exception('Errore nella creazione profilo: ' . implode(', ', $profile->getFirstErrors()));
            }

            // Crea i collegamenti con tutti i pazienti
            foreach ($patients as $patient) {
                $accountPatient = new AccountPatient();
                $accountPatient->user_id = $user->id;
                $accountPatient->patient_id = $patient->id;
                $accountPatient->relationship_type = 'parent';
                $accountPatient->has_parental_authority = true;
                
                if (!$accountPatient->save()) {
                    throw new \Exception("Errore nella creazione collegamento paziente {$patient->first_name}: " . implode(', ', $accountPatient->getFirstErrors()));
                }
            }

            // Assegna il ruolo di paziente
            $auth = Yii::$app->authManager;
            $patientRole = $auth->getRole('patient_family');
            if ($patientRole) {
                $auth->assign($patientRole, $user->id);
            }

            $transaction->commit();

            $this->stdout("✅ Famiglia numerosa (3 pazienti) creata con successo!\n");
            $this->stdout("   ACCOUNT GENITORE:\n");
            $this->stdout("     Email: $email\n");
            $this->stdout("     Password: 12345678\n");
            $this->stdout("     Nome: {$profile->first_name} {$profile->last_name}\n");
            $this->stdout("     Ruolo: Patient\n");
            $this->stdout("   PAZIENTI:\n");
            
            foreach ($patients as $index => $patient) {
                $this->stdout("     " . ($index + 1) . ". {$patient->first_name} {$patient->last_name} ({$patient->birth_date})\n");
            }
            
            $this->stdout("   🔄 Perfetto per testare il sistema di switch tra più pazienti!\n");

        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stdout("❌ Errore durante la creazione: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Genera un dataset completo per il server di test:
     *   - 1 super_admin, 5 admin, 3 coordinator, 20 therapist
     *   - 50 pazienti (ognuno con account patient_family collegato)
     *   - 1 piano terapeutico per paziente
     *   - 1 plan_therapy + 1 appointment_pattern per piano
     *   - 20 appuntamenti per piano distribuiti dal 2024 a oggi
     *   - contatori assenze aggiornati
     *
     * Per evitare wipe accidentali, va passato l'argomento "yes":
     *     php yii test-data/generate-complete yes
     */
    public function actionGenerateComplete($confirm = null)
    {
        if ($confirm !== 'yes') {
            $this->stdout("⚠️  Questa action CANCELLA tutti gli utenti, pazienti, terapisti, piani e appuntamenti.\n");
            $this->stdout("Per confermare: php yii test-data/generate-complete yes\n");
            return ExitCode::OK;
        }

        $startTs = microtime(true);
        $this->stdout("🚀 Generazione dataset completo (server di test)...\n\n");

        // 0. Fix DEFINER orfani (trigger/view definiti da utenti MySQL inesistenti)
        $this->stdout("🔧 Verifica DEFINER trigger/view...\n");
        $this->fixOrphanDefiners();

        // 1. Wipe
        $this->stdout("🗑️  Pulizia dati esistenti...\n");
        $this->wipeAll();

        // 2. Base data (regime/setting/distretti/specializzazioni/trattamenti)
        $this->stdout("📍 Verifica/seed dati di base...\n");
        $this->ensureBaseData();

        // 3. Roster utenti — password uniforme: 12345678 (default di seedStaffUser)
        $this->stdout("👥 Creazione roster utenti...\n");
        $superAdmin = $this->seedStaffUser(
            'super_admin@therapy.test',
            'Super',
            'Admin',
            'super_admin'
        );

        $admins = [];
        for ($i = 1; $i <= 5; $i++) {
            $admins[] = $this->seedStaffUser(
                "admin$i@therapy.test",
                "Admin$i",
                "Sistema",
                'admin'
            );
        }

        $managers = [];
        for ($i = 1; $i <= 2; $i++) {
            $managers[] = $this->seedStaffUser(
                "manager$i@therapy.test",
                "Manager$i",
                "Operativo",
                'manager'
            );
        }

        $coordinators = [];
        for ($i = 1; $i <= 3; $i++) {
            $coordinators[] = $this->seedStaffUser(
                "coordinator$i@therapy.test",
                "Coordinator$i",
                "Centro",
                'coordinator'
            );
        }

        // Terapisti: distribuisci sulle specializzazioni esistenti, ruota colori
        $colors = [
            '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
            '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1',
        ];
        $specializations = Specialization::find()->all();
        if (empty($specializations)) {
            $this->stdout("❌ Nessuna specializzazione trovata: il seed di base è fallito.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $therapistUsers = [];
        for ($i = 1; $i <= 20; $i++) {
            $user = $this->seedStaffUser(
                "terapista$i@therapy.test",
                "Terapista$i",
                "Cognome$i",
                'therapist'
            );

            $therapist = new Therapist();
            $therapist->user_id = $user->id;
            $therapist->specialization_id = $specializations[($i - 1) % count($specializations)]->id;
            $therapist->weekly_hours_contract = [20, 30, 38][($i - 1) % 3];
            $therapist->calendar_color = $colors[($i - 1) % count($colors)];
            $therapist->is_active = true;
            $therapist->is_internal = ($i % 2 === 0) ? 1 : 0;
            $therapist->can_supervise = ($i % 5 === 0) ? 1 : 0;
            $therapist->can_parental_training = ($i % 4 === 0) ? 1 : 0;
            if (!$therapist->save()) {
                throw new \RuntimeException("Errore creazione therapist $i: " . json_encode($therapist->getFirstErrors()));
            }
            $therapistUsers[] = $user;
        }
        $this->stdout("   ✓ 1 super_admin, 5 admin, 2 manager, 3 coordinator, 20 therapist\n");

        // 3b. Gruppi coordinatori — un gruppo per coordinatore, terapisti distribuiti round-robin
        $this->stdout("👨‍👧‍👦 Creazione gruppi coordinatori...\n");
        $groupAssignedFrom = date('Y-m-d', strtotime('2024-01-01'));
        $assignedById = $admins[0]->id;
        foreach ($coordinators as $idx => $coordUser) {
            $group = new CoordinatorGroup();
            $group->name = 'Gruppo ' . ($idx + 1);
            $group->coordinator_user_id = $coordUser->id;
            $group->is_active = 1;
            if (!$group->save()) {
                throw new \RuntimeException("Errore gruppo $idx: " . json_encode($group->getFirstErrors()));
            }

            // Distribuisci therapist al gruppo: round-robin sull'indice
            foreach ($therapistUsers as $tIdx => $tUser) {
                if ($tIdx % count($coordinators) !== $idx) {
                    continue;
                }
                $therapist = Therapist::findOne(['user_id' => $tUser->id]);
                $gt = new GroupTherapist();
                $gt->group_id = $group->id;
                $gt->therapist_id = $therapist->id;
                $gt->assigned_from = $groupAssignedFrom;
                $gt->assigned_by = $assignedById;
                if (!$gt->save()) {
                    throw new \RuntimeException("Errore group_therapist (g={$group->id}, t={$therapist->id}): " . json_encode($gt->getFirstErrors()));
                }
            }
        }
        $this->stdout("   ✓ 3 gruppi creati, 20 terapisti distribuiti\n");

        // 4. Pazienti + account collegato (patient_family)
        $this->stdout("🏥 Creazione 50 pazienti + account collegati...\n");
        $districts = District::find()->all();
        $patientFirstNames = [
            'Marco','Luca','Giulia','Sofia','Andrea','Chiara','Matteo','Alessia','Davide','Francesca',
            'Lorenzo','Martina','Gabriele','Beatrice','Tommaso','Greta','Filippo','Aurora','Riccardo','Camilla',
            'Federico','Emma','Edoardo','Alice','Leonardo','Anna','Mattia','Sara','Diego','Noemi',
            'Stefano','Eleonora','Pietro','Margherita','Antonio','Ludovica','Cristian','Elena','Niccolò','Linda',
            'Alessandro','Vittoria','Gianluca','Caterina','Simone','Bianca','Nicolò','Asia','Manuel','Rebecca',
        ];
        $patientLastNames = [
            'Rossi','Bianchi','Verdi','Romano','Ricci','Marino','Greco','Bruno','Gallo','Conti',
            'De Luca','Mancini','Costa','Giordano','Rizzo','Lombardi','Moretti','Barbieri','Fontana','Santoro',
            'Mariani','Rinaldi','Caruso','Ferrari','Galli','Martini','Leone','Longo','Gentile','Martinelli',
            'Vitale','Lombardo','Serra','Coppola','De Santis','Marchetti','Parisi','Villa','Conte','Ferraro',
            'Fabbri','Bianco','Marini','Grasso','Valentini','Messina','Sala','De Angelis','Rossini','Esposito',
        ];
        $patients = [];
        for ($i = 1; $i <= 50; $i++) {
            $patient = new Patient();
            $patient->first_name = $patientFirstNames[$i - 1];
            $patient->last_name = $patientLastNames[$i - 1];
            $patient->birth_date = date('Y-m-d', strtotime('-' . rand(3, 15) . ' years -' . rand(0, 365) . ' days'));
            $patient->gender = ($i % 2 === 0) ? 'F' : 'M';
            $patient->born_in_italy = 1;
            $patient->fiscal_code = sprintf('TSTPZN%02dA01H501%s', $i, chr(65 + ($i % 26)));
            $patient->district_id = $districts[$i % count($districts)]->id;
            $patient->residence_city = 'Napoli';
            $patient->residence_province_code = 'NA';
            $patient->notes = ($i % 7 === 0) ? 'Necessita supporto costante' : null;
            if (!$patient->save()) {
                throw new \RuntimeException("Errore creazione paziente $i: " . json_encode($patient->getFirstErrors()));
            }
            $patients[] = $patient;

            // account patient_family collegato
            $famUser = $this->seedStaffUser(
                "paziente$i@therapy.test",
                "Genitore$i",
                "Test",
                'patient_family'
            );
            $accountPatient = new AccountPatient();
            $accountPatient->user_id = $famUser->id;
            $accountPatient->patient_id = $patient->id;
            $accountPatient->relationship_type = AccountPatient::RELATIONSHIP_PARENT;
            $accountPatient->has_parental_authority = true;
            if (!$accountPatient->save()) {
                throw new \RuntimeException("Errore account_patient $i: " . json_encode($accountPatient->getFirstErrors()));
            }
        }
        $this->stdout("   ✓ 50 pazienti creati\n");

        // 5. Piani terapeutici, plan_therapies, pattern e appuntamenti
        $this->stdout("📋 Creazione piani terapeutici + 20 appuntamenti per piano...\n");
        // Escludi i regimi con regole di validazione complesse (es. ABA richiede
        // ore di supervisione/parent_training pre-definite)
        $regimes = Regime::find()
            ->where(['NOT IN', 'nome', ['ABA']])
            ->all();
        if (empty($regimes)) {
            throw new \RuntimeException("Nessun regime utilizzabile in DB (escluso ABA)");
        }
        $regimeSettings = []; // regime_id => [setting_id,...]
        foreach (RegimeSetting::find()->all() as $rs) {
            $regimeSettings[$rs->regime_id][] = $rs->setting_id;
        }

        $treatments = TreatmentType::find()->all();
        $treatmentsBySpec = []; // specialization_id => [treatment_id,...]
        foreach (SpecializationTreatment::find()->all() as $st) {
            $treatmentsBySpec[$st->specialization_id][] = $st->treatment_type_id;
        }

        $totalAppointments = 0;
        $todayDate = new \DateTime('today');
        $minStart = new \DateTime('2024-01-01');
        $maxStart = (clone $todayDate)->modify('-30 days');
        $maxStartTs = $maxStart->getTimestamp();
        $minStartTs = $minStart->getTimestamp();

        $createdById = $admins[0]->id;
        $patternCreatedById = $coordinators[0]->id;

        foreach ($patients as $idx => $patient) {
            // Plan
            $startTs2 = rand($minStartTs, $maxStartTs);
            $startDate = date('Y-m-d', $startTs2);
            $regime = $regimes[$idx % count($regimes)];

            $plan = new TherapeuticPlan();
            $plan->patient_id = $patient->id;
            $plan->start_date = $startDate;
            $plan->duration_days = 365;
            $plan->regime_id = $regime->id;
            $plan->district_id = $patient->district_id;
            $plan->status = 'active';
            $plan->approval_date = $startDate;
            $plan->protocol_number = sprintf('PROT-%04d/%s', 1000 + $idx, date('Y', $startTs2));
            $plan->notes = 'Piano terapeutico generato per ambiente di test';
            $plan->created_by = $createdById;
            if (!$plan->save()) {
                throw new \RuntimeException("Errore creazione piano per paziente {$patient->id}: " . json_encode($plan->getFirstErrors()));
            }

            // Setting valido per il regime
            $allowedSettings = $regimeSettings[$regime->id] ?? [];
            if (empty($allowedSettings)) {
                // fallback: qualsiasi setting
                $allowedSettings = array_map(fn($s) => $s->id, Setting::find()->all());
            }
            $settingId = $allowedSettings[array_rand($allowedSettings)];

            // Scegli un terapista compatibile per il pattern
            $therapistUser = $therapistUsers[$idx % count($therapistUsers)];
            $therapist = Therapist::findOne(['user_id' => $therapistUser->id]);
            $allowedTreatments = $treatmentsBySpec[$therapist->specialization_id] ?? [];
            if (empty($allowedTreatments)) {
                // fallback: qualsiasi trattamento
                $allowedTreatments = array_map(fn($t) => $t->id, $treatments);
            }
            $treatmentId = $allowedTreatments[array_rand($allowedTreatments)];

            // PlanTherapy
            $planTherapy = new PlanTherapy();
            $planTherapy->therapeutic_plan_id = $plan->id;
            $planTherapy->treatment_type_id = $treatmentId;
            $planTherapy->weekly_hours = 1.00;
            $planTherapy->setting_id = $settingId;
            $planTherapy->is_group = 0;
            if (!$planTherapy->save()) {
                throw new \RuntimeException("Errore plan_therapy per piano {$plan->id}: " . json_encode($planTherapy->getFirstErrors()));
            }

            // Pattern (settimanale)
            $startDt = new \DateTime($startDate);
            $dayOfWeek = (int)$startDt->format('N'); // 1-7
            if ($dayOfWeek > 5) {
                $dayOfWeek = 1; // sposta a lunedì
            }
            $hour = 9 + ($idx % 8); // 9-16
            $minute = ($idx % 2) * 30;
            $startTime = sprintf('%02d:%02d', $hour, $minute);
            $duration = 60;
            $validFrom = $startDate;
            $validTo = date('Y-m-d', strtotime($startDate . ' +365 days'));

            $pattern = new AppointmentPattern();
            $pattern->plan_therapy_id = $planTherapy->id;
            $pattern->therapist_id = $therapist->id;
            $pattern->id_setting = $settingId;
            $pattern->day_of_week = $dayOfWeek;
            $pattern->start_time = $startTime;
            $pattern->duration_minutes = $duration;
            $pattern->valid_from = $validFrom;
            $pattern->valid_to = $validTo;
            $pattern->created_by = $patternCreatedById;
            if (!$pattern->save()) {
                throw new \RuntimeException("Errore pattern per piano {$plan->id}: " . json_encode($pattern->getFirstErrors()));
            }

            // 20 appuntamenti distribuiti dal start_date a oggi (o fino a +20 settimane se piano molto recente)
            $created = $this->generateTwentyAppointments(
                $plan,
                $planTherapy,
                $pattern,
                $patient,
                $therapist,
                $settingId,
                $createdById,
                $todayDate
            );
            $totalAppointments += $created;

            // Counter assenze (model AbsenceCounter è fuori sync con lo schema:
            // dichiara therapist_id/created_at che la tabella non ha → INSERT diretto)
            Yii::$app->db->createCommand()->insert('absence_counters', [
                'patient_id' => $patient->id,
                'therapeutic_plan_id' => $plan->id,
                'total_appointments' => 0,
                'total_absences' => 0,
                'justified_absences' => 0,
                'unjustified_absences' => 0,
            ])->execute();
        }
        $this->stdout("   ✓ 50 piani, 50 plan_therapies, 50 pattern, $totalAppointments appuntamenti\n");

        // 6. Aggiorna contatori assenze
        $this->stdout("📊 Aggiornamento contatori assenze...\n");
        $this->updateAbsenceCountersComplete();

        // Stats
        $this->stdout("\n✅ Completato in " . round(microtime(true) - $startTs, 1) . "s\n\n");
        $this->showStats();

        $this->stdout("\n🔑 Credenziali (password uniforme: 12345678):\n");
        $this->stdout("   super_admin@therapy.test\n");
        $this->stdout("   admin1..5@therapy.test\n");
        $this->stdout("   manager1..2@therapy.test\n");
        $this->stdout("   coordinator1..3@therapy.test\n");
        $this->stdout("   terapista1..20@therapy.test\n");
        $this->stdout("   paziente1..50@therapy.test\n");

        return ExitCode::OK;
    }

    /**
     * Pulisce tutto: utenti, profili, pazienti, terapisti, piani, appuntamenti, RBAC assignments.
     * Mantiene: ruoli RBAC, regime, setting, distretti, specializzazioni, treatment_types.
     */
    private function wipeAll()
    {
        $db = Yii::$app->db;
        $db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
        try {
            $tables = [
                'absences',
                'absence_counters',
                'appointments',
                'appointment_patterns',
                'plan_therapies',
                'therapeutic_plans',
                'account_patients',
                'patients',
                'group_therapists',
                'therapist_substitutions',
                'therapist_busy_slots',
                'therapists',
                'auth_assignment',
                'auth_token',
                'user_profiles',
                'users',
            ];
            foreach ($tables as $t) {
                if ($db->getTableSchema($t, true) !== null) {
                    $db->createCommand("TRUNCATE TABLE `$t`")->execute();
                }
            }
        } finally {
            $db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
        }
    }

    /**
     * Crea utente + profilo + assegna ruolo RBAC. Ritorna il modello User.
     */
    private function seedStaffUser($email, $firstName, $lastName, $roleName, $password = '12345678')
    {
        $user = new User();
        $user->username = $email;
        $user->email = $email;
        $user->password_hash = Yii::$app->security->generatePasswordHash($password);
        $user->auth_key = Yii::$app->security->generateRandomString();
        $user->status = User::STATUS_ACTIVE;
        if (!$user->save()) {
            throw new \RuntimeException("Errore user $email: " . json_encode($user->getFirstErrors()));
        }

        $profile = new UserProfile();
        $profile->user_id = $user->id;
        $profile->first_name = $firstName;
        $profile->last_name = $lastName;
        $profile->fiscal_code = strtoupper(substr($firstName, 0, 3) . substr($lastName, 0, 3)) . sprintf('%02dA01H501A', $user->id % 100);
        $encKey = Yii::$app->params['encryptionKey'] ?? null;
        if ($encKey) {
            $profile->phone = base64_encode(Yii::$app->security->encryptByKey('3331234567', $encKey));
            $profile->address = base64_encode(Yii::$app->security->encryptByKey('Via Roma 1, Napoli', $encKey));
        }
        if (!$profile->save()) {
            throw new \RuntimeException("Errore profilo $email: " . json_encode($profile->getFirstErrors()));
        }

        $auth = Yii::$app->authManager;
        $role = $auth->getRole($roleName);
        if ($role) {
            $auth->assign($role, $user->id);
        } else {
            $this->stdout("   ⚠️  Ruolo '$roleName' non trovato — utente creato senza ruolo.\n");
        }

        return $user;
    }

    /**
     * Distribuisce 20 appuntamenti (ritmo settimanale) a partire da plan.start_date.
     * Status: passato → mix completed/absent_*; futuro → scheduled.
     */
    private function generateTwentyAppointments(
        TherapeuticPlan $plan,
        PlanTherapy $planTherapy,
        AppointmentPattern $pattern,
        Patient $patient,
        Therapist $therapist,
        int $settingId,
        int $createdById,
        \DateTime $today
    ): int {
        $count = 0;
        $cursor = new \DateTime($plan->start_date);
        // allinea al day_of_week del pattern
        while ((int)$cursor->format('N') !== (int)$pattern->day_of_week) {
            $cursor->modify('+1 day');
        }

        for ($i = 0; $i < 20; $i++) {
            $datetime = $cursor->format('Y-m-d') . ' ' . $pattern->start_time . ':00';
            $appointmentDt = new \DateTime($datetime);

            if ($appointmentDt < $today) {
                $r = rand(1, 100);
                if ($r <= 80) {
                    $status = Appointment::STATUS_COMPLETED;
                } elseif ($r <= 90) {
                    $status = Appointment::STATUS_ABSENT_JUSTIFIED;
                } elseif ($r <= 96) {
                    $status = Appointment::STATUS_ABSENT_NOT_JUSTIFIED;
                } else {
                    $status = Appointment::STATUS_CANCELLED;
                }
            } else {
                $status = Appointment::STATUS_SCHEDULED;
            }

            $appt = new Appointment();
            $appt->pattern_id = $pattern->id;
            $appt->plan_therapy_id = $planTherapy->id;
            $appt->appointment_source = Appointment::SOURCE_THERAPEUTIC_PLAN;
            $appt->therapist_id = $therapist->id;
            $appt->patient_id = $patient->id;
            $appt->appointment_datetime = $datetime;
            $appt->duration_minutes = $pattern->duration_minutes;
            $appt->id_setting = $settingId;
            $appt->status = $status;
            $appt->appointment_type = Appointment::TYPE_TERAPIA;
            $appt->appointment_category = Appointment::CATEGORY_REGULAR;
            $appt->treatment_type_id = $planTherapy->treatment_type_id;
            $appt->created_by = $createdById;
            if ($appt->save()) {
                $count++;
            } else {
                $this->stdout("     ⚠️  appuntamento non salvato: " . json_encode($appt->getFirstErrors()) . "\n");
            }

            $cursor->modify('+7 days');
        }
        return $count;
    }

    /**
     * Aggiorna counters in modo compatibile con lo schema corrente
     * (appointments.patient_id è ora denormalizzato).
     */
    private function updateAbsenceCountersComplete()
    {
        $sql = "
            UPDATE absence_counters ac
            JOIN (
                SELECT
                    pt.therapeutic_plan_id,
                    a.patient_id,
                    COUNT(*) AS total_appointments,
                    SUM(a.status IN ('absent_justified','absent_not_justified')) AS total_absences,
                    SUM(a.status = 'absent_justified') AS justified_absences,
                    SUM(a.status = 'absent_not_justified') AS unjustified_absences
                FROM appointments a
                JOIN plan_therapies pt ON a.plan_therapy_id = pt.id
                WHERE a.patient_id IS NOT NULL
                GROUP BY pt.therapeutic_plan_id, a.patient_id
            ) s
              ON s.therapeutic_plan_id = ac.therapeutic_plan_id
             AND s.patient_id = ac.patient_id
            SET ac.total_appointments = s.total_appointments,
                ac.total_absences = s.total_absences,
                ac.justified_absences = s.justified_absences,
                ac.unjustified_absences = s.unjustified_absences
        ";
        Yii::$app->db->createCommand($sql)->execute();
    }

    /**
     * Riallinea il DEFINER di trigger/view che puntano a utenti MySQL
     * inesistenti (es. 'phpmyadmin'@'localhost' creato da pma in passato).
     * Idempotente: se tutti i DEFINER sono validi, non fa nulla.
     */
    private function fixOrphanDefiners(): void
    {
        $db = Yii::$app->db;

        $existingUsers = array_map(
            fn($r) => $r['User'] . '@' . $r['Host'],
            $db->createCommand('SELECT User, Host FROM mysql.user')->queryAll()
        );

        $newDefiner = 'root@localhost';
        if (!in_array($newDefiner, $existingUsers, true)) {
            $this->stdout("   ⚠️  root@localhost non esiste — skip fix definer.\n");
            return;
        }

        // Triggers
        $triggers = $db->createCommand(
            "SELECT TRIGGER_NAME, DEFINER FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()"
        )->queryAll();
        $fixedTriggers = 0;
        foreach ($triggers as $t) {
            if (in_array($t['DEFINER'], $existingUsers, true)) {
                continue;
            }
            $name = $t['TRIGGER_NAME'];
            $row = $db->createCommand("SHOW CREATE TRIGGER `$name`")->queryOne();
            $createSql = $row['SQL Original Statement'] ?? array_values($row)[2];
            $newSql = preg_replace(
                "/DEFINER=`[^`]+`@`[^`]+`/",
                "DEFINER=`root`@`localhost`",
                $createSql,
                1
            );
            $db->createCommand("DROP TRIGGER IF EXISTS `$name`")->execute();
            $db->createCommand($newSql)->execute();
            $fixedTriggers++;
        }

        // Views
        $views = $db->createCommand(
            "SELECT TABLE_NAME, DEFINER FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE()"
        )->queryAll();
        $fixedViews = 0;
        foreach ($views as $v) {
            if (in_array($v['DEFINER'], $existingUsers, true)) {
                continue;
            }
            $name = $v['TABLE_NAME'];
            $row = $db->createCommand("SHOW CREATE VIEW `$name`")->queryOne();
            $createSql = $row['Create View'] ?? array_values($row)[1];
            $newSql = preg_replace(
                "/DEFINER=`[^`]+`@`[^`]+`/",
                "DEFINER=`root`@`localhost`",
                $createSql,
                1
            );
            // CREATE VIEW non si "DROPpa" sempre, ma OR REPLACE lo gestisce.
            $newSql = preg_replace('/^CREATE\s+/', 'CREATE OR REPLACE ', $newSql, 1);
            $db->createCommand($newSql)->execute();
            $fixedViews++;
        }

        if ($fixedTriggers + $fixedViews > 0) {
            $this->stdout("   ✓ DEFINER ricreati: $fixedTriggers trigger, $fixedViews view\n");
        } else {
            $this->stdout("   ✓ Nessun DEFINER orfano\n");
        }
    }

    /**
     * Garantisce dati di base. Se distretti/specializzazioni/trattamenti
     * sono assenti, riusa il seed legacy.
     */
    private function ensureBaseData()
    {
        if (District::find()->count() == 0
            || Specialization::find()->count() == 0
            || TreatmentType::find()->count() == 0
        ) {
            $this->generateBaseData();
        }
        if (Regime::find()->count() == 0) {
            $this->stdout("   ⚠️  Tabella 'regime' vuota: i piani non possono essere creati senza regime.\n");
            throw new \RuntimeException('Tabella regime vuota');
        }
        if (Setting::find()->count() == 0) {
            $this->stdout("   ⚠️  Tabella 'setting' vuota: necessaria per plan_therapies/appointment_patterns.\n");
            throw new \RuntimeException('Tabella setting vuota');
        }
    }
} 
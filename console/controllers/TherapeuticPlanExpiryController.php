<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use common\models\TherapeuticPlan;
use common\models\TherapeuticPlanNotification;
use common\models\NotificationTemplate;
use common\models\AccountPatient;

/**
 * Controller per gestire le notifiche di scadenza dei piani terapeutici
 * 
 * Esempio crontab (da eseguire ogni notte alle 2:00):
 * 0 2 * * * /usr/bin/php /path/to/yii therapeutic-plan-expiry/send-notifications
 */
class TherapeuticPlanExpiryController extends Controller
{
    /**
     * Array dei giorni prima della scadenza per cui inviare notifiche
     */
    private $notificationDays = [90, 60, 30, 15];

    /**
     * Invia le notifiche per i piani terapeutici in scadenza
     * 
     * @return int
     */
    public function actionSendNotifications()
    {
        $this->stdout("Inizio invio notifiche scadenza piani terapeutici...\n");
        
        $totalNotifications = 0;
        $errors = [];

        // Ottieni tutti i piani attivi
        $today = date('Y-m-d');
        $activePlans = TherapeuticPlan::find()
            ->where(['>', 'end_date', $today])
            ->with(['patient', 'regime'])
            ->all();

        foreach ($activePlans as $plan) {
            try {
                $count = $this->processNotificationsForPlan($plan);
                $totalNotifications += $count;
            } catch (\Exception $e) {
                $error = "Errore per piano {$plan->id}: " . $e->getMessage();
                $errors[] = $error;
                $this->stderr("- {$error}\n");
                Yii::error($error, __METHOD__);
            }
        }

        $this->stdout("\n=== RIEPILOGO ===\n");
        $this->stdout("Totale notifiche inviate: {$totalNotifications}\n");
        
        if (!empty($errors)) {
            $this->stdout("Errori riscontrati: " . count($errors) . "\n");
            foreach ($errors as $error) {
                $this->stderr("- {$error}\n");
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Processo completato con successo!\n");
        return ExitCode::OK;
    }

    /**
     * Elabora le notifiche per un singolo piano
     * 
     * @param TherapeuticPlan $plan
     * @return int Numero di notifiche inviate
     * @throws \Exception
     */
    private function processNotificationsForPlan($plan)
    {
        // Calcola i giorni rimanenti
        $daysRemaining = (new \DateTime($plan->end_date))->diff(new \DateTime())->days;
        
        // Trova il threshold appropriato (il più piccolo tra quelli applicabili e non ancora inviati)
        $appropriateThreshold = $this->findAppropriateThreshold($plan, $daysRemaining);
        
        if ($appropriateThreshold === null) {
            return 0; // Nessuna notifica da inviare
        }

        // Trova il template
        $templateCode = 'PLAN_EXPIRING_' . $appropriateThreshold;
        $template = NotificationTemplate::findByCode($templateCode);
        
        if (!$template) {
            throw new \Exception("Template '{$templateCode}' non trovato o non attivo");
        }

        $this->stdout("\nPiano ID {$plan->id} - {$plan->patient->getFullName()} (scade tra {$daysRemaining} giorni)\n");
        $this->stdout("  - Invio notifica {$appropriateThreshold}gg\n");

        return $this->sendNotificationsForPlan($plan, $appropriateThreshold, $template);
    }

    /**
     * Trova il threshold appropriato per le notifiche
     * 
     * @param TherapeuticPlan $plan
     * @param int $daysRemaining
     * @return int|null Il threshold da usare, o null se nessuna notifica deve essere inviata
     */
    private function findAppropriateThreshold($plan, $daysRemaining)
    {
        // Ordina i threshold in ordine crescente (dal più piccolo al più grande)
        $sortedThresholds = $this->notificationDays;
        sort($sortedThresholds);
        
        // Trova il primo threshold applicabile (dal più piccolo) che non è ancora stato inviato
        foreach ($sortedThresholds as $threshold) {
            // Se i giorni rimanenti sono più del threshold, questa notifica è applicabile
            if ($daysRemaining <= $threshold) {
                // Verifica se questa notifica è già stata inviata
                $alreadySent = TherapeuticPlanNotification::find()
                    ->where([
                        'therapeutic_plan_id' => $plan->id,
                        'days_before' => $threshold
                    ])
                    ->exists();
                    
                if (!$alreadySent) {
                    return $threshold; // Questo è il threshold da usare
                }
            }
        }
        
        return null; // Nessuna notifica da inviare
    }

    /**
     * Invia notifiche per un singolo piano terapeutico
     * 
     * @param TherapeuticPlan $plan
     * @param int $daysBefore
     * @param NotificationTemplate $template
     * @return int Numero di notifiche inviate
     * @throws \Exception
     */
    private function sendNotificationsForPlan($plan, $daysBefore, $template)
    {
        $patient = $plan->patient;
        if (!$patient) {
            throw new \Exception("Paziente non trovato per piano {$plan->id}");
        }

        // Trova tutti gli account collegati al paziente con autorità genitoriale
        $accountPatients = AccountPatient::find()
            ->where([
                'patient_id' => $patient->id,
                'has_parental_authority' => 1
            ])
            ->with('user')
            ->all();

        if (empty($accountPatients)) {
            $this->stdout("    - Nessun account con autorità genitoriale trovato per paziente {$patient->getFullName()}\n");
            return 0;
        }

        // Calcola i giorni rimanenti effettivi
        $daysRemaining = (new \DateTime($plan->end_date))->diff(new \DateTime())->days;

        // Prepara le variabili per il template
        $variables = [
            'patient_name' => $patient->getFullName(),
            'end_date' => Yii::$app->formatter->asDate($plan->end_date, 'long'),
            'regime' => $plan->getRegimeName(),
            'days_remaining' => $daysRemaining,
        ];

        $notificationsSent = 0;
        $userIds = [];

        foreach ($accountPatients as $accountPatient) {
            $user = $accountPatient->user;
            if (!$user || $user->status !== 'active') {
                continue;
            }

            // Verifica se la notifica è già stata inviata
            if (TherapeuticPlanNotification::isAlreadySent($plan->id, $user->id, $daysBefore)) {
                $this->stdout("    - Notifica già inviata a {$user->email} per piano {$plan->id}\n");
                continue;
            }

            $userIds[] = $user->id;
        }

        if (empty($userIds)) {
            return 0;
        }

        try {
            // Invia le notifiche usando il servizio
            $result = Yii::$app->notificationService->sendFromTemplate(
                $template->code,
                $userIds,
                $variables,
                null, // sender_id
                null, // scheduled_for
                [
                    'therapeutic_plan_id' => $plan->id,
                    'patient_id' => $patient->id,
                    'days_before' => $daysBefore,
                    'actual_days_remaining' => $daysRemaining,
                ]
            );

            // Registra le notifiche inviate
            if ($result['notifications_created'] > 0) {
                $notificationIds = [];
                if (isset($result['onesignal_response']['notification_ids'])) {
                    $notificationIds = $result['onesignal_response']['notification_ids'];
                }

                foreach ($userIds as $index => $userId) {
                    $planNotification = new TherapeuticPlanNotification([
                        'therapeutic_plan_id' => $plan->id,
                        'user_id' => $userId,
                        'days_before' => $daysBefore,
                        'notification_id' => $notificationIds[$index] ?? null,
                        'sent_at' => date('Y-m-d H:i:s'),
                    ]);

                    if ($planNotification->save()) {
                        $notificationsSent++;
                        $this->stdout("    - Notifica {$daysBefore}gg inviata a utente ID {$userId} (rimangono {$daysRemaining} giorni)\n");
                    } else {
                        $errors = implode(', ', $planNotification->getFirstErrors());
                        Yii::error("Errore salvataggio tracking notifica: {$errors}", __METHOD__);
                    }
                }
            }

        } catch (\Exception $e) {
            throw new \Exception("Errore invio notifiche per piano {$plan->id}: " . $e->getMessage());
        }

        return $notificationsSent;
    }

    /**
     * Test del comando (modalità dry-run)
     * Mostra quali notifiche verrebbero inviate senza inviarle realmente
     * 
     * @return int
     */
    public function actionDryRun()
    {
        $this->stdout("=== MODALITÀ DRY RUN - NESSUNA NOTIFICA VERRÀ INVIATA ===\n\n");
        
        $today = date('Y-m-d');
        $activePlans = TherapeuticPlan::find()
            ->where(['>', 'end_date', $today])
            ->with(['patient', 'regime'])
            ->orderBy(['end_date' => SORT_ASC])
            ->all();
            
        if (empty($activePlans)) {
            $this->stdout("Nessun piano terapeutico attivo trovato.\n");
            return ExitCode::OK;
        }
        
        $this->stdout("Piani terapeutici attivi:\n");
        
        foreach ($activePlans as $plan) {
            $daysRemaining = (new \DateTime($plan->end_date))->diff(new \DateTime())->days;
            
            $this->stdout("\n- Piano ID {$plan->id}: {$plan->patient->getFullName()}\n");
            $this->stdout("  Scade il: {$plan->end_date} (tra {$daysRemaining} giorni)\n");
            $this->stdout("  Regime: {$plan->getRegimeName()}\n");
            
            // Trova quale notifica verrebbe inviata
            $appropriateThreshold = $this->findAppropriateThreshold($plan, $daysRemaining);
            
            // Mostra lo stato di tutte le notifiche
            $sortedThresholds = $this->notificationDays;
            rsort($sortedThresholds); // Mostra dal più grande al più piccolo per leggibilità
            
            $this->stdout("  Stato notifiche:\n");
            foreach ($sortedThresholds as $threshold) {
                $sent = TherapeuticPlanNotification::find()
                    ->where([
                        'therapeutic_plan_id' => $plan->id,
                        'days_before' => $threshold
                    ])
                    ->exists();
                    
                $applicable = $daysRemaining <= $threshold;
                
                $status = $sent ? "INVIATA" : ($applicable ? "DA INVIARE" : "NON APPLICABILE");
                $this->stdout("    - {$threshold} giorni: {$status}");
                
                if ($threshold === $appropriateThreshold) {
                    $this->stdout(" <-- VERRÀ INVIATA QUESTA");
                }
                $this->stdout("\n");
            }
            
            if ($appropriateThreshold !== null) {
                // Conta gli account che riceverebbero la notifica
                $accountCount = AccountPatient::find()
                    ->where([
                        'patient_id' => $plan->patient->id,
                        'has_parental_authority' => 1
                    ])
                    ->count();
                    
                $this->stdout("  AZIONE: Invierà notifica {$appropriateThreshold}gg a {$accountCount} account\n");
                $this->stdout("  Il messaggio dirà: \"scadrà tra {$daysRemaining} giorni\"\n");
            } else {
                $this->stdout("  AZIONE: Nessuna notifica da inviare\n");
            }
        }
        
        $this->stdout("\n");
        return ExitCode::OK;
    }
}
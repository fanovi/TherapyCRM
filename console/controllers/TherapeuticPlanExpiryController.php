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

        // IMPORTANTE: Elabora in ordine decrescente (90, 60, 30, 15)
        // così la logica di "notifica immediatamente precedente" funziona correttamente
        $sortedDays = $this->notificationDays;
        rsort($sortedDays);

        foreach ($sortedDays as $daysBefore) {
            $this->stdout("\nElaborazione notifiche per {$daysBefore} giorni prima della scadenza...\n");
            
            try {
                $count = $this->processNotificationsForDays($daysBefore);
                $totalNotifications += $count;
                $this->stdout("  - Inviate {$count} notifiche\n");
            } catch (\Exception $e) {
                $error = "Errore per {$daysBefore} giorni: " . $e->getMessage();
                $errors[] = $error;
                $this->stderr("  - {$error}\n");
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
     * Elabora le notifiche per un numero specifico di giorni prima della scadenza
     * 
     * @param int $daysBefore
     * @return int Numero di notifiche inviate
     * @throws \Exception
     */
    private function processNotificationsForDays($daysBefore)
    {
        // Trova il template corrispondente
        $templateCode = 'PLAN_EXPIRING_' . $daysBefore;
        $template = NotificationTemplate::findByCode($templateCode);
        
        if (!$template) {
            throw new \Exception("Template '{$templateCode}' non trovato o non attivo");
        }

        $today = date('Y-m-d');
        $maxDate = date('Y-m-d', strtotime("+{$daysBefore} days"));
        
        // Trova tutti i piani che:
        // - Sono ancora attivi (end_date > oggi)
        // - Scadono entro X giorni (end_date <= oggi + X giorni)
        $expiringPlans = TherapeuticPlan::find()
            ->where(['and',
                ['>', 'end_date', $today],
                ['<=', 'end_date', $maxDate]
            ])
            ->with(['patient', 'regime'])
            ->all();

        $notificationsSent = 0;

        foreach ($expiringPlans as $plan) {
            try {
                // Verifica se questo piano ha già ricevuto questa notifica
                $alreadySent = TherapeuticPlanNotification::find()
                    ->where([
                        'therapeutic_plan_id' => $plan->id,
                        'days_before' => $daysBefore
                    ])
                    ->exists();
                    
                if ($alreadySent) {
                    continue;
                }
                
                // Verifica se ha già ricevuto una notifica con threshold maggiore
                // Es: se stiamo valutando 60gg, verifica se ha già ricevuto 90gg
                $largerThresholds = array_filter($this->notificationDays, function($days) use ($daysBefore) {
                    return $days > $daysBefore;
                });
                
                $hasLargerNotification = false;
                foreach ($largerThresholds as $threshold) {
                    if (TherapeuticPlanNotification::find()
                        ->where([
                            'therapeutic_plan_id' => $plan->id,
                            'days_before' => $threshold
                        ])
                        ->exists()) {
                        $hasLargerNotification = true;
                        break;
                    }
                }
                
                // Se ha già una notifica con threshold maggiore, salta questa
                if ($hasLargerNotification) {
                    continue;
                }
                
                $count = $this->sendNotificationsForPlan($plan, $daysBefore, $template);
                $notificationsSent += $count;
            } catch (\Exception $e) {
                Yii::error("Errore invio notifiche per piano {$plan->id}: " . $e->getMessage(), __METHOD__);
                // Continua con il prossimo piano
            }
        }

        return $notificationsSent;
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
        
        $sortedDays = $this->notificationDays;
        rsort($sortedDays);
        
        foreach ($sortedDays as $daysBefore) {
            $today = date('Y-m-d');
            $maxDate = date('Y-m-d', strtotime("+{$daysBefore} days"));
            
            $this->stdout("\n--- NOTIFICHE {$daysBefore} GIORNI ---\n");
            $this->stdout("Piani che scadono entro il {$maxDate}:\n");
            
            // Trova tutti i piani eleggibili
            $eligiblePlans = TherapeuticPlan::find()
                ->where(['and',
                    ['>', 'end_date', $today],
                    ['<=', 'end_date', $maxDate]
                ])
                ->with(['patient', 'regime'])
                ->orderBy(['end_date' => SORT_ASC])
                ->all();
                
            if (empty($eligiblePlans)) {
                $this->stdout("  - Nessun piano eleggibile\n");
            } else {
                foreach ($eligiblePlans as $plan) {
                    $daysRemaining = (new \DateTime($plan->end_date))->diff(new \DateTime())->days;
                    
                    // Verifica se ha già questa notifica
                    $hasThisNotification = TherapeuticPlanNotification::find()
                        ->where([
                            'therapeutic_plan_id' => $plan->id,
                            'days_before' => $daysBefore
                        ])
                        ->exists();
                    
                    // Verifica se ha notifiche con threshold maggiore
                    $largerNotifications = TherapeuticPlanNotification::find()
                        ->where(['and',
                            ['therapeutic_plan_id' => $plan->id],
                            ['>', 'days_before', $daysBefore]
                        ])
                        ->select(['days_before'])
                        ->column();
                    
                    $willSend = !$hasThisNotification && empty($largerNotifications);
                    
                    $this->stdout("  - Piano ID {$plan->id}: {$plan->patient->getFullName()}\n");
                    $this->stdout("    Scade il: {$plan->end_date} (tra {$daysRemaining} giorni)\n");
                    $this->stdout("    Regime: {$plan->getRegimeName()}\n");
                    $this->stdout("    Notifica {$daysBefore}gg già inviata: " . ($hasThisNotification ? "SÌ" : "NO") . "\n");
                    
                    if (!empty($largerNotifications)) {
                        $this->stdout("    Notifiche con threshold maggiore già inviate: " . implode(', ', $largerNotifications) . "gg\n");
                    }
                    
                    $this->stdout("    INVIERÀ NOTIFICA: " . ($willSend ? "SÌ" : "NO") . "\n");
                    
                    if ($willSend) {
                        // Conta gli account che riceverebbero la notifica
                        $accountCount = AccountPatient::find()
                            ->where([
                                'patient_id' => $plan->patient->id,
                                'has_parental_authority' => 1
                            ])
                            ->count();
                            
                        $this->stdout("    Account da notificare: {$accountCount}\n");
                        $this->stdout("    Messaggio mostrerà: \"scadrà tra {$daysRemaining} giorni\"\n");
                    }
                }
            }
        }
        
        $this->stdout("\n");
        return ExitCode::OK;
    }
}
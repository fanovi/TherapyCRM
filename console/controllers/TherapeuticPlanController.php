<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use common\models\TherapeuticPlan;

/**
 * Controller per aggiornamento automatico stati piani terapeutici
 * 
 * Utilizzo:
 * php yii therapeutic-plan/update-status
 * php yii therapeutic-plan/update-status --verbose=1
 * php yii therapeutic-plan/update-status --dry-run=1
 */
class TherapeuticPlanController extends Controller
{
    /**
     * @var bool Modalità verbose per output dettagliato
     */
    public $verbose = false;
    
    /**
     * @var bool Modalità dry-run (simula senza salvare)
     */
    public $dryRun = false;
    
    /**
     * {@inheritdoc}
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'verbose',
            'dryRun',
        ]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
            'v' => 'verbose',
            'd' => 'dryRun',
        ]);
    }
    
    /**
     * Aggiorna automaticamente lo stato dei piani terapeutici basandosi sulle date
     * Da eseguire quotidianamente via cronjob (consigliato: ore 00:30)
     * 
     * @return int Exit code
     */
    public function actionUpdateStatus()
    {
        $this->stdout("=== Aggiornamento stati piani terapeutici ===\n", Console::BOLD);
        $this->stdout("Data riferimento: " . date('Y-m-d H:i:s') . "\n\n");
        
        if ($this->dryRun) {
            $this->stdout("MODALITÀ DRY-RUN ATTIVA - Nessuna modifica verrà salvata\n\n", Console::FG_YELLOW);
        }
        
        $currentDate = date('Y-m-d');
        $counters = [
            'pending_to_active' => 0,
            'active_to_expired' => 0,
            'draft_to_pending' => 0,
            'errors' => 0,
            'total_processed' => 0,
        ];
        
        try {
            // 1. DRAFT → PENDING: Piani approvati ma non ancora iniziati
            $this->stdout("1. Controllo piani DRAFT da attivare come PENDING...\n", Console::BOLD);
            $draftPlans = TherapeuticPlan::find()
                ->where(['status' => 'draft'])
                ->andWhere(['not', ['approval_date' => null]])
                ->andWhere(['>', 'start_date', $currentDate])
                ->all();
            
            foreach ($draftPlans as $plan) {
                $this->processStatusChange(
                    $plan, 
                    'pending', 
                    "Piano #{$plan->id} (Paziente: {$plan->patient->getFullName()}): DRAFT → PENDING",
                    $counters,
                    'draft_to_pending'
                );
            }
            
            // 2. PENDING → ACTIVE: Piani che iniziano oggi o sono già iniziati
            $this->stdout("\n2. Controllo piani PENDING da attivare...\n", Console::BOLD);
            $pendingPlans = TherapeuticPlan::find()
                ->where(['status' => 'pending'])
                ->andWhere(['<=', 'start_date', $currentDate])
                ->andWhere(['>=', 'end_date', $currentDate])
                ->all();
            
            foreach ($pendingPlans as $plan) {
                $this->processStatusChange(
                    $plan, 
                    'active', 
                    "Piano #{$plan->id} (Paziente: {$plan->patient->getFullName()}): PENDING → ACTIVE",
                    $counters,
                    'pending_to_active'
                );
            }
            
            // 3. ACTIVE → EXPIRED: Piani scaduti
            $this->stdout("\n3. Controllo piani ACTIVE scaduti...\n", Console::BOLD);
            $activePlans = TherapeuticPlan::find()
                ->where(['status' => 'active'])
                ->andWhere(['<', 'end_date', $currentDate])
                ->all();
            
            foreach ($activePlans as $plan) {
                $this->processStatusChange(
                    $plan, 
                    'expired', 
                    "Piano #{$plan->id} (Paziente: {$plan->patient->getFullName()}): ACTIVE → EXPIRED (scaduto il {$plan->end_date})",
                    $counters,
                    'active_to_expired'
                );
            }
            
            // 4. Controllo piani SUSPENDED che dovrebbero essere expired
            $this->stdout("\n4. Controllo piani SUSPENDED scaduti...\n", Console::BOLD);
            $suspendedPlans = TherapeuticPlan::find()
                ->where(['status' => 'suspended'])
                ->andWhere(['<', 'end_date', $currentDate])
                ->all();
            
            if (count($suspendedPlans) > 0) {
                $this->stdout("Trovati " . count($suspendedPlans) . " piani sospesi che sono scaduti.\n", Console::FG_YELLOW);
                $this->stdout("Questi piani rimangono in stato SUSPENDED fino a decisione manuale.\n", Console::FG_YELLOW);
                
                if ($this->verbose) {
                    foreach ($suspendedPlans as $plan) {
                        $this->stdout(
                            "  - Piano #{$plan->id} (Paziente: {$plan->patient->getFullName()}) - " .
                            "Sospeso dal: {$plan->suspension_date}, Scaduto il: {$plan->end_date}\n"
                        );
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->stderr("ERRORE CRITICO: " . $e->getMessage() . "\n", Console::FG_RED);
            Yii::error("Errore cronjob therapeutic-plan/update-status: " . $e->getMessage(), 'cronjob');
            return ExitCode::UNSPECIFIED_ERROR;
        }
        
        // Riepilogo
        $this->stdout("\n" . str_repeat("=", 50) . "\n", Console::BOLD);
        $this->stdout("RIEPILOGO ESECUZIONE\n", Console::BOLD);
        $this->stdout(str_repeat("=", 50) . "\n", Console::BOLD);
        
        $this->stdout("Piani processati totali: {$counters['total_processed']}\n");
        $this->stdout("  - DRAFT → PENDING: ", Console::FG_CYAN);
        $this->stdout("{$counters['draft_to_pending']}\n");
        $this->stdout("  - PENDING → ACTIVE: ", Console::FG_GREEN);
        $this->stdout("{$counters['pending_to_active']}\n");
        $this->stdout("  - ACTIVE → EXPIRED: ", Console::FG_YELLOW);
        $this->stdout("{$counters['active_to_expired']}\n");
        
        if ($counters['errors'] > 0) {
            $this->stdout("  - Errori: ", Console::FG_RED);
            $this->stdout("{$counters['errors']}\n");
        }
        
        if ($this->dryRun) {
            $this->stdout("\nMODALITÀ DRY-RUN - Nessuna modifica salvata\n", Console::FG_YELLOW);
        }
        
        // Log nel sistema
        if (!$this->dryRun) {
            Yii::info(
                "Cronjob therapeutic-plan/update-status completato. " .
                "Processati: {$counters['total_processed']}, " .
                "Draft→Pending: {$counters['draft_to_pending']}, " .
                "Pending→Active: {$counters['pending_to_active']}, " .
                "Active→Expired: {$counters['active_to_expired']}, " .
                "Errori: {$counters['errors']}",
                'cronjob'
            );
        }
        
        return ExitCode::OK;
    }
    
    /**
     * Processa il cambio di stato di un piano
     * 
     * @param TherapeuticPlan $plan
     * @param string $newStatus
     * @param string $message
     * @param array $counters
     * @param string $counterKey
     */
    private function processStatusChange($plan, $newStatus, $message, &$counters, $counterKey)
    {
        $counters['total_processed']++;
        
        if ($this->verbose) {
            $this->stdout("  - $message\n");
        }
        
        if (!$this->dryRun) {
            $plan->status = $newStatus;
            
            // Pulisci campi sospensione se necessario
            if ($newStatus !== 'suspended' && $plan->status === 'suspended') {
                $plan->suspension_date = null;
                $plan->suspension_reason = null;
            }
            
            if ($plan->save(false)) {
                $counters[$counterKey]++;
                
                if ($this->verbose) {
                    $this->stdout("    ✓ Aggiornato con successo\n", Console::FG_GREEN);
                }
            } else {
                $counters['errors']++;
                $this->stderr("    ✗ Errore nel salvataggio\n", Console::FG_RED);
                
                // Log errore dettagliato
                Yii::error(
                    "Impossibile aggiornare stato piano #{$plan->id}: " . 
                    json_encode($plan->getErrors()),
                    'cronjob'
                );
            }
        } else {
            $counters[$counterKey]++;
            if ($this->verbose) {
                $this->stdout("    [DRY-RUN] Sarebbe stato aggiornato\n", Console::FG_YELLOW);
            }
        }
    }
    
    /**
     * Mostra statistiche sugli stati attuali dei piani
     * 
     * @return int Exit code
     */
    public function actionStats()
    {
        $this->stdout("=== Statistiche Stati Piani Terapeutici ===\n", Console::BOLD);
        
        $stats = TherapeuticPlan::find()
            ->select(['status', 'COUNT(*) as count'])
            ->groupBy('status')
            ->asArray()
            ->all();
        
        $total = 0;
        foreach ($stats as $stat) {
            $total += $stat['count'];
        }
        
        $this->stdout("\nDistribuzione stati:\n");
        foreach ($stats as $stat) {
            $percentage = $total > 0 ? round(($stat['count'] / $total) * 100, 1) : 0;
            $color = $this->getStatusColor($stat['status']);
            
            $this->stdout(sprintf(
                "  %-12s: %4d (%5.1f%%)\n",
                strtoupper($stat['status']),
                $stat['count'],
                $percentage
            ), $color);
        }
        
        $this->stdout("\nTotale piani: $total\n", Console::BOLD);
        
        // Piani che necessitano attenzione
        $this->stdout("\n=== Piani che necessitano attenzione ===\n", Console::BOLD);
        
        $expiredNotCompleted = TherapeuticPlan::find()
            ->where(['status' => 'expired'])
            ->count();
        
        if ($expiredNotCompleted > 0) {
            $this->stdout("- Piani scaduti da gestire: $expiredNotCompleted\n", Console::FG_YELLOW);
        }
        
        $suspended = TherapeuticPlan::find()
            ->where(['status' => 'suspended'])
            ->count();
        
        if ($suspended > 0) {
            $this->stdout("- Piani sospesi: $suspended\n", Console::FG_YELLOW);
        }
        
        return ExitCode::OK;
    }
    
    /**
     * Ottieni colore per lo stato
     * 
     * @param string $status
     * @return int
     */
    private function getStatusColor($status)
    {
        switch ($status) {
            case 'active':
                return Console::FG_GREEN;
            case 'pending':
                return Console::FG_CYAN;
            case 'draft':
                return Console::FG_GREY;
            case 'suspended':
                return Console::FG_YELLOW;
            case 'expired':
            case 'terminated':
                return Console::FG_RED;
            case 'completed':
                return Console::FG_BLUE;
            default:
                return Console::FG_GREY;
        }
    }
}
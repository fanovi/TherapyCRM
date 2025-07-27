<?php

namespace console\controllers;
    
    use Yii;
    use yii\console\Controller;
    use yii\console\ExitCode;
    use common\models\Appointment;
    use yii\db\Expression;
    use yii\helpers\Console;
    
    /**
     * Gestisce il completamento automatico degli appuntamenti
     * 
     * @author Your Name
     */
    class AppointmentController extends Controller
    {
        /**
         * @var int Numero di record da processare per batch
         */
        public $batchSize = 100;
        
        /**
         * @var bool Modalità verbose per debug
         */
        public $verbose = false;
        
        /**
         * {@inheritdoc}
         */
        public function options($actionID)
        {
            return array_merge(parent::options($actionID), [
                'batchSize',
                'verbose'
            ]);
        }
        
        /**
         * {@inheritdoc}
         */
        public function optionAliases()
        {
            return array_merge(parent::optionAliases(), [
                'b' => 'batchSize',
                'v' => 'verbose'
            ]);
        }
        
        /**
         * Completa automaticamente gli appuntamenti terminati
         * 
         * Questo comando trova tutti gli appuntamenti con status 'scheduled'
         * che sono terminati (appointment_datetime + duration_minutes < now)
         * e li marca come 'completed'.
         * 
         * Utilizzo:
         * ```
         * php yii appointment/auto-complete
         * php yii appointment/auto-complete --batchSize=200
         * php yii appointment/auto-complete -v
         * ```
         * 
         * @return int Exit code
         */
        public function actionAutoComplete()
        {
            $startTime = microtime(true);
            $processedCount = 0;
            $errorCount = 0;
            
            $this->stdout("=== Completamento automatico appuntamenti ===\n", Console::FG_CYAN);
            $this->stdout("Inizio processo: " . date('Y-m-d H:i:s') . "\n");
            
            try {
                // Query ottimizzata con indici
                // Usa SQL diretto per le date per evitare conversioni PHP
                $currentDateTime = date('Y-m-d H:i:s');
                
                // Conta totale appuntamenti da processare
                $totalCount = Appointment::find()
                    ->where(['status' => Appointment::STATUS_SCHEDULED])
                    ->andWhere([
                        '<', 
                        new Expression('DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)'),
                        $currentDateTime
                    ])
                    ->count();
                
                if ($totalCount == 0) {
                    $this->stdout("Nessun appuntamento da completare.\n", Console::FG_GREEN);
                    return ExitCode::OK;
                }
                
                $this->stdout("Trovati {$totalCount} appuntamenti da completare.\n", Console::FG_YELLOW);
                
                // Processa in batch per ottimizzare memoria e performance
                $offset = 0;
                
                while ($offset < $totalCount) {
                    // Recupera batch di appuntamenti
                    $appointments = Appointment::find()
                        ->where(['status' => Appointment::STATUS_SCHEDULED])
                        ->andWhere([
                            '<', 
                            new Expression('DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)'),
                            $currentDateTime
                        ])
                        ->limit($this->batchSize)
                        ->offset($offset)
                        ->all();
                    
                    // Se non ci sono più record, esci dal loop
                    if (empty($appointments)) {
                        break;
                    }
                    
                    // Processa batch
                    foreach ($appointments as $appointment) {
                        try {
                            $endTime = date('Y-m-d H:i:s', strtotime($appointment->appointment_datetime . ' +' . $appointment->duration_minutes . ' minutes'));
                            
                            if ($this->verbose) {
                                $this->stdout(
                                    sprintf(
                                        "  - Appuntamento #%d: %s + %d min = %s (Paziente: %s, Terapista: %s)... ",
                                        $appointment->id,
                                        $appointment->appointment_datetime,
                                        $appointment->duration_minutes,
                                        $endTime,
                                        $appointment->patient ? $appointment->patient->first_name . ' ' . $appointment->patient->last_name : 'N/A',
                                        $appointment->therapist ? $appointment->therapist->user->profile->first_name . ' ' . $appointment->therapist->user->profile->last_name : 'N/A'
                                    )
                                );
                            }
                            
                            // Marca come completato
                            $appointment->status = Appointment::STATUS_COMPLETED;
                            
                            // Disabilita behaviors non necessari per performance
                            $appointment->detachBehavior('timestamp');
                            $appointment->detachBehavior('activityLog');
                            
                            // Salva solo il campo status per efficienza
                            if ($appointment->save(false, ['status', 'updated_at'])) {
                                $processedCount++;
                                
                                if ($this->verbose) {
                                    $this->stdout("OK\n", Console::FG_GREEN);
                                }
                            } else {
                                $errorCount++;
                                if ($this->verbose) {
                                    $this->stdout("ERRORE\n", Console::FG_RED);
                                    $this->stdout("    Errori: " . json_encode($appointment->getErrors()) . "\n", Console::FG_RED);
                                }
                            }
                            
                        } catch (\Exception $e) {
                            $errorCount++;
                            Yii::error("Errore completamento appuntamento #{$appointment->id}: " . $e->getMessage(), __METHOD__);
                            
                            if ($this->verbose) {
                                $this->stdout("ERRORE\n", Console::FG_RED);
                                $this->stdout("    Eccezione: " . $e->getMessage() . "\n", Console::FG_RED);
                            }
                        }
                    }
                    
                    // Progress indicator
                    if (!$this->verbose && $processedCount > 0 && $processedCount % 10 == 0) {
                        $this->stdout(".");
                        if ($processedCount % 100 == 0) {
                            $percentage = round(($processedCount / $totalCount) * 100);
                            $this->stdout(" {$processedCount}/{$totalCount} ({$percentage}%)\n");
                        }
                    }
                    
                    // Incrementa offset
                    $offset += $this->batchSize;
                    
                    // Libera memoria
                    unset($appointments);
                    
                    // Piccola pausa per non sovraccaricare il DB
                    usleep(100000); // 0.1 secondi
                }
                
                if (!$this->verbose && $processedCount > 0) {
                    $this->stdout("\n");
                }
                
                // Report finale
                $executionTime = round(microtime(true) - $startTime, 2);
                
                $this->stdout("\n=== Riepilogo ===\n", Console::FG_CYAN);
                $this->stdout("Appuntamenti completati: {$processedCount}\n", Console::FG_GREEN);
                if ($errorCount > 0) {
                    $this->stdout("Errori: {$errorCount}\n", Console::FG_RED);
                }
                $this->stdout("Tempo di esecuzione: {$executionTime} secondi\n");
                $this->stdout("Fine processo: " . date('Y-m-d H:i:s') . "\n");
                
                // Log per monitoraggio
                Yii::info("Auto-complete appuntamenti completato: {$processedCount} completati, {$errorCount} errori in {$executionTime}s", __METHOD__);
                
                return $errorCount > 0 ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
                
            } catch (\Exception $e) {
                $this->stdout("\nERRORE CRITICO: " . $e->getMessage() . "\n", Console::FG_RED);
                Yii::error("Errore critico in auto-complete appuntamenti: " . $e->getMessage(), __METHOD__);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }
        
        /**
         * Mostra statistiche sugli appuntamenti
         * 
         * Utile per monitorare lo stato del sistema
         * 
         * @return int Exit code
         */
        public function actionStats()
        {
            $this->stdout("=== Statistiche Appuntamenti ===\n", Console::FG_CYAN);
            
            $stats = Appointment::find()
                ->select(['status', 'COUNT(*) as count'])
                ->groupBy('status')
                ->asArray()
                ->all();
            
            $this->stdout("\nAppuntamenti per stato:\n");
            foreach ($stats as $stat) {
                $statusLabel = Appointment::getStatusLabels()[$stat['status']] ?? $stat['status'];
                $this->stdout(sprintf("  %-25s: %d\n", $statusLabel, $stat['count']));
            }
            
            // Appuntamenti da completare
            $toComplete = Appointment::find()
                ->where(['status' => Appointment::STATUS_SCHEDULED])
                ->andWhere([
                    '<', 
                    new Expression('DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)'),
                    date('Y-m-d H:i:s')
                ])
                ->count();
            
            $this->stdout("\nAppuntamenti da completare automaticamente: {$toComplete}\n", Console::FG_YELLOW);
            
            // Appuntamenti futuri
            $future = Appointment::find()
                ->where(['status' => Appointment::STATUS_SCHEDULED])
                ->andWhere(['>', 'appointment_datetime', date('Y-m-d H:i:s')])
                ->count();
            
            $this->stdout("Appuntamenti futuri programmati: {$future}\n", Console::FG_GREEN);
            
            return ExitCode::OK;
        }
    
    
}
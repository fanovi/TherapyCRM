<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use common\models\ActivityLog;
use common\helpers\ActivityLogHelper;

/**
 * Controller console per la gestione dei log delle attività
 */
class ActivityLogController extends Controller
{
    /**
     * Pulizia dei log più vecchi di X mesi
     * @param int $months Numero di mesi (default: 6)
     * @return int
     */
    public function actionCleanup($months = 6)
    {
        $months = (int) $months;
        
        if ($months < 1) {
            $this->stdout("Errore: Il numero di mesi deve essere almeno 1.\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $this->stdout("Iniziando la pulizia dei log più vecchi di {$months} mesi...\n", Console::FG_YELLOW);

        // Calcola la data limite
        $dateLimit = date('Y-m-d H:i:s', strtotime("-{$months} months"));
        
        $this->stdout("Data limite: {$dateLimit}\n", Console::FG_CYAN);

        // Conta i log da eliminare
        $count = ActivityLog::find()
            ->where(['<', 'created_at', $dateLimit])
            ->count();

        if ($count == 0) {
            $this->stdout("Nessun log da eliminare.\n", Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout("Trovati {$count} log da eliminare.\n", Console::FG_YELLOW);

        // Conferma dell'utente
        if (!$this->confirm("Procedere con l'eliminazione?")) {
            $this->stdout("Operazione annullata.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        // Elimina i log
        try {
            $deleted = ActivityLog::deleteAll(['<', 'created_at', $dateLimit]);
            
            $this->stdout("Eliminati {$deleted} log con successo.\n", Console::FG_GREEN);
            
            // Log dell'operazione
            Yii::info("Cleanup activity log: eliminati {$deleted} record più vecchi di {$months} mesi", __METHOD__);
            
            return ExitCode::OK;
            
        } catch (\Exception $e) {
            $this->stdout("Errore durante l'eliminazione: " . $e->getMessage() . "\n", Console::FG_RED);
            Yii::error("Errore cleanup activity log: " . $e->getMessage(), __METHOD__);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Mostra statistiche sui log delle attività
     * @param int $days Numero di giorni per le statistiche (default: 30)
     * @return int
     */
    public function actionStats($days = 30)
    {
        $days = (int) $days;
        
        if ($days < 1) {
            $this->stdout("Errore: Il numero di giorni deve essere almeno 1.\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $dateTo = date('Y-m-d H:i:s');

        $this->stdout("Statistiche Activity Log - Ultimi {$days} giorni\n", Console::FG_CYAN);
        $this->stdout("=====================================\n", Console::FG_CYAN);
        $this->stdout("Periodo: {$dateFrom} - {$dateTo}\n\n", Console::FG_YELLOW);

        // Totale log
        $total = ActivityLog::find()
            ->where(['between', 'created_at', $dateFrom, $dateTo])
            ->count();

        $this->stdout("Totale log: {$total}\n\n", Console::FG_GREEN);

        if ($total == 0) {
            $this->stdout("Nessun log nel periodo specificato.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        // Statistiche per azione
        $this->stdout("Per Azione:\n", Console::FG_CYAN);
        $actionStats = ActivityLog::find()
            ->select(['action', 'COUNT(*) as count'])
            ->where(['between', 'created_at', $dateFrom, $dateTo])
            ->groupBy('action')
            ->asArray()
            ->all();

        foreach ($actionStats as $stat) {
            $actionLabel = [
                ActivityLog::ACTION_CREATE => 'Creazioni',
                ActivityLog::ACTION_UPDATE => 'Modifiche',
                ActivityLog::ACTION_DELETE => 'Eliminazioni',
            ][$stat['action']] ?? $stat['action'];
            
            $percentage = round(($stat['count'] / $total) * 100, 1);
            $this->stdout("  {$actionLabel}: {$stat['count']} ({$percentage}%)\n", Console::FG_GREY);
        }

        // Statistiche per entità
        $this->stdout("\nPer Entità (Top 10):\n", Console::FG_CYAN);
        $entityStats = ActivityLog::find()
            ->select(['entity_name', 'COUNT(*) as count'])
            ->where(['between', 'created_at', $dateFrom, $dateTo])
            ->groupBy('entity_name')
            ->orderBy('count DESC')
            ->limit(10)
            ->asArray()
            ->all();

        foreach ($entityStats as $stat) {
            $entityLabel = ActivityLogHelper::getEntityLabel($stat['entity_name']);
            $percentage = round(($stat['count'] / $total) * 100, 1);
            $this->stdout("  {$entityLabel}: {$stat['count']} ({$percentage}%)\n", Console::FG_GREY);
        }

        // Statistiche per utente
        $this->stdout("\nPer Utente (Top 10):\n", Console::FG_CYAN);
        $userStats = ActivityLog::find()
            ->select(['user_id', 'COUNT(*) as count'])
            ->with('user')
            ->where(['between', 'created_at', $dateFrom, $dateTo])
            ->groupBy('user_id')
            ->orderBy('count DESC')
            ->limit(10)
            ->all();

        foreach ($userStats as $log) {
            $username = $log->user ? $log->user->username : 'Utente #' . $log->user_id;
            $percentage = round(($log->count / $total) * 100, 1);
            $this->stdout("  {$username}: {$log->count} ({$percentage}%)\n", Console::FG_GREY);
        }

        return ExitCode::OK;
    }

    /**
     * Esporta i log in un file CSV
     * @param string $filename Nome del file di output
     * @param int $days Numero di giorni da esportare (default: 30)
     * @return int
     */
    public function actionExport($filename = null, $days = 30)
    {
        $days = (int) $days;
        
        if ($days < 1) {
            $this->stdout("Errore: Il numero di giorni deve essere almeno 1.\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }

        if (!$filename) {
            $filename = 'activity_log_' . date('Y-m-d_H-i-s') . '.csv';
        }

        // Assicurati che il file abbia estensione .csv
        if (!str_ends_with($filename, '.csv')) {
            $filename .= '.csv';
        }

        $dateFrom = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $dateTo = date('Y-m-d H:i:s');

        $this->stdout("Esportazione log degli ultimi {$days} giorni in {$filename}...\n", Console::FG_YELLOW);

        // Recupera i log
        $logs = ActivityLog::find()
            ->with('user')
            ->where(['between', 'created_at', $dateFrom, $dateTo])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        if (empty($logs)) {
            $this->stdout("Nessun log da esportare nel periodo specificato.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        try {
            $file = fopen($filename, 'w');
            
            // Intestazioni CSV
            fputcsv($file, [
                'ID',
                'Data/Ora',
                'Utente',
                'Azione',
                'Entità',
                'ID Entità',
                'Modifiche',
                'IP Address',
                'User Agent'
            ]);

            // Dati
            foreach ($logs as $log) {
                $changes = '';
                if ($log->action === ActivityLog::ACTION_UPDATE) {
                    $changesArray = ActivityLogHelper::formatChanges(
                        $log->getOldValuesArray(),
                        $log->getNewValuesArray()
                    );
                    $changes = implode('; ', array_map('strip_tags', $changesArray));
                }

                fputcsv($file, [
                    $log->id,
                    $log->created_at,
                    $log->getUserName(),
                    $log->getActionDescription(),
                    ActivityLogHelper::getEntityLabel($log->entity_name),
                    $log->entity_id,
                    $changes,
                    $log->ip_address,
                    $log->user_agent
                ]);
            }

            fclose($file);
            
            $count = count($logs);
            $this->stdout("Esportati {$count} log in {$filename} con successo.\n", Console::FG_GREEN);
            
            return ExitCode::OK;
            
        } catch (\Exception $e) {
            $this->stdout("Errore durante l'esportazione: " . $e->getMessage() . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Verifica l'integrità dei log
     * @return int
     */
    public function actionVerify()
    {
        $this->stdout("Verifica integrità dei log...\n", Console::FG_YELLOW);

        $issues = [];

        // Verifica JSON malformati
        $logsWithBadJson = ActivityLog::find()
            ->where(['or',
                ['and', ['IS NOT', 'old_values', null], ['not like', 'old_values', '{%']],
                ['and', ['IS NOT', 'new_values', null], ['not like', 'new_values', '{%']]
            ])
            ->all();

        foreach ($logsWithBadJson as $log) {
            $oldValues = $log->getOldValuesArray();
            $newValues = $log->getNewValuesArray();
            
            if (empty($oldValues) && !empty($log->old_values)) {
                $issues[] = "Log #{$log->id}: old_values JSON malformato";
            }
            
            if (empty($newValues) && !empty($log->new_values)) {
                $issues[] = "Log #{$log->id}: new_values JSON malformato";
            }
        }

        // Verifica utenti inesistenti
        $logsWithMissingUsers = ActivityLog::find()
            ->leftJoin('{{%user}}', '{{%activity_log}}.user_id = {{%user}}.id')
            ->where(['IS', '{{%user}}.id', null])
            ->all();

        foreach ($logsWithMissingUsers as $log) {
            $issues[] = "Log #{$log->id}: utente #{$log->user_id} non esiste";
        }

        if (empty($issues)) {
            $this->stdout("Nessun problema rilevato.\n", Console::FG_GREEN);
        } else {
            $this->stdout("Problemi rilevati:\n", Console::FG_RED);
            foreach ($issues as $issue) {
                $this->stdout("  - {$issue}\n", Console::FG_RED);
            }
        }

        return ExitCode::OK;
    }
} 
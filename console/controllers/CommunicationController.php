<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use common\helpers\CommunicationHelper;
use common\models\Notification;
use common\models\User;

/**
 * Controller console per gestire le comunicazioni interne del gestionale
 * 
 * Esempi di utilizzo:
 * ./yii communication/test-send - Test di invio comunicazione
 * ./yii communication/stats - Mostra statistiche comunicazioni
 * ./yii communication/cleanup - Pulizia comunicazioni vecchie
 */
class CommunicationController extends Controller
{
    public $defaultAction = 'help';

    /**
     * Mostra l'help dei comandi disponibili
     */
    public function actionHelp()
    {
        $this->stdout("=== SISTEMA COMUNICAZIONI INTERNE ===\n\n", Console::BOLD);
        
        $this->stdout("Comandi disponibili:\n", Console::FG_GREEN);
        $this->stdout("  test-send          Test di invio comunicazione\n");
        $this->stdout("  stats              Mostra statistiche comunicazioni\n");
        $this->stdout("  cleanup            Pulizia comunicazioni vecchie\n");
        $this->stdout("  send-to-role       Invia comunicazione a tutti gli utenti di un ruolo\n");
        $this->stdout("  demo-notifications Crea comunicazioni di esempio\n\n");
        
        $this->stdout("Esempio di utilizzo:\n", Console::FG_YELLOW);
        $this->stdout("  ./yii communication/test-send\n");
        $this->stdout("  ./yii communication/stats\n");
        $this->stdout("  ./yii communication/send-to-role admin \"Titolo\" \"Messaggio\"\n\n");
        
        return ExitCode::OK;
    }

    /**
     * Test di invio comunicazione
     */
    public function actionTestSend()
    {
        $this->stdout("=== TEST INVIO COMUNICAZIONE ===\n", Console::BOLD);
        
        // Trova il primo admin disponibile
        $auth = Yii::$app->authManager;
        $adminIds = $auth->getUserIdsByRole('admin');
        
        if (empty($adminIds)) {
            $this->stderr("Errore: Nessun admin trovato nel sistema\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        
        $adminId = reset($adminIds);
        $admin = User::findOne($adminId);
        
        $this->stdout("Invio comunicazione di test a: {$admin->username} (ID: {$adminId})\n");
        
        $result = CommunicationHelper::sendCommunication(
            $adminId,
            'Test Comunicazione Sistema',
            "Questa è una comunicazione di test generata automaticamente dal sistema.\n\nData e ora: " . date('d/m/Y H:i:s'),
            null, // Sistema
            Notification::TYPE_INTERNAL_COMMUNICATION
        );
        
        if ($result['success']) {
            $this->stdout("✓ Comunicazione inviata con successo!\n", Console::FG_GREEN);
            $this->stdout("ID Notifica: {$result['results'][0]['notification_id']}\n");
        } else {
            $this->stderr("✗ Errore nell'invio della comunicazione\n", Console::FG_RED);
            $this->stderr("Dettagli: " . print_r($result, true) . "\n");
        }
        
        return ExitCode::OK;
    }

    /**
     * Mostra statistiche delle comunicazioni
     */
    public function actionStats($days = 30)
    {
        $this->stdout("=== STATISTICHE COMUNICAZIONI (ultimi $days giorni) ===\n", Console::BOLD);
        
        $stats = CommunicationHelper::getStatistics($days);
        
        $this->stdout("\n");
        $this->stdout("Totale inviate:      {$stats['total_sent']}\n", Console::FG_CYAN);
        $this->stdout("Totale lette:        {$stats['total_read']}\n", Console::FG_GREEN);
        $this->stdout("Totale non lette:    {$stats['total_unread']}\n", Console::FG_YELLOW);
        $this->stdout("Percentuale lettura: {$stats['read_percentage']}%\n", Console::FG_PURPLE);
        
        // Statistiche per tipo
        $this->stdout("\n--- Dettaglio per tipo ---\n", Console::FG_BLUE);
        
        $typeStats = Notification::find()
            ->select(['notification_type', 'COUNT(*) as count'])
            ->where(['>=', 'created_at', date('Y-m-d H:i:s', strtotime("-$days days"))])
            ->groupBy('notification_type')
            ->asArray()
            ->all();
            
        foreach ($typeStats as $stat) {
            $typeLabel = Notification::getTypeOptions()[$stat['notification_type']] ?? $stat['notification_type'];
            $this->stdout("  {$typeLabel}: {$stat['count']}\n");
        }
        
        return ExitCode::OK;
    }

    /**
     * Pulizia comunicazioni vecchie
     */
    public function actionCleanup($days = 90, $dryRun = true)
    {
        $this->stdout("=== PULIZIA COMUNICAZIONI VECCHIE ===\n", Console::BOLD);
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$days days"));
        $this->stdout("Rimozione comunicazioni più vecchie di: $cutoffDate\n");
        
        $query = Notification::find()
            ->where(['<', 'created_at', $cutoffDate])
            ->andWhere(['notification_type' => Notification::TYPE_INTERNAL_COMMUNICATION]);
            
        $count = $query->count();
        
        if ($count === 0) {
            $this->stdout("Nessuna comunicazione da rimuovere.\n", Console::FG_GREEN);
            return ExitCode::OK;
        }
        
        $this->stdout("Trovate $count comunicazioni da rimuovere.\n", Console::FG_YELLOW);
        
        if ($dryRun === true || $dryRun === 'true') {
            $this->stdout("*** MODALITÀ DRY-RUN - Nessuna modifica effettuata ***\n", Console::FG_PURPLE);
            $this->stdout("Per eseguire realmente la pulizia: --dryRun=false\n");
        } else {
            if ($this->confirm("Sei sicuro di voler eliminare $count comunicazioni?")) {
                $deleted = $query->all();
                foreach ($deleted as $notification) {
                    $notification->delete();
                }
                $this->stdout("✓ Eliminate $count comunicazioni.\n", Console::FG_GREEN);
            } else {
                $this->stdout("Operazione annullata.\n");
            }
        }
        
        return ExitCode::OK;
    }

    /**
     * Invia comunicazione a tutti gli utenti di un ruolo
     */
    public function actionSendToRole($role, $title, $message = '')
    {
        $this->stdout("=== INVIO COMUNICAZIONE A RUOLO: $role ===\n", Console::BOLD);
        
        if (empty($title)) {
            $this->stderr("Errore: Il titolo è obbligatorio\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        
        $result = CommunicationHelper::sendToRole($role, $title, $message);
        
        if ($result['success']) {
            $this->stdout("✓ Comunicazione inviata con successo!\n", Console::FG_GREEN);
            $this->stdout("Inviate: {$result['total_sent']}\n");
            $this->stdout("Errori: {$result['total_errors']}\n");
        } else {
            $this->stderr("✗ Errore nell'invio della comunicazione\n", Console::FG_RED);
            $this->stderr("Errore: {$result['error']}\n");
        }
        
        return ExitCode::OK;
    }

    /**
     * Crea comunicazioni di esempio per test
     */
    public function actionDemoNotifications()
    {
        $this->stdout("=== CREAZIONE COMUNICAZIONI DI ESEMPIO ===\n", Console::BOLD);
        
        // Trova alcuni utenti per i test
        $auth = Yii::$app->authManager;
        $adminIds = $auth->getUserIdsByRole('admin');
        $managerIds = $auth->getUserIdsByRole('manager');
        
        if (empty($adminIds)) {
            $this->stderr("Errore: Nessun admin trovato\n", Console::FG_RED);
            return ExitCode::DATAERR;
        }
        
        $recipients = array_slice($adminIds, 0, 2); // Prime 2 admin
        if (!empty($managerIds)) {
            $recipients = array_merge($recipients, array_slice($managerIds, 0, 1)); // + 1 manager
        }
        
        // Comunicazioni di esempio
        $examples = [
            [
                'title' => 'Nuovo paziente registrato',
                'message' => "Un nuovo paziente si è registrato sulla piattaforma.\n\nNome: Mario Rossi\nEmail: mario.rossi@example.com\n\nÈ necessario completare la configurazione del profilo.",
                'type' => Notification::TYPE_INTERNAL_COMMUNICATION
            ],
            [
                'title' => 'Piano terapeutico in scadenza',
                'message' => "Il piano terapeutico del paziente Giulia Bianchi scadrà tra 7 giorni.\n\nData scadenza: " . date('d/m/Y', strtotime('+7 days')) . "\n\nProgrammare il rinnovo.",
                'type' => Notification::TYPE_REMINDER
            ],
            [
                'title' => 'Richiesta documento urgente',
                'message' => "Il paziente Francesco Verdi ha richiesto urgentemente il certificato medico.\n\nMotivo: Presentazione datore di lavoro\n\nElaborare entro 48 ore.",
                'type' => Notification::TYPE_DEADLINE
            ],
            [
                'title' => 'Aggiornamento sistema completato',
                'message' => "L'aggiornamento del sistema è stato completato con successo.\n\nNuove funzionalità disponibili:\n- Sistema comunicazioni interne\n- Miglioramenti performance\n- Correzioni bug minori",
                'type' => Notification::TYPE_INFO
            ]
        ];
        
        $totalSent = 0;
        
        foreach ($examples as $i => $example) {
            $this->stdout("Creazione comunicazione " . ($i + 1) . "/4: {$example['title']}\n");
            
            $result = CommunicationHelper::sendCommunication(
                $recipients,
                $example['title'],
                $example['message'],
                null,
                $example['type']
            );
            
            if ($result['success']) {
                $totalSent += $result['total_sent'];
                $this->stdout("  ✓ Inviata a {$result['total_sent']} utenti\n", Console::FG_GREEN);
            } else {
                $this->stderr("  ✗ Errore nell'invio\n", Console::FG_RED);
            }
        }
        
        $this->stdout("\n=== RIEPILOGO ===\n", Console::BOLD);
        $this->stdout("Totale comunicazioni inviate: $totalSent\n", Console::FG_GREEN);
        $this->stdout("Le comunicazioni sono visibili nel dropdown notifiche dell'header.\n");
        
        return ExitCode::OK;
    }

    /**
     * Utility per contare le notifiche per utente
     */
    public function actionUserStats($userId = null)
    {
        if ($userId) {
            $user = User::findOne($userId);
            if (!$user) {
                $this->stderr("Utente con ID $userId non trovato\n", Console::FG_RED);
                return ExitCode::DATAERR;
            }
            
            $this->stdout("=== STATISTICHE UTENTE: {$user->username} ===\n", Console::BOLD);
            
            $total = Notification::findByUser($userId)->count();
            $unread = Notification::findByUser($userId)->andWhere(['read_at' => null])->count();
            
            $this->stdout("Totale comunicazioni: $total\n");
            $this->stdout("Non lette: $unread\n", $unread > 0 ? Console::FG_YELLOW : Console::FG_GREEN);
            
        } else {
            $this->stdout("=== STATISTICHE PER UTENTE ===\n", Console::BOLD);
            
            $userStats = Notification::find()
                ->select(['recipient_user_id', 'COUNT(*) as total', 'SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread'])
                ->joinWith('recipientUser')
                ->groupBy('recipient_user_id')
                ->orderBy(['total' => SORT_DESC])
                ->limit(10)
                ->asArray()
                ->all();
                
            $this->stdout("Top 10 utenti per numero di comunicazioni:\n\n");
            foreach ($userStats as $stat) {
                $user = User::findOne($stat['recipient_user_id']);
                $username = $user ? $user->username : "ID {$stat['recipient_user_id']}";
                $this->stdout("  $username: {$stat['total']} totali, {$stat['unread']} non lette\n");
            }
        }
        
        return ExitCode::OK;
    }
} 
<?php

namespace console\controllers;

use yii\console\Controller;
use yii\console\ExitCode;
use Yii;

/**
 * Controller per operazioni sul database
 */
class DatabaseController extends Controller
{
    /**
     * Elimina completamente tutte le tabelle dal database
     * ATTENZIONE: Questa operazione è irreversibile!
     */
    public function actionReset()
    {
        echo "⚠️  ATTENZIONE: Questa operazione eliminerà TUTTE le tabelle dal database!\n";
        echo "Questa azione è IRREVERSIBILE!\n\n";
        
        if (!$this->confirm('Sei sicuro di voler continuare?')) {
            echo "Operazione annullata.\n";
            return ExitCode::OK;
        }

        try {
            // Disabilito i foreign key checks
            Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();

            echo "\n🗑️  Inizio eliminazione di tutte le tabelle...\n\n";

            // Ottengo la lista di tutte le tabelle esistenti
            $tables = Yii::$app->db->createCommand("SHOW TABLES")->queryColumn();

            foreach ($tables as $table) {
                try {
                    Yii::$app->db->createCommand("DROP TABLE `$table`")->execute();
                    echo "✓ Tabella '$table' eliminata con successo\n";
                } catch (\Exception $e) {
                    echo "✗ Errore eliminando tabella '$table': " . $e->getMessage() . "\n";
                }
            }

            // Riabilito i foreign key checks
            Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();

            echo "\n✅ Database completamente resettato!\n";
            echo "📝 Ora puoi riapplicare tutte le migration da zero.\n\n";

            return ExitCode::OK;

        } catch (\Exception $e) {
            echo "❌ Errore durante il reset del database: " . $e->getMessage() . "\n";
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Mostra lo stato delle tabelle nel database
     */
    public function actionStatus()
    {
        try {
            $tables = Yii::$app->db->createCommand("SHOW TABLES")->queryColumn();
            
            echo "📊 Stato del database:\n\n";
            
            if (empty($tables)) {
                echo "🆕 Database vuoto - nessuna tabella presente\n";
            } else {
                echo "📋 Tabelle presenti (" . count($tables) . "):\n";
                foreach ($tables as $table) {
                    $count = Yii::$app->db->createCommand("SELECT COUNT(*) FROM `$table`")->queryScalar();
                    echo "   - $table ($count righe)\n";
                }
            }
            echo "\n";

            return ExitCode::OK;

        } catch (\Exception $e) {
            echo "❌ Errore durante la verifica del database: " . $e->getMessage() . "\n";
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Inizializza il database da zero
     */
    public function actionInit()
    {
        echo "🚀 Inizializzazione completa del database...\n\n";

        // 1. Reset del database
        echo "1️⃣ Reset del database...\n";
        $resetResult = $this->actionReset();
        if ($resetResult !== ExitCode::OK) {
            return $resetResult;
        }

        // 2. Applicazione delle migration standard
        echo "\n2️⃣ Applicazione delle migration standard...\n";
        $migrateResult = $this->runAction('', [], ['migrate']);
        
        // 3. Applicazione delle migration RBAC
        echo "\n3️⃣ Applicazione delle migration RBAC...\n";
        $rbacResult = $this->runAction('', [], ['migrate', '--migrationPath=console/migrations']);

        echo "\n✅ Inizializzazione completata!\n";
        echo "📝 Ora puoi creare gli utenti di base usando i comandi console.\n\n";

        return ExitCode::OK;
    }
} 
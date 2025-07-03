<?php

use yii\db\Migration;

/**
 * Migration per assegnare i permessi di gestione richieste documenti ai ruoli appropriati
 * 
 * PERMESSI ASSEGNATI:
 * - ADMIN: manage_documents (può impostare stati 2 e 3)
 * - MANAGER: view_documents (può impostare solo stato 4 - consegnato)
 */
class m250201_000035_assign_document_request_permissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        echo "📄 Inizio assegnazione permessi richieste documenti...\n\n";

        // Verifica che i ruoli esistano
        $adminRole = $auth->getRole('admin');
        $managerRole = $auth->getRole('manager');

        if (!$adminRole) {
            throw new \Exception('Ruolo admin non trovato. Applicare prima la migration RBAC base.');
        }

        if (!$managerRole) {
            throw new \Exception('Ruolo manager non trovato. Applicare prima la migration RBAC base.');
        }

        // Crea i permessi se non esistono
        $permissions = [
            'manage_documents' => 'Gestire documenti - Può prendere in carico e stampare richieste',
            'view_documents' => 'Visualizzare documenti - Può segnare come consegnato',
        ];

        foreach ($permissions as $name => $description) {
            $permission = $auth->getPermission($name);
            if (!$permission) {
                $permission = $auth->createPermission($name);
                $permission->description = $description;
                $auth->add($permission);
                echo "✓ Permesso '$name' creato\n";
            } else {
                echo "- Permesso '$name' già esistente\n";
            }
        }

        // Assegna permessi ai ruoli
        echo "\n👑 Assegnazione permessi ADMIN...\n";
        $manageDocsPermission = $auth->getPermission('manage_documents');
        if ($manageDocsPermission && !$auth->hasChild($adminRole, $manageDocsPermission)) {
            $auth->addChild($adminRole, $manageDocsPermission);
            echo "  ✓ manage_documents assegnato ad admin\n";
        } else {
            echo "  - manage_documents già assegnato ad admin\n";
        }

        echo "\n👨‍💼 Assegnazione permessi MANAGER...\n";
        $viewDocsPermission = $auth->getPermission('view_documents');
        if ($viewDocsPermission && !$auth->hasChild($managerRole, $viewDocsPermission)) {
            $auth->addChild($managerRole, $viewDocsPermission);
            echo "  ✓ view_documents assegnato a manager\n";
        } else {
            echo "  - view_documents già assegnato a manager\n";
        }

        echo "\n✅ Assegnazione permessi richieste documenti completata!\n";
        echo "\n📋 RIEPILOGO PERMESSI:\n";
        echo "👑 ADMIN: manage_documents - Può prendere in carico e stampare richieste (stati 2 e 3)\n";
        echo "👨‍💼 MANAGER: view_documents - Può segnare come consegnato (stato 4)\n";
        echo "\n🔒 SICUREZZA:\n";
        echo "• Le richieste con status = 1 sono considerate 'da leggere'\n";
        echo "• Admin può aggiornare stati: 1 → 2 (Presa in carico) e 2 → 3 (Stampato)\n";
        echo "• Manager può aggiornare solo: 3 → 4 (Consegnato)\n";
        echo "• Tutti i cambi di stato vengono registrati nello storico\n\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        echo "🔄 Rimozione permessi richieste documenti...\n";

        // Ottieni ruoli e permessi
        $adminRole = $auth->getRole('admin');
        $managerRole = $auth->getRole('manager');
        $manageDocsPermission = $auth->getPermission('manage_documents');
        $viewDocsPermission = $auth->getPermission('view_documents');

        // Rimuovi assegnazioni
        if ($adminRole && $manageDocsPermission && $auth->hasChild($adminRole, $manageDocsPermission)) {
            $auth->removeChild($adminRole, $manageDocsPermission);
            echo "✓ manage_documents rimosso da admin\n";
        }

        if ($managerRole && $viewDocsPermission && $auth->hasChild($managerRole, $viewDocsPermission)) {
            $auth->removeChild($managerRole, $viewDocsPermission);
            echo "✓ view_documents rimosso da manager\n";
        }

        // Rimuovi permessi (opzionale - commentato per sicurezza)
        /*
        if ($manageDocsPermission) {
            $auth->remove($manageDocsPermission);
            echo "✓ Permesso manage_documents rimosso\n";
        }

        if ($viewDocsPermission) {
            $auth->remove($viewDocsPermission);
            echo "✓ Permesso view_documents rimosso\n";
        }
        */

        echo "✅ Rimozione completata!\n";

        return true;
    }
} 
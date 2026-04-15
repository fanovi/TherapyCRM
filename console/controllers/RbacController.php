<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\AuthItem;
use common\models\AuthItemChild;
use common\models\AuthAssignment;
use common\models\User;

/**
 * Controller console per gestire RBAC (Ruoli, Permessi e Assegnazioni)
 */
class RbacController extends Controller
{
    /**
     * Crea un nuovo ruolo
     * 
     * Uso: ./yii rbac/create-role [nome] [descrizione]
     * 
     * @param string $name Nome del ruolo
     * @param string $description Descrizione del ruolo (opzionale)
     */
    public function actionCreateRole($name, $description = null)
    {
        if (empty($name)) {
            $this->stderr("Errore: Il nome del ruolo è obbligatorio.\n");
            return self::EXIT_CODE_ERROR;
        }

        $this->stdout("Creazione ruolo '{$name}'...\n");

        try {
            // Verifica se il ruolo esiste già
            $existingRole = AuthItem::findOne(['name' => $name, 'type' => AuthItem::TYPE_ROLE]);
            if ($existingRole) {
                $this->stderr("Errore: Il ruolo '{$name}' esiste già.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Crea il nuovo ruolo
            $role = new AuthItem();
            $role->name = $name;
            $role->type = AuthItem::TYPE_ROLE;
            $role->description = $description;
            $role->created_at = time();
            $role->updated_at = time();

            if ($role->save()) {
                $this->stdout("✓ Ruolo '{$name}' creato con successo!\n");
                if ($description) {
                    $this->stdout("  Descrizione: {$description}\n");
                }
                return self::EXIT_CODE_NORMAL;
            } else {
                $this->stderr("Errore nella creazione del ruolo:\n");
                foreach ($role->errors as $attribute => $errors) {
                    foreach ($errors as $error) {
                        $this->stderr("- {$attribute}: {$error}\n");
                    }
                }
                return self::EXIT_CODE_ERROR;
            }

        } catch (\Exception $e) {
            $this->stderr("Errore durante la creazione del ruolo: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Crea un nuovo permesso
     * 
     * Uso: ./yii rbac/create-permission [nome] [descrizione]
     * 
     * @param string $name Nome del permesso
     * @param string $description Descrizione del permesso (opzionale)
     */
    public function actionCreatePermission($name, $description = null)
    {
        if (empty($name)) {
            $this->stderr("Errore: Il nome del permesso è obbligatorio.\n");
            return self::EXIT_CODE_ERROR;
        }

        $this->stdout("Creazione permesso '{$name}'...\n");

        try {
            // Verifica se il permesso esiste già
            $existingPermission = AuthItem::findOne(['name' => $name, 'type' => AuthItem::TYPE_PERMISSION]);
            if ($existingPermission) {
                $this->stderr("Errore: Il permesso '{$name}' esiste già.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Crea il nuovo permesso
            $permission = new AuthItem();
            $permission->name = $name;
            $permission->type = AuthItem::TYPE_PERMISSION;
            $permission->description = $description;
            $permission->created_at = time();
            $permission->updated_at = time();

            if ($permission->save()) {
                $this->stdout("✓ Permesso '{$name}' creato con successo!\n");
                if ($description) {
                    $this->stdout("  Descrizione: {$description}\n");
                }
                return self::EXIT_CODE_NORMAL;
            } else {
                $this->stderr("Errore nella creazione del permesso:\n");
                foreach ($permission->errors as $attribute => $errors) {
                    foreach ($errors as $error) {
                        $this->stderr("- {$attribute}: {$error}\n");
                    }
                }
                return self::EXIT_CODE_ERROR;
            }

        } catch (\Exception $e) {
            $this->stderr("Errore durante la creazione del permesso: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Associa un permesso ad un ruolo
     * 
     * Uso: ./yii rbac/assign-permission-to-role [ruolo] [permesso]
     * 
     * @param string $roleName Nome del ruolo
     * @param string $permissionName Nome del permesso
     */
    public function actionAssignPermissionToRole($roleName, $permissionName)
    {
        if (empty($roleName) || empty($permissionName)) {
            $this->stderr("Errore: Specificare sia il ruolo che il permesso.\n");
            return self::EXIT_CODE_ERROR;
        }

        $this->stdout("Associazione permesso '{$permissionName}' al ruolo '{$roleName}'...\n");

        try {
            // Verifica che il ruolo esista
            $role = AuthItem::findOne(['name' => $roleName, 'type' => AuthItem::TYPE_ROLE]);
            if (!$role) {
                $this->stderr("Errore: Il ruolo '{$roleName}' non esiste.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Verifica che il permesso esista
            $permission = AuthItem::findOne(['name' => $permissionName, 'type' => AuthItem::TYPE_PERMISSION]);
            if (!$permission) {
                $this->stderr("Errore: Il permesso '{$permissionName}' non esiste.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Verifica se l'associazione esiste già
            $existingAssignment = AuthItemChild::findOne(['parent' => $roleName, 'child' => $permissionName]);
            if ($existingAssignment) {
                $this->stderr("Errore: Il permesso '{$permissionName}' è già associato al ruolo '{$roleName}'.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Crea l'associazione
            $assignment = new AuthItemChild();
            $assignment->parent = $roleName;
            $assignment->child = $permissionName;

            if ($assignment->save()) {
                $this->stdout("✓ Permesso '{$permissionName}' associato al ruolo '{$roleName}' con successo!\n");
                return self::EXIT_CODE_NORMAL;
            } else {
                $this->stderr("Errore nell'associazione:\n");
                foreach ($assignment->errors as $attribute => $errors) {
                    foreach ($errors as $error) {
                        $this->stderr("- {$attribute}: {$error}\n");
                    }
                }
                return self::EXIT_CODE_ERROR;
            }

        } catch (\Exception $e) {
            $this->stderr("Errore durante l'associazione: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Associa un permesso direttamente ad un utente
     * 
     * Uso: ./yii rbac/assign-permission-to-user [user_id] [permesso]
     * 
     * @param int $userId ID dell'utente
     * @param string $permissionName Nome del permesso
     */
    public function actionAssignPermissionToUser($userId, $permissionName)
    {
        if (empty($userId) || empty($permissionName)) {
            $this->stderr("Errore: Specificare sia l'utente che il permesso.\n");
            return self::EXIT_CODE_ERROR;
        }

        $this->stdout("Associazione permesso '{$permissionName}' all'utente ID {$userId}...\n");

        try {
            // Verifica che l'utente esista
            $user = User::findOne($userId);
            if (!$user) {
                $this->stderr("Errore: L'utente con ID {$userId} non esiste.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Verifica che il permesso esista
            $permission = AuthItem::findOne(['name' => $permissionName, 'type' => AuthItem::TYPE_PERMISSION]);
            if (!$permission) {
                $this->stderr("Errore: Il permesso '{$permissionName}' non esiste.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Verifica se l'assegnazione esiste già
            $existingAssignment = AuthAssignment::findOne(['item_name' => $permissionName, 'user_id' => $userId]);
            if ($existingAssignment) {
                $this->stderr("Errore: Il permesso '{$permissionName}' è già assegnato all'utente '{$user->username}'.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Crea l'assegnazione
            if (AuthAssignment::assign($permissionName, $userId)) {
                $this->stdout("✓ Permesso '{$permissionName}' assegnato all'utente '{$user->username}' con successo!\n");
                return self::EXIT_CODE_NORMAL;
            } else {
                $this->stderr("Errore nell'assegnazione del permesso.\n");
                return self::EXIT_CODE_ERROR;
            }

        } catch (\Exception $e) {
            $this->stderr("Errore durante l'assegnazione: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Associa un ruolo ad un utente
     * 
     * Uso: ./yii rbac/assign-role-to-user [user_id] [ruolo]
     * 
     * @param int $userId ID dell'utente
     * @param string $roleName Nome del ruolo
     */
    public function actionAssignRoleToUser($userId, $roleName)
    {
        if (empty($userId) || empty($roleName)) {
            $this->stderr("Errore: Specificare sia l'utente che il ruolo.\n");
            return self::EXIT_CODE_ERROR;
        }

        $this->stdout("Associazione ruolo '{$roleName}' all'utente ID {$userId}...\n");

        try {
            // Verifica che l'utente esista
            $user = User::findOne($userId);
            if (!$user) {
                $this->stderr("Errore: L'utente con ID {$userId} non esiste.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Verifica che il ruolo esista
            $role = AuthItem::findOne(['name' => $roleName, 'type' => AuthItem::TYPE_ROLE]);
            if (!$role) {
                $this->stderr("Errore: Il ruolo '{$roleName}' non esiste.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Verifica se l'assegnazione esiste già
            $existingAssignment = AuthAssignment::findOne(['item_name' => $roleName, 'user_id' => $userId]);
            if ($existingAssignment) {
                $this->stderr("Errore: Il ruolo '{$roleName}' è già assegnato all'utente '{$user->username}'.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Crea l'assegnazione
            if (AuthAssignment::assign($roleName, $userId)) {
                $this->stdout("✓ Ruolo '{$roleName}' assegnato all'utente '{$user->username}' con successo!\n");
                return self::EXIT_CODE_NORMAL;
            } else {
                $this->stderr("Errore nell'assegnazione del ruolo.\n");
                return self::EXIT_CODE_ERROR;
            }

        } catch (\Exception $e) {
            $this->stderr("Errore durante l'assegnazione: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Revoca un ruolo o permesso da un utente
     * 
     * Uso: ./yii rbac/revoke-from-user [user_id] [ruolo_o_permesso]
     * 
     * @param int $userId ID dell'utente
     * @param string $itemName Nome del ruolo o permesso da revocare
     */
    public function actionRevokeFromUser($userId, $itemName)
    {
        if (empty($userId) || empty($itemName)) {
            $this->stderr("Errore: Specificare sia l'utente che il ruolo/permesso.\n");
            return self::EXIT_CODE_ERROR;
        }

        $this->stdout("Revoca '{$itemName}' dall'utente ID {$userId}...\n");

        try {
            // Verifica che l'utente esista
            $user = User::findOne($userId);
            if (!$user) {
                $this->stderr("Errore: L'utente con ID {$userId} non esiste.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Verifica che l'item esista
            $item = AuthItem::findOne(['name' => $itemName]);
            if (!$item) {
                $this->stderr("Errore: Il ruolo/permesso '{$itemName}' non esiste.\n");
                return self::EXIT_CODE_ERROR;
            }

            // Revoca l'assegnazione
            $revoked = AuthAssignment::revoke($itemName, $userId);
            if ($revoked > 0) {
                $itemType = $item->isRole() ? 'ruolo' : 'permesso';
                $this->stdout("✓ {$itemType} '{$itemName}' revocato dall'utente '{$user->username}' con successo!\n");
                return self::EXIT_CODE_NORMAL;
            } else {
                $this->stderr("Errore: L'utente '{$user->username}' non ha il ruolo/permesso '{$itemName}' assegnato.\n");
                return self::EXIT_CODE_ERROR;
            }

        } catch (\Exception $e) {
            $this->stderr("Errore durante la revoca: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Lista tutti i ruoli esistenti
     * 
     * Uso: ./yii rbac/list-roles
     */
    public function actionListRoles()
    {
        $this->stdout("Ruoli esistenti:\n");
        $this->stdout("===============\n");

        try {
            $roles = AuthItem::findRoles();

            if (empty($roles)) {
                $this->stdout("Nessun ruolo trovato.\n");
                return self::EXIT_CODE_NORMAL;
            }

            foreach ($roles as $role) {
                $this->stdout("• {$role->name}");
                if ($role->description) {
                    $this->stdout(" - {$role->description}");
                }
                $this->stdout("\n");

                // Mostra i permessi del ruolo
                $permissions = $role->children;
                if (!empty($permissions)) {
                    $this->stdout("  Permessi:\n");
                    foreach ($permissions as $permission) {
                        $this->stdout("    - {$permission->name}");
                        if ($permission->description) {
                            $this->stdout(" ({$permission->description})");
                        }
                        $this->stdout("\n");
                    }
                }
                $this->stdout("\n");
            }

            return self::EXIT_CODE_NORMAL;

        } catch (\Exception $e) {
            $this->stderr("Errore durante la visualizzazione dei ruoli: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Lista tutti i permessi esistenti
     * 
     * Uso: ./yii rbac/list-permissions
     */
    public function actionListPermissions()
    {
        $this->stdout("Permessi esistenti:\n");
        $this->stdout("==================\n");

        try {
            $permissions = AuthItem::findPermissions();

            if (empty($permissions)) {
                $this->stdout("Nessun permesso trovato.\n");
                return self::EXIT_CODE_NORMAL;
            }

            foreach ($permissions as $permission) {
                $this->stdout("• {$permission->name}");
                if ($permission->description) {
                    $this->stdout(" - {$permission->description}");
                }
                $this->stdout("\n");
            }

            return self::EXIT_CODE_NORMAL;

        } catch (\Exception $e) {
            $this->stderr("Errore durante la visualizzazione dei permessi: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Mostra tutti i ruoli e permessi di un utente
     * 
     * Uso: ./yii rbac/user-assignments [user_id]
     * 
     * @param int $userId ID dell'utente
     */
    public function actionUserAssignments($userId)
    {
        if (empty($userId)) {
            $this->stderr("Errore: Specificare l'ID dell'utente.\n");
            return self::EXIT_CODE_ERROR;
        }

        try {
            $user = User::findOne($userId);
            if (!$user) {
                $this->stderr("Errore: L'utente con ID {$userId} non esiste.\n");
                return self::EXIT_CODE_ERROR;
            }

            $this->stdout("Assegnazioni per l'utente '{$user->username}' (ID: {$userId}):\n");
            $this->stdout("=".str_repeat("=", strlen($user->username) + 20)."\n");

            $assignments = AuthAssignment::find()
                ->where(['user_id' => $userId])
                ->with('item')
                ->all();

            if (empty($assignments)) {
                $this->stdout("Nessuna assegnazione trovata.\n");
                return self::EXIT_CODE_NORMAL;
            }

            $roles = [];
            $permissions = [];

            foreach ($assignments as $assignment) {
                if ($assignment->item->isRole()) {
                    $roles[] = $assignment->item;
                } else {
                    $permissions[] = $assignment->item;
                }
            }

            if (!empty($roles)) {
                $this->stdout("\nRuoli:\n");
                foreach ($roles as $role) {
                    $this->stdout("• {$role->name}");
                    if ($role->description) {
                        $this->stdout(" - {$role->description}");
                    }
                    $this->stdout("\n");
                }
            }

            if (!empty($permissions)) {
                $this->stdout("\nPermessi diretti:\n");
                foreach ($permissions as $permission) {
                    $this->stdout("• {$permission->name}");
                    if ($permission->description) {
                        $this->stdout(" - {$permission->description}");
                    }
                    $this->stdout("\n");
                }
            }

            return self::EXIT_CODE_NORMAL;

        } catch (\Exception $e) {
            $this->stderr("Errore durante la visualizzazione delle assegnazioni: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Inizializza tutte le assegnazioni di permessi ai ruoli secondo la configurazione di default
     * 
     * Uso: ./yii rbac/setup-default-permissions
     */
    public function actionSetupDefaultPermissions()
    {
        $this->stdout("Inizializzazione delle assegnazioni di permessi di default...\n");
        $this->stdout("============================================================\n\n");

        try {
            // Configurazione default dei permessi per ogni ruolo
            $rolePermissions = [
                'admin' => [
                    'create_admin',
                    'create_coordinator', 
                    'create_patient',
                    'create_therapist',
                    'delete_admin',
                    'delete_coordinator',
                    'delete_therapist',
                    'manage_appointments',
                    'manage_communications',
                    'manage_documents',
                    'manage_patients',
                    'manage_plans',
                    'manage_system',
                    'manage_therapists',
                    'manage_users',
                    'update_admin',
                    'update_coordinator',
                    'update_therapist',
                    'view_admin',
                    'view_coordinator',
                    'view_reports',
                    'view_statistics',
                    'view_therapist'
                ],
                'coordinator' => [
                    'platform_login',
                    'view_therapist',
                    'view_patient',
                    'view_calendar',
                    'view_documents',
                    'download_documents',
                    'view_reports',
                    'view_appointment',
                    'view_messages',
                    'view_own_data',
                    'update_own_data',
                    'view_own_group_therapists',
                    'view_notifications',
                ],
                'manager' => [
                    'create_therapist',
                    'delete_therapist',
                    'manage_communications',
                    'manage_documents',
                    'update_patient',
                    'update_therapist',
                    'view_patient',
                    'view_statistics',
                    'view_therapist'
                ],
                'patient' => [
                    'view_own_appointments',
                    'view_own_data'
                ],
                'patient_family' => [
                    'view_own_appointments',
                    'view_own_data',
                    'view_patient_data'
                ],
                'therapist' => [
                    'manage_appointments',
                    'view_own_appointments',
                    'view_patient',
                    'view_patient_data'
                ]
            ];

            $assignedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            foreach ($rolePermissions as $roleName => $permissions) {
                $this->stdout("Configurazione ruolo '{$roleName}':\n");
                
                // Verifica che il ruolo esista
                $role = AuthItem::findOne(['name' => $roleName, 'type' => AuthItem::TYPE_ROLE]);
                if (!$role) {
                    $this->stderr("  ✗ Ruolo '{$roleName}' non esiste. Saltato.\n");
                    $errorCount++;
                    continue;
                }

                foreach ($permissions as $permissionName) {
                    // Verifica che il permesso esista
                    $permission = AuthItem::findOne(['name' => $permissionName, 'type' => AuthItem::TYPE_PERMISSION]);
                    if (!$permission) {
                        $this->stderr("  ✗ Permesso '{$permissionName}' non esiste. Saltato.\n");
                        $errorCount++;
                        continue;
                    }

                    // Verifica se l'associazione esiste già
                    $existingAssignment = AuthItemChild::findOne(['parent' => $roleName, 'child' => $permissionName]);
                    if ($existingAssignment) {
                        $this->stdout("  - {$permissionName} (già assegnato)\n");
                        $skippedCount++;
                        continue;
                    }

                    // Crea l'associazione
                    $assignment = new AuthItemChild();
                    $assignment->parent = $roleName;
                    $assignment->child = $permissionName;

                    if ($assignment->save()) {
                        $this->stdout("  ✓ {$permissionName}\n");
                        $assignedCount++;
                    } else {
                        $this->stderr("  ✗ Errore nell'assegnazione di '{$permissionName}'\n");
                        $errorCount++;
                    }
                }
                $this->stdout("\n");
            }

            // Riepilogo
            $this->stdout("Riepilogo operazione:\n");
            $this->stdout("====================\n");
            $this->stdout("✓ Permessi assegnati: {$assignedCount}\n");
            $this->stdout("- Già esistenti: {$skippedCount}\n");
            if ($errorCount > 0) {
                $this->stderr("✗ Errori: {$errorCount}\n");
            }
            $this->stdout("\n");

            if ($errorCount > 0) {
                $this->stderr("Operazione completata con errori.\n");
                return self::EXIT_CODE_ERROR;
            } else {
                $this->stdout("✓ Configurazione default dei permessi completata con successo!\n");
                return self::EXIT_CODE_NORMAL;
            }

        } catch (\Exception $e) {
            $this->stderr("Errore durante l'inizializzazione: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Mostra l'help con tutti i comandi disponibili
     * 
     * Uso: ./yii rbac/help
     */
    public function actionHelp()
    {
        $this->stdout("RBAC Controller - Gestione Ruoli e Permessi\n");
        $this->stdout("===========================================\n\n");

        $commands = [
            'create-role [nome] [descrizione]' => 'Crea un nuovo ruolo',
            'create-permission [nome] [descrizione]' => 'Crea un nuovo permesso',
            'assign-permission-to-role [ruolo] [permesso]' => 'Associa un permesso ad un ruolo',
            'assign-permission-to-user [user_id] [permesso]' => 'Associa un permesso ad un utente',
            'assign-role-to-user [user_id] [ruolo]' => 'Associa un ruolo ad un utente',
            'revoke-from-user [user_id] [ruolo_o_permesso]' => 'Revoca un ruolo/permesso da un utente',
            'setup-default-permissions' => 'Inizializza tutte le assegnazioni di permessi di default',
            'list-roles' => 'Lista tutti i ruoli esistenti',
            'list-permissions' => 'Lista tutti i permessi esistenti',
            'user-assignments [user_id]' => 'Mostra le assegnazioni di un utente',
            'help' => 'Mostra questo help'
        ];

        foreach ($commands as $command => $description) {
            $this->stdout(sprintf("%-50s %s\n", "./yii rbac/{$command}", $description));
        }

        $this->stdout("\nEsempi di utilizzo:\n");
        $this->stdout("===================\n");
        $this->stdout("./yii rbac/create-role manager \"Gestore del sistema\"\n");
        $this->stdout("./yii rbac/create-permission view_reports \"Visualizzare i report\"\n");
        $this->stdout("./yii rbac/assign-permission-to-role manager view_reports\n");
        $this->stdout("./yii rbac/assign-role-to-user 1 manager\n");
        $this->stdout("./yii rbac/setup-default-permissions\n");
        $this->stdout("./yii rbac/user-assignments 1\n");

        return self::EXIT_CODE_NORMAL;
    }
}
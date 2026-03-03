<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use common\models\User;

/**
 * Esporta i permessi RBAC in formato CSV leggendoli dal database.
 *
 * Azioni:
 *   export-permissions/roles  - Matrice permessi x ruoli
 *   export-permissions/users  - Permessi per ogni utente (ruolo + diretti)
 */
class ExportPermissionsController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Verifica che l'utente abbia il permesso manage_permissions.
     */
    private function checkAdminAccess()
    {
        if (!Yii::$app->user->can('manage_permissions')) {
            throw new ForbiddenHttpException('Non hai i permessi per esportare i dati RBAC.');
        }
    }

    /**
     * Pagina con anteprima e pulsanti di download CSV.
     */
    public function actionIndex()
    {
        $this->checkAdminAccess();

        $db = Yii::$app->db;

        // Dati per la matrice ruoli
        $roles = $db->createCommand(
            'SELECT name, description FROM auth_item WHERE type = 1 ORDER BY name'
        )->queryAll();

        $roleNames = array_column($roles, 'name');

        $permissions = $db->createCommand(
            'SELECT name, description FROM auth_item WHERE type = 2 ORDER BY name'
        )->queryAll();

        $rolePermMap = [];
        foreach ($roleNames as $role) {
            $rolePermMap[$role] = [];
        }

        $children = $db->createCommand(
            'SELECT parent, child FROM auth_item_child'
        )->queryAll();

        foreach ($children as $row) {
            if (isset($rolePermMap[$row['parent']])) {
                $rolePermMap[$row['parent']][$row['child']] = true;
            }
        }

        // Conteggi per ruolo
        $roleCounts = [];
        foreach ($roleNames as $role) {
            $roleCounts[$role] = count($rolePermMap[$role]);
        }

        // Dati utenti
        $users = User::find()
            ->with('profile')
            ->where(['status' => User::STATUS_ACTIVE])
            ->orderBy('id')
            ->all();

        $allRoles = $roleNames;

        $userRows = [];
        foreach ($users as $user) {
            $assignments = $db->createCommand(
                'SELECT item_name FROM auth_assignment WHERE user_id = :uid',
                [':uid' => (string) $user->id]
            )->queryColumn();

            $uRoles = [];
            $directCount = 0;
            $fromRoleCount = 0;

            foreach ($assignments as $itemName) {
                if (in_array($itemName, $allRoles)) {
                    $uRoles[] = $itemName;
                } else {
                    $directCount++;
                }
            }

            foreach ($uRoles as $r) {
                if (isset($rolePermMap[$r])) {
                    $fromRoleCount += count($rolePermMap[$r]);
                }
            }

            $userRows[] = [
                'id' => $user->id,
                'email' => $user->email,
                'firstName' => $user->profile ? $user->profile->first_name : '',
                'lastName' => $user->profile ? $user->profile->last_name : '',
                'roles' => $uRoles,
                'directCount' => $directCount,
                'fromRoleCount' => $fromRoleCount,
            ];
        }

        return $this->render('index', [
            'roleNames' => $roleNames,
            'permissions' => $permissions,
            'rolePermMap' => $rolePermMap,
            'roleCounts' => $roleCounts,
            'userRows' => $userRows,
        ]);
    }

    /**
     * Genera CSV con matrice Permessi x Ruoli.
     *
     * Ogni riga è un permesso, ogni colonna è un ruolo.
     * Le celle contengono "X" se il ruolo ha quel permesso.
     */
    public function actionRoles()
    {
        $this->checkAdminAccess();

        $db = Yii::$app->db;

        // Recupera tutti i ruoli (type=1) ordinati per nome
        $roles = $db->createCommand(
            'SELECT name, description FROM auth_item WHERE type = 1 ORDER BY name'
        )->queryAll();

        $roleNames = array_column($roles, 'name');

        // Recupera tutti i permessi (type=2) ordinati per nome
        $permissions = $db->createCommand(
            'SELECT name, description FROM auth_item WHERE type = 2 ORDER BY name'
        )->queryAll();

        // Mappa ruolo -> set di permessi figli
        $rolePermMap = [];
        foreach ($roleNames as $role) {
            $rolePermMap[$role] = [];
        }

        $children = $db->createCommand(
            'SELECT parent, child FROM auth_item_child'
        )->queryAll();

        foreach ($children as $row) {
            if (isset($rolePermMap[$row['parent']])) {
                $rolePermMap[$row['parent']][$row['child']] = true;
            }
        }

        // Costruisci le righe CSV
        $lines = [];

        // Header
        $header = ['Permesso', 'Descrizione'];
        foreach ($roleNames as $role) {
            $header[] = $role;
        }
        $lines[] = $header;

        // Righe permessi
        foreach ($permissions as $perm) {
            $row = [$perm['name'], $perm['description'] ?? ''];
            foreach ($roleNames as $role) {
                $row[] = isset($rolePermMap[$role][$perm['name']]) ? 'X' : '';
            }
            $lines[] = $row;
        }

        // Riga riepilogo conteggio
        $countRow = ['TOTALE', ''];
        foreach ($roleNames as $role) {
            $count = 0;
            foreach ($permissions as $perm) {
                if (isset($rolePermMap[$role][$perm['name']])) {
                    $count++;
                }
            }
            $countRow[] = $count;
        }
        $lines[] = $countRow;

        return $this->sendCsv($lines, 'permessi_ruoli.csv');
    }

    /**
     * Genera CSV con i permessi effettivi di ogni utente attivo.
     *
     * Per ogni utente mostra: ruoli assegnati, e per ogni permesso indica
     * se deriva dal ruolo (con nome ruolo) o è assegnato direttamente.
     */
    public function actionUsers()
    {
        $this->checkAdminAccess();

        $db = Yii::$app->db;

        // Utenti attivi con profilo
        $users = User::find()
            ->with('profile')
            ->where(['status' => User::STATUS_ACTIVE])
            ->orderBy('id')
            ->all();

        // Tutti i permessi ordinati
        $allPermissions = $db->createCommand(
            'SELECT name FROM auth_item WHERE type = 2 ORDER BY name'
        )->queryColumn();

        // Tutti i ruoli
        $allRoles = $db->createCommand(
            'SELECT name FROM auth_item WHERE type = 1'
        )->queryColumn();

        // Mappa ruolo -> permessi figli
        $rolePermMap = [];
        foreach ($allRoles as $role) {
            $rolePermMap[$role] = [];
        }

        $children = $db->createCommand(
            'SELECT parent, child FROM auth_item_child'
        )->queryAll();

        foreach ($children as $row) {
            if (isset($rolePermMap[$row['parent']])) {
                $rolePermMap[$row['parent']][$row['child']] = true;
            }
        }

        // Header
        $header = ['ID', 'Email', 'Nome', 'Cognome', 'Ruoli'];
        foreach ($allPermissions as $perm) {
            $header[] = $perm;
        }
        $lines = [$header];

        foreach ($users as $user) {
            $firstName = $user->profile ? $user->profile->first_name : '';
            $lastName = $user->profile ? $user->profile->last_name : '';

            // Assegnazioni dirette dell'utente
            $assignments = $db->createCommand(
                'SELECT item_name FROM auth_assignment WHERE user_id = :uid',
                [':uid' => (string) $user->id]
            )->queryColumn();

            $userRoles = [];
            $directPermissions = [];

            foreach ($assignments as $itemName) {
                if (in_array($itemName, $allRoles)) {
                    $userRoles[] = $itemName;
                } else {
                    $directPermissions[$itemName] = true;
                }
            }

            // Permessi ereditati dai ruoli
            $rolePermissions = [];
            foreach ($userRoles as $role) {
                if (isset($rolePermMap[$role])) {
                    foreach ($rolePermMap[$role] as $perm => $v) {
                        if (!isset($rolePermissions[$perm])) {
                            $rolePermissions[$perm] = $role;
                        } else {
                            $rolePermissions[$perm] .= ', ' . $role;
                        }
                    }
                }
            }

            // Costruisci la riga
            $row = [
                $user->id,
                $user->email,
                $firstName,
                $lastName,
                implode(', ', $userRoles),
            ];

            foreach ($allPermissions as $perm) {
                $isDirect = isset($directPermissions[$perm]);
                $fromRole = $rolePermissions[$perm] ?? null;

                if ($isDirect && $fromRole) {
                    $row[] = "X (diretto + {$fromRole})";
                } elseif ($isDirect) {
                    $row[] = 'X (diretto)';
                } elseif ($fromRole) {
                    $row[] = "X ({$fromRole})";
                } else {
                    $row[] = '';
                }
            }

            $lines[] = $row;
        }

        return $this->sendCsv($lines, 'permessi_utenti.csv');
    }

    /**
     * Invia le righe CSV come download al browser.
     *
     * @param array $lines Array di array (righe CSV)
     * @param string $filename Nome del file scaricato
     * @return string
     */
    private function sendCsv(array $lines, string $filename): string
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        $handle = fopen('php://temp', 'r+');

        // BOM UTF-8 per Excel
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($lines as $row) {
            fputcsv($handle, $row, ',', '"');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content;
    }
}

<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\AuthItem;
use common\models\AuthItemChild;
use common\models\AuthAssignment;
use common\models\User;

class PermissionController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['roles', 'view-role', 'toggle-role-permission'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->user->can('assign_role_permissions');
                        }
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->user->can('manage_permissions');
                        }
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'toggle-role-permission' => ['POST'],
                    'assign-permission-to-users' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lista ruoli con conteggio permessi
     */
    public function actionRoles()
    {
        $roles = AuthItem::find()
            ->where(['type' => AuthItem::TYPE_ROLE])
            ->andWhere(['!=', 'name', 'super_admin'])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        $rolesData = [];
        foreach ($roles as $role) {
            $inactivePermissions = \common\models\PermissionMetadata::find()
                ->select('permission_name')
                ->where(['is_active' => 0]);

            $permissionCount = AuthItemChild::find()
                ->where(['parent' => $role->name])
                ->joinWith('childItem')
                ->andWhere(['{{%auth_item}}.type' => AuthItem::TYPE_PERMISSION])
                ->andWhere(['NOT IN', '{{%auth_item}}.name', $inactivePermissions])
                ->count();

            $rolesData[] = [
                'model' => $role,
                'permissionCount' => $permissionCount,
            ];
        }

        return $this->render('roles', [
            'rolesData' => $rolesData,
        ]);
    }

    /**
     * Griglia permessi per un ruolo raggruppati per categoria
     */
    public function actionViewRole($name)
    {
        $role = AuthItem::find()
            ->where(['name' => $name, 'type' => AuthItem::TYPE_ROLE])
            ->one();

        if (!$role) {
            throw new NotFoundHttpException('Ruolo non trovato.');
        }

        if ($role->name === 'super_admin') {
            throw new ForbiddenHttpException('Non è possibile visualizzare i permessi di Super Admin.');
        }

        $permissions = AuthItem::findActivePermissions()->all();

        $assignedPermissions = AuthItemChild::find()
            ->where(['parent' => $name])
            ->select('child')
            ->column();

        // Group permissions by category
        $groupedPermissions = [];
        foreach ($permissions as $permission) {
            $category = $this->getCategoryFromName($permission->name);
            $groupedPermissions[$category][] = $permission;
        }
        ksort($groupedPermissions);

        $canAssignToRole = true; // Only users with assign_role_permissions can access this page

        return $this->render('view-role', [
            'role' => $role,
            'groupedPermissions' => $groupedPermissions,
            'assignedPermissions' => $assignedPermissions,
            'canAssignToRole' => $canAssignToRole,
        ]);
    }

    /**
     * AJAX POST: aggiunge/rimuove permesso da ruolo
     */
    public function actionToggleRolePermission()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $roleName = Yii::$app->request->post('role');
        $permissionName = Yii::$app->request->post('permission');
        $assign = Yii::$app->request->post('assign');

        if (!$roleName || !$permissionName) {
            return ['status' => 'error', 'message' => 'Parametri mancanti.'];
        }

        $role = AuthItem::find()->where(['name' => $roleName, 'type' => AuthItem::TYPE_ROLE])->one();
        $permission = AuthItem::find()->where(['name' => $permissionName, 'type' => AuthItem::TYPE_PERMISSION])->one();

        if (!$role || !$permission) {
            return ['status' => 'error', 'message' => 'Ruolo o permesso non trovato.'];
        }

        if ($role->name === 'super_admin') {
            return ['status' => 'error', 'message' => 'Non è possibile modificare i permessi di Super Admin.'];
        }

        $systemPermissions = ['platform_login', 'app_login', 'manage_system', 'manage_notifications', 'view_statistics'];
        if (in_array($permissionName, $systemPermissions)) {
            return ['status' => 'error', 'message' => 'I permessi di accesso sistema non possono essere modificati da questa interfaccia.'];
        }

        if ($assign) {
            $existing = AuthItemChild::find()->where(['parent' => $roleName, 'child' => $permissionName])->one();
            if (!$existing) {
                $child = new AuthItemChild();
                $child->parent = $roleName;
                $child->child = $permissionName;
                if (!$child->save()) {
                    return ['status' => 'error', 'message' => 'Errore durante l\'assegnazione.'];
                }
            }
            return ['status' => 'success', 'message' => 'Permesso assegnato.'];
        } else {
            AuthItemChild::deleteAll(['parent' => $roleName, 'child' => $permissionName]);
            return ['status' => 'success', 'message' => 'Permesso rimosso.'];
        }
    }

    /**
     * AJAX GET: utenti del ruolo per modale riassegnazione
     */
    public function actionGetRoleUsers($role, $permission)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userIds = AuthAssignment::find()
            ->select('user_id')
            ->where(['item_name' => $role])
            ->column();

        if (empty($userIds)) {
            return ['status' => 'success', 'users' => []];
        }

        $users = User::find()
            ->joinWith('profile')
            ->where(['users.id' => $userIds, 'users.status' => User::STATUS_ACTIVE])
            ->orderBy(['user_profiles.last_name' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($users as $user) {
            $hasDirect = AuthAssignment::find()
                ->where(['user_id' => (string)$user->id, 'item_name' => $permission])
                ->exists();

            $result[] = [
                'id' => $user->id,
                'name' => $user->profile ? $user->profile->last_name . ' ' . $user->profile->first_name : $user->username,
                'email' => $user->email,
                'hasPermission' => $hasDirect,
            ];
        }

        return ['status' => 'success', 'users' => $result];
    }

    /**
     * AJAX POST: assegna permesso diretto a utenti selezionati
     */
    public function actionAssignPermissionToUsers()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $permissionName = Yii::$app->request->post('permission');
        $userIds = Yii::$app->request->post('user_ids', []);

        if (!$permissionName) {
            return ['status' => 'error', 'message' => 'Parametri mancanti.'];
        }

        $permission = AuthItem::find()
            ->where(['name' => $permissionName, 'type' => AuthItem::TYPE_PERMISSION])
            ->one();

        if (!$permission) {
            return ['status' => 'error', 'message' => 'Permesso non trovato.'];
        }

        $assigned = 0;
        foreach ($userIds as $userId) {
            $existing = AuthAssignment::find()
                ->where(['item_name' => $permissionName, 'user_id' => (string)$userId])
                ->one();
            if (!$existing) {
                if (AuthAssignment::assign($permissionName, $userId)) {
                    $assigned++;
                }
            }
        }

        return [
            'status' => 'success',
            'message' => "Permesso assegnato a {$assigned} utente/i.",
        ];
    }

    /**
     * AJAX GET: restituisce la lista permessi di un ruolo (per form utenti)
     */
    public function actionGetRolePermissions($role)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $permissions = AuthItemChild::find()
            ->where(['{{%auth_item_child}}.parent' => $role])
            ->joinWith('childItem')
            ->andWhere(['{{%auth_item}}.type' => AuthItem::TYPE_PERMISSION])
            ->select(['{{%auth_item_child}}.child'])
            ->column();

        return ['status' => 'success', 'permissions' => $permissions];
    }

    /**
     * Categorizza un permesso dal suo nome
     */
    public static function getCategoryFromName($name)
    {
        $categoryMap = [
            'platform_login' => 'Accesso Sistema',
            'app_login' => 'Accesso Sistema',
            'manage_system' => 'Accesso Sistema',
            'manage_notifications' => 'Accesso Sistema',
            'view_statistics' => 'Accesso Sistema',
            'manage_districts' => 'Configurazione',
            'manage_settings' => 'Configurazione',
            'manage_permissions' => 'Configurazione',
            'view_reports' => 'Report',
            'generate_reports' => 'Report',
            'export_data' => 'Report',
            'manage_communications' => 'Comunicazioni',
            'send_messages' => 'Comunicazioni',
            'view_messages' => 'Comunicazioni',
            'view_own_data' => 'Dati Personali',
            'update_own_data' => 'Dati Personali',
            'view_assigned_patients' => 'Dati Personali',
            'manage_coordinator_groups' => 'Gestione Coordinatori',
            'manage_users' => 'Gestione Utenti Generica',
            'manage_patients' => 'Gestione Utenti Generica',
            'manage_therapists' => 'Gestione Utenti Generica',
            'manage_plans' => 'Piani Terapeutici',
            'manage_plan_therapies' => 'Piani Terapeutici',
            'manage_appointments' => 'Appuntamenti',
            'manage_appointment_patterns' => 'Appuntamenti',
            'view_calendar' => 'Calendario',
            'manage_calendar' => 'Calendario',
            'view_own_appointments' => 'Calendario',
            'manage_own_schedule' => 'Calendario',
            'manage_absence_recovery' => 'Assenze',
            'view_absence_statistics' => 'Assenze',
            'view_patient_absence' => 'Assenze',
            'manage_notification_templates' => 'Notifiche',
            'send_notifications' => 'Notifiche',
            'manage_documents' => 'Documenti',
            'view_documents' => 'Documenti',
            'download_documents' => 'Documenti',
            'change_document_request_status' => 'Documenti',
            'mark_document_request_delivered' => 'Documenti',
            'manage_treatment_types' => 'Specializzazioni',
        ];

        if (isset($categoryMap[$name])) {
            return $categoryMap[$name];
        }

        // Infer from prefix
        $prefixMap = [
            'admin' => 'Gestione Admin',
            'manager' => 'Gestione Manager',
            'coordinator' => 'Gestione Coordinatori',
            'therapist' => 'Gestione Terapisti',
            'patient' => 'Gestione Pazienti',
            'user' => 'Gestione Utenti Generica',
            'therapeutic_plan' => 'Piani Terapeutici',
            'appointment' => 'Appuntamenti',
            'absence' => 'Assenze',
            'notification' => 'Notifiche',
            'document_request' => 'Documenti',
            'specialization' => 'Specializzazioni',
            'specialist_visit' => 'Visite Specialistiche',
        ];

        foreach ($prefixMap as $suffix => $category) {
            if (preg_match('/^(create|view|update|delete)_' . preg_quote($suffix, '/') . '$/', $name)) {
                return $category;
            }
        }

        return 'Altro';
    }

    /**
     * Helper: ottiene dati permessi per un ruolo (usato anche da altri controller)
     */
    public static function getPermissionData($roleName, $userId = null)
    {
        $allPermissions = AuthItem::findActivePermissions()->all();

        // Permessi specifici dell'app mobile: visibili solo per i ruoli che
        // accedono via app (therapist, patient_family). Per qualunque altro
        // ruolo il flag non ha senso e va nascosto dall'editor permessi:
        // nessuno fuori da questi due deve poter loggarsi sull'app.
        $mobileOnlyPermissions = ['app_login'];
        $mobileRoles = ['therapist', 'patient_family'];
        if (!in_array($roleName, $mobileRoles, true)) {
            $allPermissions = array_values(array_filter(
                $allPermissions,
                static fn ($p) => !in_array($p->name, $mobileOnlyPermissions, true)
            ));
        }

        $rolePermissions = AuthItemChild::find()
            ->where(['{{%auth_item_child}}.parent' => $roleName])
            ->joinWith('childItem')
            ->andWhere(['{{%auth_item}}.type' => AuthItem::TYPE_PERMISSION])
            ->select(['{{%auth_item_child}}.child'])
            ->column();

        $userDirectPermissions = [];
        if ($userId) {
            // Get direct permission assignments (not roles)
            $userDirectPermissions = AuthAssignment::find()
                ->joinWith('item')
                ->where(['{{%auth_assignment}}.user_id' => (string)$userId])
                ->andWhere(['{{%auth_item}}.type' => AuthItem::TYPE_PERMISSION])
                ->select(['{{%auth_assignment}}.item_name'])
                ->column();
        }

        // Group by category
        $categories = [];
        foreach ($allPermissions as $permission) {
            $category = static::getCategoryFromName($permission->name);
            $categories[$category][] = $permission;
        }
        ksort($categories);

        return [
            'allPermissions' => $allPermissions,
            'rolePermissions' => $rolePermissions,
            'userDirectPermissions' => $userDirectPermissions,
            'categories' => $categories,
        ];
    }

    /**
     * Helper: salva permessi extra dall'array POST
     */
    public static function saveExtraPermissions($userId, $roleName, $postedPermissions = [])
    {
        // Filtro server-side: app_login non puo' essere assegnato come extra
        // a ruoli non mobile (POST tampering safety).
        $mobileOnlyPermissions = ['app_login'];
        $mobileRoles = ['therapist', 'patient_family'];
        if (!in_array($roleName, $mobileRoles, true)) {
            $postedPermissions = array_values(array_diff($postedPermissions, $mobileOnlyPermissions));
        }

        $rolePermissions = AuthItemChild::find()
            ->where(['{{%auth_item_child}}.parent' => $roleName])
            ->joinWith('childItem')
            ->andWhere(['{{%auth_item}}.type' => AuthItem::TYPE_PERMISSION])
            ->select(['{{%auth_item_child}}.child'])
            ->column();

        // Get current direct permissions
        $currentDirect = AuthAssignment::find()
            ->joinWith('item')
            ->where(['user_id' => (string)$userId])
            ->andWhere(['{{%auth_item}}.type' => AuthItem::TYPE_PERMISSION])
            ->select('item_name')
            ->column();

        // Extra permissions = posted permissions that are NOT in the role
        $extraPermissions = array_diff($postedPermissions, $rolePermissions);

        // Remove permissions no longer selected
        $toRemove = array_diff($currentDirect, $extraPermissions);
        foreach ($toRemove as $permName) {
            AuthAssignment::revoke($permName, $userId);
        }

        // Add new extra permissions
        $toAdd = array_diff($extraPermissions, $currentDirect);
        foreach ($toAdd as $permName) {
            // Verify it's a valid permission
            $exists = AuthItem::find()->where(['name' => $permName, 'type' => AuthItem::TYPE_PERMISSION])->exists();
            if ($exists) {
                AuthAssignment::assign($permName, $userId);
            }
        }
    }
}

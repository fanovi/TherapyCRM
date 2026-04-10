<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\AuthItem;
use common\models\AuthItemChild;
use common\models\AuthAssignment;
use common\models\User;

class RolesController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->user->can('super_admin');
                        }
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'toggle-permission' => ['POST'],
                    'get-role-users' => ['GET'],
                    'assign-permission-to-users' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $roles = AuthItem::find()
            ->where(['type' => AuthItem::TYPE_ROLE])
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

        return $this->render('index', [
            'rolesData' => $rolesData,
        ]);
    }

    public function actionView($name)
    {
        $role = AuthItem::find()
            ->where(['name' => $name, 'type' => AuthItem::TYPE_ROLE])
            ->one();

        if (!$role) {
            throw new NotFoundHttpException('Ruolo non trovato.');
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

        return $this->render('view', [
            'role' => $role,
            'groupedPermissions' => $groupedPermissions,
            'assignedPermissions' => $assignedPermissions,
        ]);
    }

    public function actionTogglePermission()
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
     * Returns users that have a specific role (for the reassign modal)
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
            // Check if user already has this permission directly
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
     * Assigns a permission directly to selected users
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

    private function getCategoryFromName($name)
    {
        $categoryMap = [
            'platform_login' => 'Accesso Sistema',
            'app_login' => 'Accesso Sistema',
            'manage_system' => 'Accesso Sistema',
            'manage_notifications' => 'Accesso Sistema',
            'view_statistics' => 'Accesso Sistema',
            'manage_districts' => 'Configurazione',
            'manage_settings' => 'Configurazione',
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
}

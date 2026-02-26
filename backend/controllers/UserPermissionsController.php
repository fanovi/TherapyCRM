<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use common\models\User;
use common\models\AuthItem;
use common\models\AuthItemChild;
use common\models\AuthAssignment;

class UserPermissionsController extends Controller
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
                    'add-permission' => ['POST'],
                    'remove-permission' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        // Only show users with roles that have platform_login permission
        $rolesWithPlatformLogin = AuthItemChild::find()
            ->select('parent')
            ->where(['child' => 'platform_login'])
            ->column();

        $query = User::find()
            ->joinWith('profile')
            ->innerJoin('{{%auth_assignment}} aa', 'aa.user_id = CAST(users.id AS CHAR)')
            ->where(['users.status' => User::STATUS_ACTIVE])
            ->andWhere([
                'or',
                ['aa.item_name' => $rolesWithPlatformLogin],
                ['aa.item_name' => 'platform_login']
            ])
            ->distinct()
            ->orderBy(['user_profiles.last_name' => SORT_ASC, 'user_profiles.first_name' => SORT_ASC]);

        $search = Yii::$app->request->get('search');
        if ($search) {
            $query->andWhere([
                'or',
                ['like', 'user_profiles.first_name', $search],
                ['like', 'user_profiles.last_name', $search],
                ['like', 'users.email', $search],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'search' => $search,
        ]);
    }

    public function actionView($id)
    {
        $user = User::findOne($id);
        if (!$user) {
            throw new NotFoundHttpException('Utente non trovato.');
        }

        // Get user's role assignments
        $roleAssignments = AuthAssignment::find()
            ->joinWith('item')
            ->where(['user_id' => (string)$id])
            ->andWhere(['{{%auth_item}}.type' => AuthItem::TYPE_ROLE])
            ->all();

        $userRoles = array_map(function ($a) { return $a->item_name; }, $roleAssignments);

        // Get permissions inherited from roles
        $rolePermissions = [];
        foreach ($userRoles as $roleName) {
            $perms = AuthItemChild::find()
                ->where(['parent' => $roleName])
                ->joinWith('childItem')
                ->andWhere(['{{%auth_item}}.type' => AuthItem::TYPE_PERMISSION])
                ->all();
            foreach ($perms as $perm) {
                $rolePermissions[$perm->child] = $roleName;
            }
        }
        ksort($rolePermissions);

        // Get direct permission assignments (not roles)
        $directAssignments = AuthAssignment::find()
            ->joinWith('item')
            ->where(['user_id' => (string)$id])
            ->andWhere(['{{%auth_item}}.type' => AuthItem::TYPE_PERMISSION])
            ->all();

        $directPermissions = array_map(function ($a) { return $a->item_name; }, $directAssignments);

        // Get all permissions for the dropdown (exclude already assigned ones)
        $excludeNames = array_merge(array_keys($rolePermissions), $directPermissions);
        $availableQuery = AuthItem::find()->where(['type' => AuthItem::TYPE_PERMISSION]);
        if (!empty($excludeNames)) {
            $availableQuery->andWhere(['not in', 'name', $excludeNames]);
        }
        $availablePermissions = $availableQuery->orderBy(['name' => SORT_ASC])->all();

        return $this->render('view', [
            'user' => $user,
            'userRoles' => $userRoles,
            'rolePermissions' => $rolePermissions,
            'directPermissions' => $directPermissions,
            'availablePermissions' => $availablePermissions,
        ]);
    }

    public function actionAddPermission()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userId = Yii::$app->request->post('user_id');
        $permissionName = Yii::$app->request->post('permission');

        if (!$userId || !$permissionName) {
            return ['status' => 'error', 'message' => 'Parametri mancanti.'];
        }

        $user = User::findOne($userId);
        $permission = AuthItem::find()->where(['name' => $permissionName, 'type' => AuthItem::TYPE_PERMISSION])->one();

        if (!$user || !$permission) {
            return ['status' => 'error', 'message' => 'Utente o permesso non trovato.'];
        }

        // Check if already assigned
        $existing = AuthAssignment::find()
            ->where(['item_name' => $permissionName, 'user_id' => (string)$userId])
            ->one();

        if ($existing) {
            return ['status' => 'error', 'message' => 'Permesso già assegnato.'];
        }

        if (AuthAssignment::assign($permissionName, $userId)) {
            return ['status' => 'success', 'message' => 'Permesso assegnato con successo.'];
        }

        return ['status' => 'error', 'message' => 'Errore durante l\'assegnazione.'];
    }

    public function actionRemovePermission()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userId = Yii::$app->request->post('user_id');
        $permissionName = Yii::$app->request->post('permission');

        if (!$userId || !$permissionName) {
            return ['status' => 'error', 'message' => 'Parametri mancanti.'];
        }

        AuthAssignment::revoke($permissionName, $userId);
        return ['status' => 'success', 'message' => 'Permesso rimosso con successo.'];
    }
}

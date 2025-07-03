<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\ActivityLog;
use common\models\User;

$this->title = 'Dashboard';

// Recupera gli ultimi 5 log
$recentLogs = ActivityLog::find()
    ->with(['user'])
    ->orderBy(['created_at' => SORT_DESC])
    ->limit(5)
    ->all();

// Statistiche rapide
$totalUsers = User::find()->count();
$todayLogs = ActivityLog::find()
    ->where(['>=', 'created_at', date('Y-m-d 00:00:00')])
    ->count();
?>

<div class="space-y-6">
    <!-- Stats -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Utenti Totali -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow">
            <dt class="truncate text-sm font-medium text-gray-500">Utenti Totali</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"><?= $totalUsers ?></dd>
        </div>
        
        <!-- Log Oggi -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow">
            <dt class="truncate text-sm font-medium text-gray-500">Log Oggi</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900"><?= $todayLogs ?></dd>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-medium leading-6 text-gray-900">Attività Recenti</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Ultimi 5 log di attività nel sistema</p>
            </div>
            <?= Html::a('Vedi Tutti', ['/activity-log/index'], ['class' => 'inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600']) ?>
        </div>
        <div class="border-t border-gray-200">
            <div class="overflow-hidden">
                <ul role="list" class="divide-y divide-gray-200">
                    <?php foreach ($recentLogs as $log): ?>
                        <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex flex-col">
                                    <p class="text-sm font-medium text-gray-900"><?= Html::encode($log->getSummary()) ?></p>
                                    <div class="flex space-x-4">
                                        <p class="text-sm text-gray-500">
                                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                                <?= Html::encode($log->getActionDescription()) ?>
                                            </span>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?= Yii::$app->formatter->asDatetime($log->created_at) ?>
                                        </p>
                                    </div>
                                </div>
                                <?= Html::a('Dettagli', ['/activity-log/view', 'id' => $log->id], ['class' => 'text-indigo-600 hover:text-indigo-900 text-sm font-medium']) ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

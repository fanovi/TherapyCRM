<?php

/** @var \yii\web\View $this */
/** @var string $content */

use backend\assets\AppAsset;
use common\widgets\Alert;
use yii\helpers\Html;
use yii\helpers\Url;

AppAsset::register($this);

$currentController = Yii::$app->controller->id;
$currentAction = Yii::$app->controller->action->id;
$currentRoute = $currentController . '/' . $currentAction;

// Menu items configuration
$menuItems = [
    [
        'label' => 'Dashboard',
        'url' => ['/site/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />',
        'routes' => ['site/index'],
    ],
    [
        'label' => 'Distretti',
        'url' => ['/districts/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />',
        'routes' => ['districts/index', 'districts/create', 'districts/view', 'districts/update'],
    ],
    [
        'label' => 'Log Attività',
        'url' => ['/activity-log/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />',
        'routes' => ['activity-log/index', 'activity-log/view', 'activity-log/stats'],
    ],
    [
        'label' => 'Gestione Ruoli',
        'url' => ['/roles/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
        'routes' => ['roles/index', 'roles/view'],
    ],
    [
        'label' => 'Permessi Utente',
        'url' => ['/user-permissions/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />',
        'routes' => ['user-permissions/index', 'user-permissions/view'],
    ],
    [
        'label' => 'Gestione Permessi',
        'url' => ['/permissions/index'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />',
        'routes' => ['permissions/index', 'permissions/create', 'permissions/update'],
    ],
    [
        'label' => 'Impostazioni 2FA',
        'url' => ['/system-setting/two-factor'],
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />',
        'routes' => ['system-setting/two-factor', 'system-setting/update-two-factor', 'system-setting/enable-user-2fa', 'system-setting/disable-user-2fa', 'system-setting/enable-all-2fa'],
    ],
];
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-full">

<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> - Backend</title>
    <?php $this->head() ?>

</head>

<body class="h-full bg-gray-100">
    <?php $this->beginBody() ?>

    <div class="min-h-full flex">
        <!-- Mobile sidebar overlay -->
        <div id="sidebar-overlay"
             class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden hidden">
        </div>

        <!-- Sidebar -->
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-800 transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 flex flex-col">

            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-4 bg-gray-900">
                <a href="<?= Url::to(['/site/index']) ?>" class="flex items-center">
                    <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="ml-3 text-white text-lg font-semibold">CGM Backend</span>
                </a>
                <button id="sidebar-close-btn" class="lg:hidden text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <?php foreach ($menuItems as $item): ?>
                    <?php $isActive = in_array($currentRoute, $item['routes']); ?>
                    <a href="<?= Url::to($item['url']) ?>"
                       class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors <?= $isActive
                            ? 'bg-gray-900 text-white'
                            : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?>">
                        <svg class="w-5 h-5 mr-3 flex-shrink-0 <?= $isActive ? 'text-indigo-400' : 'text-gray-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?= $item['icon'] ?>
                        </svg>
                        <?= Html::encode($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Sidebar footer -->
            <div class="border-t border-gray-700 p-4">
                <?php if (!Yii::$app->user->isGuest): ?>
                    <div class="flex items-center mb-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-medium">
                            <?= strtoupper(substr(Yii::$app->user->identity->username ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="ml-3 min-w-0">
                            <p class="text-sm font-medium text-white truncate"><?= Html::encode(Yii::$app->user->identity->username ?? '') ?></p>
                            <p class="text-xs text-gray-400">Super Admin</p>
                        </div>
                    </div>
                    <?= Html::beginForm(['/site/logout'], 'post') ?>
                        <?= Html::submitButton('Logout', [
                            'class' => 'w-full flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors'
                        ]) ?>
                    <?= Html::endForm() ?>
                <?php else: ?>
                    <?= Html::a('Login', ['/site/login'], [
                        'class' => 'flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors'
                    ]) ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main content area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top bar (mobile hamburger + breadcrumb area) -->
            <header class="bg-white shadow-sm border-b border-gray-200 lg:hidden">
                <div class="flex items-center h-14 px-4">
                    <button id="sidebar-open-btn" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <span class="ml-3 text-lg font-semibold text-gray-800">CGM Backend</span>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <?= Alert::widget() ?>
                    <?= $content ?>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200">
                <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center">
                        <p class="text-sm text-gray-500">&copy; <?= Html::encode(Yii::$app->name) ?> <?= date('Y') ?></p>
                        <p class="text-sm text-gray-500"><?= Yii::powered() ?></p>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        var openBtn = document.getElementById('sidebar-open-btn');
        var closeBtn = document.getElementById('sidebar-close-btn');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
        }
        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            overlay.classList.add('hidden');
        }
        if (openBtn) openBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);
    });
    </script>
    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>

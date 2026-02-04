<?php

/** @var \yii\web\View $this */
/** @var string $content */

use backend\assets\AppAsset;
use common\widgets\Alert;
use yii\helpers\Html;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-full bg-gray-100">

<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>

<body class="h-full">
    <?php $this->beginBody() ?>

    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="text-white text-xl font-bold">San Luca CGM</span>
                        </div>
                        <div class="hidden md:block">
                            <div class="ml-10 flex items-baseline space-x-4">
                                <?= Html::a('Home', ['/site/index'], ['class' => 'text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium']) ?>
                                <?= Html::a('Distretti', ['/districts/index'], ['class' => 'text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium']) ?>
                                <?= Html::a('Log Attività', ['/activity-log/index'], ['class' => 'text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-4 flex items-center md:ml-6">
                            <?php if (Yii::$app->user->isGuest) : ?>
                                <?= Html::a('Login', ['/site/login'], ['class' => 'text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium']) ?>
                            <?php else : ?>
                                <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'flex']) ?>
                                <?= Html::submitButton(
                                    'Logout (' . Html::encode(Yii::$app->user->identity ? Yii::$app->user->identity->username : '') . ')',
                                    ['class' => 'text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium']
                                ) ?>
                                <?= Html::endForm() ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main>
            <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <?= Alert::widget() ?>
                <?= $content ?>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white shadow mt-8">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <p class="text-sm text-gray-500">&copy; <?= Html::encode(Yii::$app->name) ?> <?= date('Y') ?></p>
                    <p class="text-sm text-gray-500"><?= Yii::powered() ?></p>
                </div>
            </div>
        </footer>
    </div>

    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>
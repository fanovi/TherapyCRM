<?php

/** @var \yii\web\View $this */

use frontend\widgets\AlertBanner;

$flashes = Yii::$app->session->getAllFlashes(true);
if (empty($flashes)) {
    return;
}

$variantMap = [
    'success' => 'success',
    'error'   => 'danger',
    'danger'  => 'danger',
    'warning' => 'warning',
    'info'    => 'info',
];
?>

<div class="px-4 md:px-6 pt-4 space-y-3">
    <?php foreach ($flashes as $type => $message): ?>
        <?php $variant = $variantMap[$type] ?? 'info'; ?>
        <?php foreach ((array) $message as $msg): ?>
            <?= AlertBanner::widget([
                'variant' => $variant,
                'message' => $msg,
                'rawMessage' => true,
            ]) ?>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

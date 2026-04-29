<?php

/** @var \yii\web\View $this */

$flashes = Yii::$app->session->getAllFlashes(true);
if (empty($flashes)) {
    return;
}

$styles = [
    'success' => [
        'wrapper' => 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800',
        'icon'    => 'text-green-400',
        'text'    => 'text-green-800 dark:text-green-200',
        'path'    => 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z',
    ],
    'error' => [
        'wrapper' => 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800',
        'icon'    => 'text-red-400',
        'text'    => 'text-red-800 dark:text-red-200',
        'path'    => 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z',
    ],
    'warning' => [
        'wrapper' => 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800',
        'icon'    => 'text-amber-400',
        'text'    => 'text-amber-800 dark:text-amber-200',
        'path'    => 'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.59c.75 1.335-.213 2.98-1.742 2.98H3.48c-1.53 0-2.493-1.645-1.743-2.98L8.257 3.1zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z',
    ],
    'info' => [
        'wrapper' => 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800',
        'icon'    => 'text-blue-400',
        'text'    => 'text-blue-800 dark:text-blue-200',
        'path'    => 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z',
    ],
];
?>

<div class="px-4 md:px-6 pt-4 space-y-3">
    <?php foreach ($flashes as $type => $message): ?>
        <?php $style = $styles[$type] ?? $styles['info']; ?>
        <?php foreach ((array) $message as $msg): ?>
            <div class="rounded-lg p-4 border <?= $style['wrapper'] ?>">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 <?= $style['icon'] ?>" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="<?= $style['path'] ?>" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium <?= $style['text'] ?>">
                            <?= $msg ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</div>

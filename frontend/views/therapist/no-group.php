<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var string $message */

$this->title = 'Gruppo Non Assegnato';
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Content Start -->
    <div class="text-center py-12">
        <svg class="mx-auto text-gray-400" style="height: 96px; width: 96px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
        </svg>
        <h2 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Nessun Gruppo Assegnato</h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            <?= Html::encode($message) ?>
        </p>
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            Contatta l'amministratore per essere assegnato a un gruppo di terapisti.
        </p>
        
        <div class="mt-8">
            <?= Html::a('Torna alla Home', ['/site/index'], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-600 border border-transparent rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
            ]) ?>
        </div>
    </div>
    <!-- Content End -->
</div> 
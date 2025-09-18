<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\District;

/* @var $this yii\web\View */
/* @var $model District */
/* @var $patientsCount int */

$this->title = $model->getFullName();
$this->params['breadcrumbs'][] = ['label' => 'Distretti', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Registra CSS personalizzato per la vista
$this->registerCss('
.detail-badge {
    @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
}
.detail-table td {
    @apply border-b border-gray-200 dark:border-gray-700 last:border-b-0;
}
');
?>
<div class="districts-view">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100"><?= Html::encode($this->title) ?></h1>
        <div class="flex gap-2">
            <?= Html::a('Modifica', ['update', 'id' => $model->id], [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500'
            ]) ?>
            <?= Html::a('Elimina', ['delete', 'id' => $model->id], [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500',
                'data' => [
                    'confirm' => 'Sei sicuro di voler eliminare questo distretto?',
                    'method' => 'post',
                ],
            ]) ?>
            <?= Html::a('Torna alla Lista', ['index'], [
                'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
            ]) ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Informazioni Distretto -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Dettagli Distretto</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 w-1/3">
                                        ID
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <?= Html::encode($model->id) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 w-1/3">
                                        Codice
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                            <?= Html::encode($model->code) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 w-1/3">
                                        Nome
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <?= Html::encode($model->name) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 w-1/3">
                                        Riferimento ASL
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        <?= $model->asl_reference ? Html::encode($model->asl_reference) : '<span class="text-gray-400 italic">Non specificato</span>' ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiche -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Statistiche</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">Pazienti Associati</dt>
                            <dd class="mt-1 text-2xl font-semibold text-blue-600 dark:text-blue-400">
                                <?= $patientsCount ?>
                            </dd>
                        </div>
                        
                        <?php if ($patientsCount > 0): ?>
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                Questo distretto ha pazienti associati
                            </div>
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Gestione pazienti disponibile nel frontend
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                Nessun paziente associato
                            </div>
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Il distretto è pronto per essere utilizzato
                            </div>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Azioni Rapide -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mt-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Azioni Rapide</h3>
                </div>
                <div class="px-6 py-4 space-y-3">
                    <?= Html::a('Nuovo Distretto', ['create'], [
                        'class' => 'w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
                    ]) ?>
                    
                    <?= Html::a('Elenco Distretti', ['index'], [
                        'class' => 'w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Informazioni Aggiuntive -->
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    Informazioni sui Distretti
                </h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <ul class="list-disc list-inside space-y-1">
                        <li>I distretti sono utilizzati per organizzare geograficamente i pazienti</li>
                        <li>Ogni paziente può essere associato a un distretto specifico</li>
                        <li>Il codice distretto deve essere univoco nel sistema</li>
                        <li>Il riferimento ASL è opzionale e serve per identificare l'ASL di competenza</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
$(document).ready(function() {
    // Gestione AJAX per eliminazione
    $(document).on('click', 'a[data-method="post"]', function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var message = $link.data('confirm');
        
        if (message && !confirm(message)) {
            return;
        }
        
        $.ajax({
            url: $link.attr('href'),
            type: 'POST',
            data: {
                '_csrf': $('meta[name=csrf-token]').attr('content')
            },
            success: function(response) {
                if (response.status === 'success') {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        location.reload();
                    }
                } else {
                    alert(response.message || 'Errore durante l\'operazione');
                }
            },
            error: function() {
                alert('Errore durante l\'operazione');
            }
        });
    });
});
</script>

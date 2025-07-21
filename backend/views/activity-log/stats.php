<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/** @var yii\web\View $this */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var array $actionStats */
/** @var array $entityStats */
/** @var array $userStats */
/** @var array $dailyStats */

$this->title = 'Statistiche Log Attività';
$this->params['breadcrumbs'][] = ['label' => 'Log Attività', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Statistiche';



// Prepara i dati per i grafici
$actionLabels = [
    'create' => 'Creazione',
    'update' => 'Modifica', 
    'delete' => 'Eliminazione'
];

$actionColors = [
    'create' => '#10b981',
    'update' => '#3b82f6',
    'delete' => '#ef4444'
];

// Registra Chart.js
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js', ['position' => View::POS_HEAD]);
?>

<div class="mx-auto max-w-7xl p-4 md:p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100"><?= Html::encode($this->title) ?></h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Analisi delle attività dal <?= date('d/m/Y', strtotime($dateFrom)) ?> al <?= date('d/m/Y', strtotime($dateTo)) ?>
        </p>
    </div>

    <!-- Filtri Date -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
        <?= Html::beginForm(['stats'], 'get', ['class' => 'flex flex-wrap gap-4']) ?>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Data Inizio</label>
                <?= Html::input('date', 'date_from', $dateFrom, [
                    'class' => 'block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm px-3 py-2'
                ]) ?>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Data Fine</label>
                <?= Html::input('date', 'date_to', $dateTo, [
                    'class' => 'block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm px-3 py-2'
                ]) ?>
            </div>
            <div class="flex items-end gap-2">
                <?= Html::submitButton('Aggiorna', [
                    'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500'
                ]) ?>
                <?= Html::a('Torna alla Lista', ['index'], [
                    'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                ]) ?>
            </div>
        <?= Html::endForm() ?>
    </div>

    <!-- Riepilogo Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <?php
        $totalActions = 0;
        foreach ($actionStats as $stat) {
            $totalActions += $stat['count'];
        }
        $totalCreations = 0;
        $totalUpdates = 0;
        $totalDeletions = 0;
        
        foreach ($actionStats as $stat) {
            switch ($stat['action']) {
                case 'create':
                    $totalCreations = $stat['count'];
                    break;
                case 'update':
                    $totalUpdates = $stat['count'];
                    break;
                case 'delete':
                    $totalDeletions = $stat['count'];
                    break;
            }
        }
        ?>
        
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Totale Azioni</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100"><?= number_format($totalActions) ?></p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Creazioni</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100"><?= number_format($totalCreations) ?></p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Modifiche</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100"><?= number_format($totalUpdates) ?></p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Eliminazioni</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100"><?= number_format($totalDeletions) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafici -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Grafico Azioni -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90 mb-4">Distribuzione per Tipo di Azione</h3>
            <div class="relative h-64">
                <canvas id="actionChart"></canvas>
            </div>
        </div>

        <!-- Grafico Timeline -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90 mb-4">Attività Giornaliere</h3>
            <div class="relative h-64">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabelle Statistiche -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Entità -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Top 10 Entità più Modificate
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Entità</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($entityStats as $stat): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                <?= Html::encode($stat['entity_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                <?= number_format($stat['count']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Utenti -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Top 10 Utenti più Attivi
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Utente</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($userStats as $stat): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            <?= isset($stat['user']) ? Html::encode($stat['user']['username']) : '<span class="text-gray-400">Utente eliminato</span>' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <?= number_format($stat['count']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Prepara i dati per i grafici JavaScript
$actionChartData = [];
$actionChartLabels = [];
$actionChartColors = [];

foreach ($actionStats as $stat) {
    $actionChartLabels[] = $actionLabels[$stat['action']] ?? $stat['action'];
    $actionChartData[] = $stat['count'];
    $actionChartColors[] = $actionColors[$stat['action']] ?? '#6b7280';
}

$dailyChartLabels = [];
$dailyChartData = [];

foreach ($dailyStats as $stat) {
    $dailyChartLabels[] = date('d/m', strtotime($stat['date']));
    $dailyChartData[] = $stat['count'];
}

$this->registerJs("
// Configurazione comune per i grafici
Chart.defaults.font.family = 'Inter, system-ui, -apple-system, sans-serif';

// Rileva il tema dark
const isDarkMode = document.documentElement.classList.contains('dark');
const textColor = isDarkMode ? '#e5e7eb' : '#374151';
const gridColor = isDarkMode ? '#374151' : '#e5e7eb';

// Grafico Azioni
const actionCtx = document.getElementById('actionChart').getContext('2d');
new Chart(actionCtx, {
    type: 'doughnut',
    data: {
        labels: " . json_encode($actionChartLabels) . ",
        datasets: [{
            data: " . json_encode($actionChartData) . ",
            backgroundColor: " . json_encode($actionChartColors) . ",
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    color: textColor,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Grafico Timeline
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: " . json_encode($dailyChartLabels) . ",
        datasets: [{
            label: 'Azioni',
            data: " . json_encode($dailyChartData) . ",
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false,
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: textColor,
                    font: {
                        size: 11
                    }
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: gridColor,
                    borderDash: [5, 5]
                },
                ticks: {
                    color: textColor,
                    font: {
                        size: 11
                    },
                    stepSize: 1
                }
            }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        }
    }
});
", View::POS_READY);
?>
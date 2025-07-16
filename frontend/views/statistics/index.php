<?php

/** @var \yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Statistiche';
$this->params['breadcrumbs'][] = $this->title;

// Registra Chart.js e CSS personalizzato
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', [
    'depends' => [\yii\web\JqueryAsset::class]
]);

// Registra Flatpickr per i datepicker
$this->registerJsFile('https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js');
$this->registerJsFile('https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/it.js');
$this->registerCssFile('https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css');

$this->registerCssFile('@web/css/statistics.css', [
    'depends' => [\frontend\assets\AppAsset::class]
]);

?>

<div class="statistics-dashboard">
    <div class="mx-auto max-w-7xl px-4 pb-8 pt-6 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Statistiche</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Analisi dettagliata dei dati del centro terapeutico</p>
                </div>
                <div class="mt-4 sm:mt-0 flex gap-3">
                    <button id="toggle-filters" type="button" class="stats-button inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 focus-ring">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"></path>
                        </svg>
                        <span id="filter-toggle-text">Nascondi Filtri</span>
                    </button>
                    <button id="refresh-all" type="button" class="stats-button inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus-ring">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Aggiorna Tutto
                    </button>
                </div>
            </div>
        </div>

        <!-- Filtri Migliorati -->
        <div id="filters-section" class="mb-8 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 stats-card filter-container expanded">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Filtri Statistiche</h3>
                    <div class="flex items-center text-sm text-gray-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        I filtri si applicano a tutte le statistiche
                    </div>
                </div>
            </div>
            <div class="p-6">
                <!-- Filtri Rapidi -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Filtri Rapidi</h4>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="quick-filter stats-button px-3 py-1 text-xs rounded-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 dark:hover:bg-blue-900 dark:hover:border-blue-600 dark:hover:text-blue-300" data-period="today">
                            Oggi
                        </button>
                        <button type="button" class="quick-filter stats-button px-3 py-1 text-xs rounded-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 dark:hover:bg-blue-900 dark:hover:border-blue-600 dark:hover:text-blue-300" data-period="week">
                            Questa Settimana
                        </button>
                        <button type="button" class="quick-filter stats-button px-3 py-1 text-xs rounded-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 dark:hover:bg-blue-900 dark:hover:border-blue-600 dark:hover:text-blue-300" data-period="month">
                            Questo Mese
                        </button>
                        <button type="button" class="quick-filter stats-button px-3 py-1 text-xs rounded-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 dark:hover:bg-blue-900 dark:hover:border-blue-600 dark:hover:text-blue-300" data-period="quarter">
                            Ultimo Trimestre
                        </button>
                        <button type="button" class="quick-filter stats-button px-3 py-1 text-xs rounded-full border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 dark:hover:bg-blue-900 dark:hover:border-blue-600 dark:hover:text-blue-300" data-period="year">
                            Quest'Anno
                        </button>
                    </div>
                </div>

                <!-- Filtri Dettagliati -->
                <div class="space-y-6">
                    
                    <!-- Sezione 1: Periodo Temporale - Layout orizzontale ottimizzato -->
                    <div class="filter-section bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
                        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            📅 Periodo di Analisi
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-blue-700 dark:text-blue-300 mb-1">Data Inizio</label>
                                <input type="text" id="date-from" placeholder="Seleziona data inizio..." class="datepicker-input">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-blue-700 dark:text-blue-300 mb-1">Data Fine</label>
                                <input type="text" id="date-to" placeholder="Seleziona data fine..." class="datepicker-input">
                            </div>
                        </div>
                    </div>

                    <!-- Sezione 2: Demografia e Età - Layout ottimizzato -->
                    <div class="filter-section bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
                        <h4 class="text-sm font-semibold text-green-900 dark:text-green-100 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            👥 Demografia Pazienti
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-green-700 dark:text-green-300 mb-1">Genere</label>
                                <select id="gender" class="w-full px-3 py-2 border border-green-300 dark:border-green-600 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-colors duration-200">
                                    <option value="">🔄 Tutti i generi</option>
                                    <option value="M">👨 Maschi</option>
                                    <option value="F">👩 Femmine</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-green-700 dark:text-green-300 mb-1">Età Minima</label>
                                <input type="number" id="age-from" min="0" max="120" placeholder="0" class="w-full px-3 py-2 border border-green-300 dark:border-green-600 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-colors duration-200">
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-green-700 dark:text-green-300 mb-1">Età Massima</label>
                                <input type="number" id="age-to" min="0" max="120" placeholder="120" class="w-full px-3 py-2 border border-green-300 dark:border-green-600 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-colors duration-200">
                            </div>
                        </div>
                    </div>

                    <!-- Sezione 3: Trattamenti - Layout ottimizzato -->
                    <div class="filter-section bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                        <h4 class="text-sm font-semibold text-purple-900 dark:text-purple-100 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 7.172V5L8 4z"></path>
                            </svg>
                            🩺 Selezione Trattamenti
                        </h4>
                        
                        <div class="treatments-container relative">
                            <button type="button" id="treatments-toggle" class="w-full px-3 py-2 text-left border border-purple-300 dark:border-purple-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-purple-900/30 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200">
                                <span id="treatments-text">🔄 Tutti i trattamenti</span>
                                <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <div id="treatments-dropdown" class="hidden absolute z-50 w-full mt-1 bg-white dark:bg-gray-700 border border-purple-300 dark:border-purple-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                <div class="p-2">
                                    <div class="flex items-center p-2 hover:bg-purple-50 dark:hover:bg-purple-600 rounded">
                                        <input type="checkbox" id="select-all-treatments" class="mr-2 text-purple-600 focus:ring-purple-500">
                                        <label for="select-all-treatments" class="text-sm font-medium text-gray-700 dark:text-gray-300">Tutti i trattamenti</label>
                                    </div>
                                    <hr class="my-2 border-purple-200 dark:border-purple-600">
                                    <?php foreach ($treatments as $treatment): ?>
                                        <div class="flex items-center p-2 hover:bg-purple-50 dark:hover:bg-purple-600 rounded">
                                            <input type="checkbox" name="treatments[]" value="<?= $treatment['id'] ?>" id="treatment-<?= $treatment['id'] ?>" class="treatment-checkbox mr-2 text-purple-600 focus:ring-purple-500">
                                            <label for="treatment-<?= $treatment['id'] ?>" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer flex-1">
                                                <span class="font-medium"><?= Html::encode($treatment['name']) ?></span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 block"><?= Html::encode($treatment['code']) ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div id="selected-treatments" class="mt-2 hidden">
                            <div class="flex flex-wrap gap-1" id="treatment-tags">
                                <!-- I tag dei trattamenti selezionati appariranno qui -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pulsanti Azione -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button id="apply-filters" type="button" class="filter-action-btn primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"></path>
                            </svg>
                            Applica Filtri
                        </button>
                        <button id="refresh-all" type="button" class="filter-action-btn success">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Aggiorna Tutto
                        </button>
                        <button id="reset-filters" type="button" class="filter-action-btn secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Azzera Tutto
                        </button>
                        <button id="export-data" type="button" class="filter-action-btn export">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Esporta Dati
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiche Cards -->
        <div class="stats-grid">
            
            <!-- Card Assenze -->
            <div class="stats-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="stats-title text-lg font-medium text-gray-900 dark:text-white">Analisi Assenze</h3>
                        <p class="stats-subtitle text-sm text-gray-500 dark:text-gray-400">Pattern e statistiche delle assenze</p>
                    </div>
                    <button id="load-absences" type="button" class="btn-absences stats-button inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 focus-ring">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Carica
                    </button>
                </div>
                <div class="p-6">
                    <div id="absences-loading" class="hidden text-center py-8">
                        <div class="inline-flex items-center">
                            <svg class="animate-spin h-5 w-5 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Caricamento dati assenze...</span>
                        </div>
                    </div>
                    <div id="absences-content" class="hidden chart-container">
                        <canvas id="absences-chart"></canvas>
                    </div>
                    <div id="absences-empty" class="text-center py-8">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Clicca "Carica" per visualizzare le statistiche delle assenze</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Demografia -->
            <div class="stats-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="stats-title text-lg font-medium text-gray-900 dark:text-white">Demografia Pazienti</h3>
                        <p class="stats-subtitle text-sm text-gray-500 dark:text-gray-400">Distribuzione per età e genere</p>
                        <!-- Pulsanti di scelta tipo distribuzione -->
                        <div class="mt-2 flex gap-2">
                            <button id="demo-gender-btn" type="button" data-demo-type="gender" class="demo-type-btn active text-xs px-3 py-1 rounded-full bg-green-100 text-green-700 border border-green-300 hover:bg-green-200 transition-colors">
                                Genere
                            </button>
                            <button id="demo-age-btn" type="button" data-demo-type="age" class="demo-type-btn text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200 transition-colors">
                                Età
                            </button>
                        </div>
                    </div>
                    <button id="load-demographics" type="button" class="btn-demographics stats-button inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 focus-ring">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Carica
                    </button>
                </div>
                <div class="p-6">
                    <div id="demographics-loading" class="hidden text-center py-8">
                        <div class="inline-flex items-center">
                            <svg class="animate-spin h-5 w-5 text-green-500 mr-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Caricamento demografia...</span>
                        </div>
                    </div>
                    <div id="demographics-content" class="hidden">
                        <!-- Totale Pazienti -->
                        <div id="total-patients-display" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-green-800">Totale Pazienti</span>
                                </div>
                                <span id="total-patients-count" class="text-lg font-bold text-green-900">0</span>
                            </div>
                        </div>
                        
                        <!-- Grafico -->
                        <div class="chart-container">
                            <canvas id="demographics-chart"></canvas>
                        </div>
                    </div>
                    <div id="demographics-empty" class="text-center py-8">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Clicca "Carica" per visualizzare la demografia dei pazienti</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Trattamenti -->
            <div class="stats-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="stats-title text-lg font-medium text-gray-900 dark:text-white">Statistiche Trattamenti</h3>
                        <p class="stats-subtitle text-sm text-gray-500 dark:text-gray-400">Pazienti per tipo di trattamento</p>
                    </div>
                    <button id="load-treatments" type="button" class="btn-treatments stats-button inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 focus-ring">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Carica
                    </button>
                </div>
                <div class="p-6">
                    <div id="treatments-loading" class="hidden text-center py-8">
                        <div class="inline-flex items-center loading-pulse">
                            <svg class="animate-spin h-5 w-5 text-purple-500 mr-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Caricamento trattamenti...</span>
                        </div>
                    </div>
                    <div id="treatments-content" class="hidden chart-container">
                        <canvas id="treatments-chart"></canvas>
                    </div>
                    <div id="treatments-empty" class="text-center py-8">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Clicca "Carica" per visualizzare le statistiche dei trattamenti</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Regimi -->
            <div class="stats-card bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="stats-title text-lg font-medium text-gray-900 dark:text-white">Distribuzione Regimi</h3>
                        <p class="stats-subtitle text-sm text-gray-500 dark:text-gray-400">Pazienti attivi vs dimessi</p>
                    </div>
                    <button id="load-regimes" type="button" class="btn-regimes stats-button inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 focus-ring">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Carica
                    </button>
                </div>
                <div class="p-6">
                    <div id="regimes-loading" class="hidden text-center py-8">
                        <div class="inline-flex items-center loading-pulse">
                            <svg class="animate-spin h-5 w-5 text-orange-500 mr-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Caricamento regimi...</span>
                        </div>
                    </div>
                    <div id="regimes-content" class="hidden chart-container">
                        <canvas id="regimes-chart"></canvas>
                    </div>
                    <div id="regimes-empty" class="text-center py-8">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2zm0 0V3a2 2 0 012-2h2a2 2 0 012 2v2m-6 0h6m0 0v6a2 2 0 01-2 2H9a2 2 0 01-2-2V7a2 2 0 012-2z"></path>
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Clicca "Carica" per visualizzare la distribuzione dei regimi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Funzione per animare il contatore (definita globalmente)
function animateCounter(element, targetValue) {
    console.log('animateCounter called with:', element, targetValue);
    const startValue = 0;
    const duration = 1000; // 1 secondo
    const increment = targetValue / (duration / 16); // 60fps
    let currentValue = startValue;
    
    function updateCounter() {
        currentValue += increment;
        if (currentValue >= targetValue) {
            element.textContent = targetValue;
        } else {
            element.textContent = Math.floor(currentValue);
            requestAnimationFrame(updateCounter);
        }
    }
    
    updateCounter();
}

document.addEventListener('DOMContentLoaded', function() {
    // Variabili globali per i grafici
    let charts = {
        absences: null,
        demographics: null,
        treatments: null,
        regimes: null
    };

    // Gestione filtri
    const filters = {
        dateFrom: document.getElementById('date-from'),
        dateTo: document.getElementById('date-to'),
        ageFrom: document.getElementById('age-from'),
        ageTo: document.getElementById('age-to'),
        gender: document.getElementById('gender'),
        treatments: [] // Array per i trattamenti selezionati
    };

    // Pulsanti azione
    const buttons = {
        applyFilters: document.getElementById('apply-filters'),
        resetFilters: document.getElementById('reset-filters'),
        refreshAll: document.getElementById('refresh-all'),
        loadAbsences: document.getElementById('load-absences'),
        loadDemographics: document.getElementById('load-demographics'),
        loadTreatments: document.getElementById('load-treatments'),
        loadRegimes: document.getElementById('load-regimes'),
        exportData: document.getElementById('export-data')
    };

    // Gestione dropdown trattamenti
    const treatmentsToggle = document.getElementById('treatments-toggle');
    const treatmentsDropdown = document.getElementById('treatments-dropdown');
    const treatmentsText = document.getElementById('treatments-text');
    const selectAllTreatments = document.getElementById('select-all-treatments');
    const treatmentCheckboxes = document.querySelectorAll('.treatment-checkbox');
    const selectedTreatmentsDiv = document.getElementById('selected-treatments');
    const treatmentTags = document.getElementById('treatment-tags');

    // Toggle filtri
    const filtersSection = document.getElementById('filters-section');
    const filterToggleText = document.getElementById('filter-toggle-text');
    let filtersVisible = true;
    
    buttons.toggleFilters = document.getElementById('toggle-filters');
    buttons.toggleFilters.addEventListener('click', function() {
        filtersVisible = !filtersVisible;
        
        if (filtersVisible) {
            filtersSection.classList.remove('collapsed');
            filtersSection.classList.add('expanded');
            filterToggleText.textContent = 'Nascondi Filtri';
        } else {
            filtersSection.classList.remove('expanded');
            filtersSection.classList.add('collapsed');
            filterToggleText.textContent = 'Mostra Filtri';
        }
    });

    // Filtri rapidi
    document.querySelectorAll('.quick-filter').forEach(button => {
        button.addEventListener('click', function() {
            const period = this.getAttribute('data-period');
            setQuickPeriod(period);
            
            // Evidenzia il bottone selezionato
            document.querySelectorAll('.quick-filter').forEach(btn => {
                btn.classList.remove('bg-blue-100', 'border-blue-300', 'text-blue-700', 'dark:bg-blue-900', 'dark:border-blue-600', 'dark:text-blue-300');
            });
            this.classList.add('bg-blue-100', 'border-blue-300', 'text-blue-700', 'dark:bg-blue-900', 'dark:border-blue-600', 'dark:text-blue-300');
            
            showNotification(`Periodo impostato: ${this.textContent}`, 'info');
        });
    });

    function setQuickPeriod(period) {
        const today = new Date();
        let startDate, endDate = today;

        switch (period) {
            case 'today':
                startDate = new Date(today);
                break;
            case 'week':
                startDate = new Date(today);
                startDate.setDate(today.getDate() - 7);
                break;
            case 'month':
                startDate = new Date(today);
                startDate.setMonth(today.getMonth() - 1);
                break;
            case 'quarter':
                startDate = new Date(today);
                startDate.setMonth(today.getMonth() - 3);
                break;
            case 'year':
                startDate = new Date(today);
                startDate.setFullYear(today.getFullYear() - 1);
                break;
        }

        filters.dateFrom.value = startDate.toISOString().split('T')[0];
        filters.dateTo.value = endDate.toISOString().split('T')[0];
    }

    // Gestione dropdown trattamenti
    treatmentsToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        treatmentsDropdown.classList.toggle('hidden');
    });

    // Chiudi dropdown quando si clicca fuori
    document.addEventListener('click', function() {
        treatmentsDropdown.classList.add('hidden');
    });

    // Previeni chiusura quando si clicca dentro al dropdown
    treatmentsDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Gestione "Seleziona tutti"
    selectAllTreatments.addEventListener('change', function() {
        treatmentCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateTreatmentSelection();
    });

    // Gestione singoli checkbox
    treatmentCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateTreatmentSelection();
            
            // Aggiorna stato "Seleziona tutti"
            const checkedCount = Array.from(treatmentCheckboxes).filter(cb => cb.checked).length;
            selectAllTreatments.checked = checkedCount === treatmentCheckboxes.length;
            selectAllTreatments.indeterminate = checkedCount > 0 && checkedCount < treatmentCheckboxes.length;
        });
    });

    function updateTreatmentSelection() {
        const selectedTreatments = Array.from(treatmentCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => ({
                id: cb.value,
                name: cb.parentElement.querySelector('span.font-medium').textContent,
                code: cb.parentElement.querySelector('span.text-xs').textContent
            }));

        filters.treatments = selectedTreatments.map(t => t.id);

        // Aggiorna testo del toggle
        if (selectedTreatments.length === 0) {
            treatmentsText.textContent = '🔄 Tutti i trattamenti';
            selectedTreatmentsDiv.classList.add('hidden');
        } else if (selectedTreatments.length === treatmentCheckboxes.length) {
            treatmentsText.textContent = '🎯 Tutti i trattamenti';
            selectedTreatmentsDiv.classList.add('hidden');
        } else {
            treatmentsText.textContent = `🎯 ${selectedTreatments.length} trattamenti selezionati`;
            
            // Mostra tag dei trattamenti selezionati
            treatmentTags.innerHTML = selectedTreatments.map(treatment => `
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                    ${treatment.name}
                    <button type="button" class="ml-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200" onclick="removeTreatment('${treatment.id}')">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </span>
            `).join('');
            selectedTreatmentsDiv.classList.remove('hidden');
        }
    }

    // Funzione per rimuovere un trattamento dai selezionati
    window.removeTreatment = function(treatmentId) {
        const checkbox = document.getElementById(`treatment-${treatmentId}`);
        if (checkbox) {
            checkbox.checked = false;
            updateTreatmentSelection();
        }
    }

    // Reset filtri
    buttons.resetFilters.addEventListener('click', function() {
        // Reset campi di testo
        Object.values(filters).forEach(filter => {
            if (filter && filter.value !== undefined) {
                filter.value = '';
            }
        });
        
        // Reset trattamenti
        treatmentCheckboxes.forEach(checkbox => checkbox.checked = false);
        selectAllTreatments.checked = false;
        selectAllTreatments.indeterminate = false;
        filters.treatments = [];
        updateTreatmentSelection();
        
        // Reset filtri rapidi
        document.querySelectorAll('.quick-filter').forEach(btn => {
            btn.classList.remove('bg-blue-100', 'border-blue-300', 'text-blue-700', 'dark:bg-blue-900', 'dark:border-blue-600', 'dark:text-blue-300');
        });
        
        // Imposta periodo predefinito
        setQuickPeriod('quarter');
        
        // Feedback visivo
        buttons.resetFilters.classList.add('success-state');
        setTimeout(() => {
            buttons.resetFilters.classList.remove('success-state');
        }, 1000);
        
        showNotification('Filtri ripristinati alle impostazioni predefinite', 'success');
    });

    // Aggiorna tutto
    buttons.refreshAll.addEventListener('click', function() {
        loadAllCharts();
    });

    // Applica filtri e aggiorna automaticamente tutti i grafici
    buttons.applyFilters.addEventListener('click', function() {
        loadAllCharts();
        showNotification('Filtri applicati! Aggiornamento grafici in corso...', 'info');
    });

    // Export dati
    buttons.exportData.addEventListener('click', function() {
        const filters = getFilters();
        
        // Verifica se ci sono dati da esportare
        const hasData = Object.keys(charts).some(chartType => 
            charts[chartType] && charts[chartType].data && charts[chartType].data.labels.length > 0
        );
        
        if (!hasData) {
            showNotification('Nessun dato disponibile per l\'esportazione. Carica prima alcuni grafici.', 'warning');
            return;
        }
        
        // Prepara i dati per Excel
        const exportData = [];
        
        // Aggiungi informazioni sui filtri
        exportData.push(['REPORT STATISTICHE THERAPYCRM']);
        exportData.push(['Generato il:', new Date().toLocaleDateString('it-IT')]);
        exportData.push(['']);
        
        // Aggiungi filtri applicati
        exportData.push(['FILTRI APPLICATI:']);
        if (filters.date_from) exportData.push(['Periodo Da:', filters.date_from]);
        if (filters.date_to) exportData.push(['Periodo A:', filters.date_to]);
        if (filters.gender) exportData.push(['Genere:', filters.gender === 'M' ? 'Maschi' : filters.gender === 'F' ? 'Femmine' : 'Tutti']);
        if (filters.age_from) exportData.push(['Età Minima:', filters.age_from]);
        if (filters.age_to) exportData.push(['Età Massima:', filters.age_to]);
        if (filters.treatments) exportData.push(['Trattamenti:', 'Selezionati (' + filters.treatments.length + ')']);
        exportData.push(['']);
        
        // Aggiungi dati dei grafici
        Object.keys(charts).forEach(chartType => {
            if (charts[chartType] && charts[chartType].data) {
                const chartData = charts[chartType].data;
                const chartTitle = getChartTitle(chartType);
                
                exportData.push([chartTitle.toUpperCase()]);
                exportData.push(['Categoria', 'Valore']);
                
                chartData.labels.forEach((label, index) => {
                    exportData.push([label, chartData.datasets[0].data[index] || 0]);
                });
                
                exportData.push(['']);
            }
        });
        
        // Crea il file Excel
        createExcelFile(exportData, `Statistiche_TherapyCRM_${new Date().toISOString().split('T')[0]}.xlsx`);
        
        showNotification('File Excel generato e scaricato con successo!', 'success');
    });
    
    // Funzione per ottenere il titolo del grafico
    function getChartTitle(type) {
        switch(type) {
            case 'absences': return 'Analisi Assenze';
            case 'demographics': return selectedDemoType === 'gender' ? 'Distribuzione per Genere' : 'Distribuzione per Fasce d\'Età';
            case 'treatments': return 'Statistiche Trattamenti';
            case 'regimes': return 'Status Pazienti';
            default: return 'Statistiche';
        }
    }
    
    // Funzione per creare file Excel
    function createExcelFile(data, filename) {
        // Crea un CSV (più compatibile e non richiede librerie esterne)
        let csvContent = '';
        data.forEach(row => {
            const csvRow = row.map(field => {
                // Gestisci celle che contengono virgole o caratteri speciali
                if (typeof field === 'string' && (field.includes(',') || field.includes('"') || field.includes('\n'))) {
                    return '"' + field.replace(/"/g, '""') + '"';
                }
                return field;
            }).join(',');
            csvContent += csvRow + '\n';
        });
        
        // Aggiungi BOM per supporto UTF-8 in Excel
        const BOM = '\uFEFF';
        const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
        
        // Crea link per download
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename.replace('.xlsx', '.csv'));
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    // Aggiunta gestione pulsante refresh-all se non esiste già
    if (!buttons.refreshAll) {
        const refreshAllBtn = document.getElementById('refresh-all');
        if (refreshAllBtn) {
            refreshAllBtn.addEventListener('click', function() {
                loadAllCharts();
                showNotification('Aggiornamento di tutti i grafici in corso...', 'info');
            });
        }
    }

    // Caricatori individuali
    buttons.loadAbsences.addEventListener('click', () => loadChart('absences'));
    buttons.loadDemographics.addEventListener('click', () => loadChart('demographics'));
    buttons.loadTreatments.addEventListener('click', () => loadChart('treatments'));
    buttons.loadRegimes.addEventListener('click', () => loadChart('regimes'));

    // Pulsanti di selezione tipo demografico
    let selectedDemoType = 'gender'; // Default

    document.querySelectorAll('.demo-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Rimuovi classe active da tutti
            document.querySelectorAll('.demo-type-btn').forEach(b => b.classList.remove('active'));
            
            // Aggiungi classe active al corrente
            this.classList.add('active');
            
            // Aggiorna il tipo selezionato
            selectedDemoType = this.getAttribute('data-demo-type');
            
            // Notifica del cambio
            const typeLabel = selectedDemoType === 'gender' ? 'Genere' : 'Fasce d\'Età';
            showNotification(`Visualizzazione cambiata: Distribuzione per ${typeLabel}`, 'info');
            
            // Ricarica automaticamente il grafico se già caricato
            if (charts.demographics) {
                loadChart('demographics');
            }
        });
    });

    // Funzione per raccogliere filtri
    function getFilters() {
        const baseFilters = {
            date_from: filters.dateFrom.value || null,
            date_to: filters.dateTo.value || null,
            age_from: filters.ageFrom.value || null,
            age_to: filters.ageTo.value || null,
            gender: filters.gender.value || null,
            treatments: filters.treatments.length > 0 ? filters.treatments : null
        };
        
        // Rimuovi i valori null per evitare di inviarli come stringa 'null'
        Object.keys(baseFilters).forEach(key => {
            if (baseFilters[key] === null || baseFilters[key] === '' || baseFilters[key] === 'null') {
                delete baseFilters[key];
            }
        });
        
        // Aggiungi il tipo demografico se disponibile
        if (typeof selectedDemoType !== 'undefined') {
            baseFilters.type = selectedDemoType;
        }
        
        return baseFilters;
    }

    // Funzione per mostrare loading
    function showLoading(type) {
        const loadingEl = document.getElementById(`${type}-loading`);
        const contentEl = document.getElementById(`${type}-content`);
        const emptyEl = document.getElementById(`${type}-empty`);
        
        loadingEl.classList.remove('hidden');
        loadingEl.classList.add('loading-pulse');
        contentEl.classList.add('hidden');
        emptyEl.classList.add('hidden');
        
        // Disabilita il bottone durante il caricamento
        const loadButton = document.getElementById(`load-${type}`);
        if (loadButton) {
            loadButton.disabled = true;
            loadButton.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    // Funzione per nascondere loading
    function hideLoading(type) {
        const loadingEl = document.getElementById(`${type}-loading`);
        loadingEl.classList.add('hidden');
        loadingEl.classList.remove('loading-pulse');
        
        // Riabilita il bottone
        const loadButton = document.getElementById(`load-${type}`);
        if (loadButton) {
            loadButton.disabled = false;
            loadButton.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    // Funzione per mostrare contenuto
    function showContent(type) {
        document.getElementById(`${type}-content`).classList.remove('hidden');
        document.getElementById(`${type}-empty`).classList.add('hidden');
    }

    // Funzione per mostrare messaggio vuoto
    function showEmpty(type) {
        document.getElementById(`${type}-empty`).classList.remove('hidden');
        document.getElementById(`${type}-content`).classList.add('hidden');
    }

    // Funzione principale per caricare un grafico
    function loadChart(type) {
        showLoading(type);
        
        const endpoints = {
            absences: '<?= Url::to(['statistics/absence-stats']) ?>',
            demographics: '<?= Url::to(['statistics/patient-stats']) ?>',
            treatments: '<?= Url::to(['statistics/treatment-stats']) ?>',
            regimes: '<?= Url::to(['statistics/regime-stats']) ?>'
        };

        const params = new URLSearchParams(getFilters());
        
        fetch(`${endpoints[type]}?${params}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': $('meta[name=csrf-token]').attr('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading(type);
            
            if (data.success && data.data) {
                createChart(type, data.data);
                
                // Aggiorna il totale pazienti se è demographics
                if (type === 'demographics' && data.data.total_patients !== undefined) {
                    console.log('Updating total patients:', data.data.total_patients);
                    const totalPatientsElement = document.getElementById('total-patients-count');
                    if (totalPatientsElement) {
                        animateCounter(totalPatientsElement, data.data.total_patients);
                    } else {
                        console.error('Total patients element not found');
                    }
                }
                
                showContent(type);
            } else {
                showEmpty(type);
                console.error(`Errore caricamento ${type}:`, data.message || 'Dati non disponibili');
            }
        })
        .catch(error => {
            hideLoading(type);
            showEmpty(type);
            console.error(`Errore caricamento ${type}:`, error);
            showNotification(`Errore nel caricamento dei dati di ${type}`, 'error');
        });
    }



    // Funzione per caricare tutti i grafici
    function loadAllCharts() {
        Object.keys(charts).forEach(type => {
            loadChart(type);
        });
    }



    // Funzione per mostrare notifiche
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white max-w-sm transition-all duration-300 transform translate-x-full`;
        
        switch (type) {
            case 'success':
                notification.classList.add('bg-green-500');
                break;
            case 'error':
                notification.classList.add('bg-red-500');
                break;
            case 'warning':
                notification.classList.add('bg-yellow-500');
                break;
            default:
                notification.classList.add('bg-blue-500');
        }
        
        notification.innerHTML = `
            <div class="flex items-center">
                <span class="flex-1">${message}</span>
                <button type="button" class="ml-3 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animazione di entrata
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Rimozione automatica dopo 5 secondi
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    // Migliorie per i grafici
    function createChart(type, data) {
        // Distruggi grafico esistente
        if (charts[type]) {
            charts[type].destroy();
        }

        const ctx = document.getElementById(`${type}-chart`).getContext('2d');
        
        // Configurazioni migliorate per i grafici
        const chartConfigs = {
            absences: {
                type: 'bar',
                data: {
                    labels: data.labels || ['Assenze Totali', 'Giustificate', 'Non Giustificate', 'Con Recupero', 'Trattamenti Coinvolti'],
                    datasets: [{
                        label: 'Conteggio',
                        data: data.values || [0, 0, 0, 0, 0],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(147, 51, 234, 0.8)',
                            'rgba(245, 158, 11, 0.8)'
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(34, 197, 94, 1)',
                            'rgba(239, 68, 68, 1)',
                            'rgba(147, 51, 234, 1)',
                            'rgba(245, 158, 11, 1)'
                        ],
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Analisi Assenze',
                            font: { size: 16, weight: 'bold' }
                        },
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.1)' },
                            ticks: { color: '#6b7280' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#6b7280' }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutQuart'
                    }
                }
            },
            demographics: {
                type: 'doughnut',
                data: {
                    labels: data.labels || ['Maschi', 'Femmine'],
                    datasets: [{
                        data: data.values || [0, 0],
                        backgroundColor: generateColors(data.labels ? data.labels.length : 2, 0.8),
                        borderColor: generateColors(data.labels ? data.labels.length : 2, 1),
                        borderWidth: 3,
                        hoverBorderWidth: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: getDemographicsTitle(),
                            font: { size: 16, weight: 'bold' }
                        },
                        legend: {
                            position: 'bottom',
                            labels: { padding: 20, usePointStyle: true }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        duration: 1500
                    }
                }
            },
            treatments: {
                type: 'bar',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        label: 'Pazienti Attivi',
                        data: data.values || [],
                        backgroundColor: 'rgba(147, 51, 234, 0.8)',
                        borderColor: 'rgba(147, 51, 234, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Grafico orizzontale per migliore leggibilità
                    plugins: {
                        title: {
                            display: true,
                            text: 'Top 10 Trattamenti',
                            font: { size: 16, weight: 'bold' }
                        },
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.1)' },
                            ticks: { color: '#6b7280' }
                        },
                        y: {
                            grid: { display: false },
                            ticks: { color: '#6b7280' }
                        }
                    },
                    animation: {
                        duration: 1200,
                        easing: 'easeInOutQuart'
                    }
                }
            },
            regimes: {
                type: 'pie',
                data: {
                    labels: data.labels || ['Piani Attivi', 'Pazienti Dimessi'],
                    datasets: [{
                        data: data.values || [0, 0],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderColor: [
                            'rgba(34, 197, 94, 1)',
                            'rgba(239, 68, 68, 1)'
                        ],
                        borderWidth: 3,
                        hoverBorderWidth: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Status Pazienti',
                            font: { size: 16, weight: 'bold' }
                        },
                        legend: {
                            position: 'bottom',
                            labels: { padding: 20, usePointStyle: true }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        duration: 1500
                    }
                }
            }
        };

        charts[type] = new Chart(ctx, chartConfigs[type]);
        
        // Feedback di successo
        showNotification(`Grafico ${type} caricato con successo`, 'success');
    }

    // Funzione per generare colori unici per i grafici
    function generateColors(count, opacity) {
        // Palette di colori predefiniti per una migliore leggibilità
        const predefinedColors = [
            [59, 130, 246],    // Blu
            [236, 72, 153],    // Rosa
            [34, 197, 94],     // Verde
            [245, 158, 11],    // Arancione
            [147, 51, 234],    // Viola
            [239, 68, 68],     // Rosso
            [6, 182, 212],     // Teal
            [251, 191, 36],    // Giallo
            [107, 114, 128],   // Grigio
            [16, 185, 129]     // Verde smeraldo
        ];
        
        const colors = [];
        for (let i = 0; i < count; i++) {
            if (i < predefinedColors.length) {
                // Usa colori predefiniti
                const [r, g, b] = predefinedColors[i];
                colors.push(`rgba(${r}, ${g}, ${b}, ${opacity})`);
            } else {
                // Genera colori aggiuntivi se necessario
                const hue = (i * 137.5) % 360;
                colors.push(`hsla(${hue}, 70%, 60%, ${opacity})`);
            }
        }
        return colors;
    }

    // Funzione per ottenere il titolo dinamico per la demografia
    function getDemographicsTitle() {
        if (selectedDemoType === 'gender') {
            return 'Distribuzione per Genere';
        } else { // age
            return 'Distribuzione per Fasce d\'Età';
        }
    }

    // Inizializzazione
    function initializeDashboard() {
        // Inizializza datepicker
        flatpickr("#date-from", {
            dateFormat: "Y-m-d",
            locale: "it",
            maxDate: "today",
            allowInput: true,
            clickOpens: true,
            onChange: function(selectedDates, dateStr, instance) {
                // Aggiorna automaticamente il limite minimo del datepicker "A"
                const dateTo = document.querySelector("#date-to")._flatpickr;
                if (dateTo && dateStr) {
                    dateTo.set('minDate', dateStr);
                }
            }
        });
        
        flatpickr("#date-to", {
            dateFormat: "Y-m-d", 
            locale: "it",
            maxDate: "today",
            allowInput: true,
            clickOpens: true,
            onChange: function(selectedDates, dateStr, instance) {
                // Aggiorna automaticamente il limite massimo del datepicker "Da"
                const dateFrom = document.querySelector("#date-from")._flatpickr;
                if (dateFrom && dateStr) {
                    dateFrom.set('maxDate', dateStr);
                }
            }
        });
        
        // Imposta periodo predefinito (ultimo trimestre)
        setQuickPeriod('quarter');
        
        // Evidenzia il filtro rapido predefinito
        const quarterButton = document.querySelector('[data-period="quarter"]');
        if (quarterButton) {
            quarterButton.classList.add('bg-blue-100', 'border-blue-300', 'text-blue-700', 'dark:bg-blue-900', 'dark:border-blue-600', 'dark:text-blue-300');
        }
        
        // Inizializza stato trattamenti
        updateTreatmentSelection();
        
        // Mostra notifica di benvenuto
        setTimeout(() => {
            showNotification('Dashboard Statistiche pronta! Usa i filtri rapidi o personalizzati per iniziare.', 'info');
        }, 1000);
    }

    // Avvia inizializzazione
    initializeDashboard();
});
</script> 
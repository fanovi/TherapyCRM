<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */

$this->title = 'Manuale d\'Uso';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">

    <!-- Header -->
    <div class="text-center mb-10">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white/90">Manuale d'Uso</h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Centro Medico San Luca - Sistema di Gestione Terapeutica</p>
    </div>

    <!-- Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Card Gestionale -->
        <a href="<?= Url::to(['/site/manuale-gestionale']) ?>" class="block group">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6 transition-all hover:shadow-lg hover:border-blue-300 dark:hover:border-blue-700 h-full">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-blue-50 dark:bg-blue-900/20">
                        <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Il Gestionale</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Applicazione Web</p>
                    </div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed mb-4">
                    Guida completa all'utilizzo del gestionale web: gestione pazienti, terapisti, piani terapeutici, calendario, assenze, richieste documenti, statistiche e amministrazione.
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">Pazienti</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">Terapisti</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">Piani</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">Calendario</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">Statistiche</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400">Automatismi</span>
                </div>
                <div class="mt-4 flex items-center text-sm font-medium text-blue-500 group-hover:text-blue-600">
                    Apri il manuale <svg class="ml-1 w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </div>
        </a>

        <!-- Card App Mobile -->
        <a href="<?= Url::to(['/site/manuale-app']) ?>" class="block group">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] p-6 transition-all hover:shadow-lg hover:border-green-300 dark:hover:border-green-700 h-full">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-green-50 dark:bg-green-900/20">
                        <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">L'App Mobile</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Per Pazienti e Terapisti</p>
                    </div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed mb-4">
                    Guida all'utilizzo dell'app mobile: accesso per pazienti e terapisti, calendario appuntamenti, richieste documenti, notifiche push e gestione presenze.
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400">Paziente</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400">Terapista</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400">Calendario</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400">Richieste</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400">Notifiche</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400">Presenze</span>
                </div>
                <div class="mt-4 flex items-center text-sm font-medium text-green-500 group-hover:text-green-600">
                    Apri il manuale <svg class="ml-1 w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            </div>
        </a>

    </div>
</div>

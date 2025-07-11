<?php

use yii\helpers\Url;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $totalPatients int */
/* @var $newPatientsThisMonth int */
/* @var $patientsGrowthPercentage float */
/* @var $totalTherapists int */
/* @var $newTherapistsThisMonth int */
/* @var $totalAppointmentsToday int */
/* @var $completedAppointmentsToday int */
/* @var $upcomingAppointments array */
/* @var $pendingDocumentRequests int */
/* @var $completedDocumentRequestsThisMonth int */
/* @var $requestsGrowthPercentage float */
/* @var $activeTherapeuticPlans int */
/* @var $unreadNotifications int */
/* @var $dailyAppointments array */
/* @var $dayLabels array */
/* @var $requestsData array */

$this->title = 'Dashboard - TherapyCRM';

// Registra ApexCharts per i grafici con versione specifica
$this->registerJsFile('https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
  <div class="space-y-4 md:space-y-6">
    
    <!-- Prima Riga: Metriche Principali (3 colonne uguali) -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6 mb-4">
      <div class="col-span-1">
        <!-- Pazienti Totali -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 h-full">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M8.80443 5.60156C7.59109 5.60156 6.60749 6.58517 6.60749 7.79851C6.60749 9.01185 7.59109 9.99545 8.80443 9.99545C10.0178 9.99545 11.0014 9.01185 11.0014 7.79851C11.0014 6.58517 10.0178 5.60156 8.80443 5.60156ZM5.10749 7.79851C5.10749 5.75674 6.76267 4.10156 8.80443 4.10156C10.8462 4.10156 12.5014 5.75674 12.5014 7.79851C12.5014 9.84027 10.8462 11.4955 8.80443 11.4955C6.76267 11.4955 5.10749 9.84027 5.10749 7.79851ZM4.86252 15.3208C4.08769 16.0881 3.70377 17.0608 3.51705 17.8611C3.48384 18.0034 3.5211 18.1175 3.60712 18.2112C3.70161 18.3141 3.86659 18.3987 4.07591 18.3987H13.4249C13.6343 18.3987 13.7992 18.3141 13.8937 18.2112C13.9797 18.1175 14.017 18.0034 13.9838 17.8611C13.7971 17.0608 13.4132 16.0881 12.6383 15.3208C11.8821 14.572 10.6899 13.955 8.75042 13.955C6.81096 13.955 5.61877 14.572 4.86252 15.3208Z" fill=""/>
            </svg>
          </div>
          <div class="mt-5 flex items-end justify-between">
            <div>
              <span class="text-sm text-gray-500 dark:text-gray-400">Pazienti Totali</span>
              <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                <?= number_format($totalPatients) ?>
              </h4>
            </div>
            <?php if ($patientsGrowthPercentage >= 0): ?>
              <span class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z" fill=""/>
                </svg>
                +<?= abs($patientsGrowthPercentage) ?>%
              </span>
            <?php else: ?>
              <span class="flex items-center gap-1 rounded-full bg-error-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">
                <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M5.31462 10.3761C5.45194 10.5293 5.65136 10.6257 5.87329 10.6257C5.8736 10.6257 5.8739 10.6257 5.87421 10.6257C6.0663 10.6259 6.25845 10.5527 6.40505 10.4062L9.40514 7.4082C9.69814 7.11541 9.69831 6.64054 9.40552 6.34754C9.11273 6.05454 8.63785 6.05438 8.34486 6.34717L6.62329 8.06753L6.62329 1.875C6.62329 1.46079 6.28751 1.125 5.87329 1.125C5.45908 1.125 5.12329 1.46079 5.12329 1.875L5.12329 8.06422L3.40516 6.34719C3.11218 6.05439 2.6373 6.05454 2.3445 6.34752C2.0517 6.64051 2.05185 7.11538 2.34484 7.40818L5.31462 10.3761Z" fill=""/>
                </svg>
                <?= $patientsGrowthPercentage ?>%
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-span-1">
        <!-- Richieste Documenti Pendenti -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 h-full">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M11.665 3.75621C11.8762 3.65064 12.1247 3.65064 12.3358 3.75621L18.7807 6.97856L12.3358 10.2009C12.1247 10.3065 11.8762 10.3065 11.665 10.2009L5.22014 6.97856L11.665 3.75621ZM4.29297 8.19203V16.0946C4.29297 16.3787 4.45347 16.6384 4.70757 16.7654L11.25 20.0366V11.6513C11.1631 11.6205 11.0777 11.5843 10.9942 11.5426L4.29297 8.19203ZM12.75 20.037L19.2933 16.7654C19.5474 16.6384 19.7079 16.3787 19.7079 16.0946V8.19202L13.0066 11.5426C12.9229 11.5844 12.8372 11.6208 12.75 11.6516V20.037Z" fill=""/>
            </svg>
          </div>
          <div class="mt-5 flex items-end justify-between">
            <div>
              <span class="text-sm text-gray-500 dark:text-gray-400">Richieste Pendenti</span>
              <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                <?= number_format($pendingDocumentRequests) ?>
              </h4>
            </div>
            <?php if ($requestsGrowthPercentage >= 0): ?>
              <span class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z" fill=""/>
                </svg>
                +<?= abs($requestsGrowthPercentage) ?>%
              </span>
            <?php else: ?>
              <span class="flex items-center gap-1 rounded-full bg-error-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">
                <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M5.31462 10.3761C5.45194 10.5293 5.65136 10.6257 5.87329 10.6257C5.8736 10.6257 5.8739 10.6257 5.87421 10.6257C6.0663 10.6259 6.25845 10.5527 6.40505 10.4062L9.40514 7.4082C9.69814 7.11541 9.69831 6.64054 9.40552 6.34754C9.11273 6.05454 8.63785 6.05438 8.34486 6.34717L6.62329 8.06753L6.62329 1.875C6.62329 1.46079 6.28751 1.125 5.87329 1.125C5.45908 1.125 5.12329 1.46079 5.12329 1.875L5.12329 8.06422L3.40516 6.34719C3.11218 6.05439 2.6373 6.05454 2.3445 6.34752C2.0517 6.64051 2.05185 7.11538 2.34484 7.40818L5.31462 10.3761Z" fill=""/>
                </svg>
                <?= $requestsGrowthPercentage ?>%
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-span-1 md:col-span-2 xl:col-span-1">
        <!-- Attività di Oggi (Riprogettato) -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 h-full">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M6.75 2.25C6.75 1.83579 7.08579 1.5 7.5 1.5C7.91421 1.5 8.25 1.83579 8.25 2.25V3H15.75V2.25C15.75 1.83579 16.0858 1.5 16.5 1.5C16.9142 1.5 17.25 1.83579 17.25 2.25V3H18C19.6569 3 21 4.34315 21 6V18C21 19.6569 19.6569 21 18 21H6C4.34315 21 3 19.6569 3 18V6C3 4.34315 4.34315 3 6 3H6.75V2.25ZM19.5 9H4.5V18C4.5 18.8284 5.17157 19.5 6 19.5H18C18.8284 19.5 19.5 18.8284 19.5 18V9Z" fill=""/>
            </svg>
          </div>
          <div class="mt-5 flex items-end justify-between">
            <div>
              <span class="text-sm text-gray-500 dark:text-gray-400">Appuntamenti Oggi</span>
              <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                <?= $completedAppointmentsToday ?>/<?= $totalAppointmentsToday ?>
              </h4>
            </div>
            <?php if ($totalAppointmentsToday > 0): ?>
              <?php $percentage = round(($completedAppointmentsToday / $totalAppointmentsToday) * 100); ?>
              <span class="flex items-center gap-1 rounded-full bg-brand-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-500">
                <?= $percentage ?>%
              </span>
            <?php else: ?>
              <span class="flex items-center gap-1 rounded-full bg-gray-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-gray-600 dark:bg-gray-500/15 dark:text-gray-500">
                0%
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Seconda Riga: Grafici (2 colonne: 2/3 + 1/3) -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 md:gap-6 mb-4">
     

      <div class="col-span-1 xl:col-span-1">
        <!-- Stato Richieste Documenti -->
        <div class="rounded-2xl border border-gray-200 bg-white px-5 pb-5 pt-5 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 sm:pt-6 h-full">
          <div class="flex flex-col gap-2 mb-6">
            <div class="w-full">
              <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                Stato Richieste Documenti
              </h3>
              <p class="mt-1 text-gray-500 text-theme-sm dark:text-gray-400">
                Flusso di lavorazione delle richieste
              </p>
            </div>
          </div>
          <div class="max-w-full overflow-x-auto custom-scrollbar">
            <div id="requestsStatusChart" class="-ml-4 min-w-[350px] pl-2" data-requests='<?= json_encode($requestsData) ?>'></div>
          </div>
        </div>
      </div>
      <div class="col-span-1 xl:col-span-1">
        <!-- Statistiche Sistema -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6 h-full">
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800 mb-5">
            <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M3 6C3 4.34315 4.34315 3 6 3H18C19.6569 3 21 4.34315 21 6V18C21 19.6569 19.6569 21 18 21H6C4.34315 21 3 19.6569 3 18V6ZM6 5C5.44772 5 5 5.44772 5 6V8H19V6C19 5.44772 18.5523 5 18 5H6ZM19 10H5V18C5 18.5523 5.44772 19 6 19H18C18.5523 19 19 18.5523 19 18V10Z" fill=""/>
            </svg>
          </div>
          
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <span class="text-sm text-gray-500 dark:text-gray-400">Terapisti Attivi</span>
                <h4 class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">
                  <?= $totalTherapists ?>
                </h4>
              </div>
              <span class="flex items-center gap-1 rounded-full bg-success-50 py-1 px-2 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z" fill=""/>
                </svg>
                Attivo
              </span>
            </div>
            
            <div class="h-px bg-gray-200 dark:bg-gray-800"></div>
            
            <div class="flex items-center justify-between">
              <div>
                <span class="text-sm text-gray-500 dark:text-gray-400">Piani Terapeutici</span>
                <h4 class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">
                  <?= $activeTherapeuticPlans ?>
                </h4>
              </div>
              <span class="flex items-center gap-1 rounded-full bg-brand-50 py-1 px-2 text-sm font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-500">
                In corso
              </span>
            </div>
            
            <div class="h-px bg-gray-200 dark:bg-gray-800"></div>
            
            <div class="flex items-center justify-between">
              <div>
                <span class="text-sm text-gray-500 dark:text-gray-400">Notifiche</span>
                <h4 class="mt-1 text-lg font-bold text-gray-800 dark:text-white/90">
                  <?= $unreadNotifications ?>
                </h4>
              </div>
              <?php if ($unreadNotifications > 0): ?>
                <span class="flex items-center gap-1 rounded-full bg-warning-50 py-1 px-2 text-sm font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">
                  <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.31462 10.3761C5.45194 10.5293 5.65136 10.6257 5.87329 10.6257C5.8736 10.6257 5.8739 10.6257 5.87421 10.6257C6.0663 10.6259 6.25845 10.5527 6.40505 10.4062L9.40514 7.4082C9.69814 7.11541 9.69831 6.64054 9.40552 6.34754C9.11273 6.05454 8.63785 6.05438 8.34486 6.34717L6.62329 8.06753L6.62329 1.875C6.62329 1.46079 6.28751 1.125 5.87329 1.125C5.45908 1.125 5.12329 1.46079 5.12329 1.875L5.12329 8.06422L3.40516 6.34719C3.11218 6.05439 2.6373 6.05454 2.3445 6.34752C2.0517 6.64051 2.05185 7.11538 2.34484 7.40818L5.31462 10.3761Z" fill=""/>
                  </svg>
                  Da leggere
                </span>
              <?php else: ?>
                <span class="flex items-center gap-1 rounded-full bg-success-50 py-1 px-2 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                  Nessuna
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="col-span-1 xl:col-span-2">
        <!-- Nuovi Pazienti -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 h-full">
          <div class="flex justify-between">
            <div>
              <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                Nuovi Pazienti
              </h3>
              <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                Pazienti registrati questo mese
              </p>
            </div>
          </div>
          <div class="border-gary-200 my-6 overflow-hidden rounded-2xl border bg-gray-50 px-4 py-6 dark:border-gray-800 dark:bg-gray-900 sm:px-6">
            <div class="text-center">
              <div class="text-6xl font-bold text-brand-500 mb-4">
                <?= $newPatientsThisMonth ?>
              </div>
              <p class="text-lg text-gray-600 dark:text-gray-400">
                Nuovi pazienti questo mese
              </p>
              <?php if ($patientsGrowthPercentage != 0): ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                  <?= $patientsGrowthPercentage >= 0 ? '+' : '' ?><?= $patientsGrowthPercentage ?>% rispetto al mese scorso
                </p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

   

    <!-- Quarta Riga: Tabella Prossimi Appuntamenti (fullwidth) -->
    <div class="grid grid-cols-1 gap-4 md:gap-6 mt-4">
      <div class="col-span-1">
        <!-- Prossimi Appuntamenti -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 h-full">
          <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                Prossimi Appuntamenti
              </h3>
            </div>
            <div class="flex items-center gap-3">
              <a href="<?= Url::to(['/appointment/index']) ?>" 
                 class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700
dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200">
               Vedi tutti
             </a>
           </div>
         </div>

         <div class="w-full overflow-x-auto">
           <table class="min-w-full">
             <thead>
               <tr class="border-gray-100 border-y dark:border-gray-800">
                 <th class="py-3">
                   <div class="flex items-center">
                     <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                       Paziente
                     </p>
                   </div>
                 </th>
                 <th class="py-3">
                   <div class="flex items-center">
                     <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                       Terapista
                     </p>
                   </div>
                 </th>
                 <th class="py-3">
                   <div class="flex items-center">
                     <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                       Data e Ora
                     </p>
                   </div>
                 </th>
                 <th class="py-3">
                   <div class="flex items-center">
                     <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                       Stato
                     </p>
                   </div>
                 </th>
               </tr>
             </thead>
             <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
               <?php if (empty($upcomingAppointments)): ?>
                 <tr>
                   <td colspan="4" class="py-8 text-center">
                     <div class="text-gray-500 dark:text-gray-400">
                       <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                       </svg>
                       <p class="text-sm">Nessun appuntamento programmato</p>
                     </div>
                   </td>
                 </tr>
               <?php else: ?>
                 <?php foreach ($upcomingAppointments as $appointment): ?>
                   <tr>
                     <td class="py-3">
                       <div class="flex items-center">
                         <div class="flex items-center gap-3">
                           <div class="h-[40px] w-[40px] overflow-hidden rounded-full bg-brand-100 flex items-center justify-center">
                             <span class="text-brand-600 font-semibold">
                               <?php if ($appointment->patient && $appointment->patient->first_name && $appointment->patient->last_name): ?>
                                 <?= substr($appointment->patient->first_name, 0, 1) . substr($appointment->patient->last_name, 0, 1) ?>
                               <?php else: ?>
                                 --
                               <?php endif; ?>
                             </span>
                           </div>
                           <div>
                             <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                               <?= $appointment->patient ? Html::encode($appointment->patient->getFullName()) : 'Paziente non trovato' ?>
                             </p>
                             <span class="text-gray-500 text-theme-xs dark:text-gray-400">
                               ID: <?= $appointment->patient ? $appointment->patient->id : 'N/A' ?>
                             </span>
                           </div>
                         </div>
                       </div>
                     </td>
                     <td class="py-3">
                       <div class="flex items-center">
                         <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                           <?php if ($appointment->therapist && $appointment->therapist->user && $appointment->therapist->user->profile): ?>
                             <?= Html::encode($appointment->therapist->user->profile->first_name . ' ' . $appointment->therapist->user->profile->last_name) ?>
                           <?php else: ?>
                             Terapista non assegnato
                           <?php endif; ?>
                         </p>
                       </div>
                     </td>
                     <td class="py-3">
                       <div class="flex items-center">
                         <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                           <?= date('d/m/Y H:i', strtotime($appointment->appointment_datetime)) ?>
                         </p>
                       </div>
                     </td>
                     <td class="py-3">
                       <div class="flex items-center">
                         <p class="rounded-full bg-warning-50 px-2 py-0.5 text-theme-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-orange-400">
                           Programmato
                         </p>
                       </div>
                     </td>
                   </tr>
                 <?php endforeach; ?>
               <?php endif; ?>
             </tbody>
           </table>
         </div>
       </div>
     </div>
   </div>
 </div>
</div>
<!-- JavaScript per i grafici -->
<script>
// Funzione per creare il grafico degli appuntamenti degli ultimi 7 giorni
function createDailyChart() {
  const chartElement = document.getElementById('chartOne');
  if (!chartElement) {
    console.error('Elemento chartOne non trovato');
    return;
  }
  
  const dailyData = chartElement.getAttribute('data-daily');
  const labelsData = chartElement.getAttribute('data-labels');
  
  if (!dailyData || !labelsData) {
    console.error('Dati per chartOne mancanti');
    return;
  }
  
  const data = JSON.parse(dailyData);
  const labels = JSON.parse(labelsData);
  
  const options = {
    chart: {
      type: 'line',
      height: 350,
      toolbar: {
        show: false
      }
    },
    series: [{
      name: 'Appuntamenti',
      data: data
    }],
    xaxis: {
      categories: labels,
      axisBorder: {
        show: false
      },
      axisTicks: {
        show: false
      }
    },
    colors: ['#3B82F6'],
    stroke: {
      curve: 'smooth',
      width: 3
    },
    grid: {
      borderColor: '#f1f5f9'
    },
    markers: {
      size: 4
    }
  };

  if (typeof ApexCharts !== 'undefined') {
    const targetElement = document.querySelector("#chartOne");
    if (targetElement) {
      const chart = new ApexCharts(targetElement, options);
      chart.render();
    } else {
      console.error('Target element #chartOne non trovato per il rendering');
    }
  }
}

// Funzione per creare il grafico delle richieste documenti
function createRequestsChart() {
  const chartElement = document.getElementById('requestsStatusChart');
  if (!chartElement) {
    console.error('Elemento requestsStatusChart non trovato');
    return;
  }
  
  const requestsData = chartElement.getAttribute('data-requests');
  if (!requestsData) {
    console.error('Dati per requestsStatusChart mancanti');
    return;
  }
  
  const data = JSON.parse(requestsData);
  
  const categories = data.map(item => item.status_name || 'Sconosciuto');
  const values = data.map(item => parseInt(item.count));
  
  // Colori specifici per ogni stato del workflow sanitario
  const statusColors = {
    'Inviata': '#3B82F6',           // Blu - nuovo/iniziale
    'Presa in carico': '#F59E0B',   // Giallo/arancione - in lavorazione
    'Stampato': '#8B5CF6',          // Viola - quasi completato
    'Consegnato': '#10B981',        // Verde - completato
    'Richieste Presenti': '#6B7280' // Grigio - fallback
  };
  
  // Assegna colori basati sul nome dello stato
  const chartColors = categories.map(status => statusColors[status] || '#6B7280');
  
  const options = {
    chart: {
      type: 'donut',
      height: 280,
      toolbar: {
        show: false
      }
    },
    series: values,
    labels: categories,
    colors: chartColors,
    plotOptions: {
      pie: {
        donut: {
          size: '65%',
          labels: {
            show: true,
            total: {
              show: true,
              label: 'Totale',
              fontSize: '16px',
              fontWeight: 600,
              color: '#374151',
              formatter: function (w) {
                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
              }
            }
          }
        }
      }
    },
    legend: {
      position: 'bottom',
      fontSize: '14px',
      markers: {
        width: 12,
        height: 12
      }
    },
    dataLabels: {
      enabled: true,
      style: {
        fontSize: '12px',
        fontWeight: 'bold'
      },
      formatter: function(val, opts) {
        return Math.round(val) + '%';
      }
    },
    tooltip: {
      y: {
        formatter: function(val, opts) {
          return val + ' richieste';
        }
      }
    },
    responsive: [{
      breakpoint: 480,
      options: {
        chart: {
          height: 300
        },
        legend: {
          position: 'bottom',
          fontSize: '12px'
        }
      }
    }]
  };

  if (typeof ApexCharts !== 'undefined') {
    const targetElement = document.querySelector("#requestsStatusChart");
    if (targetElement) {
      const chart = new ApexCharts(targetElement, options);
      chart.render();
    } else {
      console.error('Target element #requestsStatusChart non trovato per il rendering');
    }
  }
}

// Funzione per verificare se ApexCharts è disponibile
function waitForApexCharts(callback, attempts = 50) {
  if (typeof ApexCharts !== 'undefined') {
    callback();
  } else if (attempts > 0) {
    setTimeout(() => waitForApexCharts(callback, attempts - 1), 100);
  } else {
    console.error('ApexCharts non è disponibile dopo 5 secondi');
  }
}

// Inizializza i grafici quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function() {
  // Aspetta che ApexCharts sia caricato correttamente
  waitForApexCharts(function() {
    try {
      createDailyChart();
      createRequestsChart();
    } catch (error) {
      console.error('Errore nella creazione dei grafici:', error);
    }
  });
});
</script>
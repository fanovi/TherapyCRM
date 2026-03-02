<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $rolesData */

$this->title = 'Permessi Ruoli';

$roleLabels = [
    'super_admin' => 'Super Admin',
    'admin' => 'Amministratore',
    'manager' => 'Manager',
    'coordinator' => 'Coordinatore',
    'therapist' => 'Terapista',
    'patient' => 'Paziente',
    'patient_family' => 'Familiare Paziente',
];
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: 'Permessi Ruoli'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>

            <nav>
                <ol class="flex items-center gap-1.5">
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= Url::to(['/site/index']) ?>">
                            Home
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName"></li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Ruoli del Sistema
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestisci i permessi assegnati a ciascun ruolo. Le modifiche si applicano a tutti gli utenti con quel ruolo.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left font-medium text-gray-700 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50">Ruolo</th>
                        <th class="px-5 py-3 text-left font-medium text-gray-700 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50">Descrizione</th>
                        <th class="px-5 py-3 text-center font-medium text-gray-700 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50">Permessi</th>
                        <th class="px-5 py-3 text-right font-medium text-gray-700 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rolesData as $data): ?>
                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-5 py-4 font-medium text-gray-800 dark:text-white/90">
                            <?= Html::encode($roleLabels[$data['model']->name] ?? ucfirst($data['model']->name)) ?>
                            <code class="block text-xs text-gray-400 mt-0.5"><?= Html::encode($data['model']->name) ?></code>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-400">
                            <?= Html::encode($data['model']->description ?: '-') ?>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-100 text-brand-800 dark:bg-brand-900 dark:text-brand-200">
                                <?= $data['permissionCount'] ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <?= Html::a(
                                '<svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>Gestisci',
                                ['view-role', 'name' => $data['model']->name],
                                ['class' => 'inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2']
                            ) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

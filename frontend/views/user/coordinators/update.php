<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $profile common\models\UserProfile */
/* @var $form yii\widgets\ActiveForm */

$this->title = 'Modifica Coordinatore: ' . ($user->profile ? $user->profile->nome . ' ' . $user->profile->cognome : $user->username);
$this->params['breadcrumbs'][] = ['label' => 'Coordinatori', 'url' => ['coordinators']];
$this->params['breadcrumbs'][] = ['label' => ($user->profile ? $user->profile->nome . ' ' . $user->profile->cognome : $user->username), 'url' => ['view-coordinator', 'id' => $user->id]];
$this->params['breadcrumbs'][] = 'Modifica';
?>

<div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <nav>
                <ol class="flex items-center gap-1.5">
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/site/index']) ?>">
                            Home
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/user/coordinators']) ?>">
                            Coordinatori
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/user/view-coordinator', 'id' => $user->id]) ?>">
                            <?= Html::encode($user->profile ? $user->profile->nome . ' ' . $user->profile->cognome : $user->username) ?>
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li class="text-sm text-gray-800 dark:text-white/90">Modifica</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Form Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Modifica Dati Coordinatore
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Aggiorna le informazioni del coordinatore.
            </p>
        </div>
        
        <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
            <?php $form = ActiveForm::begin([
                'id' => 'coordinator-form',
                'options' => ['class' => 'space-y-6'],
            ]); ?>
            
            <!-- Account Information -->
            <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg">
                <h4 class="text-sm font-medium text-gray-800 dark:text-white/90 mb-4">Informazioni Account</h4>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Username -->
                    <div>
                        <?= $form->field($user, 'username')->textInput([
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                            'placeholder' => 'Inserisci username'
                        ])->label('Username', [
                            'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                        ]) ?>
                    </div>

                    <!-- Email -->
                    <div>
                        <?= $form->field($user, 'email')->input('email', [
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                            'placeholder' => 'coordinator@example.com'
                        ])->label('Email', [
                            'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                        ]) ?>
                    </div>
                </div>
                
                <!-- Status -->
                <div class="mt-6">
                    <?= $form->field($user, 'status')->dropDownList([
                        10 => 'Attivo',
                        0 => 'Inattivo'
                    ], [
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30'
                    ])->label('Stato', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg">
                <h4 class="text-sm font-medium text-gray-800 dark:text-white/90 mb-4">Informazioni Personali</h4>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Nome -->
                    <div>
                        <?= $form->field($profile, 'nome')->textInput([
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                            'placeholder' => 'Inserisci il nome'
                        ])->label('Nome', [
                            'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                        ]) ?>
                    </div>

                    <!-- Cognome -->
                    <div>
                        <?= $form->field($profile, 'cognome')->textInput([
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                            'placeholder' => 'Inserisci il cognome'
                        ])->label('Cognome', [
                            'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                        ]) ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mt-6">
                    <!-- Telefono -->
                    <div>
                        <?= $form->field($profile, 'telefono')->textInput([
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                            'placeholder' => '+39 123 456 7890'
                        ])->label('Telefono', [
                            'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                        ]) ?>
                    </div>

                    <!-- Data di nascita -->
                    <div>
                        <?= $form->field($profile, 'data_nascita')->textInput([
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                            'type' => 'date'
                        ])->label('Data di Nascita', [
                            'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                        ]) ?>
                    </div>
                </div>

                <!-- Sesso -->
                <div class="mt-6">
                    <?= $form->field($profile, 'sesso')->dropDownList([
                        '' => 'Seleziona sesso',
                        'M' => 'Maschio',
                        'F' => 'Femmina'
                    ], [
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30'
                    ])->label('Sesso', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <!-- Indirizzo -->
                <div class="mt-6">
                    <?= $form->field($profile, 'indirizzo')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Via Roma, 123'
                    ])->label('Indirizzo', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 mt-6">
                    <!-- Città -->
                    <div>
                        <?= $form->field($profile, 'citta')->textInput([
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                            'placeholder' => 'Roma'
                        ])->label('Città', [
                            'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                        ]) ?>
                    </div>

                    <!-- CAP -->
                    <div>
                        <?= $form->field($profile, 'cap')->textInput([
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                            'placeholder' => '00100'
                        ])->label('CAP', [
                            'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                        ]) ?>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-800">
                <?= Html::a('Annulla', ['view-coordinator', 'id' => $user->id], [
                    'class' => 'px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-700'
                ]) ?>
                
                <?= Html::submitButton('Aggiorna Coordinatore', [
                    'class' => 'px-6 py-2.5 text-sm font-medium text-white bg-brand-600 border border-transparent rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800'
                ]) ?>
            </div>
            
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div> 
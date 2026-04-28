<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $patient common\models\Patient */
/* @var $user common\models\User */
/* @var $profile common\models\UserProfile */
/* @var $accountPatient common\models\AccountPatient */
/* @var $relationshipLabels array */
/* @var $form yii\widgets\ActiveForm */

$this->title = 'Crea Credenziali per ' . $patient->last_name . ' ' . $patient->first_name;
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $patient->fullName, 'url' => ['view', 'id' => $patient->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
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
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/patient/index']) ?>">
                            Pazienti
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/patient/view', 'id' => $patient->id]) ?>">
                            <?= Html::encode($patient->fullName) ?>
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                    </li>
                    <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName"></li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <?php
    $relationshipType = (string) ($accountPatient->relationship_type ?? '');
    $alpineState = json_encode([
        'relationship' => $relationshipType,
        'showPassword' => false,
        'showPasswordRepeat' => false,
    ]);
    ?>

    <?php $form = ActiveForm::begin([
        'id' => 'credentials-form',
        'enableClientValidation' => true,
        'validateOnSubmit' => true,
        'validateOnChange' => true,
        'validateOnBlur' => true,
        'options' => [
            'class' => 'space-y-6',
            'x-data' => $alpineState,
        ],
        'fieldConfig' => [
            'errorOptions' => ['class' => 'text-red-500 text-sm mt-1 help-block-error'],
        ],
    ]); ?>

    <style>
        .has-error .form-control {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 1px #ef4444 !important;
        }

        .help-block-error {
            color: #ef4444 !important;
        }
    </style>

    <!-- Informazioni Paziente -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Informazioni Paziente
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Dati del paziente per cui creare le credenziali di accesso.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Nome</label>
                    <div class="text-sm text-gray-900 dark:text-white/90"><?= Html::encode($patient->first_name) ?></div>
                </div>

                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Cognome</label>
                    <div class="text-sm text-gray-900 dark:text-white/90"><?= Html::encode($patient->last_name) ?></div>
                </div>

                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Codice Fiscale</label>
                    <div class="text-sm text-gray-900 dark:text-white/90"><?= Html::encode($patient->fiscal_code ?: '-') ?></div>
                </div>

                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">Data di Nascita</label>
                    <div class="text-sm text-gray-900 dark:text-white/90"><?= Html::encode(Yii::$app->formatter->asDate($patient->birth_date)) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Relazione con il Paziente -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Chi accede all'app?
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Specifica chi sara' la persona collegata a questo account.
                Se e' il paziente stesso, i dati anagrafici verranno presi
                automaticamente dalla sua scheda.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <?= $form->field($accountPatient, 'relationship_type')->dropDownList($relationshipLabels, [
                        'prompt' => 'Seleziona tipo di relazione...',
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'x-model' => 'relationship',
                    ])->label('Tipo di Relazione', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-2" x-show="relationship && relationship !== 'self'" x-cloak>
                    <?= $form->field($accountPatient, 'has_parental_authority', [
                        'template' => '<div class="flex items-center">{input}{label}</div>{error}{hint}',
                        'labelOptions' => [
                            'class' => 'ml-2 text-sm font-medium text-gray-700 dark:text-gray-300'
                        ],
                        'options' => ['class' => '']
                    ])->checkbox([
                        'class' => 'w-4 h-4 text-brand-600 bg-gray-100 border-gray-300 rounded focus:ring-brand-500 dark:focus:ring-brand-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600',
                        'uncheck' => '0',
                        'value' => '1'
                    ])->label(false) ?>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Seleziona se questa persona ha l'autorita' genitoriale sul paziente.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Informazioni Profilo (solo se familiare/tutore/altro) -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
         x-show="relationship && relationship !== 'self'"
         x-cloak>
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati di chi accede
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Nome, cognome e contatti della persona che utilizzera' l'account
                (genitore, tutore o altro familiare).
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-1">
                    <?= $form->field($profile, 'first_name')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Inserisci nome',
                    ])->label('Nome', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-1">
                    <?= $form->field($profile, 'last_name')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Inserisci cognome',
                    ])->label('Cognome', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-1">
                    <?= $form->field($profile, 'fiscal_code')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'RSSMRA80E45F205T',
                    ])->label('Codice Fiscale', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-1">
                    <?= $form->field($profile, 'phone')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => '+39 123 456 7890',
                    ])->label('Telefono', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-2">
                    <?= $form->field($profile, 'address')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Via Roma 1, 00100 Roma (RM)',
                    ])->label('Indirizzo', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Riassunto dati paziente in modalita' "io stesso" -->
    <div class="rounded-2xl border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800"
         x-show="relationship === 'self'"
         x-cloak>
        <div class="px-5 py-4 sm:px-6 sm:py-5 flex items-start gap-3">
            <svg class="h-5 w-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    L'account verra' creato a nome del paziente
                </p>
                <p class="mt-1 text-xs text-blue-700 dark:text-blue-300">
                    Nome, cognome, codice fiscale e telefono vengono presi dalla scheda del paziente, non c'e' bisogno di inserirli di nuovo. Saranno modificabili dalla scheda paziente.
                </p>
                <dl class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 text-xs">
                    <div class="flex gap-2">
                        <dt class="text-blue-700 dark:text-blue-300">Nome:</dt>
                        <dd class="font-medium text-blue-900 dark:text-blue-100"><?= Html::encode($patient->first_name) ?></dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="text-blue-700 dark:text-blue-300">Cognome:</dt>
                        <dd class="font-medium text-blue-900 dark:text-blue-100"><?= Html::encode($patient->last_name) ?></dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="text-blue-700 dark:text-blue-300">Codice Fiscale:</dt>
                        <dd class="font-medium text-blue-900 dark:text-blue-100"><?= Html::encode($patient->fiscal_code ?: '-') ?></dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="text-blue-700 dark:text-blue-300">Telefono:</dt>
                        <dd class="font-medium text-blue-900 dark:text-blue-100"><?= Html::encode($patient->phone_number ?: '-') ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Credenziali di Accesso -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
         x-show="relationship"
         x-cloak>
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Credenziali di Accesso
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Email e password per l'accesso all'app mobile. La password e' stata generata automaticamente: puoi modificarla o rigenerarla con il bottone.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <?= $form->field($user, 'email')->input('email', [
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'inserisci.email@esempio.com'
                    ])->label('Email di Accesso', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-1">
                    <?= $form->field($user, 'password', [
                        'template' => '{label}<div class="relative">{input}<button type="button" tabindex="-1" @click="showPassword = !showPassword" class="absolute top-1/2 right-3 z-10 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" :title="showPassword ? \'Nascondi password\' : \'Mostra password\'"><svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg></button></div>{error}',
                    ])->passwordInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none pl-4 pr-11 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => '8-20 caratteri con maiuscola, minuscola, numero e speciale',
                        'id' => 'credentials-password',
                        'autocomplete' => 'new-password',
                        ':type' => "showPassword ? 'text' : 'password'",
                    ])->label('Password', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-1">
                    <?= $form->field($user, 'password_repeat', [
                        'template' => '{label}<div class="relative">{input}<button type="button" tabindex="-1" @click="showPasswordRepeat = !showPasswordRepeat" class="absolute top-1/2 right-3 z-10 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" :title="showPasswordRepeat ? \'Nascondi password\' : \'Mostra password\'"><svg x-show="!showPasswordRepeat" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><svg x-show="showPasswordRepeat" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg></button></div>{error}',
                    ])->passwordInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none pl-4 pr-11 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Ripeti la password',
                        'id' => 'credentials-password-repeat',
                        'autocomplete' => 'new-password',
                        ':type' => "showPasswordRepeat ? 'text' : 'password'",
                    ])->label('Conferma Password', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <div class="sm:col-span-2 flex items-center justify-between gap-3 pt-1">
                    <button type="button"
                            id="regenerate-password-btn"
                            class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-brand-700 bg-brand-50 border border-brand-200 rounded-lg hover:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-1 dark:bg-brand-900/30 dark:text-brand-200 dark:border-brand-800 dark:hover:bg-brand-900/50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Rigenera password casuale
                    </button>
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                        Verra' inviata via email e mostrata nel PDF.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center justify-between gap-4 pt-6">
        <div>
            <?= Html::a(
                '<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Annulla',
                ['view', 'id' => $patient->id],
                [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
                ]
            ) ?>
        </div>

        <div class="flex space-x-3">
            <?= Html::submitButton('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Crea Credenziali', [
                'class' => 'inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',
                'id' => 'submit-button',
                'style' => 'background-color: #2563eb !important; color: white !important;',
                ':disabled' => '!relationship',
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$this->registerJs("
// Funzione per mostrare notifiche toast (stesso stile di Statistics)
function showNotification(message, type = 'info') {
    // Notifica vanilla con lo stesso stile di Statistics
    var notification = document.createElement('div');
    notification.className = 'vanilla-notification notification-' + type;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 4px;
        color: white;
        font-size: 14px;
        z-index: 99999;
        opacity: 0;
        transition: opacity 0.3s;
        max-width: 300px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    `;
    
    // Colori per tipo (stesso stile di Statistics)
    var colors = {
        'success': '#1cc88a',
        'error': '#e74a3b',
        'warning': '#f6c23e',
        'info': '#36b9cc'
    };
    
    notification.style.backgroundColor = colors[type] || colors.info;
    
    document.body.appendChild(notification);
    
    // Mostra con animazione
    setTimeout(function() {
        notification.style.opacity = '1';
    }, 10);
    
    // Rimuovi dopo 3 secondi
    setTimeout(function() {
        notification.style.opacity = '0';
        setTimeout(function() {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

$(document).ready(function() {
    $('#credentials-form').on('beforeSubmit', function(e) {
        e.preventDefault();
        
        var \$form = $(this);
        var \$submitBtn = $('#submit-button');
        
        // Disable submit button and show loading
        \$submitBtn.prop('disabled', true);
        \$submitBtn.html('<svg class=\"animate-spin -ml-1 mr-2 h-4 w-4 text-white\" fill=\"none\" viewBox=\"0 0 24 24\"><circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle><path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\"></path></svg>Creazione in corso...');
        
        $.ajax({
            url: \$form.attr('action'),
            type: 'POST',
            data: \$form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message with toast
                    showNotification('Credenziali create con successo!', 'success');
                    
                    // Download PDF automatically
                    if (response.downloadUrl) {
                        window.open(response.downloadUrl, '_blank');
                    }
                    
                    // Redirect after a short delay
                    setTimeout(function() {
                        if (response.redirectUrl) {
                            window.location.href = response.redirectUrl;
                        }
                    }, 1000);
                } else {
                    showNotification('Errore: ' + (response.error || response.message || 'Errore sconosciuto'), 'error');
                    \$submitBtn.prop('disabled', false);
                    \$submitBtn.html('<svg class=\"mr-2 h-4 w-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"></path></svg>Crea Credenziali');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showNotification('Errore di comunicazione con il server. Riprova.', 'error');
                \$submitBtn.prop('disabled', false);
                \$submitBtn.html('<svg class=\"mr-2 h-4 w-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M5 13l4 4L19 7\"></path></svg>Crea Credenziali');
            }
        });
        
        return false;
    });
    
    // Auto-uppercase fiscal code input (same pattern as other forms)
    const fiscalCodeInput = document.querySelector('input[name=\"UserProfile[fiscal_code]\"]');
    if (fiscalCodeInput) {
        fiscalCodeInput.addEventListener('keyup', function() {
            const currentValue = this.value;
            this.value = currentValue.toUpperCase();
        });
    }

    // Rigenera password casuale rispettando le regole di validazione:
    // 8-20 caratteri, almeno una maiuscola, una minuscola, un numero e un
    // carattere speciale. Esclude caratteri ambigui (0/O/o/1/l/I).
    function generateRandomPassword(length) {
        var upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        var lower = 'abcdefghijkmnopqrstuvwxyz';
        var digits = '23456789';
        var special = '!@#\$%^&*-_+=?';
        var len = Math.max(8, Math.min(20, length || 12));

        function rand(max) {
            if (window.crypto && window.crypto.getRandomValues) {
                var arr = new Uint32Array(1);
                window.crypto.getRandomValues(arr);
                return arr[0] % max;
            }
            return Math.floor(Math.random() * max);
        }
        function pick(charset) { return charset.charAt(rand(charset.length)); }

        var chars = [pick(upper), pick(lower), pick(digits), pick(special)];
        var all = upper + lower + digits + special;
        for (var i = chars.length; i < len; i++) {
            chars.push(pick(all));
        }
        for (var j = chars.length - 1; j > 0; j--) {
            var k = rand(j + 1);
            var tmp = chars[j]; chars[j] = chars[k]; chars[k] = tmp;
        }
        return chars.join('');
    }

    $('#regenerate-password-btn').on('click', function () {
        var newPwd = generateRandomPassword(12);
        var \$pwd = $('#credentials-password');
        var \$pwdRepeat = $('#credentials-password-repeat');
        \$pwd.val(newPwd).trigger('change');
        \$pwdRepeat.val(newPwd).trigger('change');
        // Rendi visibili temporaneamente le password rigenerate (5s)
        var root = document.querySelector('#credentials-form');
        if (root && root._x_dataStack) {
            var data = root._x_dataStack[0];
            data.showPassword = true;
            data.showPasswordRepeat = true;
            setTimeout(function () {
                data.showPassword = false;
                data.showPasswordRepeat = false;
            }, 5000);
        }
        showNotification('Password rigenerata. Verra' + String.fromCharCode(39) + ' mostrata per 5 secondi.', 'info');
    });
});
");

$this->registerCss('[x-cloak] { display: none !important; }');
?>
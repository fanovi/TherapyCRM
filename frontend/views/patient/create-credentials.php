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

$this->title = 'Crea Credenziali per ' . $patient->first_name . ' ' . $patient->last_name;
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-6xl p-4 md:p-6">
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
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/patient/index']) ?>">
                            Pazienti
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

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Patient Information Card -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Informazioni Paziente
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Dati del paziente per cui creare le credenziali.
                </p>
            </div>
            
            <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($patient->first_name) ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cognome</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($patient->last_name) ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Codice Fiscale</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($patient->fiscal_code) ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data di Nascita</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white/90"><?= Html::encode($patient->birth_date) ?></dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Credentials Form Card -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Crea Account Utente
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Inserisci i dati per creare l'account associato al paziente.
                </p>
            </div>
            
            <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800">
                <?php $form = ActiveForm::begin([
                    'id' => 'credentials-form',
                    'options' => ['class' => 'space-y-6'],
                ]); ?>

                <!-- Account Credentials -->
                <div class="space-y-4">
                    <h4 class="text-sm font-medium text-gray-800 dark:text-white/90">Credenziali di Accesso</h4>
                    
                    <?= $form->field($user, 'username')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Nome utente per il login'
                    ])->label('Username', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>

                    <?= $form->field($user, 'email')->input('email', [
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Email per il login'
                    ])->label('Email', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>

                    <?= $form->field($user, 'password')->passwordInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Password (minimo 6 caratteri)'
                    ])->label('Password', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <!-- Profile Information -->
                <div class="space-y-4 border-t border-gray-100 pt-6 dark:border-gray-800">
                    <h4 class="text-sm font-medium text-gray-800 dark:text-white/90">Informazioni Profilo</h4>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <?= $form->field($profile, 'first_name')->textInput([
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30'
                        ])->label('Nome', [
                            'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                        ]) ?>

                        <?= $form->field($profile, 'last_name')->textInput([
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30'
                        ])->label('Cognome', [
                            'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                        ]) ?>
                    </div>

                    <?= $form->field($profile, 'fiscal_code')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30'
                    ])->label('Codice Fiscale', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>

                    <?= $form->field($profile, 'phone')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => '+39 123 456 7890'
                    ])->label('Telefono', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <!-- Relationship Information -->
                <div class="space-y-4 border-t border-gray-100 pt-6 dark:border-gray-800">
                    <h4 class="text-sm font-medium text-gray-800 dark:text-white/90">Relazione con il Paziente</h4>
                    
                    <?= $form->field($accountPatient, 'relationship_type')->dropDownList($relationshipLabels, [
                        'prompt' => 'Seleziona relazione...',
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30'
                    ])->label('Tipo di Relazione', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>

                    <?= $form->field($accountPatient, 'notes')->textarea([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Note aggiuntive sulla relazione...',
                        'rows' => 3
                    ])->label('Note', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-800">
                    <?= Html::a('Annulla', ['index'], [
                        'class' => 'px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-700'
                    ]) ?>
                    
                    <?= Html::submitButton('Crea Credenziali', [
                        'class' => 'px-6 py-2.5 text-sm font-medium text-white bg-brand-600 border border-transparent rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800'
                    ]) ?>
                </div>
                
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div> 
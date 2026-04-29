<?php

use common\models\AccountPatient;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $profile common\models\UserProfile */
/* @var $accountPatients common\models\AccountPatient[] */

$profileName = $profile && $profile->last_name
    ? trim($profile->last_name . ' ' . $profile->first_name)
    : $user->email;

$this->title = 'Modifica Account: ' . $profileName;
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Account', 'url' => ['accounts']];
$this->params['breadcrumbs'][] = ['label' => $profileName, 'url' => ['view-account', 'id' => $user->id]];
$this->params['breadcrumbs'][] = 'Modifica';

$inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white';
$labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2';
?>

<div class="mx-auto max-w-3xl p-4 md:p-6">
    <!-- Breadcrumb -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            Modifica Account
        </h2>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= Url::to(['/site/index']) ?>">
                        Home
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= Url::to(['/patient/accounts']) ?>">
                        Account
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= Url::to(['/patient/view-account', 'id' => $user->id]) ?>">
                        <?= Html::encode($profileName) ?>
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800 dark:text-white/90">Modifica</li>
            </ol>
        </nav>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="mb-1 text-base font-medium text-gray-800 dark:text-white/90">Dati Account</h3>
        <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
            Aggiorna anagrafica e credenziali di accesso. L'email viene usata anche come username.
        </p>

        <form method="post" action="<?= Url::to(['edit-account', 'id' => $user->id]) ?>">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="<?= $labelClass ?>" for="first_name">Nome</label>
                    <input type="text" name="first_name" id="first_name"
                           value="<?= Html::encode($profile->first_name ?? '') ?>"
                           class="<?= $inputClass ?>" required>
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="last_name">Cognome</label>
                    <input type="text" name="last_name" id="last_name"
                           value="<?= Html::encode($profile->last_name ?? '') ?>"
                           class="<?= $inputClass ?>" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="<?= $labelClass ?>" for="email">Email</label>
                    <input type="email" name="email" id="email"
                           value="<?= Html::encode($user->email) ?>"
                           class="<?= $inputClass ?>" required>
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="fiscal_code">Codice Fiscale</label>
                    <input type="text" name="fiscal_code" id="fiscal_code"
                           value="<?= Html::encode($profile->fiscal_code ?? '') ?>"
                           class="<?= $inputClass ?> uppercase"
                           style="text-transform: uppercase;">
                </div>
                <div>
                    <label class="<?= $labelClass ?>" for="phone">Telefono</label>
                    <input type="text" name="phone" id="phone"
                           value="<?= Html::encode($profile->phone ?? '') ?>"
                           class="<?= $inputClass ?>">
                </div>
                <div class="sm:col-span-2">
                    <label class="<?= $labelClass ?>" for="address">Indirizzo</label>
                    <input type="text" name="address" id="address"
                           value="<?= Html::encode($profile->address ?? '') ?>"
                           class="<?= $inputClass ?>">
                </div>
            </div>

            <?php if (!empty($accountPatients)): ?>
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90 mb-1">Pazienti Collegati</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        Tipo di relazione e autorita parentale per ciascun paziente collegato a questo account.
                        Gli account "io stesso" hanno autorita parentale implicita e relazione fissa.
                    </p>

                    <?php $relationshipLabels = AccountPatient::getRelationshipLabels(); ?>
                    <div class="space-y-3">
                        <?php foreach ($accountPatients as $ap): ?>
                            <?php
                            $patient = $ap->patient;
                            $patientName = $patient
                                ? trim($patient->last_name . ' ' . $patient->first_name)
                                : 'Paziente non disponibile';
                            $isSelf = $ap->relationship_type === AccountPatient::RELATIONSHIP_SELF;
                            ?>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50 p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        <?= Html::encode($patientName) ?>
                                    </div>
                                    <?php if ($isSelf): ?>
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            Io stesso
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="<?= $labelClass ?>" for="relationship_type_<?= (int) $ap->id ?>">
                                            Tipo di Relazione
                                        </label>
                                        <?php if ($isSelf): ?>
                                            <input type="text"
                                                   value="<?= Html::encode($relationshipLabels[$ap->relationship_type] ?? $ap->relationship_type) ?>"
                                                   class="<?= $inputClass ?> bg-gray-100 cursor-not-allowed dark:bg-gray-700"
                                                   readonly disabled>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                La relazione "io stesso" non e modificabile.
                                            </p>
                                        <?php else: ?>
                                            <select name="AccountPatient[<?= (int) $ap->id ?>][relationship_type]"
                                                    id="relationship_type_<?= (int) $ap->id ?>"
                                                    class="<?= $inputClass ?>">
                                                <?php foreach ($relationshipLabels as $value => $label): ?>
                                                    <?php if ($value === AccountPatient::RELATIONSHIP_SELF) { continue; } ?>
                                                    <option value="<?= Html::encode($value) ?>"
                                                            <?= $ap->relationship_type === $value ? 'selected' : '' ?>>
                                                        <?= Html::encode($label) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <label class="<?= $labelClass ?>">Autorita Parentale</label>
                                        <?php if ($isSelf): ?>
                                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-700">
                                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span class="text-sm text-emerald-800 dark:text-emerald-200">Implicita (io stesso)</span>
                                            </div>
                                        <?php else: ?>
                                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 cursor-pointer hover:bg-white dark:hover:bg-gray-700">
                                                <input type="hidden" name="AccountPatient[<?= (int) $ap->id ?>][has_parental_authority]" value="0">
                                                <input type="checkbox"
                                                       name="AccountPatient[<?= (int) $ap->id ?>][has_parental_authority]"
                                                       value="1"
                                                       <?= $ap->has_parental_authority ? 'checked' : '' ?>
                                                       class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Ha autorita parentale</span>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-6 flex items-center justify-end gap-3">
                <?= Html::a('Annulla', ['view-account', 'id' => $user->id], [
                    'class' => 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
                    'data-pjax' => '0',
                ]) ?>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-brand-500 rounded-lg hover:bg-brand-600">
                    Salva Modifiche
                </button>
            </div>
        </form>
    </div>
</div>

<?php $this->registerJs(<<<JS
(function () {
    var fc = document.getElementById('fiscal_code');
    if (fc) {
        fc.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    }
})();
JS); ?>

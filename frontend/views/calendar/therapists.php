<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $idTherapist int|null */
/* @var $selectedTherapistName string|null */

$this->title = 'Calendario terapisti';
$this->params['breadcrumbs'][] = ['label' => 'Terapisti', 'url' => ['/therapist/index']];
$this->params['breadcrumbs'][] = $this->title;

$calendarReadOnly = !Yii::$app->user->can('manage_calendar');
$iframeSrc = null;

if ($idTherapist) {
    $iframeSrc = Yii::$app->params['iframeSrc'] . 'therapist/' . $idTherapist;
    if ($calendarReadOnly) {
        $separator = (strpos($iframeSrc, '?') !== false) ? '&' : '?';
        $iframeSrc .= $separator . 'readOnly=1';
    }
}

$hubUrl = Url::to(['therapists']);
$searchUrl = Url::to(['search-therapists']);

$this->registerCssFile('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [
    'depends' => [\yii\web\YiiAsset::class],
]);
$this->registerJsFile('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [
    'depends' => [\yii\web\JqueryAsset::class],
]);

$selectOptions = [];
if ($idTherapist && $selectedTherapistName) {
    $selectOptions[$idTherapist] = $selectedTherapistName;
}
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90"><?= Html::encode($this->title) ?></h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cerca e seleziona un terapista per aprire il suo calendario.</p>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <label for="calendar-therapist-select" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Terapista
            </label>
            <?= Html::dropDownList('id_therapist', $idTherapist, $selectOptions, [
                'id' => 'calendar-therapist-select',
                'class' => 'form-control',
                'style' => 'width: 100%;',
                'prompt' => 'Cerca un terapista...',
            ]) ?>
        </div>

        <?php if ($idTherapist && $iframeSrc): ?>
            <div class="border-t border-gray-100 dark:border-gray-800">
                <div class="calendar-iframe-wrapper">
                    <iframe src="<?= Html::encode($iframeSrc) ?>" frameborder="0" style="width: 125%; height: 100vh; min-height: 600px;transform:scale(0.8);transform-origin: top left;" title="Calendario terapista" allowfullscreen>
                        <p>Il tuo browser non supporta gli iframe.
                            <a href="<?= Html::encode($iframeSrc) ?>" target="_blank">Apri l'app in una nuova finestra</a>
                        </p>
                    </iframe>
                </div>
            </div>
        <?php else: ?>
            <div class="border-t border-gray-100 px-5 py-10 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                Seleziona un terapista per vedere il calendario.
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .calendar-iframe-wrapper {
        position: relative;
        overflow: hidden;
    }

    .calendar-iframe-wrapper iframe {
        border: none;
        border-radius: 0 0 1rem 1rem;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--single {
        height: 42px;
        border-color: #d1d5db;
        border-radius: 0.5rem;
        padding: 6px 8px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px;
        color: #1f2937;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }
</style>

<?php
$currentId = $idTherapist ? (int) $idTherapist : 0;
$hubUrlJs = json_encode($hubUrl, JSON_UNESCAPED_SLASHES);
$searchUrlJs = json_encode($searchUrl, JSON_UNESCAPED_SLASHES);
$this->registerJs(<<<JS
(function() {
    var hubUrl = {$hubUrlJs};
    var searchUrl = {$searchUrlJs};
    var currentId = {$currentId};

    $('#calendar-therapist-select').select2({
        placeholder: 'Cerca un terapista...',
        allowClear: true,
        width: '100%',
        minimumInputLength: 1,
        language: {
            inputTooShort: function() { return 'Digita almeno un carattere'; },
            searching: function() { return 'Ricerca...'; },
            noResults: function() { return 'Nessun terapista trovato'; },
            errorLoading: function() { return 'Errore nel caricamento'; }
        },
        ajax: {
            url: searchUrl,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term || '',
                    page: params.page || 1
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: data.results || [],
                    pagination: {
                        more: !!(data.pagination && data.pagination.more)
                    }
                };
            },
            cache: true
        }
    });

    $('#calendar-therapist-select').on('select2:select', function(e) {
        var id = e.params.data.id;
        if (id && String(id) !== String(currentId)) {
            window.location.href = hubUrl.replace(/\\/\$/, '') + '/' + id;
        }
    });

    $('#calendar-therapist-select').on('select2:clear', function() {
        window.location.href = hubUrl;
    });
})();
JS
, \yii\web\View::POS_READY);
?>

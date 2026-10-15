<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $idTherapist int|null */
/* @var $selectedTherapistName string|null */
/* @var $therapistNav array|null ['prev' => [id, name], 'next' => [id, name], 'position' => int, 'total' => int] */

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
            <div class="therapist-nav-row">
                <?php if ($therapistNav): ?>
                    <a href="<?= Html::encode(rtrim($hubUrl, '/') . '/' . $therapistNav['prev']['id']) ?>"
                       class="therapist-nav-btn"
                       title="Terapista precedente: <?= Html::encode($therapistNav['prev']['name']) ?>"
                       aria-label="Terapista precedente: <?= Html::encode($therapistNav['prev']['name']) ?>">
                        <svg class="therapist-nav-chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="therapist-nav-text">
                            <span class="therapist-nav-caption">Precedente</span>
                            <span class="therapist-nav-name"><?= Html::encode($therapistNav['prev']['name']) ?></span>
                        </span>
                    </a>
                <?php endif; ?>

                <div class="therapist-nav-select">
                    <?= Html::dropDownList('id_therapist', $idTherapist, $selectOptions, [
                        'id' => 'calendar-therapist-select',
                        'class' => 'form-control',
                        'style' => 'width: 100%;',
                        'prompt' => 'Cerca un terapista...',
                    ]) ?>
                    <?php if ($therapistNav): ?>
                        <p class="therapist-nav-position">
                            <?= (int) $therapistNav['position'] ?> di <?= (int) $therapistNav['total'] ?>
                        </p>
                    <?php endif; ?>
                </div>

                <?php if ($therapistNav): ?>
                    <a href="<?= Html::encode(rtrim($hubUrl, '/') . '/' . $therapistNav['next']['id']) ?>"
                       class="therapist-nav-btn therapist-nav-btn--next"
                       title="Terapista successivo: <?= Html::encode($therapistNav['next']['name']) ?>"
                       aria-label="Terapista successivo: <?= Html::encode($therapistNav['next']['name']) ?>">
                        <span class="therapist-nav-text">
                            <span class="therapist-nav-caption">Successivo</span>
                            <span class="therapist-nav-name"><?= Html::encode($therapistNav['next']['name']) ?></span>
                        </span>
                        <svg class="therapist-nav-chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
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

    .therapist-nav-row {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .therapist-nav-select {
        flex: 1 1 auto;
        min-width: 0;
    }

    .therapist-nav-position {
        margin-top: 0.375rem;
        text-align: center;
        font-size: 0.75rem;
        color: var(--color-gray-500);
    }

    .therapist-nav-btn {
        display: inline-flex;
        flex: 0 0 13.5rem;
        align-items: center;
        gap: 0.625rem;
        min-width: 0;
        height: 42px;
        padding: 0 0.875rem 0 0.625rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        background: #fff;
        color: var(--color-gray-700);
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }

    .therapist-nav-btn--next {
        justify-content: flex-end;
        padding: 0 0.625rem 0 0.875rem;
        text-align: right;
    }

    .therapist-nav-btn:hover {
        border-color: var(--color-brand-500);
        background: var(--color-brand-50);
    }

    .therapist-nav-btn:focus-visible {
        outline: 2px solid var(--color-brand-500);
        outline-offset: 2px;
    }

    .therapist-nav-chevron {
        flex-shrink: 0;
        width: 1.375rem;
        height: 1.375rem;
        color: var(--color-brand-500);
    }

    .therapist-nav-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
        line-height: 1.2;
    }

    .therapist-nav-caption {
        font-size: 0.6875rem;
        color: var(--color-gray-500);
    }

    .therapist-nav-name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.8125rem;
        font-weight: 500;
    }

    .dark .therapist-nav-btn {
        border-color: var(--color-gray-700);
        background: transparent;
        color: var(--color-gray-300);
    }

    .dark .therapist-nav-btn:hover {
        border-color: var(--color-brand-400);
        background: rgba(70, 95, 255, 0.12);
    }

    .dark .therapist-nav-chevron {
        color: var(--color-brand-400);
    }

    .dark .therapist-nav-caption,
    .dark .therapist-nav-position {
        color: var(--color-gray-400);
    }

    @media (max-width: 767px) {
        .therapist-nav-btn,
        .therapist-nav-btn--next {
            flex-basis: 42px;
            justify-content: center;
            padding: 0;
        }

        .therapist-nav-text {
            display: none;
        }
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
        language: {
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

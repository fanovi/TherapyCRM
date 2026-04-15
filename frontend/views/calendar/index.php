<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $idPatient int|null */
/* @var $idTherapist int|null */

$this->title = 'Gestione Terapie';
$this->params['breadcrumbs'][] = $this->title;

// Modalità iframe per sviluppo
$useIframe = true;  // Cambia a false per usare la versione integrata

// Determina se il calendario deve essere in sola lettura (es. coordinator)
$calendarReadOnly = !Yii::$app->user->can('update_appointment');

// Costruisci l'URL dell'iframe con i parametri come rotta React
$iframeSrc = Yii::$app->params['iframeSrc'];
if ($idTherapist) {
    $iframeSrc .= 'therapist/' . $idTherapist;
} elseif ($idPatient) {
    $iframeSrc .= $idPatient;
}

// Aggiungi il parametro readOnly all'URL dell'iframe se necessario
if ($calendarReadOnly) {
    $separator = (strpos($iframeSrc, '?') !== false) ? '&' : '?';
    $iframeSrc .= $separator . 'readOnly=1';
}

// Costruisci i parametri GET per la modalità integrata (se useIframe = false)
$queryParams = [];
if ($idPatient) {
    $queryParams['id_patient'] = $idPatient;
}
if ($idTherapist) {
    $queryParams['id_therapist'] = $idTherapist;
}
$queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>

            <?php if ($idPatient) : ?>
                <div style="display: none;" class="text-sm text-gray-500 dark:text-gray-400">
                    Vista Paziente: ID <?= Html::encode($idPatient) ?>
                </div>
            <?php elseif ($idTherapist) : ?>
                <div style="display: none;" class="text-sm text-gray-500 dark:text-gray-400">
                    Vista Terapista: ID <?= Html::encode($idTherapist) ?>
                </div>
            <?php endif; ?>

            <div style="display: none;" class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded">
                <?= $useIframe ? 'Modalità Iframe (Dev)' : 'Modalità Integrata (Prod)' ?>
                <?php if ($useIframe) : ?>
                    <br>URL: <?= Html::encode($iframeSrc) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <!-- <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Sistema di Gestione Terapie
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Pianifica e gestisci gli appuntamenti terapeutici.
            </p>
        </div> -->

        <!-- React App Container -->
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?php if ($useIframe) : ?>
                <!-- Modalità Iframe per sviluppo -->
                <div class="calendar-iframe-wrapper">
                    <iframe src="<?= Html::encode($iframeSrc) ?>" frameborder="0" style="width: 125%; height: 100vh; min-height: 600px;transform:scale(0.8);transform-origin: top left;" title="Calendar App" allowfullscreen>
                        <p>Il tuo browser non supporta gli iframe.
                            <a href="<?= Html::encode($iframeSrc) ?>" target="_blank">Apri l'app in una nuova finestra</a>
                        </p>
                    </iframe>
                </div>
            <?php else : ?>
                <!-- Modalità integrata per produzione -->
                <div class="calendar-app-wrapper">
                    <div id="root" data-query-params="<?= Html::encode($queryString) ?>" data-read-only="<?= $calendarReadOnly ? '1' : '0' ?>"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Content End -->
</div>

<?php if (!$useIframe) : ?>
    <?php
    // Registra il CSS di React isolato e il JS solo se non usiamo iframe
    $this->registerCssFile('@web/calendar-app/dist/style.css', [
        'depends' => [\yii\web\YiiAsset::class],
    ]);
    $this->registerJsFile('@web/calendar-app/dist/index.js', [
        'depends' => [\yii\web\YiiAsset::class],
        'position' => \yii\web\View::POS_END,
    ]);

    // Script per passare i parametri a React tramite data attributes
    $script = <<<JS
        // I parametri sono già disponibili tramite data-query-params
        // React li leggerà direttamente senza modificare l'URL
        JS;
    $this->registerJs($script, \yii\web\View::POS_END);
    ?>
<?php else : ?>
    <?php
    // Script per comunicazione con l'iframe se necessario
    $script = <<<JS

        // Funzione per ridimensionare l'iframe se necessario
        function resizeIframe() {
            const iframe = document.querySelector('.calendar-iframe-wrapper iframe');
        }

        // Ascolta eventi dall'iframe se necessario (per comunicazione cross-frame)
        window.addEventListener('message', function(event) {
            // Verifica l'origine per sicurezza
            if (event.origin !== 'http://localhost:8080') return;
            
            // Gestisci messaggi specifici dall'iframe
            if (event.data.type === 'resize') {
                const iframe = document.querySelector('.calendar-iframe-wrapper iframe');
                if (iframe && event.data.height) {
                    iframe.style.height = event.data.height + 'px';
                }
            }
        });

        // Carica iframe
        document.addEventListener('DOMContentLoaded', resizeIframe);
        JS;
    $this->registerJs($script, \yii\web\View::POS_END);
    ?>
<?php endif; ?>

<style>
    .calendar-iframe-wrapper {
        position: relative;
        overflow: hidden;
    }

    .calendar-iframe-wrapper iframe {
        border: none;
        border-radius: 0 0 1rem 1rem;
    }

    /* Stili per migliorare l'integrazione dell'iframe */
    .calendar-iframe-wrapper::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
        z-index: 1;
    }
</style>
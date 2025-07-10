<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $idPatient int|null */
/* @var $idTherapist int|null */

$this->title = 'Gestione Terapie';
$this->params['breadcrumbs'][] = $this->title;

// Costruisci i parametri GET per React
$queryParams = [];
if ($idPatient) {
    $queryParams['id_patient'] = $idPatient;
}
if ($idTherapist) {
    $queryParams['id_therapist'] = $idTherapist;
}
$queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';

// Registra il CSS di React isolato e il JS
$this->registerCssFile('@web/calendar-app/dist/index.css', [
    'depends' => [\yii\web\YiiAsset::class],
]);
$this->registerJsFile('@web/calendar-app/dist/index.js', [
    'depends' => [\yii\web\YiiAsset::class],
    'position' => \yii\web\View::POS_END,
]);
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <?php if ($idPatient): ?>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Vista Paziente: ID <?= Html::encode($idPatient) ?>
                </div>
            <?php elseif ($idTherapist): ?>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Vista Terapista: ID <?= Html::encode($idTherapist) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Sistema di Gestione Terapie
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Pianifica e gestisci gli appuntamenti terapeutici.
            </p>
        </div>
        
        <!-- React App Container -->
        <div class="border-t border-gray-100 dark:border-gray-800">
            <div class="calendar-app-wrapper">
                <div id="root" data-query-params="<?= Html::encode($queryString) ?>"></div>
            </div>
        </div>
    </div>
    <!-- Content End -->
</div>

<?php
// Con PostCSS prefix non serve più CSS personalizzato per isolare gli stili

// Script per passare i parametri a React tramite data attributes
$script = <<<JS
// I parametri sono già disponibili tramite data-query-params
// React li leggerà direttamente senza modificare l'URL
console.log('Calendar app loaded with params:', document.getElementById('root').dataset.queryParams);
JS;
$this->registerJs($script, \yii\web\View::POS_END);
?> 
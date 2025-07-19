<?php
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $widget frontend\widgets\StatisticsFilter */
/* @var $filterId string */
?>
<div <?= Html::renderTagAttributes($widget->containerOptions) ?>>
    <!-- Header -->
    <div class="px-4 pt-4 pb-2 flex items-center justify-between border-b border-gray-100">
        <h6 class="text-base font-semibold text-gray-700 flex items-center gap-2">
            <i class="fas fa-filter"></i>
            <?= Html::encode($widget->title) ?>
        </h6>
    </div>
    <!-- Body -->
    <div class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($widget->fields as $field): ?>
                <div><?= $widget->renderField($field) ?></div>
            <?php endforeach; ?>
        </div>
        <div class="flex flex-wrap items-center justify-between mt-4 gap-2">
            <div class="flex gap-2">
                <?= Html::submitButton('<i class="fas fa-search mr-1"></i> Filtra', [
                    'class' => 'inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-sm font-medium',
                ]) ?>
                <?= Html::a('<i class="fas fa-eraser mr-1"></i> Pulisci', Url::current(), [
                    'class' => 'inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition text-sm font-medium',
                ]) ?>
            </div>
            <div>
                <?= Html::button('<i class="fas fa-download mr-1"></i> Esporta', [
                    'class' => 'inline-flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition text-sm font-medium',
                    'onclick' => 'exportData("excel"); return false;'
                ]) ?>
            </div>
        </div>
    </div>
</div>
<script>
function exportData(format) {
    var formData = $('form').serialize();
    var currentPath = window.location.pathname;
    var exportType = 'general';
    if (currentPath.includes('/absences')) exportType = 'absences';
    else if (currentPath.includes('/patients')) exportType = 'patients';
    else if (currentPath.includes('/treatments')) exportType = 'treatments';
    else if (currentPath.includes('/plans')) exportType = 'plans';
    var exportUrl = '<?= Url::to(['statistics/export']) ?>?type=' + exportType + '&format=' + format + '&' + formData;
    window.open(exportUrl, '_blank');
}
</script> 
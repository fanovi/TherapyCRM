<?php
// Esempio di utilizzo del widget ActivityLogWidget
// Da includere in una view di dettaglio (es. frontend/views/patient/view.php)

use common\widgets\ActivityLogWidget;

/* @var $this yii\web\View */
/* @var $model common\models\Patient */

$this->title = $model->getFullName();
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="patient-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <!-- Dettagli del paziente -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Informazioni Paziente</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Nome Completo:</dt>
                        <dd class="col-sm-9"><?= Html::encode($model->getFullName()) ?></dd>
                        
                        <dt class="col-sm-3">Data di Nascita:</dt>
                        <dd class="col-sm-9"><?= Html::encode($model->getFormattedBirthDate()) ?></dd>
                        
                        <dt class="col-sm-3">Età:</dt>
                        <dd class="col-sm-9"><?= $model->getAge() ?> anni</dd>
                        
                        <dt class="col-sm-3">Codice Fiscale:</dt>
                        <dd class="col-sm-9"><?= Html::encode($model->fiscal_code) ?></dd>
                        
                        <dt class="col-sm-3">Distretto:</dt>
                        <dd class="col-sm-9"><?= $model->district ? Html::encode($model->district->name) : '-' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
        
        <!-- Widget Cronologia Attività -->
        <div class="col-md-4">
            <?= ActivityLogWidget::widget([
                'entityName' => 'Patient',
                'entityId' => $model->id,
                'limit' => 8,
                'title' => 'Cronologia Modifiche',
                'showUser' => true,
                'showChanges' => true,
                'showIp' => false,
                'showViewAllLink' => true,
                'options' => ['class' => 'activity-log-widget shadow-sm'],
            ]) ?>
        </div>
    </div>

    <!-- Altre sezioni della view -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Piani Terapeutici</h5>
                </div>
                <div class="card-body">
                    <!-- Lista piani terapeutici -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS personalizzato per il widget -->
<style>
.activity-log-widget {
    max-height: 500px;
    overflow-y: auto;
}

.activity-log-widget .widget-title {
    color: #2c3e50;
    border-bottom: 2px solid #3498db;
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
}

.activity-log-item {
    transition: background-color 0.2s;
}

.activity-log-item:hover {
    background-color: #f8f9fa;
    border-radius: 0.25rem;
}
</style>

<?php
// Esempio di utilizzo avanzato del widget con configurazioni multiple

// Widget minimalista per sidebar
echo ActivityLogWidget::widget([
    'entityName' => 'Patient',
    'entityId' => $model->id,
    'limit' => 3,
    'title' => 'Ultime Attività',
    'showUser' => false,
    'showChanges' => false,
    'showViewAllLink' => false,
    'options' => ['class' => 'activity-log-sidebar'],
]);

// Widget completo per dashboard admin
echo ActivityLogWidget::widget([
    'entityName' => 'Patient',
    'entityId' => $model->id,
    'limit' => 15,
    'title' => 'Audit Trail Completo',
    'showUser' => true,
    'showChanges' => true,
    'showIp' => true,
    'showViewAllLink' => true,
    'options' => ['class' => 'activity-log-admin'],
]);

// Widget personalizzato con callback
echo ActivityLogWidget::widget([
    'entityName' => 'Patient',
    'entityId' => $model->id,
    'limit' => 5,
    'title' => 'Cronologia Paziente',
    'showUser' => true,
    'showChanges' => true,
]);
?>

<?php
// Esempio di integrazione con JavaScript per auto-refresh
$this->registerJs("
// Auto-refresh del widget ogni 30 secondi
setInterval(function() {
    $.pjax.reload({
        container: '.activity-log-widget'
    });
}, 30000);

// Tooltip per le modifiche troncate
$('.activity-log-widget [data-toggle=\"tooltip\"]').tooltip();

// Highlight nuove attività
$('.activity-log-item').each(function() {
    var createdAt = $(this).find('.created-at').text();
    var now = new Date();
    var itemDate = new Date(createdAt);
    var diffMinutes = (now - itemDate) / (1000 * 60);
    
    if (diffMinutes < 5) {
        $(this).addClass('recent-activity');
    }
});
");

$this->registerCss("
.recent-activity {
    background-color: #e8f5e8;
    border-left: 4px solid #28a745;
    animation: fadeIn 0.5s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
");
?> 
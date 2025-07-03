<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\ActivityLog $model */

$this->title = 'Log #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Log Attività', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="activity-log-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="card">
        <div class="card-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    [
                        'attribute' => 'created_at',
                        'format' => ['datetime', 'php:d/m/Y H:i:s'],
                    ],
                    [
                        'attribute' => 'user_id',
                        'value' => $model->user ? $model->user->username : null,
                        'label' => 'Utente',
                    ],
                    [
                        'attribute' => 'action',
                        'value' => function ($model) {
                            $actions = [
                                'create' => 'Creazione',
                                'update' => 'Modifica',
                                'delete' => 'Eliminazione'
                            ];
                            return $actions[$model->action] ?? $model->action;
                        },
                    ],
                    'entity_name',
                    'entity_id',
                    [
                        'attribute' => 'parent_log_id',
                        'value' => function ($model) {
                            return $model->parent_log_id ? 
                                Html::a('Log Padre #' . $model->parent_log_id, ['view', 'id' => $model->parent_log_id]) : 
                                null;
                        },
                        'format' => 'raw',
                    ],
                    'ip_address',
                ],
            ]) ?>

            <?php if ($model->action === 'update'): ?>
                <h5 class="mt-4">Modifiche</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Valore Precedente</th>
                                <th>Nuovo Valore</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $oldValues = json_decode($model->old_values, true) ?: [];
                            $newValues = json_decode($model->new_values, true) ?: [];
                            $allFields = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
                            sort($allFields);
                            
                            foreach ($allFields as $field):
                                $oldValue = isset($oldValues[$field]) ? Html::encode(is_array($oldValues[$field]) ? json_encode($oldValues[$field]) : $oldValues[$field]) : '';
                                $newValue = isset($newValues[$field]) ? Html::encode(is_array($newValues[$field]) ? json_encode($newValues[$field]) : $newValues[$field]) : '';
                                
                                if ($oldValue !== $newValue):
                            ?>
                                <tr>
                                    <td><?= Html::encode($field) ?></td>
                                    <td class="text-danger"><?= $oldValue ?></td>
                                    <td class="text-success"><?= $newValue ?></td>
                                </tr>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($model->action === 'create'): ?>
                <h5 class="mt-4">Valori Iniziali</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Valore</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $newValues = json_decode($model->new_values, true) ?: [];
                            ksort($newValues);
                            
                            foreach ($newValues as $field => $value):
                            ?>
                                <tr>
                                    <td><?= Html::encode($field) ?></td>
                                    <td><?= Html::encode(is_array($value) ? json_encode($value) : $value) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($model->action === 'delete'): ?>
                <h5 class="mt-4">Valori Eliminati</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Valore</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $oldValues = json_decode($model->old_values, true) ?: [];
                            ksort($oldValues);
                            
                            foreach ($oldValues as $field => $value):
                            ?>
                                <tr>
                                    <td><?= Html::encode($field) ?></td>
                                    <td><?= Html::encode(is_array($value) ? json_encode($value) : $value) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($model->parent_log_id === null): ?>
                <h5 class="mt-4">Log Correlati</h5>
                <?php
                $childLogs = \common\models\ActivityLog::find()
                    ->where(['parent_log_id' => $model->id])
                    ->orderBy(['created_at' => SORT_ASC])
                    ->all();
                
                if (!empty($childLogs)):
                ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data/Ora</th>
                                    <th>Entità</th>
                                    <th>Azione</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($childLogs as $log): ?>
                                    <tr>
                                        <td><?= $log->id ?></td>
                                        <td><?= Yii::$app->formatter->asDatetime($log->created_at, 'php:d/m/Y H:i:s') ?></td>
                                        <td><?= $log->entity_name ?></td>
                                        <td><?= $log->action ?></td>
                                        <td><?= Html::a('Visualizza', ['view', 'id' => $log->id], ['class' => 'btn btn-sm btn-primary']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Nessun log correlato trovato.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$this->registerCss("
    .activity-log-view .card { background-color: #f8f9fa; }
    .activity-log-view .table { background-color: white; }
    .activity-log-view .text-danger { color: #dc3545 !important; }
    .activity-log-view .text-success { color: #28a745 !important; }
");
?> 
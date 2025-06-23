<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\ArrayHelper;
use common\models\District;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel frontend\models\PatientSearch */

$this->title = 'Pazienti';
$this->params['breadcrumbs'][] = $this->title;

// Get districts for filter
$districts = ArrayHelper::map(District::find()->all(), 'id', 'name');
?>

<div class="mx-auto max-w-7xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <!-- Action Button -->
            <?php if (Yii::$app->user->can('create_patient')): ?>
            <div>
                <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>Nuovo Paziente', 
                    ['create'], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-600 border border-transparent rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800'
                ]) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Lista Pazienti
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestisci tutti i pazienti del sistema.
            </p>
        </div>
        
        <!-- Filters -->
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-4 sm:px-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Cerca per nome/cognome
                    </label>
                    <input type="text" id="search-name" placeholder="Inserisci nome o cognome..." 
                           class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-2 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Distretto
                    </label>
                    <select id="filter-district" 
                            class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:ring-2 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">Tutti i distretti</option>
                        <?php foreach ($districts as $id => $name): ?>
                            <option value="<?= Html::encode($name) ?>"><?= Html::encode($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Codice Fiscale
                    </label>
                    <input type="text" id="search-fiscal" placeholder="Cerca per codice fiscale..." 
                           class="shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-2 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                </div>
                
                <div class="flex items-end">
                    <button type="button" onclick="resetFilters()" 
                            class="h-10 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        Reset Filtri
                    </button>
                </div>
            </div>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <!-- Horizontal scroll wrapper -->
            <div class="overflow-x-auto">
                <?php Pjax::begin([
                    'id' => 'patients-pjax',
                    'enablePushState' => false,
                ]); ?>
                
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                    'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'],
                    'rowOptions' => ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'],
                    'columns' => [
                        [
                            'attribute' => 'id',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[80px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white'],
                        ],
                        [
                            'attribute' => 'first_name',
                            'label' => 'Nome',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[120px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        ],
                        [
                            'attribute' => 'last_name',
                            'label' => 'Cognome',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[120px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                        ],
                        [
                            'attribute' => 'fiscal_code',
                            'label' => 'Codice Fiscale',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[160px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap font-mono text-xs'],
                            'value' => function($model) {
                                return $model->fiscal_code ?: '-';
                            }
                        ],
                        [
                            'attribute' => 'birth_date',
                            'label' => 'Data di Nascita',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[130px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'format' => ['date', 'php:d/m/Y'],
                        ],
                        [
                            'label' => 'Età',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[80px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-center'],
                            'value' => function($model) {
                                return $model->age ? $model->age . ' anni' : '-';
                            }
                        ],
                        [
                            'attribute' => 'district.name',
                            'label' => 'Distretto',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[150px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'value' => function($model) {
                                return $model->district ? $model->district->name : '-';
                            }
                        ],
                        [
                            'attribute' => 'notes',
                            'label' => 'Note',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[200px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 max-w-xs truncate'],
                            'value' => function($model) {
                                return $model->notes ? (strlen($model->notes) > 50 ? substr($model->notes, 0, 50) . '...' : $model->notes) : '-';
                            },
                        ],
                        [
                            'attribute' => 'created_at',
                            'label' => 'Creato il',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[130px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-xs text-gray-500'],
                            'format' => ['date', 'php:d/m/Y'],
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => 'Azioni',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[200px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'template' => '{view} {update} {credentials} {delete}',
                            'buttons' => [
                                'view' => function ($url, $model, $key) {
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>', 
                                        $url, [
                                        'title' => 'Visualizza',
                                        'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3'
                                    ]);
                                },
                                'update' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('update_patient')) return '';
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', 
                                        $url, [
                                        'title' => 'Modifica',
                                        'class' => 'text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 mr-3'
                                    ]);
                                },
                                'credentials' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('create_patient')) return '';
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>', 
                                        ['create-credentials', 'id' => $model->id], [
                                        'title' => 'Crea Credenziali',
                                        'class' => 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3'
                                    ]);
                                },
                                'delete' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('delete_patient')) return '';
                                    return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>', 
                                        $url, [
                                        'title' => 'Elimina',
                                        'class' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300',
                                        'data' => [
                                            'confirm' => 'Sei sicuro di voler eliminare questo paziente?',
                                            'method' => 'post',
                                        ],
                                    ]);
                                },
                            ],
                        ],
                    ],
                ]); ?>
                
                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
</div>

<script>
// Client-side filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchName = document.getElementById('search-name');
    const searchFiscal = document.getElementById('search-fiscal');
    const filterDistrict = document.getElementById('filter-district');
    
    function applyFilters() {
        const nameValue = searchName.value.toLowerCase();
        const fiscalValue = searchFiscal.value.toLowerCase();
        const districtValue = filterDistrict.value.toLowerCase();
        
        const rows = document.querySelectorAll('#patients-pjax tbody tr');
        
        rows.forEach(row => {
            const nameCell = row.cells[1]?.textContent.toLowerCase() || '';
            const surnameCell = row.cells[2]?.textContent.toLowerCase() || '';
            const fiscalCell = row.cells[3]?.textContent.toLowerCase() || '';
            const districtCell = row.cells[6]?.textContent.toLowerCase() || '';
            
            const nameMatch = nameValue === '' || nameCell.includes(nameValue) || surnameCell.includes(nameValue);
            const fiscalMatch = fiscalValue === '' || fiscalCell.includes(fiscalValue);
            const districtMatch = districtValue === '' || districtCell.includes(districtValue);
            
            if (nameMatch && fiscalMatch && districtMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    searchName.addEventListener('input', applyFilters);
    searchFiscal.addEventListener('input', applyFilters);
    filterDistrict.addEventListener('change', applyFilters);
});

function resetFilters() {
    document.getElementById('search-name').value = '';
    document.getElementById('search-fiscal').value = '';
    document.getElementById('filter-district').value = '';
    
    // Show all rows
    const rows = document.querySelectorAll('#patients-pjax tbody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
}
</script> 
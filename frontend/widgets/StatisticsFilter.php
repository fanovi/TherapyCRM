<?php

namespace frontend\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
// use kartik\select2\Select2;
// use kartik\date\DatePicker;
// use kartik\daterange\DateRangePicker;

/**
 * Widget per filtri delle statistiche
 * 
 * Uso:
 * echo StatisticsFilter::widget([
 *     'model' => $searchModel,
 *     'form' => $form,
 *     'fields' => ['dateRange', 'treatments', 'gender'],
 *     'options' => ['treatments' => $treatmentOptions]
 * ]);
 */
class StatisticsFilter extends Widget
{
    /**
     * @var \yii\base\Model Il model con i filtri
     */
    public $model;

    /**
     * @var ActiveForm Il form attivo
     */
    public $form;

    /**
     * @var array Lista dei campi da mostrare
     */
    public $fields = [];

    /**
     * @var array Opzioni per i select dropdown
     */
    public $options = [];

    /**
     * @var bool Se il filtro è collassabile
     */
    public $collapsible = true;

    /**
     * @var bool Se il filtro inizia aperto o chiuso
     */
    public $collapsed = false;

    /**
     * @var string Titolo del filtro
     */
    public $title = 'Filtri';

    /**
     * @var array Attributi HTML del container
     */
    public $containerOptions = [];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        
        if (!$this->model) {
            throw new \InvalidArgumentException('Il parametro "model" è obbligatorio');
        }

        // CSS classes di default
        Html::addCssClass($this->containerOptions, 'statistics-filter bg-white rounded-lg shadow mb-6');
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        return $this->renderTemplate('statistics-filter', [
            'widget' => $this,
            'filterId' => 'filter-' . $this->getId(),
        ]);
    }

    /**
     * Renderizza un campo specifico
     *
     * @param string $fieldName
     * @return string
     */
    public function renderField($fieldName)
    {
        $method = 'render' . ucfirst($fieldName) . 'Field';
        
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        
        // Campo generico
        return $this->renderGenericField($fieldName);
    }

    /**
     * Renderizza campo date range
     *
     * @return string
     */
    protected function renderDateRangeField()
    {
        $output = '<div class="flex gap-2">';
        $output .= $this->form->field($this->model, 'dateFrom')->input('date', ['class' => 'form-input rounded border-gray-300'])->label('Data da');
        $output .= $this->form->field($this->model, 'dateTo')->input('date', ['class' => 'form-input rounded border-gray-300'])->label('Data a');
        $output .= '</div>';
        return $output;
    }

    /**
     * Renderizza campo data singola
     *
     * @return string
     */
    protected function renderDateFromField()
    {
        return $this->form->field($this->model, 'dateFrom', [
            'options' => ['class' => 'mb-0'],
            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
        ])->input('date', [
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
        ])->label('Data Creazione da');
    }

    /**
     * Renderizza campo data a
     *
     * @return string
     */
    protected function renderDateToField()
    {
        return $this->form->field($this->model, 'dateTo', [
            'options' => ['class' => 'mb-0'],
            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
        ])->input('date', [
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
        ])->label('Data Creazione a');
    }

    /**
     * Renderizza campo trattamenti multipli
     *
     * @return string
     */
    protected function renderTreatmentsField()
    {
        $treatmentOptions = $this->options['treatments'] ?? [];
        
        return $this->form->field($this->model, 'treatmentTypeIds', [
            'options' => ['class' => 'mb-0'],
            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
        ])->dropDownList($treatmentOptions, [
            'multiple' => true, 
            'prompt' => 'Seleziona trattamenti...', 
            'size' => 8, 
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-h-[200px] max-h-[300px]'
        ])->label('Tipi di Trattamento');
    }

    /**
     * Renderizza campo genere
     *
     * @return string
     */
    protected function renderGenderField()
    {
        $genderOptions = ['all' => 'Tutti', 'M' => 'Maschio', 'F' => 'Femmina', 'N' => 'Non specificato',];
        
        return $this->form->field($this->model, 'gender', [
            'options' => ['class' => 'mb-0'],
            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
        ])->dropDownList($genderOptions, [
            'prompt' => 'Seleziona genere...', 
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
        ])->label('Genere');
    }

    /**
     * Renderizza campo età da
     *
     * @return string
     */
    protected function renderAgeFromField()
    {
        return $this->form->field($this->model, 'ageFrom', [
            'options' => ['class' => 'mb-0'],
            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
        ])->textInput([
            'type' => 'number', 
            'min' => 0, 
            'max' => 120, 
            'placeholder' => 'Età minima', 
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
        ])->label('Età da');
    }

    /**
     * Renderizza campo età a
     *
     * @return string
     */
    protected function renderAgeToField()
    {
        return $this->form->field($this->model, 'ageTo', [
            'options' => ['class' => 'mb-0'],
            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
        ])->textInput([
            'type' => 'number', 
            'min' => 0, 
            'max' => 120, 
            'placeholder' => 'Età massima', 
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
        ])->label('Età a');
    }

    /**
     * Renderizza campo terapista
     *
     * @return string
     */
    protected function renderTherapistIdField()
    {
        $therapistOptions = $this->options['therapists'] ?? [];
        
        return $this->form->field($this->model, 'therapistId', [
            'options' => ['class' => 'mb-0'],
            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
        ])->dropDownList($therapistOptions, [
            'prompt' => 'Seleziona terapista...', 
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
        ])->label('Terapista');
    }

    /**
     * Renderizza campo paziente
     *
     * @return string
     */
    protected function renderPatientIdField()
    {
        $patientOptions = $this->options['patients'] ?? [];
        
        return $this->form->field($this->model, 'patientId', [
            'options' => ['class' => 'mb-0'],
            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
        ])->dropDownList($patientOptions, [
            'prompt' => 'Seleziona paziente...', 
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
        ])->label('Paziente');
    }

    /**
     * Renderizza campo giorno settimana
     *
     * @return string
     */
    protected function renderDayOfWeekField()
    {
        $dayOptions = ['' => 'Tutti i giorni', 1 => 'Lunedì', 2 => 'Martedì', 3 => 'Mercoledì', 4 => 'Giovedì', 5 => 'Venerdì', 6 => 'Sabato', 7 => 'Domenica',];
        
        return $this->form->field($this->model, 'dayOfWeek')->dropDownList($dayOptions, ['class' => 'form-select rounded border-gray-300']);
    }

    /**
     * Renderizza campo ora da
     *
     * @return string
     */
    protected function renderHourFromField()
    {
        $hourOptions = ['' => 'Tutte le ore'];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourOptions[$hour] = sprintf('%02d:00', $hour);
        }
        
        return $this->form->field($this->model, 'hourFrom')->dropDownList($hourOptions, ['class' => 'form-select rounded border-gray-300']);
    }

    /**
     * Renderizza campo ora a
     *
     * @return string
     */
    protected function renderHourToField()
    {
        $hourOptions = ['' => 'Tutte le ore'];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourOptions[$hour] = sprintf('%02d:00', $hour);
        }
        
        return $this->form->field($this->model, 'hourTo')->dropDownList($hourOptions, ['class' => 'form-select rounded border-gray-300']);
    }

    /**
     * Renderizza campo generata da
     *
     * @return string
     */
    protected function renderGeneratedByField()
    {
        $generatedByOptions = ['all' => 'Tutti', 'patient' => 'Paziente', 'therapist' => 'Terapista', 'system' => 'Sistema',];
        
        return $this->form->field($this->model, 'generatedBy')->dropDownList($generatedByOptions, ['class' => 'form-select rounded border-gray-300']);
    }

    /**
     * Renderizza campo giustificata
     *
     * @return string
     */
    protected function renderIsJustifiedField()
    {
        return $this->form->field($this->model, 'isJustified')->dropDownList(['' => 'Tutte', 1 => 'Solo giustificate', 0 => 'Solo non giustificate',], ['class' => 'form-select rounded border-gray-300']);
    }

    /**
     * Renderizza campo status
     *
     * @return string
     */
    protected function renderStatusField()
    {
        $statusOptions = ['all' => 'Tutti', 'active' => 'Attivi', 'dismissed' => 'Dimessi',];
        
        return $this->form->field($this->model, 'status', [
            'options' => ['class' => 'mb-0'],
            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
        ])->dropDownList($statusOptions, [
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
        ])->label('Stato');
    }

    /**
     * Renderizza campo modalità combinazione
     *
     * @return string
     */
    protected function renderCombinationModeField()
    {
        $modeOptions = ['any' => 'Almeno uno', 'all' => 'Tutti', 'exact' => 'Esattamente questi',];
        
        return $this->form->field($this->model, 'combinationMode')->radioList($modeOptions, ['item' => function ($index, $label, $name, $checked, $value) { $radio = Html::radio($name, $checked, ['value' => $value, 'id' => "radio-{$name}-{$value}", 'class' => 'form-radio text-blue-600']); $label = Html::label($label, "radio-{$name}-{$value}", ['class' => 'ml-2']); return Html::tag('div', $radio . ' ' . $label, ['class' => 'inline-flex items-center mr-4']); }]);
    }

    /**
     * Renderizza campo generico
     *
     * @param string $fieldName
     * @return string
     */
    protected function renderGenericField($fieldName)
    {
        // Se il campo ha opzioni specifiche, usa dropDownList
        if (isset($this->options[$fieldName])) {
            return $this->form->field($this->model, $fieldName, [
                'options' => ['class' => 'mb-0'],
                'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
            ])->dropDownList($this->options[$fieldName], [
                'prompt' => 'Seleziona...', 
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
            ]);
        }
        
        // Altrimenti campo di testo
        return $this->form->field($this->model, $fieldName, [
            'options' => ['class' => 'mb-0'],
            'template' => '<div class="space-y-2">{label}<div class="mt-1">{input}</div>{error}</div>'
        ])->textInput([
            'class' => 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
        ]);
    }

    /**
     * Renderizza il template del widget
     *
     * @param string $view
     * @param array $params
     * @return string
     */
    protected function renderTemplate($view, $params = [])
    {
        return $this->renderFile(__DIR__ . '/views/' . $view . '.php', $params);
    }
} 
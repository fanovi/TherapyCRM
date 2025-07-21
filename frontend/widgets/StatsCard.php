<?php

namespace frontend\widgets;

use yii\base\Widget;
use yii\helpers\Html;

/**
 * Widget per card delle statistiche (Tailwind CSS)
 * 
 * Uso:
 * echo StatsCard::widget([
 *     'title' => 'Pazienti Attivi',
 *     'value' => 125,
 *     'icon' => 'fas fa-users',
 *     'color' => 'primary',
 *     'footer' => 'Ultimo aggiornamento: oggi'
 * ]);
 */
class StatsCard extends Widget
{
    public $title;
    public $value;
    public $icon;
    public $color = 'primary';
    public $footer;
    public $url;
    public $valueFormat = 'number';
    public $options = [];

    public function init()
    {
        parent::init();
        if (!$this->title) {
            throw new \InvalidArgumentException('Il parametro "title" è obbligatorio');
        }
        if ($this->value === null) {
            throw new \InvalidArgumentException('Il parametro "value" è obbligatorio');
        }
        // Tailwind: padding, rounded, shadow, border-left simulato con border-l-4
        $colorClass = self::getColorClasses()[$this->color] ?? 'border-l-blue-500';
        Html::addCssClass($this->options, "bg-white p-4 rounded-lg shadow flex items-center border-l-4 {$colorClass}");
        if ($this->url) {
            Html::addCssClass($this->options, 'cursor-pointer hover:bg-gray-50 transition');
        }
    }

    public function run()
    {
        $content = $this->renderCard();
        if ($this->url) {
            return Html::a($content, $this->url, $this->options);
        }
        return Html::tag('div', $content, $this->options);
    }

    protected function renderCard()
    {
        $formattedValue = $this->formatValue($this->value);
        $iconHtml = $this->icon ? Html::tag('i', '', [
            'class' => $this->icon . ' text-3xl text-gray-300 mr-4'
        ]) : '';
        $body = Html::beginTag('div', ['class' => 'flex-1']);
        $body .= Html::tag('div', Html::encode($this->title), [
            'class' => 'text-xs font-semibold text-gray-500 uppercase mb-1'
        ]);
        $body .= Html::tag('div', $formattedValue, [
            'class' => 'text-2xl font-bold text-gray-800 mb-1'
        ]);
        if ($this->footer) {
            $body .= Html::tag('div', Html::encode($this->footer), [
                'class' => 'text-xs text-gray-400 mt-1'
            ]);
        }
        $body .= Html::endTag('div');
        return Html::tag('div',
            $iconHtml . $body,
            ['class' => 'flex items-center']
        );
    }

    protected function formatValue($value)
    {
        switch ($this->valueFormat) {
            case 'percentage':
                return number_format((float)$value, 1) . '%';
            case 'currency':
                return '€ ' . number_format((float)$value, 2, ',', '.');
            case 'number':
            default:
                if (is_numeric($value)) {
                    return number_format((float)$value, 0, ',', '.');
                }
                return Html::encode($value);
        }
    }

    public static function getColorClasses()
    {
        return [
            'primary' => 'border-l-blue-500',
            'secondary' => 'border-l-gray-400',
            'success' => 'border-l-green-500',
            'danger' => 'border-l-red-500',
            'warning' => 'border-l-yellow-400',
            'info' => 'border-l-cyan-500',
            'light' => 'border-l-gray-100',
            'dark' => 'border-l-gray-800',
        ];
    }
} 
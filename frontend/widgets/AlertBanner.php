<?php

namespace frontend\widgets;

use yii\base\Widget;
use yii\helpers\Html;

/**
 * Widget riusabile per banner di stato/avviso evidenti.
 *
 * Esempio:
 * echo AlertBanner::widget([
 *     'variant' => 'danger',
 *     'title' => 'Piano Terapeutico Interrotto',
 *     'message' => 'Questo piano è stato interrotto il 29/04/2026.<br>Motivo: ...',
 * ]);
 *
 * Variants: info | success | warning | danger
 */
class AlertBanner extends Widget
{
    public $variant = 'info';
    public $title = '';
    public $message = '';
    /** Se true, $message non viene escapata (usare con attenzione, già escaped in chiamante). */
    public $rawMessage = false;
    /** Override icona SVG (path stringa). Se null usa default per variant. */
    public $iconSvgPath = null;
    public $options = [];

    public function run()
    {
        $config = $this->getVariantConfig($this->variant);
        $iconPath = $this->iconSvgPath ?? $config['icon'];

        $title = $this->title !== '' ? Html::encode($this->title) : '';
        $message = $this->rawMessage ? $this->message : nl2br(Html::encode($this->message));

        $wrapperClass = "rounded-xl border-2 shadow-md p-5 mb-6 flex items-start gap-4 {$config['wrapper']}";
        Html::addCssClass($this->options, $wrapperClass);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">'
            . '<path stroke-linecap="round" stroke-linejoin="round" d="' . $iconPath . '"/>'
            . '</svg>';

        $iconWrapper = '<div class="' . $config['iconWrap'] . ' flex items-center justify-center w-12 h-12 rounded-full flex-shrink-0">' . $svg . '</div>';

        $body = '<div class="flex-1 min-w-0">';
        if ($title !== '') {
            $body .= '<h3 class="text-base font-semibold ' . $config['titleColor'] . ' mb-1">' . $title . '</h3>';
        }
        if ($message !== '') {
            $body .= '<div class="text-sm ' . $config['textColor'] . '">' . $message . '</div>';
        }
        $body .= '</div>';

        return Html::tag('div', $iconWrapper . $body, $this->options);
    }

    protected function getVariantConfig($variant)
    {
        $defaults = [
            'info' => [
                'wrapper' => 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/30',
                'iconWrap' => 'bg-blue-100 text-blue-600 dark:bg-blue-800 dark:text-blue-300',
                'titleColor' => 'text-blue-900 dark:text-blue-100',
                'textColor' => 'text-blue-800 dark:text-blue-200',
                'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            'success' => [
                'wrapper' => 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/30',
                'iconWrap' => 'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-300',
                'titleColor' => 'text-green-900 dark:text-green-100',
                'textColor' => 'text-green-800 dark:text-green-200',
                'icon' => 'M5 13l4 4L19 7',
            ],
            'warning' => [
                'wrapper' => 'border-yellow-400 bg-yellow-50 dark:border-yellow-600 dark:bg-yellow-900/30',
                'iconWrap' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-300',
                'titleColor' => 'text-yellow-900 dark:text-yellow-100',
                'textColor' => 'text-yellow-800 dark:text-yellow-200',
                'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            ],
            'danger' => [
                'wrapper' => 'border-red-400 bg-red-50 dark:border-red-700 dark:bg-red-900/30',
                'iconWrap' => 'bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-300',
                'titleColor' => 'text-red-900 dark:text-red-100',
                'textColor' => 'text-red-800 dark:text-red-200',
                'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            ],
        ];
        return $defaults[$variant] ?? $defaults['info'];
    }
}

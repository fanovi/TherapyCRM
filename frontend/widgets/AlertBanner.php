<?php

namespace frontend\widgets;

use yii\base\Widget;
use yii\helpers\Html;

/**
 * Widget riusabile per banner di stato/avviso.
 * Usa inline-style con colori pastello: indipendente dalla build di Tailwind.
 *
 * Esempio:
 * echo AlertBanner::widget([
 *     'variant' => 'danger',
 *     'title' => 'Piano Terapeutico Interrotto',
 *     'message' => 'Questo piano è stato interrotto il 29/04/2026.<br>Motivo: ...',
 *     'rawMessage' => true,
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

        $wrapperStyle = sprintf(
            'display:flex;align-items:center;gap:14px;border-radius:12px;border:2px solid %s;background:%s;padding:14px 18px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,0.06);',
            $config['border'],
            $config['bg']
        );
        Html::addCssStyle($this->options, $wrapperStyle);

        $iconWrapStyle = sprintf(
            'display:flex;align-items:center;justify-content:center;flex-shrink:0;width:40px;height:40px;border-radius:9999px;background:%s;color:%s;',
            $config['iconBg'],
            $config['iconColor']
        );

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">'
            . '<path stroke-linecap="round" stroke-linejoin="round" d="' . $iconPath . '"/></svg>';

        $iconWrapper = '<div style="' . $iconWrapStyle . '">' . $svg . '</div>';

        $body = '<div style="flex:1;min-width:0;display:flex;flex-direction:column;justify-content:center;">';
        if ($title !== '') {
            $body .= '<div style="font-weight:600;font-size:15px;line-height:1.3;color:' . $config['titleColor'] . ';margin-bottom:2px;">' . $title . '</div>';
        }
        if ($message !== '') {
            $body .= '<div style="font-size:14px;line-height:1.4;color:' . $config['textColor'] . ';">' . $message . '</div>';
        }
        $body .= '</div>';

        return Html::tag('div', $iconWrapper . $body, $this->options);
    }

    protected function getVariantConfig($variant)
    {
        $variants = [
            'info' => [
                'bg' => '#eff6ff',
                'border' => '#93c5fd',
                'iconBg' => '#dbeafe',
                'iconColor' => '#1d4ed8',
                'titleColor' => '#1e3a8a',
                'textColor' => '#1e40af',
                'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            'success' => [
                'bg' => '#ecfdf5',
                'border' => '#6ee7b7',
                'iconBg' => '#d1fae5',
                'iconColor' => '#047857',
                'titleColor' => '#064e3b',
                'textColor' => '#065f46',
                'icon' => 'M5 13l4 4L19 7',
            ],
            'warning' => [
                'bg' => '#fffbeb',
                'border' => '#fcd34d',
                'iconBg' => '#fef3c7',
                'iconColor' => '#b45309',
                'titleColor' => '#78350f',
                'textColor' => '#92400e',
                'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            ],
            'danger' => [
                'bg' => '#fef2f2',
                'border' => '#fca5a5',
                'iconBg' => '#fee2e2',
                'iconColor' => '#b91c1c',
                'titleColor' => '#7f1d1d',
                'textColor' => '#991b1b',
                'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            ],
        ];
        return $variants[$variant] ?? $variants['info'];
    }
}

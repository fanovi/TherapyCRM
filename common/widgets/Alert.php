<?php

namespace common\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Alert widget renders a message from session flash. All flash messages are displayed
 * in the sequence they were assigned using setFlash. You can set message as following:
 *
 * ```php
 * Yii::$app->session->setFlash('error', 'This is the message');
 * Yii::$app->session->setFlash('success', 'This is the message');
 * Yii::$app->session->setFlash('info', 'This is the message');
 * ```
 *
 * Multiple messages could be set as follows:
 *
 * ```php
 * Yii::$app->session->setFlash('error', ['Error 1', 'Error 2']);
 * ```
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @author Alexander Makarov <sam@rmcreative.ru>
 */
class Alert extends Widget
{
    /**
     * @var array the alert types configuration for the flash messages.
     * This array is setup as $key => $value, where:
     * - key: the name of the session flash variable
     * - value: the Tailwind CSS classes for the alert type
     */
    public $alertTypes = [
        'error'   => 'bg-red-100 border border-red-400 text-red-700',
        'danger'  => 'bg-red-100 border border-red-400 text-red-700',
        'success' => 'bg-green-100 border border-green-400 text-green-700',
        'info'    => 'bg-blue-100 border border-blue-400 text-blue-700',
        'warning' => 'bg-yellow-100 border border-yellow-400 text-yellow-700'
    ];

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $session = Yii::$app->session;
        $flashes = $session->getAllFlashes();
        $appendClass = isset($this->options['class']) ? ' ' . $this->options['class'] : '';

        foreach ($flashes as $type => $flash) {
            if (!isset($this->alertTypes[$type])) {
                continue;
            }

            foreach ((array) $flash as $i => $message) {
                $id = $this->getId() . '-' . $type . '-' . $i;
                echo Html::tag('div', 
                    Html::tag('div',
                        $message . 
                        Html::button(
                            Html::tag('span', '×', ['class' => 'sr-only']) .
                            Html::tag('span', '×', ['aria-hidden' => 'true']),
                            [
                                'type' => 'button',
                                'class' => 'close-button absolute top-0 right-0 px-4 py-3',
                                'onclick' => "this.parentElement.parentElement.style.display='none'"
                            ]
                        ),
                        ['class' => 'flex justify-between items-center px-4 py-3']
                    ),
                    [
                        'id' => $id,
                        'class' => 'relative px-4 py-3 rounded ' . $this->alertTypes[$type] . $appendClass,
                        'role' => 'alert'
                    ]
                );
            }

            $session->removeFlash($type);
        }
    }
}

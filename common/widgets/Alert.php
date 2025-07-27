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
        'error'   => 'bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200',
        'danger'  => 'bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200',
        'success' => 'bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200',
        'info'    => 'bg-blue-50 border border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200',
        'warning' => 'bg-yellow-50 border border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-200'
    ];

    /**
     * @var array the icons for different alert types
     */
    public $alertIcons = [
        'error' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        'danger' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        'success' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        'info' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        'warning' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>'
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
                
                // Get icon for this alert type
                $icon = isset($this->alertIcons[$type]) ? $this->alertIcons[$type] : '';
                
                echo Html::tag('div', 
                    Html::tag('div', 
                        Html::tag('div', $icon, ['class' => 'flex-shrink-0']) .
                        Html::tag('div', $message, ['class' => 'ml-3 flex-1']) .
                        Html::button(
                            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
                            [
                                'type' => 'button',
                                'class' => 'ml-3 inline-flex flex-shrink-0 text-opacity-50 hover:text-opacity-75',
                                'onclick' => "this.parentElement.parentElement.style.display='none'"
                            ]
                        ),
                        ['class' => 'flex items-center']
                    ),
                    [
                        'id' => $id,
                        'class' => 'mb-4 rounded-lg p-4 ' . $this->alertTypes[$type] . $appendClass,
                        'role' => 'alert'
                    ]
                );
            }

            $session->removeFlash($type);
        }
    }
}

<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "notification_templates".
 *
 * @property int $id
 * @property string $code
 * @property string|null $type
 * @property string|null $title_template
 * @property string|null $message_template
 * @property int|null $days_before
 * @property bool $is_active
 */
class NotificationTemplate extends ActiveRecord
{
    const TYPE_INFO = 'info';
    const TYPE_REMINDER = 'reminder';
    const TYPE_DEADLINE = 'deadline';
    const TYPE_MANDATORY_READ = 'mandatory_read';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%notification_templates}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code'], 'required'],
            [['message_template'], 'string'],
            [['days_before'], 'integer'],
            [['is_active'], 'boolean'],
            [['code', 'type'], 'string', 'max' => 50],
            [['title_template'], 'string', 'max' => 255],
            [['code'], 'unique'],
            [['type'], 'in', 'range' => [self::TYPE_INFO, self::TYPE_REMINDER, self::TYPE_DEADLINE, self::TYPE_MANDATORY_READ]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Codice',
            'type' => 'Tipo',
            'title_template' => 'Template Titolo',
            'message_template' => 'Template Messaggio',
            'days_before' => 'Giorni Prima',
            'is_active' => 'Attivo',
        ];
    }

    /**
     * Sostituisce i placeholder nel template con i valori forniti
     * 
     * @param array $variables Variabili da sostituire nel formato ['placeholder' => 'valore']
     * @return array ['title' => 'titolo processato', 'message' => 'messaggio processato']
     */
    public function processTemplate($variables = [])
    {
        $title = $this->title_template;
        $message = $this->message_template;

        foreach ($variables as $key => $value) {
            $placeholder = '{' . $key . '}';
            $title = str_replace($placeholder, $value, $title);
            $message = str_replace($placeholder, $value, $message);
        }

        return [
            'title' => $title,
            'message' => $message
        ];
    }

    /**
     * Trova un template attivo per codice
     * 
     * @param string $code
     * @return NotificationTemplate|null
     */
    public static function findByCode($code)
    {
        return static::findOne(['code' => $code, 'is_active' => true]);
    }

    /**
     * Restituisce tutti i tipi disponibili
     * 
     * @return array
     */
    public static function getTypeOptions()
    {
        return [
            self::TYPE_INFO => 'Informativa',
            self::TYPE_REMINDER => 'Promemoria',
            self::TYPE_DEADLINE => 'Scadenza',
            self::TYPE_MANDATORY_READ => 'Lettura Obbligatoria',
        ];
    }
} 
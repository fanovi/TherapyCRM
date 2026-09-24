<?php

use yii\db\Migration;

/**
 * Classifica i settings tra interni (sessioni svolte in sede) ed esterni
 * (sessioni svolte fuori struttura: setting domiciliare e scolastico).
 *
 * Gli esterni sono individuati per nome perche' l'id di "Scuola" dipende
 * dall'auto-increment del singolo ambiente.
 */
class m260924_120000_add_location_type_to_setting extends Migration
{
    const EXTERNAL_SETTINGS = ['Domiciliare', 'Scuola'];

    public function safeUp()
    {
        $this->addColumn(
            '{{%setting}}',
            'location_type',
            $this->string(16)->notNull()->defaultValue('internal')->after('nome')
        );

        $this->createIndex('idx-setting-location_type', '{{%setting}}', 'location_type');

        $this->update(
            '{{%setting}}',
            ['location_type' => 'external'],
            ['nome' => self::EXTERNAL_SETTINGS]
        );
    }

    public function safeDown()
    {
        $this->dropIndex('idx-setting-location_type', '{{%setting}}');
        $this->dropColumn('{{%setting}}', 'location_type');
    }
}

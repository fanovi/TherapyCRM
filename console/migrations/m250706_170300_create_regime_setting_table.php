<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%regime_setting}}`.
 */
class m250706_170300_create_regime_setting_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%regime_setting}}', [
            'id' => $this->primaryKey(),
            'regime_id' => $this->integer()->notNull(),
            'setting_id' => $this->integer()->notNull(),
        ]);

        // Aggiungo gli indici per le foreign key
        $this->createIndex(
            'idx-regime_setting-regime_id',
            '{{%regime_setting}}',
            'regime_id'
        );

        $this->createIndex(
            'idx-regime_setting-setting_id',
            '{{%regime_setting}}',
            'setting_id'
        );

        // Aggiungo le foreign key
        $this->addForeignKey(
            'fk-regime_setting-regime_id',
            '{{%regime_setting}}',
            'regime_id',
            '{{%regime}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-regime_setting-setting_id',
            '{{%regime_setting}}',
            'setting_id',
            '{{%setting}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Recupero gli ID dei regimi
        $l11Id = (new \yii\db\Query())
            ->select('id')
            ->from('{{%regime}}')
            ->where(['nome' => 'L11'])
            ->scalar($this->db);

        $privatoId = (new \yii\db\Query())
            ->select('id')
            ->from('{{%regime}}')
            ->where(['nome' => 'Privato'])
            ->scalar($this->db);

        // Recupero gli ID dei setting
        $settingIds = (new \yii\db\Query())
            ->select(['id', 'nome'])
            ->from('{{%setting}}')
            ->all($this->db);

        $settingMap = [];
        foreach ($settingIds as $setting) {
            $settingMap[$setting['nome']] = $setting['id'];
        }

        // Inserimento relazioni per L11
        $l11Settings = [
            'Ambulatoriale',
            'Domiciliare',
            'Piccolo gruppo (PG)',
            'Ambulatoriale + PG',
            'Centro diurno',
            'Semiconvitto'
        ];

        foreach ($l11Settings as $settingNome) {
            $this->insert('{{%regime_setting}}', [
                'regime_id' => $l11Id,
                'setting_id' => $settingMap[$settingNome]
            ]);
        }

        // Inserimento relazioni per Privato
        $privatoSettings = [
            'Ambulatoriale',
            'Domiciliare'
        ];

        foreach ($privatoSettings as $settingNome) {
            $this->insert('{{%regime_setting}}', [
                'regime_id' => $privatoId,
                'setting_id' => $settingMap[$settingNome]
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-regime_setting-setting_id', '{{%regime_setting}}');
        $this->dropForeignKey('fk-regime_setting-regime_id', '{{%regime_setting}}');
        $this->dropIndex('idx-regime_setting-setting_id', '{{%regime_setting}}');
        $this->dropIndex('idx-regime_setting-regime_id', '{{%regime_setting}}');
        $this->dropTable('{{%regime_setting}}');
    }
}

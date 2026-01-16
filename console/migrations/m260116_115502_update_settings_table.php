<?php

use yii\db\Migration;

class m260116_115502_update_settings_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Create new setting "Centro Diurno Adulti"
        $this->insert('{{%setting}}', [
            'nome' => 'Centro Diurno Adulti'
        ]);

        // 2. Delete setting with id 3 "Piccolo gruppo (PG)"
        $this->delete('{{%setting}}', ['id' => 3]);

        // 3. Update setting id 4 from "Ambulatoriale + PG" to "Ambulatoriale PG"
        $this->update(
            '{{%setting}}',
            ['nome' => 'Ambulatoriale PG'],
            ['id' => 4]
        );

        // 4. Update setting id 5 from "Centro diurno" to "Centro Diurno Disabili"
        $this->update(
            '{{%setting}}',
            ['nome' => 'Centro Diurno Disabili'],
            ['id' => 5]
        );

        // 5. Delete setting with id 10 "Medicina di base Privata"
        $this->delete('{{%setting}}', ['id' => 10]);

        // 6. Delete setting with id 11 "Medicina di base convenzionata"
        $this->delete('{{%setting}}', ['id' => 11]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Revert deletion of setting id 11
        $this->insert('{{%setting}}', [
            'id' => 11,
            'nome' => 'Medicina di base convenzionata'
        ]);

        // Revert deletion of setting id 10
        $this->insert('{{%setting}}', [
            'id' => 10,
            'nome' => 'Medicina di base Privata'
        ]);

        // Revert update of setting id 5
        $this->update(
            '{{%setting}}',
            ['nome' => 'Centro diurno'],
            ['id' => 5]
        );

        // Revert update of setting id 4
        $this->update(
            '{{%setting}}',
            ['nome' => 'Ambulatoriale + PG'],
            ['id' => 4]
        );

        // Revert deletion of setting id 3
        $this->insert('{{%setting}}', [
            'id' => 3,
            'nome' => 'Piccolo gruppo (PG)'
        ]);

        // Delete the newly created "Centro Diurno Adulti"
        $this->delete('{{%setting}}', ['nome' => 'Centro Diurno Adulti']);
    }
}

<?php

use yii\db\Migration;

class m250912_172641_add_is_internal_column_to_therapists extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%therapists}}', 'is_internal', $this->tinyInteger(1)->notNull()->defaultValue(0)->comment('0 = Consulente esterno, 1 = Terapista interno'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%therapists}}', 'is_internal');
    }
}

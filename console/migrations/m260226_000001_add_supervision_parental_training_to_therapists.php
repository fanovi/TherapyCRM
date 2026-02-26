<?php

use yii\db\Migration;

class m260226_000001_add_supervision_parental_training_to_therapists extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%therapists}}', 'can_supervise', $this->tinyInteger(1)->notNull()->defaultValue(0)->after('is_internal')->comment('1 = Può effettuare supervisioni'));
        $this->addColumn('{{%therapists}}', 'can_parental_training', $this->tinyInteger(1)->notNull()->defaultValue(0)->after('can_supervise')->comment('1 = Può effettuare parental training'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%therapists}}', 'can_parental_training');
        $this->dropColumn('{{%therapists}}', 'can_supervise');
    }
}

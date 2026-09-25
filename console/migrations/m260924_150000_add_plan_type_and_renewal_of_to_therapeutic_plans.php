<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Distingue i piani terapeutici nuovi dai rinnovi.
 *
 *   - plan_type: 'new' | 'renewal', usato dalle statistiche (scheda "Nuovi piani")
 *   - renewal_of_id: piano rinnovato (facoltativo, il piano precedente potrebbe
 *     non essere presente nel gestionale)
 *
 * Recupero dei piani esistenti: per ogni coppia paziente + regime, ordinati per
 * data inizio, il primo piano e' "nuovo" e ogni successivo e' "rinnovo" del
 * piano che lo precede. E' una stima: i piani esistenti non hanno questa
 * informazione.
 */
class m260924_150000_add_plan_type_and_renewal_of_to_therapeutic_plans extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%therapeutic_plans}}',
            'plan_type',
            $this->string(16)->null()->after('regime_id')
        );
        $this->addColumn(
            '{{%therapeutic_plans}}',
            'renewal_of_id',
            $this->integer()->null()->after('plan_type')
        );

        $this->createIndex('idx-therapeutic_plans-plan_type', '{{%therapeutic_plans}}', 'plan_type');
        $this->createIndex('idx-therapeutic_plans-renewal_of_id', '{{%therapeutic_plans}}', 'renewal_of_id');
        $this->addForeignKey(
            'fk-therapeutic_plans-renewal_of_id',
            '{{%therapeutic_plans}}',
            'renewal_of_id',
            '{{%therapeutic_plans}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->backfill();
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-therapeutic_plans-renewal_of_id', '{{%therapeutic_plans}}');
        $this->dropIndex('idx-therapeutic_plans-renewal_of_id', '{{%therapeutic_plans}}');
        $this->dropIndex('idx-therapeutic_plans-plan_type', '{{%therapeutic_plans}}');
        $this->dropColumn('{{%therapeutic_plans}}', 'renewal_of_id');
        $this->dropColumn('{{%therapeutic_plans}}', 'plan_type');
    }

    private function backfill()
    {
        $plans = (new Query())
            ->select(['id', 'patient_id', 'regime_id'])
            ->from('{{%therapeutic_plans}}')
            ->orderBy(['patient_id' => SORT_ASC, 'regime_id' => SORT_ASC, 'start_date' => SORT_ASC, 'id' => SORT_ASC])
            ->all($this->db);

        $newIds = [];
        $renewals = 0;
        $previous = null;

        foreach ($plans as $plan) {
            $sameChain = $previous !== null
                && $previous['patient_id'] == $plan['patient_id']
                && $previous['regime_id'] == $plan['regime_id'];

            if ($sameChain) {
                $this->db->createCommand()->update(
                    '{{%therapeutic_plans}}',
                    ['plan_type' => 'renewal', 'renewal_of_id' => (int) $previous['id']],
                    ['id' => (int) $plan['id']]
                )->execute();
                $renewals++;
            } else {
                $newIds[] = (int) $plan['id'];
            }

            $previous = $plan;
        }

        foreach (array_chunk($newIds, 500) as $chunk) {
            $this->db->createCommand()->update(
                '{{%therapeutic_plans}}',
                ['plan_type' => 'new'],
                ['id' => $chunk]
            )->execute();
        }

        echo '    > piani nuovi: ' . count($newIds) . ", rinnovi: {$renewals}\n";
    }
}

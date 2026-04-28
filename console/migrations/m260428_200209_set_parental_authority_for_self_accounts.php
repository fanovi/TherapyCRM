<?php

use yii\db\Migration;

/**
 * Imposta `has_parental_authority = 1` per tutti gli account "io stesso".
 *
 * Razionale: se l'account paziente e' la stessa persona del paziente
 * (relationship_type = 'self'), l'autorita' parentale e' implicita —
 * non ha senso un self senza autorita' su se stesso. Dopo questo fix
 * il filtro "destinatari notifiche" puo' usare la regola
 *   relationship_type = 'self' OR has_parental_authority = 1
 * per individuare gli account che devono ricevere comunicazioni e
 * notifiche relative al paziente.
 */
class m260428_200209_set_parental_authority_for_self_accounts extends Migration
{
    public function safeUp()
    {
        $rows = $this->db->createCommand()
            ->update(
                'account_patients',
                ['has_parental_authority' => 1],
                ['relationship_type' => 'self', 'has_parental_authority' => 0]
            )
            ->execute();

        echo "Aggiornati $rows account 'self' con has_parental_authority = 1.\n";
        return true;
    }

    public function safeDown()
    {
        // Nessuna reverse: non sappiamo distinguere i self originariamente
        // gia' a 1 da quelli portati a 1 da questa migration. Rollback no-op.
        echo "Rollback no-op (i flag has_parental_authority restano cosi'.\n";
        return true;
    }
}

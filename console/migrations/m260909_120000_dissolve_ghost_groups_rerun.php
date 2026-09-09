<?php

use yii\db\Migration;

/**
 * Ticket #296: seconda pulizia dei "gruppi fantasma".
 *
 * La migration m260611_150000 aveva ripulito i gruppi rimasti con un solo
 * membro attivo, ma il percorso di modifica appuntamento (actionUpdateAppointment)
 * continuava a generarne: con "applica azioni a tutto il gruppo" deselezionato e
 * solo il terapista cambiato, il paziente restava agganciato al gruppo di
 * partenza. Corretto il codice, questa migration ripulisce i gruppi fantasma
 * creati nel frattempo.
 *
 * Azzera group_session_id sugli appuntamenti ATTIVI (non cancellati) che
 * appartengono a gruppi con al massimo un membro attivo. Gli appuntamenti
 * cancellati restano invariati per lo storico. Nessun altro dato (data, orario,
 * terapista, stato) viene toccato.
 */
class m260909_120000_dissolve_ghost_groups_rerun extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $count = $this->db->createCommand("
            SELECT COUNT(*) FROM {{%appointments}} a
            JOIN (
                SELECT group_session_id
                FROM {{%appointments}}
                WHERE group_session_id IS NOT NULL
                GROUP BY group_session_id
                HAVING SUM(status != 'cancelled') <= 1
            ) g ON a.group_session_id = g.group_session_id
            WHERE a.status != 'cancelled'
        ")->queryScalar();

        if ((int)$count === 0) {
            echo "Nessun gruppo fantasma da correggere.\n";
            return true;
        }

        $this->execute("
            UPDATE {{%appointments}} a
            JOIN (
                SELECT group_session_id
                FROM {{%appointments}}
                WHERE group_session_id IS NOT NULL
                GROUP BY group_session_id
                HAVING SUM(status != 'cancelled') <= 1
            ) g ON a.group_session_id = g.group_session_id
            SET a.group_session_id = NULL
            WHERE a.status != 'cancelled'
        ");

        echo "Gruppi fantasma dissolti: azzerato group_session_id su {$count} appuntamenti attivi.\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Non reversibile: il group_session_id rimosso non e' recuperabile.
        echo "m260909_120000_dissolve_ghost_groups_rerun non e' reversibile (modifica solo dati derivati).\n";
        return true;
    }
}

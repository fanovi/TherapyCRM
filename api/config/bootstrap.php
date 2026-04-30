<?php

// Timezone Europe/Rome per coerenza con il gestionale e con quanto mostrato
// agli utenti (locali, non UTC). Tutte le date salvate da API tramite
// date()/time() saranno quindi nella timezone italiana.
date_default_timezone_set('Europe/Rome');

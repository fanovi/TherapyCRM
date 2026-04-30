<?php
// Timezone Europe/Rome per tutti gli ambienti (frontend gestionale, backend,
// console, api). Allineato con common/config/main.php->formatter->defaultTimeZone.
date_default_timezone_set('Europe/Rome');

Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@frontend', dirname(dirname(__DIR__)) . '/frontend');
Yii::setAlias('@backend', dirname(dirname(__DIR__)) . '/backend');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');
Yii::setAlias('@api', dirname(dirname(__DIR__)) . '/api');

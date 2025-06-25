<?php
require_once '../config/bootstrap.php';

echo "🕐 TEST CONFIGURAZIONE TIMEZONE UTC\n";
echo "=====================================\n\n";

// Verifica configurazione timezone
echo "⚙️  Configurazione timezone PHP:\n";
echo "   Default timezone: " . date_default_timezone_get() . "\n";
echo "   Current time UTC: " . date('Y-m-d H:i:s T') . "\n";
echo "   Current time ISO8601: " . date('c') . "\n\n";

// Test DateTime con UTC esplicito
echo "📅 Test DateTime con UTC esplicito:\n";
$now = new DateTime('now', new DateTimeZone('UTC'));
echo "   DateTime UTC: " . $now->format('Y-m-d\TH:i:s\Z') . "\n";

// Test DateTime senza timezone (dovrebbe usare UTC)
$nowDefault = new DateTime('now');
echo "   DateTime default: " . $nowDefault->format('Y-m-d\TH:i:s T') . "\n";

// Verifica che siano uguali (o quasi)
$diff = abs($now->getTimestamp() - $nowDefault->getTimestamp());
echo "   Differenza timestamp: {$diff} secondi\n";

if ($diff <= 1) {
    echo "   ✅ Configurazione timezone UTC corretta!\n";
} else {
    echo "   ❌ Problema configurazione timezone!\n";
}

echo "\n🔧 Test funzioni di validazione date:\n";

// Simula le funzioni del controller
function isValidDateTest($dateString) {
    if (!$dateString) return false;
    $date = DateTime::createFromFormat('Y-m-d', $dateString, new DateTimeZone('UTC'));
    return $date && $date->format('Y-m-d') === $dateString;
}

function compareDatesTest($dateFrom, $dateTo) {
    if (!$dateFrom || !$dateTo) return 0;
    $from = DateTime::createFromFormat('Y-m-d', $dateFrom, new DateTimeZone('UTC'));
    $to = DateTime::createFromFormat('Y-m-d', $dateTo, new DateTimeZone('UTC'));
    if (!$from || !$to) return 0;
    return $from <=> $to;
}

// Test validazione date
$testDates = [
    '2025-01-15' => true,
    '2025-13-01' => false,
    '2025-01-32' => false,
    'invalid' => false
];

echo "   Test validazione date:\n";
foreach ($testDates as $date => $expected) {
    $result = isValidDateTest($date);
    $status = ($result === $expected) ? '✅' : '❌';
    echo "     $status '$date' -> " . ($result ? 'valid' : 'invalid') . "\n";
}

// Test confronto date
echo "\n   Test confronto date:\n";
$compareTests = [
    ['2025-01-15', '2025-01-20', -1, 'date_from < date_to'],
    ['2025-01-20', '2025-01-15', 1, 'date_from > date_to'],
    ['2025-01-15', '2025-01-15', 0, 'date_from = date_to']
];

foreach ($compareTests as [$from, $to, $expected, $desc]) {
    $result = compareDatesTest($from, $to);
    $status = ($result === $expected) ? '✅' : '❌';
    echo "     $status $desc: compareDates('$from', '$to') = $result\n";
}

echo "\n🏁 Test configurazione timezone completato!\n";
?> 
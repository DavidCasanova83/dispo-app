<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Qualifications par mois depuis août 2025:\n";
echo str_repeat('=', 50) . "\n";

$results = DB::table('qualifications')
    ->selectRaw('strftime("%Y-%m", created_at) as month, COUNT(*) as count')
    ->where('created_at', '>=', '2025-08-01')
    ->groupBy('month')
    ->orderBy('month')
    ->get();

foreach($results as $row) {
    echo sprintf("%s: %d qualifications\n", $row->month, $row->count);
}

echo "\nTotal: " . DB::table('qualifications')->where('created_at', '>=', '2025-08-01')->count() . "\n";

echo "\nTous les mois de la base (sans filtre de date):\n";
$allMonths = DB::table('qualifications')
    ->selectRaw('strftime("%Y-%m", created_at) as month, COUNT(*) as count')
    ->groupBy('month')
    ->orderBy('month')
    ->get();

foreach($allMonths as $row) {
    echo sprintf("%s: %d qualifications\n", $row->month, $row->count);
}

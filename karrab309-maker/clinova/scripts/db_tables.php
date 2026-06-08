<?php

declare(strict_types=1);

$dbPath = __DIR__ . '/../database/database.sqlite';
if (!file_exists($dbPath)) {
    fwrite(STDERR, "DB file not found: {$dbPath}\n");
    exit(1);
}

$pdo = new PDO('sqlite:' . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$rows = $pdo->query("select name from sqlite_master where type='table' order by name")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['name'], PHP_EOL;
}


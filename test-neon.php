<?php

$dsn  = 'pgsql:host=ep-floral-glitter-abk7qjtw-pooler.eu-west-2.aws.neon.tech;dbname=neondb;sslmode=require;options=endpoint=ep-floral-glitter-abk7qjtw';
$user = 'neondb_owner';
$pass = 'npg_1pLU6elRDmzZ';
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    var_dump($pdo->query('SELECT 1')->fetchAll());
} catch (Throwable $e) {
    var_dump($e->getMessage());
}

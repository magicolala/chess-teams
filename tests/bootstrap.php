<?php

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Ensure the test database schema is in sync with current ORM metadata
// This is especially important for SQLite file DBs where old files may persist between runs.
if (($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) !== 'test') {
    return;
}

$databaseUrl = (string) ($_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? '');
if ('' !== $databaseUrl && !str_starts_with($databaseUrl, 'sqlite:') && !str_starts_with($databaseUrl, 'sqlite3:')) {
    return;
}

$kernel = new App\Kernel('test', true);
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();
$metadata = $em->getMetadataFactory()->getAllMetadata();
if (!empty($metadata)) {
    $tool = new SchemaTool($em);
    // Drop and recreate schema to start clean for tests
    try {
        $tool->dropSchema($metadata);
    } catch (Throwable $e) {
        // ignore drop errors
    }
    $tool->createSchema($metadata);
}

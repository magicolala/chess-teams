<?php

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManagerInterface;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Ensure the test database schema is in sync with current ORM metadata
// This is especially important for SQLite file DBs where old files may persist between runs.
if (($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) === 'test') {
    $kernel = new \App\Kernel('test', true);
    $kernel->boot();

    /** @var EntityManagerInterface $em */
    $em = $kernel->getContainer()->get('doctrine')->getManager();

    // Only run schema tool for SQLite to avoid interfering with other setups
    $conn = $em->getConnection();
    // Detect SQLite via DATABASE_URL to avoid relying on removed DBAL APIs like Driver::getName()
    $databaseUrl = (string)($_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? '');
    if ($databaseUrl !== '' && (str_starts_with($databaseUrl, 'sqlite:') || str_starts_with($databaseUrl, 'sqlite3:'))) {
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $tool = new SchemaTool($em);
            // Drop and recreate schema to start clean for tests
            try {
                $tool->dropSchema($metadata);
            } catch (\Throwable $e) {
                // ignore drop errors
            }
            $tool->createSchema($metadata);
        }
    }
}

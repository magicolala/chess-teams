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

$kernel = new App\Kernel('test', true);
$kernel->boot();

/** @var EntityManagerInterface $em */
$em = $kernel->getContainer()->get('doctrine')->getManager();

try {
    $connection = $em->getConnection();

    if (!$connection->isConnected()) {
        $connection->connect();
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf(
            "Skipping schema sync: unable to connect to test database (%s).\n",
            $exception->getMessage()
        )
    );

    return;
}

$metadata = $em->getMetadataFactory()->getAllMetadata();
if (empty($metadata)) {
    return;
}

$tool = new SchemaTool($em);

try {
    $tool->dropSchema($metadata);
} catch (Throwable $exception) {
    // ignore drop errors (for example when schema was never created)
}

try {
    $tool->createSchema($metadata);
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf(
            "Unable to sync test schema: %s\n",
            $exception->getMessage()
        )
    );
}

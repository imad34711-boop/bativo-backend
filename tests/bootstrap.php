<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// La base de test est creee une fois par execution de la suite.
// Le schema est ensuite reconstruit par chaque cas de test.
passthru(sprintf(
    'php %s doctrine:database:create --if-not-exists --env=test --quiet',
    escapeshellarg(dirname(__DIR__).'/bin/console')
));

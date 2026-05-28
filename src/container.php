<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Container\ContainerInterface;

return [

    // Database (Eloquent ORM via Illuminate)
    'db' => function () {
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST']  ?? '127.0.0.1',
            'port'      => $_ENV['DB_PORT']  ?? '3306',
            'database'  => $_ENV['DB_NAME']  ?? 'university_course_portal',
            'username'  => $_ENV['DB_USER']  ?? 'root',
            'password'  => $_ENV['DB_PASS']  ?? '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        return $capsule;
    },

    // Alias the class name so PHP-DI auto-wiring resolves Twig type-hints
    \Slim\Views\Twig::class => \DI\get('view'),

    // Twig view
    'view' => function (ContainerInterface $c) {
        $c->get('db'); // boot DB
        $twig = \Slim\Views\Twig::create(__DIR__ . '/../templates', [
            'cache' => false,
            'debug' => true,
        ]);
        // Global template vars
        $twig->getEnvironment()->addGlobal('app_name', $_ENV['APP_NAME'] ?? 'University Course Portal');
        $twig->getEnvironment()->addGlobal('session', $_SESSION);

        // Read and immediately clear flash so it only shows once
        $flash = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);
        $twig->getEnvironment()->addGlobal('flash_success', $flash);
        return $twig;
    },

];

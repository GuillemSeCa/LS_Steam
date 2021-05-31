<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Views\TwigMiddleware;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();

$dotenv->load(__DIR__ . '/../.env');

require_once __DIR__ . '/../config/dependencies.php';

AppFactory::setContainer($container);

$app = AppFactory::create();

$app->add(TwigMiddleware::createFromContainer($app));

$app->addBodyParsingMiddleware();

$app->addRoutingMiddleware();

$app->addErrorMiddleware(true, false, false);

require_once __DIR__ . '/../config/routing.php';

$app->run();
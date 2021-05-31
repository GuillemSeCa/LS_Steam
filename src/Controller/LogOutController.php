<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;

final class LogOutController
{
    public function __construct()
    {
    }

    public function handle_log_out(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
            unset($_SESSION);
        }

        return $response
            ->withHeader('Location', $routeParser->urlFor("home"))
            ->withStatus(301);
    }
}

<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SallePW\SlimApp\Model\UserRepository;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;


final class WalletController
{

    public function __construct(private Twig $twig, private UserRepository $userRepository)
    {
    }

    public function show(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $errors = [];

        $money = $this->userRepository->getMoney($_SESSION['id']);

        return $this->twig->render(
            $response,
            'wallet.twig',
            [
                'money' => number_format($money, 2, ',', '.'),

                'is_user_logged' => isset($_SESSION['id']),
                'errors' => $errors,
                'profilePic' => (!isset($_SESSION['profilePic']) ? "" : $routeParser->urlFor('home') . $_SESSION['profilePic']),
            ]
        );
    }

    public function handleUpdate(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $errors = [];

        $curr_money = $this->userRepository->getMoney($_SESSION['id']);
        $add_value = $data['money'];

        if ($add_value > PHP_INT_MAX) {

            $errors['tooBig'] = true;
        } else {

            if ($add_value != "" && !is_numeric($add_value)) {
                $errors['isNumeric'] = true;

                // Mai pasara. no fa falta implemetar la ui.
            } else {

                $errors['positiveVal'] = ($add_value <= 0);

                if (!$errors['positiveVal']) {
                    $curr_money += $add_value;
                    $this->userRepository->setMoney($_SESSION['id'], (int)$curr_money);
                }

            }

        }

        return $this->twig->render(
            $response,
            'wallet.twig',
            [
                'money' => number_format($curr_money, 2, ',', '.'),

                'errors' => $errors,

                'is_user_logged' => isset($_SESSION['id']),
                'profilePic' => (!isset($_SESSION['profilePic']) ? "" : $routeParser->urlFor('home') . $_SESSION['profilePic']),
            ]
        );
    }
}
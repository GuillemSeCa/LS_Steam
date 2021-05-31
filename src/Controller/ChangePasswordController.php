<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class ChangePasswordController
{

    public function __construct(private Twig $twig, private UserRepository $userRepository)
    {
    }

    public function handleUpdate(Request $request, Response $response): Response
    {
        $user = $this->userRepository->getUser($_SESSION['id']);
        $data = $request->getParsedBody();

        $errors = $this->checkPassword($data, $user);

        if (empty($errors)) {
            $user->setPassword(password_hash($data['password'], PASSWORD_DEFAULT));
            $this->userRepository->updateUser($user);

            $errors['success'] = "Password updated successfully!";
            return $this->show($request, $response, $errors);
        }

        return $this->show($request, $response, $errors);
    }

    private function checkPassword(array $data, User $user): array
    {
        $errors = [];

        if (!(password_verify($data['old_password'], $user->password())))
            $errors['old_password'] = 'Incorrect password.';

        if (empty($data['password']) || strlen($data['password']) <= 6)
            $errors['password'] = 'The password must contain at least 7 characters.';

        elseif (!(preg_match('/[A-Z]/', $data['password']) && preg_match('/[a-z]/', $data['password'])))
            $errors['password'] = "The password must contain at least 1 uppercase and 1 lowercase.";

        elseif (!preg_match("#[0-9]+#", $data['password']))
            $errors['password'] = "The password must contain at least 1 number.";

        if ($data['password'] != $data['password_repeat'])
            $errors['password_repeat'] = "Passwords must match";

        return $errors;
    }

    public function show(Request $request, Response $response, array $errors): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $this->twig->render($response, 'changePassword.twig', [
            'formErrors' => $errors,

            'formAction' => $routeParser->urlFor("changePassword"),
            'formMethod' => "POST",
            'is_user_logged' => isset($_SESSION['id']),
            'submitValue' => "Change",
            'formTitle' => "Change password",

            // Hrefs de la base
            'profilePic' => (!isset($_SESSION['profilePic']) ? "" : $routeParser->urlFor('home') . $_SESSION['profilePic']),
        ]);
    }

    private function print(string $msg)
    {
        error_log(print_r($msg, TRUE));
    }
}
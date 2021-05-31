<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use DateInterval;
use DateTime;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SallePW\SlimApp\Model\UserRepository;
use Slim\Flash\Messages;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

abstract class GenericFormController
{
    public function __construct(private Twig $twig,
                                private UserRepository $userRepository,
                                private bool $is_login,
                                private Messages $flash)
    {
    }

    protected function showForm(Request $request, Response $response,
                                string $formAction, string $submitValue, string $formTitle, array $errors): Response
    {

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $messages = $this->flash->getMessages();

        return $this->twig->render(
            $response,
            'login_register.twig',
            [
                'is_user_logged' => isset($_SESSION['id']),

                // Mostar flash message.
                'flash_messages' => $messages['session_error'] ?? [],

                'formData' => $request->getParsedBody(),
                'formErrors' => $errors,
                'formAction' => $routeParser->urlFor($formAction),
                'formMethod' => "POST",
                'is_login' => $this->is_login,
                'submitValue' => $submitValue,
                'formTitle' => $formTitle,

                'profilePic' => (!isset($_SESSION['profilePic']) ? "" : $routeParser->urlFor('home') . $_SESSION['profilePic']),
            ]
        );
    }

    abstract protected function handleFormSubmission(Request $request, Response $response): Response;

    protected function checkForm(Request $request): array
    {
        $data = $request->getParsedBody();
        $errors = [];

        //Fem que el mateix camp (email) tingui els dos significats i pertant, totes les verificacions
        if ($this->is_login)
            $data['username'] = $data['email'];

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'The email address is not valid';
        } elseif (!(str_contains($data['email'], '@salle.url.edu') || str_contains($data['email'], '@students.salle.url.edu'))) {
            $errors['email'] = 'The email domain not accepted. Try using a @salle.url.edu or students.salle.url.edu domain';
        }

        if (!ctype_alnum($data['username'])) {
            $errors['username'] = 'The username is not valid';
        }

        // Si no es genera un error per username, silenciem el error de email perque el login es correcte.
        if (!isset($errors['username'])) {
            unset($errors['email']);
        }

        if (!isset($errors['email'])) {
            unset($errors['username']);
        }

        $this->checkPassword($data, $errors);

        if (!$this->is_login) {

            if ($this->userRepository->emailExists($data['email'])) {
                $errors['email'] = 'The email address is already used';
            }

            if (!$this->is_login && $this->userRepository->usernameExists($data['username'])) {
                $errors['username'] = 'The username already exists';
            }

            if ($data['password'] != $data['password_repeat']) {
                $errors['password_repeat'] = "Passwords must match";
            }

            if (!empty($data['phone'] && (mb_strlen($data['phone'], "utf8") != 9 || ($data['phone'][0] != 6 && $data['phone'][0] != 7) || ($data['phone'][0] == 7 && $data['phone'][1] == 0)))) {
                $errors['phone'] = "The phone number is not valid.";
            }

            try {
                // Es crea objecte de dateTime
                $bday = new DateTime($data['birthday']);

                // Afegim 18 anys
                $bday->add(new DateInterval("P18Y"));

                // Mirem si la data supera l'actual per saber si és major d'edat
                if ($bday >= new DateTime()) {
                    $errors['birthday'] = "You must be over 18 to register";
                }

            } catch (Exception $exception) {
                // No hauria de passar mai....
                $errors['birthday'] = "El format de la data introduida no es correcte.";
            }
        }

        return $errors;
    }

    private function checkPassword(array $data, array &$errors)
    {
        if (empty($data['password']) || strlen($data['password']) <= 6) {
            $errors['password'] = 'The password must contain at least 7 characters.';
        } elseif (!(preg_match('/[A-Z]/', $data['password']) && preg_match('/[a-z]/', $data['password']))) {
            $errors['password'] = "The password must contain at least 1 uppercase and 1 lowercase.";
        } elseif (!preg_match("#[0-9]+#", $data['password'])) {
            $errors['password'] = "The password must contain at least 1 number.";
        }
    }
}
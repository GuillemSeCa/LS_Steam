<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use DateTime;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SallePW\SlimApp\Model\GifRepository;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use Slim\Flash\Messages;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class RegisterController extends GenericFormController
{
    public function __construct(private Twig $twig,
                                private UserRepository $userRepository,
                                private GifRepository $gifRepository,
                                private Messages $flash)
    {
        parent::__construct($twig, $userRepository, false, $flash);
    }

    public function show(Request $request, Response $response): Response
    {
        return parent::showForm($request, $response, "handle-register", "Register", "Register", []);
    }

    public function handleFormSubmission(Request $request, Response $response): Response
    {

        //checks errors of register Data
        $errors = parent::checkForm($request);

        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        if (!empty($errors)) {
            return parent::showForm($request, $response, "handle-register", "Register", "Register", $errors);
        }

        try {
            $data = $request->getParsedBody();

            $user = new User(
                0,
                $data['username'],
                $data['email'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                new DateTime($data['birthday']),
                $data['phone'],
                'default.jpg'
            );

            $this->userRepository->savePendingUser($user);

            $base = 'http://localhost:8030/activate';//$routeParser->urlFor('verify');

            $_SESSION['email'] = $user->email();

            //We send the email to the User
            $this->sendEmail($user, $base);

        } catch (Exception $exception) {
            //  Email used or db exception. (No pasa mai si tot va be aka email funciona i bbdd pot guardar usuari)

            $errors['email'] = 'Error: ' . $exception->getMessage();
            return parent::showForm($request, $response, "handle-register", "Register", "Register", $errors);
        }

        // Mostrem vista register done.
        return $this->twig->render(
            $response,
            'register_done.twig',
            [
                'user_email' => $user->email(),
                'gif_url' => $this->gifRepository->getRandomGif("success"),

                'formTitle' => "Register",

                'profilePic' => (!isset($_SESSION['profilePic']) ? "" : $routeParser->urlFor('home') . $_SESSION['profilePic']),
            ]
        );
    }

    public function sendEmail(User $user, string $base): void
    {

        $mail = new PHPMailer(true);

        try {
            //Code settings
//            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                //Enable verbose debug output
            $mail->isSMTP();                                      //Send using SMTP
            $mail->Host = 'mail.smtpbucket.com';            //Set the SMTP server to send through
            $mail->Port = 8025;                              //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

            //Recipients
            $mail->setFrom('lsteam@lsteam.com', 'LSTEAM BACKEND TEAM');
            $mail->addAddress($user->email(), $user->getUsername());
            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = 'Activation LSteam';

            //Generate the link to send in the email to activate
            $mail->Body = 'Click this link to verify! <a href="' . $base . '?token=' . $this->userRepository->getUserToken($user) . '"> Link</a>';
            $mail->AltBody = 'Click this link to verify! <a href="' . $base . '?token=' . $this->userRepository->getUserToken($user) . '"> Link</a>';

            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}


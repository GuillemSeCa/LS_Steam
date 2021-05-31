<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SallePW\SlimApp\Model\GifRepository;
use SallePW\SlimApp\Model\UserRepository;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class VerifyUserController
{

    public function __construct(private Twig $twig, private UserRepository $userRepository,
                                private GifRepository $gifRepository)
    {
    }

    public function verifyUser(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();


        $token = $request->getQueryParams()['token'];
        $isSuccess = $this->userRepository->verifyUser($token);

        if ($isSuccess) {
            $message = "User confirmation done! Check your inbox to complete the registration and earn 50€!";
            $gif_query = "money";

            $this->userRepository->setMoney($this->userRepository->getIdByGivenEmail($_SESSION['email']), 50);
            $this->sendEmail($_SESSION['email'], 'http://localhost:8030/login');
        } else {
            $message = "Error! Impossible to verify the user. Maybe you are already verified?";
            $gif_query = "sad";
        }
        return $this->twig->render(
            $response,
            'verifyUser.twig',
            [

                'isSuccess' => $isSuccess,
                'message' => $message,
                'is_user_logged' => isset($_SESSION['id']),

                'gif_url' => $this->gifRepository->getRandomGif($gif_query),
                'profilePic' => (!isset($_SESSION['profilePic']) ? "" : $routeParser->urlFor('home') . $_SESSION['profilePic']),
            ]
        );
    }

    public function sendEmail(string $email, string $base): void
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
            $mail->addAddress($email);
            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = 'We added 50$ into your account! Open this link to start in LSteam';

            //Generate the link to send in the email to activate
            $mail->Body = 'Click this link to start the xperience! <a href="' . $base . '"> Link</a>';
            $mail->AltBody = 'Click this link to start the xperience! <a href="' . $base . '"> Link</a>';

            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
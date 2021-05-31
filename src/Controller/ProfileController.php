<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use DateTime;
use Error;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class ProfileController
{
    public const DATE_FORMAT = 'Y-m-d';
    public const UPLOADS_DIR = 'uploads';

    private const DEFAULT_IMG = 'default.jpg';

    private const UNEXPECTED_ERROR = "An unexpected error occurred uploading the file '%s'...";
    private const INVALID_EXTENSION_ERROR = "The received file extension '%s' is not valid";
    private const INVALID_SIZE_ERROR = "The file must be under 1MB";
    private const INVALID_DIMENSIONS_ERROR = "The file must be 500x500 pixels";
    private const TOO_MANY_FILES_ERROR = "Only one file can be uploaded!";

    private const ALLOWED_EXTENSIONS = ['jpg', 'png'];

    public function __construct(private Twig $twig, private UserRepository $userRepository)
    {
    }

    public function handleUpdate(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles()['files'];
        $user = $this->userRepository->getUser($_SESSION['id']);

        $errors = $this->checkForm($request, $user);

        $profilePic = $user->getProfilePic();
        $uploadedFile = array_pop($uploadedFiles);

        $imgErr = $this->checkImage($uploadedFile, $profilePic);
        if (!empty($imgErr)) $errors['profilePic'] = $imgErr;

        if (empty($errors)) {
            $data = $request->getParsedBody();

            $this->userRepository->updateUser(new User(
                0,
                $user->getUsername(),
                $user->email(),
                $user->password(),
                new DateTime(),
                empty($data['phone']) ? $user->getPhone() : $data['phone'],
                $profilePic
            ));

            $_SESSION['profilePic'] = ProfileController::UPLOADS_DIR . DIRECTORY_SEPARATOR . $profilePic;
            $errors['success'] = "Profile updated correctly!";
        }

        return $this->show($request, $response, $errors);
    }

    protected function checkForm(Request $request, User $user): array
    {
        $data = $request->getParsedBody();
        $errors = [];

        if (!empty($data['phone'] && (mb_strlen($data['phone'], "utf8") != 9 || ($data['phone'][0] != 6 && $data['phone'][0] != 7) || ($data['phone'][0] == 7 && $data['phone'][1] == 0))))
            $errors['phone'] = "The phone number is not valid.";

        return $errors;
    }

    protected function checkImage($uploadedFile, &$profilePic): ?string
    {
        $original_name = $uploadedFile->getClientFilename();
        $error = NULL;

        if (!empty($original_name)) {
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK)
                return sprintf(self::UNEXPECTED_ERROR,
                    $uploadedFile->getClientFilename());

            $uuid = Uuid::uuid4();
            $name = $uuid->toString();

            $fileInfo = pathinfo($original_name);
            $format = $fileInfo['extension'];
            $img_size = $uploadedFile->getSize();

            if (!in_array(strtolower($format), self::ALLOWED_EXTENSIONS, true))
                return sprintf(self::INVALID_EXTENSION_ERROR, $format);

            if ($img_size > pow(2, 20))
                return sprintf(self::INVALID_SIZE_ERROR, $format);

            try {
                $uploadedFile->moveTo("./" . self::UPLOADS_DIR . DIRECTORY_SEPARATOR . $name . "." . $format);
                $sizeInfo = getimagesize(self::UPLOADS_DIR . DIRECTORY_SEPARATOR . $name . "." . $format);
                if ($sizeInfo[0] > 500 || $sizeInfo[1] > 500) {
                    unlink(self::UPLOADS_DIR . DIRECTORY_SEPARATOR . $name . "." . $format);
                    return sprintf(self::INVALID_DIMENSIONS_ERROR, $format);
                }

                if ($profilePic != self::DEFAULT_IMG) unlink(self::UPLOADS_DIR . DIRECTORY_SEPARATOR . $profilePic);
                $profilePic = $name . "." . $format;

            } catch (Error $e) {
                return $e->getMessage();
            }
        }
        return NULL;
    }

    public function show(Request $request, Response $response, array $errors): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $user = $this->userRepository->getUser($_SESSION['id']);

        $profilePic = $user->getProfilePic();
        $profilePic = self::UPLOADS_DIR . DIRECTORY_SEPARATOR . "$profilePic";

        return $this->twig->render($response, 'profile.twig', [
            'formErrors' => $errors,

            'formData' => $request->getParsedBody(),
            'formAction' => $routeParser->urlFor("profile"),
            'formMethod' => "POST",
            'is_user_logged' => isset($_SESSION['id']),
            'submitValue' => "Update profile",
            'formTitle' => "Profile",

            'username' => $user->getUsername(),
            'email' => $user->email(),
            'phone' => $user->getPhone(),
            'birthday' => $user->getBirthday()->format(self::DATE_FORMAT),
            'profilePic' => (!isset($_SESSION['profilePic']) ? "" : $routeParser->urlFor('home') . $_SESSION['profilePic']),

            'change_password_href' => $routeParser->urlFor('changePassword'),
        ]);
    }
}
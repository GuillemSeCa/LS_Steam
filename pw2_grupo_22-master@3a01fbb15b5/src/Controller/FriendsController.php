<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Controller;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SallePW\SlimApp\Model\FriendsRepository;
use SallePW\SlimApp\Model\UserRepository;
use SallePW\SlimApp\Repository\MySQLFriendsRepository;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

final class FriendsController
{

    public function __construct(private Twig $twig, private UserRepository $userRepository, private FriendsRepository $friendsRepository)
    {
    }

    public function show(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $friends = $this->friendsRepository->getFriends($_SESSION['id'], MySQLFriendsRepository::REQUEST_ACCEPTED);

        return $this->twig->render($response, 'friends.twig', [
            'friendList' => $friends,
            'listTitle' => 'Friend list',
            'isRequests' => false,
            'emptyMessage' => "You don't have any friend yet!",

            'requests_href' => $routeParser->urlFor('friendRequests'),
            'sendRequest_href' => $routeParser->urlFor('sendRequest'),

            'is_user_logged' => isset($_SESSION['id']),
            'profilePic' => (!isset($_SESSION['profilePic']) ? "" : $routeParser->urlFor('home') . $_SESSION['profilePic']),
        ]);
    }

    public function showRequests(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $friends = $this->friendsRepository->getFriends($_SESSION['id'], MySQLFriendsRepository::REQUEST_PENDING);

        return $this->twig->render($response, 'friends.twig', [
            'friendList' => $friends,
            'listTitle' => 'Friend requests',
            'isRequests' => true,
            'emptyMessage' => "It seems that you don't have any friend request to handle",

            'accept_href' => $routeParser->urlFor('acceptFriendRequest', ['requestId' => 0]),
            'decline_href' => $routeParser->urlFor('declineFriendRequest', ['requestId' => 0]),

            'is_user_logged' => isset($_SESSION['id']),
            'profilePic' => (!isset($_SESSION['profilePic']) ? "" : $routeParser->urlFor('home') . $_SESSION['profilePic']),
        ]);
    }

    public function handleSendRequest(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $friendId = -1;
        $error = $this->checkSendRequest($data, $friendId);

        if (!$error) {
            $this->friendsRepository->newRequest($_SESSION['id'], $friendId);
            return $response
                ->withHeader('Location', $routeParser->urlFor("friends"))
                ->withStatus(301);
        } else return $this->showRequestCreation($request, $response, $error);
    }

    private function checkSendRequest($data, int &$friendId): array
    {
        $error = [];
        try {
            $friendId = $this->userRepository->getIdByUsername($data['newFriend']);
            $errorId = $this->friendsRepository->friendCheck($_SESSION['id'], $friendId);

            if ($friendId == $_SESSION['id']) {
                $error['requestError'] = "You cannot send a friend request to yourself!";
            } elseif ($errorId == 0) {
                $error['requestError'] = "This request is already made. You will have to wait for " . $data['newFriend'] . " to answer";
            } elseif ($errorId == 1) {
                $error['requestError'] = $data['newFriend'] . " is already your friend!";
            }

        } catch (Exception $e) {
            $error['requestError'] = "Error! There isn't any user with this username";
        }
        return $error;
    }

    public function showRequestCreation(Request $request, Response $response, array $error): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        return $this->twig->render($response, 'sendRequest.twig', [
            'error' => $error,

            // Hrefs de la base
            'is_user_logged' => isset($_SESSION['id']),
            'profilePic' => (!isset($_SESSION['profilePic']) ? "" : $routeParser->urlFor('home') . $_SESSION['profilePic']),
        ]);
    }

    public function acceptRequest(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $this->friendsRepository->updateRequest((int)$request->getAttribute('requestId'), $_SESSION['id'], MySQLFriendsRepository::REQUEST_ACCEPTED);
        return $response
            ->withHeader('Location', $routeParser->urlFor("friendRequests"))
            ->withStatus(301);
    }

    public function declineRequest(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $this->friendsRepository->updateRequest((int)$request->getAttribute('requestId'), $_SESSION['id'], MySQLFriendsRepository::REQUEST_DECLINED);
        return $response
            ->withHeader('Location', $routeParser->urlFor("friendRequests"))
            ->withStatus(301);
    }

    private function print(string $msg)
    {
        error_log(print_r($msg, TRUE));
    }
}
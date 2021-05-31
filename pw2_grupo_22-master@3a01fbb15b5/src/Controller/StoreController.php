<?php


namespace SallePW\SlimApp\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use SallePW\SlimApp\Model\GameRepository;
use SallePW\SlimApp\Model\RetailGamesRepository;
use SallePW\SlimApp\Model\UserRepository;
use Slim\Flash\Messages;
use Slim\Psr7\Request;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;

class StoreController
{
    public function __construct(private Twig $twig,
                                private UserRepository $userRepository,
                                private RetailGamesRepository $cheapSharkRepository,
                                private GameRepository $gameRepository,
                                private Messages $flash)
    {
    }

    public function show(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $messages = $this->flash->getMessages();

        $deals = $this->cheapSharkRepository->getDeals();

        // Si user ha fet login
        if (isset($_SESSION['id'])) {

            $ownedGames = $this->gameRepository->getOwnedGames($_SESSION['id']);
            $wishedGame_ids = $this->gameRepository->getWishedGamesIds($_SESSION['id']);

            foreach ($deals as $deal) {
                foreach ($wishedGame_ids as $game_dealID) {
                    if (strcmp($deal->getGameId(), $game_dealID) == 0) {
                        $deal->setWished(true);
                        $deal->setOwned(false);
                    }
                }

                foreach ($ownedGames as $game) {
                    if (strcmp($deal->getGameId(), $game->getGameId()) == 0) {
                        $deal->setOwned(true);
                        $deal->setWished(false);
                    }
                }
            }
        }

        return $this->twig->render(
            $response,
            'generic_game_display.twig',
            [
                'formTitle' => "LSteam Store",
                'formSubtitle' => "Wellcome to store! These are the 60 best deals:",

                'flash_messages' => $messages['buy-error'] ?? [],

                'game_deals' => $deals,
                'is_user_logged' => isset($_SESSION['id']),

                // Nota: El game id s'ignora aqui. Twig fa repace per al valor correcte.
                'buyAction' => $routeParser->urlFor('handle-store-buy', ['gameId' => 1]),

                // Hrefs de la base
                'profilePic' => (!isset($_SESSION['profilePic']) ? "" : $routeParser->urlFor('home') . $_SESSION['profilePic']),
            ]
        );
    }

    public function buy(Request $request, Response $response): Response
    {
        if (isset($_SESSION['id'])) {

            $gameId = basename($request->getUri());

            $game = $this->cheapSharkRepository->getGame($gameId);
            $resulting_money = $this->userRepository->getMoney($_SESSION['id']) - $game->getPrice();

            if ($resulting_money >= 0) {
                $this->userRepository->setMoney($_SESSION['id'], $resulting_money);

                // Eliminem el joc de wishlist si estava
                $wishedGame_ids = $this->gameRepository->getWishedGamesIds($_SESSION['id']);

                foreach ($wishedGame_ids as $game_id) {
                    if (strcmp($game->getGameId(), $game_id) == 0) {
                        $this->gameRepository->removeWishedGame($gameId, $_SESSION['id']);
                        break;
                    }
                }

                // Comprem el joc.
                $this->gameRepository->addBoughtGame($game, (int)$_SESSION['id']);
                return $response->withStatus(200);
            } else {
                $this->flash->addMessage('buy-error', "Error: There is not enough money in your wallet to buy that item. You need " . $resulting_money * -1 . " coins");

                return $response
                    ->withStatus(403);
//                    ->withHeader('Location', $routeParser->urlFor("store"));

            }
        } else {
            $this->flash->addMessage('buy-error', "Error: You are not logged in. Please login!");
            return $response
//                ->withHeader('Location', $routeParser->urlFor("store"))
                ->withStatus(403);
        }
    }

    public function myGames(Request $request, Response $response): Response
    {
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $games = $this->gameRepository->getOwnedGames($_SESSION['id']);

        return $this->twig->render(
            $response,
            'generic_game_display.twig',
            [

                'formTitle' => "All my games",
                'formSubtitle' => "There are all the games you own. Great choice!",

                'game_deals' => $games,
                'isMyGames' => true,
                'is_user_logged' => isset($_SESSION['id']),

                // Nota: El game id s'ignora aqui. Twig fa repace per al valor correcte.
                'buyAction' => $routeParser->urlFor('handle-store-buy', ['gameId' => 1]),

                'profilePic' => (!isset($_SESSION['profilePic']) ? "" : $routeParser->urlFor('home') . $_SESSION['profilePic']),
            ]
        );
    }
}
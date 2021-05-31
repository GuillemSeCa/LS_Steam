<?php


namespace SallePW\SlimApp\Repository;


use DateTime;
use GuzzleHttp\Client;
use SallePW\SlimApp\Model\Game;
use SallePW\SlimApp\Model\RetailGamesRepository;

class HTTPCheapSharkRepository implements RetailGamesRepository
{
    private static ?HTTPCheapSharkRepository $instance = null;
    private Client $client;

    private function __construct()
    {
        $this->client = new Client();
    }

    public static function getInstance(): HTTPCheapSharkRepository
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getGame(int $gameId): Game
    {
        $res = $this->client->request('GET', 'https://www.cheapshark.com/api/1.0/games',
            [
                'query' => [
                    'id' => $gameId,
                ]
            ]);

        # Decodifiquem el body
        $game = json_decode($res->getBody()->getContents(), true);

        //Processem el thumbnail per aconseguir la versio augmentada.
        $bigger_thumbnail = $this->tryGetBiggerThumbnail($game['info']['thumb']);

        $deal_id = $game['deals'][0]['dealID'];

        //No sabem perquè, pero no funciona si es passen parametres amb aquest endpoint...
        $res = $this->client->request('GET', 'https://www.cheapshark.com/api/1.0/deals' . '?id=' . $deal_id,
            [
//                'query' => [
//                    'id' => $deal_id,
//                ]
            ]);

        # Decodifiquem el body
        $deal = json_decode($res->getBody()->getContents(), true);

        $release_date = $deal['gameInfo']['releaseDate'];

        return new Game($deal['gameInfo']['name'],
            $gameId,
            $deal['gameInfo']['retailPrice'],
            $bigger_thumbnail,
            $deal['gameInfo']['metacriticScore'],
            new DateTime('@' . $release_date),
            $deal['cheapestPrice']['price'] ?? -1.0,
            False,
            False,
        );
    }


    // Donat un game id. Genera un Game.

    private function tryGetBiggerThumbnail(string $thumb): string
    {
        $parsed_thumb = parse_url($thumb);

        if ($parsed_thumb['host'] == 'cdn.cloudflare.steamstatic.com') {

            // Sabem que es pot millorar la calitat de l'imatge modificant la url!
            $newPath = implode('/', explode('/', $parsed_thumb['path'], -1));
            $parsed_thumb['path'] = $newPath . "/header.jpg";
            return $this->unparse_url($parsed_thumb);

        } elseif ($parsed_thumb['host'] == 'images-na.ssl-images-amazon.com') {
            // Sabem que es pot millorar la calitat de l'imatge modificant la url!
            $parsed_thumb['path'] = substr($parsed_thumb['path'], 0, -12) . ".jpg";
            return $this->unparse_url($parsed_thumb);
        } else {
            // No hi ha forma de aconseguir una foto millor (sense agafar dades d'unaltre api)
            return $thumb;
        }
    }


    // Empra el endopoint getMultiplegames

    private function unparse_url($parsed_url): string
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = $parsed_url['host'] ?? '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = $parsed_url['user'] ?? '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = $parsed_url['path'] ?? '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }


//    public function getGamesFromIds(array $wishedGame_ids): array
//    {
//
//        // Enlloc de fer una query per a cada joc, buscant informacio sobre el deal individual,
//        // Es busca amb el endpoint deals i la seva paginacio.
//        // No es possible nomes fer servir el endpoint de Multiple Game Lookup perque ens falta igualment info.
//
//        // NOTA: Com nomes es mostra 1 pagina a la store, no s'ha de avançar de pagina!
//        // NOTA 2: Potser convindira cachejar aquesta funcio. MInim revisar que ho estigui.
//
//        $deals = $this->getDeals();
//
//            // Es busca el deal perque ens falta $metacriticScore, $releaseDate
//            foreach ($deals as $deal_game){
//                if(strcmp($deal_game->getDealID(),$deal['dealID']) == 0){
//
//                    $metacritic = $deal_game->getMetacriticScore();
//                    $release_date = $deal_game->ger();
//                    break;
//                }
//            }
//
//            array_push($games, new Game($game_result['info']['title'],
//                $game_id,
//                $deal['retailPrice'],
//                $possible_bigger_tumbnail,
//                $metacritic,
//                new DateTime("@$release_date"),
//                $game_result['cheapestPriceEver']['price'],
//            true,
//                false
//            ));
//
//        }
//
//        return $games;
//    }

    public function getGamesFromIds(array $game_ids): array
    {

        $res = $this->client->request('GET', 'https://www.cheapshark.com/api/1.0/games',
            [
                'verify' => false,
                'query' => [
                    // La magia del implode :)
                    'ids' => implode(',', $game_ids),
                ]
            ]);

        # Decodifiquem el body
        $jsonResponse = json_decode($res->getBody()->getContents(), true);

        $games = [];

        // Enlloc de fer una query per a cada joc, buscant informacio sobre el deal individual,
        // Es busca amb el endpoint deals i la seva paginacio.
        // NOTA: Com nomes es mostra 1 pagina a la store, no s'ha de avançar de pagina!
        // NOTA 2: Potser convindira cachejar aquesta funcio. MInim revisar que ho estigui.
        $deals = $this->getDeals();

        # Guardem els resultats que ens interesen.
        foreach ($jsonResponse as $game_id => $game_result) {


//             * Aixo seria util en un futur si la API implementes multiple cerca de deals.
//             *
//            // No podem crear el game perque ens falta $metacriticScore, $releaseDate
//            $game_unfinished = [
//                'title' => $game_result['info']['title'],
//                'gameId' => $game_id,
//                'price' => $deal['retailPrice'],
//                'thumb' => $possible_bigger_tumbnail,
//                'dealId' => $deal['dealID'],
//                'cheapestPriceEver' => $game_result['cheapestPriceEver']['price'],
//            ];


            // Es busca el deal perque ens falta $metacriticScore, $releaseDate
            foreach ($deals as $deal_game) {
                foreach ($game_result['deals'] as $game_result_deal) {
                    if (strcmp($deal_game->getDealID(), $game_result_deal['dealID']) == 0) {
                        $deal_game->setWished(true);
                        array_push($games, $deal_game);
                        break;
                    }
                }
            }
        }

        return $games;
    }

    public function getDeals(): array
    {

        $res = $this->client->request('GET', 'https://www.cheapshark.com/api/1.0/deals',
            [
                'query' => [
                    'storeID' => '1',
                ]
            ]);

        # Decodifiquem el body
        $jsonResponse = json_decode($res->getBody()->getContents(), true);

        $games = [];

        # Guardem els resultats que ens interesen.
        foreach ($jsonResponse as $game) {

            //Processem el thumbnail per aconseguir la versio augmentada.
            $bigger_thumbnail = $this->tryGetBiggerThumbnail($game['thumb']);
            $release_date = $game['releaseDate'];
            array_push($games, new Game($game['title'],
                $game['gameID'],
                $game['normalPrice'],
                $bigger_thumbnail,
                $game['metacriticScore'],
                new DateTime("@$release_date"),
                0.0,
                false,
                false,
                $game['dealID']
            ));
        }

        return $games;
    }

}
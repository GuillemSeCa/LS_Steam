<?php


namespace SallePW\SlimApp\CacheManipulation;

use DateInterval;
use DateTime;
use Exception;
use SallePW\SlimApp\Model\Game;

class Cache
{

    // Timeout es en segons
    // Callback sempre ha de retornar un array!
    public function remember(string $data_id, int $timeout, $callback): array
    {

        $file_name = "./cachedData/" . $data_id;
        $file_exists = file_exists($file_name);

        try {
            $now = new DateTime('NOW');

            if ($file_exists) {
                $cached_data = json_decode(file_get_contents($file_name), true);

                $lastTime = new DateTime('@' . $cached_data['lastTime']);
                $lastTimeout = $cached_data['lastTimeout'];

                $lastTime->add(new DateInterval('PT' . $lastTimeout . 'S'));

                if ($lastTime >= $now) {
                    // Dades correctes. Retornem les daddes de la cache
                    error_log("Cache hit of " . $file_name);

                    $results = [];
                    foreach ($cached_data['data'] as $serialized_game) {
                        array_push($results, Game::fromJSON($serialized_game));
                    }
                    return $results;
                }
            }

            // Ha caducat la cache o no l'hem trobat!
            error_log("Renovant la cache del fitxer " . $file_name);

            //Renovem dades.
            $new_data = $callback();

            $new_cache = [
                'lastTime' => $now->getTimestamp(),
                'lastTimeout' => $timeout,
                'data' => $new_data,
            ];


            file_put_contents($file_name, json_encode($new_cache, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT));

            return $new_data;

        } catch (Exception $e) {
            error_log(print_r($e->getMessage(), true));
            return [];
        }
    }
}
<?php


namespace SallePW\SlimApp\Repository;

use GuzzleHttp\Client;
use SallePW\SlimApp\Model\GifRepository;

class GiphyRepository implements GifRepository
{
    private static ?GiphyRepository $instance = null;
    private Client $client;

    private function __construct(
        private string $api_key
    )
    {
        $this->client = new Client();
    }

    public static function getInstance(string $api_key): GiphyRepository
    {
        if (self::$instance === null) {
            self::$instance = new self($api_key);
        }
        return self::$instance;
    }

    // Retorna un gif aleatori dels top 10 resultats trobats segons query.
    public function getRandomGif(string $query): string
    {
        $offset = rand(0, 10);

        $res = $this->client->request('GET', 'https://api.giphy.com/v1/gifs/search',
            [
                'query' => [
                    'q' => $query,
                    'limit' => '1',
                    'offset' => $offset,
                    'rating' => 'pg',
                    'api_key' => $this->api_key]
            ]);

        # Decodifiquem el body
        $jsonResponse = json_decode($res->getBody()->getContents(), true);
        return $jsonResponse['data'][0]['images']['original']['url'];
    }

    // Retorna la url del gif obtingut amb la query. Sempre es el top 1. Rating per a tots els publics.
    public function getBestGif(string $query): string
    {
        $res = $this->client->request('GET', 'https://api.giphy.com/v1/gifs/search',
            [
                'query' => [
                    'q' => $query,
                    'limit' => '1',
                    'offset' => '0',
                    'rating' => 'pg',
                    'api_key' => $this->api_key]
            ]);

        # Decodifiquem el body
        $jsonResponse = json_decode($res->getBody()->getContents(), true);
        return $jsonResponse['data'][0]['images']['original']['url'];
    }
}
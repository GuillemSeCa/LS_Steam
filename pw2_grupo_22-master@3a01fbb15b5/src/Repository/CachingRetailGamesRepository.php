<?php


namespace SallePW\SlimApp\Repository;


use SallePW\SlimApp\CacheManipulation\Cache;
use SallePW\SlimApp\Model\Game;
use SallePW\SlimApp\Model\RetailGamesRepository;

class CachingRetailGamesRepository implements RetailGamesRepository
{

    // Actualitzem la cache cada hora aprox.
    const CACHE_TIMEOUT_SECONDS = 60 * 60;

    public function __construct(private RetailGamesRepository $repository, private Cache $cache)
    {
    }

    public function getDeals(): array
    {
        // Pull the games out of cache, if it exists...
        return $this->cache->remember('deals.all', $this::CACHE_TIMEOUT_SECONDS, function () {
            // If cache has expired, grab the games out of the API
            return $this->repository->getDeals();
        });
    }

    public function getGame(int $gameId): Game
    {
        // Pull the game out of cache, if it exists...
        return $this->cache->remember('game.' . $gameId, $this::CACHE_TIMEOUT_SECONDS, function () use ($gameId) {
            // If cache has expired, grab the game out of the API
            return [$this->repository->getGame($gameId)];
        })[0];
    }

    public function getGamesFromIds(array $game_ids): array
    {
        // Pull the games out of cache, if it exists...
        return $this->cache->remember('wished_games.' . implode(',', $game_ids), $this::CACHE_TIMEOUT_SECONDS, function () use ($game_ids) {
            // If cache has expired, grab the games out of the API
            return $this->repository->getGamesFromIds($game_ids);
        });
    }
}
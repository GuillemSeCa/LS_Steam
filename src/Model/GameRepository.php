<?php


namespace SallePW\SlimApp\Model;

// Guardat a la DB propia
interface GameRepository
{


    public function addBoughtGame(Game $game, int $userId): bool;

    public function getOwnedGames(int $userId): array;

    public function getBoughtGamesIds(int $userId): array;

    public function addWishedGame(int $gameId, int $userId): bool;

    public function removeWishedGame(int $gameId, int $userId): bool;

    public function getWishedGamesIds(int $userId): array;
}
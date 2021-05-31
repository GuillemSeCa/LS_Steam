<?php


namespace SallePW\SlimApp\Model;


// API
interface RetailGamesRepository
{

    public function getDeals(): array;

    public function getGame(int $gameId): Game;

    public function getGamesFromIds(array $game_ids): array;
}
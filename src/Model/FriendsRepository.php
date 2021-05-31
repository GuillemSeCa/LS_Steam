<?php


namespace SallePW\SlimApp\Model;


interface FriendsRepository
{
    public function getFriends(int $user, int $state): array;

    public function newRequest(int $orig, int $dest);

    public function updateRequest(int $orig, int $dest, int $state);

    public function friendCheck(int $orig, int $dest): int;
}
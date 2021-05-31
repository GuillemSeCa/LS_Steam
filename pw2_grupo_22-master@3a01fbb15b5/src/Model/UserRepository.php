<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Model;

interface UserRepository
{
    public function saveUser(User $user): void;

    public function verifyUser(string $token): bool;

    public function getUserToken(User $user): ?string;

    public function getUser(int $id): ?User;

    public function updateUser(User $user): void;

    public function getId(string $emailOrUsername, string $password): int;

    public function setMoney(int $id, int $money): void;

    public function getMoney(int $id): int;

    public function getIdByGivenEmail(string $email): int;

    public function getIdByUsername(string $username): int;
}
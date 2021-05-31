<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Repository;

use DateTime;
use Exception;
use PDO;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\UserRepository;

final class MySQLUserRepository implements UserRepository
{
    public const DATE_FORMAT = 'Y-m-d H:i:s';

    private PDOSingleton $database;

    public function __construct(PDOSingleton $database)
    {
        $this->database = $database;
    }

    public function getId(string $emailOrUsername, string $password): int
    {
        try {
            return $this->getIdByEmail($emailOrUsername, $password);
        } catch (Exception $exception) {
            return $this->getIdByUsernameAndPswd($emailOrUsername, $password);
        }
    }

    public function getIdByEmail(string $email, string $password): int
    {
        $query = <<< 'QUERY'
        SELECT * FROM users WHERE email=:email
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('email', $email, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();

        if (!(is_array($res) && password_verify($password, $res['password']))) {
            throw new Exception('Credentials dont match any user');
        }

        return (int)$res['id'];
    }

    private function getIdByUsernameAndPswd(string $username, string $password): int
    {
        $query = <<< 'QUERY'
        SELECT * FROM users WHERE username=:username
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('username', $username, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();

        if (!(is_array($res) && password_verify($password, $res['password']))) {
            throw new Exception('Credentials dont match any user');
        }

        return (int)$res['id'];
    }

    public function getIdByUsername(string $username): int
    {
        $query = <<< 'QUERY'
        SELECT * FROM users WHERE username=:username
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('username', $username, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();

        if (!is_array($res)) throw new Exception('Credentials dont match any user');

        return (int)$res['id'];
    }

    // Mira si un usuari existeix a la taula d'usuraris
    // verificats basant-se en el email.
    public function emailExists(string $email): bool
    {
        $query = <<< 'QUERY'
        SELECT * FROM users WHERE email=:email
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('email', $email, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();

        if ($res != false) return true;

        $query = <<< 'QUERY'
        SELECT * FROM usersPending WHERE email=:email
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('email', $email, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();
        return $res != false;
    }

    // Mira si un usuari existeix a la taula d'usuraris
    // verificats basant-se en el username.
    public function usernameExists(string $username): bool
    {
        $query = <<< 'QUERY'
        SELECT * FROM users WHERE username=:username
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('username', $username, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();
        if ($res != false) return true;

        $query = <<< 'QUERY'
        SELECT * FROM usersPending WHERE username=:username
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('username', $username, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();
        return $res != false;
    }

    // Mira si un token existeix en la taula de pending users

    public function getUserToken(User $user): ?string
    {
        $query = <<< 'QUERY'
        SELECT token FROM usersPending WHERE username=:username
        QUERY;

        $username = $user->getUsername();

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('username', $username, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();

        if (count($res) == 0) return NULL;

        return $res['token'];
    }

    // Mira si un token existeix en la taula de pending users

    public function getUser(int $id): ?User
    {
        $query = <<< 'QUERY'
        SELECT * FROM users WHERE id=:id
        QUERY;
        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();

        if (!is_array($res)) return NULL;

        return new User(
            (int)$res['id'],
            $res['username'],
            $res['email'],
            $res['password'],
            new DateTime($res['birthday']),
            $res['phone'] ?? '',
            $res['profilePic'] ?? 'default.jpg',
        );
    }

    public function verifyUser(string $token): bool
    {

        error_log(print_r($token, TRUE));
        $user = $this->getPendingUser($token);

        error_log(print_r("ABOUT TO VERIFY", TRUE));
        error_log(print_r($user, TRUE));
        if ($user != NULL) {
            $this->deletePendingUser($token);
            $this->saveUser($user);
            return true;
        } else {
            return false;
        }
    }

    // Mira si un token existeix en la taula de pending users

    public function getPendingUser(string $token): ?User
    {
        $query = <<< 'QUERY'
        SELECT * FROM usersPending WHERE token=:token
        QUERY;
        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('token', $token, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();
        if (!is_array($res)) return NULL;

        return new User(
            0,
            $res['username'],
            $res['email'],
            $res['password'],
            new DateTime($res['birthday']),
            $res['phone'] ?? '',
            $res['profilePic'] ?? 'default.jpg'
        );
    }

    public function deletePendingUser(string $token): bool
    {
        $query = <<< 'QUERY'
        DELETE FROM usersPending WHERE token=:token
        QUERY;
        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('token', $token, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();

        return $res != NULL;
    }

    public function saveUser(User $user): void
    {

        $query = <<<'QUERY'
        INSERT INTO users(username, email, password, birthday, phone, money)
        VALUES(:username, :email, :password, :birthday, :phone, 0)
        QUERY;

        $statement = $this->database->connection()->prepare($query);

        $username = $user->getUsername();
        $email = $user->email();
        $password = $user->password();
        $birthday = $user->getBirthday()->format(self::DATE_FORMAT);
        $phone = $user->getPhone();

        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->bindParam('password', $password, PDO::PARAM_STR);
        $statement->bindParam('birthday', $birthday, PDO::PARAM_STR);
        $statement->bindParam('phone', $phone, PDO::PARAM_STR);

        $statement->execute();
    }

    public function updateUser(User $user): void
    {

        $query = <<<'QUERY'
        UPDATE users
        SET username=:username, email=:email, password=:password, phone=:phone, profilePic=:profilePic
        WHERE id=:id
        QUERY;

        $statement = $this->database->connection()->prepare($query);

        $username = $user->getUsername();
        $email = $user->email();
        $password = $user->password();
        $phone = $user->getPhone();
        $profilePic = $user->getProfilePic();
        $id = $_SESSION['id'];

        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->bindParam('password', $password, PDO::PARAM_STR);
        $statement->bindParam('phone', $phone, PDO::PARAM_STR);
        $statement->bindParam('profilePic', $profilePic, PDO::PARAM_STR);
        $statement->bindParam('id', $id, PDO::PARAM_STR);

        $statement->execute();
    }


    public function savePendingUser(User $user): void
    {

        $query = <<<'QUERY'
        INSERT INTO usersPending(username, email, password, birthday, phone)
        VALUES(:username, :email, :password, :birthday, :phone)
        QUERY;

        $statement = $this->database->connection()->prepare($query);

        $username = $user->getUsername();
        $email = $user->email();
        $password = $user->password();
        $birthady = $user->getBirthday()->format(self::DATE_FORMAT);
        $phone = $user->getPhone();

        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->bindParam('password', $password, PDO::PARAM_STR);
        $statement->bindParam('birthday', $birthady, PDO::PARAM_STR);
        $statement->bindParam('phone', $phone, PDO::PARAM_STR);

        $statement->execute();
    }

    public function setMoney(int $id, int $money): void
    {

        $query = <<<'QUERY'
        UPDATE users
        SET money=:money
        WHERE id=:id
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        error_log(print_r("Tot be amic!", true));
        error_log(print_r($id, true));
        error_log(print_r($money, true));
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->bindParam('money', $money, PDO::PARAM_STR);

        $statement->execute();
    }

    public function getMoney(int $id): int
    {
        $query = <<< 'QUERY'
        SELECT users.money FROM users WHERE id=:id
        QUERY;
        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();

        if (!is_array($res)) return -1;
        error_log(print_r($res, true));
        return (int)$res['money'];
    }

    public function getIdByGivenEmail(string $email): int
    {
        $query = <<< 'QUERY'
        SELECT * FROM users WHERE email=:email
        QUERY;
        error_log(print_r("Ara es printa el email", true));
        error_log(print_r($email, true));

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('email', $email, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();
        error_log(print_r($res, true));
        if (!is_array($res)) return -1;

        return (int)$res['id'];
    }
}
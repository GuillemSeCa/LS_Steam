<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Repository;

use DateTime;
use PDO;
use SallePW\SlimApp\Model\FriendsRepository;
use SallePW\SlimApp\Model\User;

final class MySQLFriendsRepository implements FriendsRepository
{

    public const REQUEST_PENDING = 0;
    public const REQUEST_ACCEPTED = 1;
    public const REQUEST_DECLINED = 2;

    private PDOSingleton $database;

    public function __construct(PDOSingleton $database)
    {
        $this->database = $database;
    }

    public function getFriends(int $user, int $state): array
    {
        switch ($state) {
            case self::REQUEST_ACCEPTED:
                $query = <<<'QUERY'
                SELECT u.id, u.username, u.email, u.birthday, u.phone, u.profilePic, fr.accept_time 
                FROM users as u
                INNER JOIN friendRequests fr ON u.id = fr.id_orig
                WHERE u.id != :id and fr.id_dest = :id and fr.state = :state
                UNION
                SELECT u.id, u.username, u.email, u.birthday, u.phone, u.profilePic, fr.accept_time 
                FROM users as u
                INNER JOIN friendRequests fr ON u.id = fr.id_dest
                WHERE u.id != :id and fr.id_orig = :id and fr.state = :state;
                QUERY;
                break;
            case self::REQUEST_PENDING:
                $query = <<<'QUERY'
                SELECT u.id, u.username, u.email, u.birthday, u.phone, u.profilePic, fr.accept_time 
                FROM users as u
                INNER JOIN friendRequests fr ON u.id = fr.id_orig
                WHERE fr.id_dest = :id and fr.state = :state;
                QUERY;
                break;
        }

        $statement = $this->database->connection()->prepare($query);

        $statement->bindParam('id', $user, PDO::PARAM_STR);
        $statement->bindParam('state', $state, PDO::PARAM_STR);

        $statement->execute();

        $friends = [];
        while (true) {
            $res = $statement->fetch();
            if (!$res) break;

            $friend = new User(
                (int)$res['id'],
                $res['username'],
                $res['email'],
                "",
                new DateTime($res['birthday']),
                $res['phone'],
                $res['profilePic'] ?? 'default.jpg'
            );
            if ($state == self::REQUEST_ACCEPTED) $friend->setAcceptDate(new DateTime($res['accept_time']));

            error_log(print_r($friend->getUsername(), true));
            error_log(print_r($state, true));

            array_push($friends, $friend);
        }

        return $friends;
    }

    public function newRequest(int $orig, int $dest)
    {
        $query = <<<'QUERY'
        INSERT INTO friendRequests(id_orig, id_dest)
        VALUES(:id_orig, :id_dest)
        QUERY;

        $statement = $this->database->connection()->prepare($query);

        $statement->bindParam('id_orig', $orig, PDO::PARAM_STR);
        $statement->bindParam('id_dest', $dest, PDO::PARAM_STR);

        $statement->execute();
    }

    public function updateRequest(int $orig, int $dest, int $state)
    {
        $query = <<<'QUERY'
        UPDATE friendRequests
        SET state=:state, accept_time=CURRENT_TIMESTAMP
        WHERE id_orig=:orig and id_dest=:dest and state=0
        QUERY;

        $statement = $this->database->connection()->prepare($query);

        $statement->bindParam('orig', $orig, PDO::PARAM_STR);
        $statement->bindParam('dest', $dest, PDO::PARAM_STR);
        $statement->bindParam('state', $state, PDO::PARAM_STR);

        $statement->execute();
    }

    public function friendCheck(int $orig, int $dest): int
    {
        $query = <<<'QUERY'
        SELECT 1 as aux 
        FROM friendRequests
        WHERE ((id_orig = :id_orig and id_dest = :id_dest) or (id_orig = :id_dest and id_dest = :id_orig)) and state = 1 
        UNION
        SELECT 0 as aux 
        FROM friendRequests
        WHERE (id_orig = :id_orig and id_dest = :id_dest or id_orig = :id_dest and id_dest = :id_orig) and (state = 0 or state = 2) 
        QUERY;

        $statement = $this->database->connection()->prepare($query);

        $statement->bindParam('id_orig', $orig, PDO::PARAM_STR);
        $statement->bindParam('id_dest', $dest, PDO::PARAM_STR);

        $statement->execute();
        $res = $statement->fetch();

        if (!isset($res['aux'])) return -1;

        return (int)$res['aux'];
    }
}
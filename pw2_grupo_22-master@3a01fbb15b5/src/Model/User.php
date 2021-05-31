<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Model;

use DateTime;

final class User
{

    private string $acceptDate;

    public function __construct(
        private int $id,
        private string $username,
        private string $email,
        private string $password,
        private DateTime $birthday,
        private string $phone,
        private string $profilePic
    )
    {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getProfilePic(): string
    {
        return $this->profilePic;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return DateTime
     */
    public function getBirthday(): DateTime
    {
        return $this->birthday;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return DateTime
     */
    public function getAcceptDate(): string
    {
        return $this->acceptDate;
    }

    /**
     * @param DateTime $acceptDate
     */
    public function setAcceptDate(DateTime $acceptDate): void
    {
        $this->acceptDate = $acceptDate->format('D d M Y G:i');
    }

}
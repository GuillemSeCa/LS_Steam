<?php


namespace SallePW\SlimApp\Model;

use DateTime;
use Exception;
use JsonSerializable;

class Game implements JsonSerializable
{

    // Tots els camps son opcionals per a poder crear games buits.
    public function __construct(
        private string $title = "",
        private int $gameid = 0,
        private float $price = 0,
        private string $thumbnail = "",
        private int $metacriticScore = 0,
        private ?DateTime $releaseDate = null,
        private float $cheapestPriceEver = 0.0,
        private bool $wished = false,
        private bool $owned = false,
        private string $dealID = ""
    )
    {
    }

    // sg == Serialized Game
    public static function fromJSON($sg): Game
    {
        $ng = new Game();
        foreach ($sg as $key => $value) {
            if ($key == "releaseDate") {
                try {
                    $ng->{$key} = new DateTime('@' . $value);
                } catch (Exception $e) {
                    $ng->{$key} = null;
                }
            } else {
                $ng->{$key} = $value;
            }
        }
        return $ng;
    }

    public function jsonSerialize()
    {

        //$reflection = new ReflectionClass($this);
        //error_log("Properties are ");
        //error_log(print_r($reflection->getProperties(),true));
        //return $reflection->getProperties();
        //return get_class_vars ( __CLASS__ );

        //Nota: POC ESCALABLE.
        return [
            'title' => $this->title,
            'gameid' => $this->gameid,
            'price' => $this->price,
            'thumbnail' => $this->thumbnail,
            'metacriticScore' => $this->metacriticScore,
            'releaseDate' => $this->releaseDate->getTimestamp(),
            'cheapestPriceEver' => $this->cheapestPriceEver,
            'wished' => $this->wished,
            'owned' => $this->owned,
            'dealID' => $this->dealID,
        ];
    }


    /**
     * @return string
     */
    public function getDealID(): string
    {
        return $this->dealID;
    }

    /**
     * @return String
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getGameId(): int
    {
        return $this->gameid;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return String
     */
    public function getThumbnail(): string
    {
        return $this->thumbnail;
    }

    /**
     * @return int
     */
    public function getMetacriticScore(): int
    {
        return $this->metacriticScore;
    }

    /**
     * @return DateTime
     */
    public function getReleaseDate(): DateTime
    {
        return $this->releaseDate;
    }

    /**
     * @return bool
     */
    public function getOwned(): bool
    {
        return $this->owned;
    }

    /**
     * @return bool
     */
    public function isOwned(): bool
    {
        return $this->owned;
    }

    /**
     * @param bool $owned
     */
    public function setOwned(bool $owned): void
    {
        $this->owned = $owned;
    }

    /**
     * @return bool
     */
    public function isWished(): bool
    {
        return $this->wished;
    }

    /**
     * @param bool $wished
     */
    public function setWished(bool $wished): void
    {
        $this->wished = $wished;
    }

    /**
     * @return float
     */
    public function getCheapestPriceEver(): float
    {
        return $this->cheapestPriceEver;
    }

}
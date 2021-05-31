<?php


namespace SallePW\SlimApp\Model;


interface GifRepository
{
    public function getRandomGif(string $query): string;

    public function getBestGif(string $query): string;
}
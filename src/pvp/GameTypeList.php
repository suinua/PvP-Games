<?php


namespace pvp;


use game_chef\models\GameType;

class GameTypeList
{
    static function TeamDeathMatch(): GameType {
        return new GameType("TeamDeathMatch");
    }
}
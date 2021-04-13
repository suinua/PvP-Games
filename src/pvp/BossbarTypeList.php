<?php


namespace pvp;


use game_chef\models\GameType;
use game_chef\pmmp\bossbar\BossbarType;

class BossbarTypeList
{
    static function TeamDeathMatch(): BossbarType {
        return new BossbarType("TeamDeathMatch");
    }
}
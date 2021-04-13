<?php


namespace pvp\tdm;


use game_chef\models\TeamGame;
use game_chef\pmmp\bossbar\Bossbar;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pvp\BossbarTypeList;

class TeamDeathMatchController
{
    static function go(Player $player, TeamGame $game): void {
        $map = $game->getMap();
        $levelName = $map->getLevelName();
        $level = Server::getInstance()->getLevelByName($levelName);

        //$player->teleport($level->getSpawnLocation());
        $player->teleport(Position::fromObject($player->getSpawn(), $level));

        //ボスバー
        $bossbar = new Bossbar($player, BossbarTypeList::TeamDeathMatch(), "", 1.0);
        $bossbar->send();
        //スコアボード
        TeamDeathMatchScoreboard::send($player, $game);
    }

    static function back(Player $player): void {
        $level = Server::getInstance()->getLevelByName("lobby");
        $player->teleport($level->getSpawnLocation());
        $player->getInventory()->setContents([
            //todo:インベントリセット
        ]);

        //ボスバー削除
        $bossbar = Bossbar::findByType($player, BossbarTypeList::TeamDeathMatch());
        if ($bossbar !== null) $bossbar->remove();
        //スコアボード削除
        TeamDeathMatchScoreboard::delete($player);
    }
}
<?php


namespace tdm;

use Exception;
use game_chef\api\GameChef;
use game_chef\api\TeamGameBuilder;
use game_chef\models\Score;
use game_chef\models\TeamGame;
use game_chef\pmmp\bossbar\Bossbar;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;

class TeamDeathMatchController
{
    /**
     * @throws Exception
     */
    static function buildTeamDeathMatch() {
        $tdmGames = GameChef::getGamesByType(GameTypeList::TeamDeathMatch());
        if (count($tdmGames) !== 0) throw new Exception("TDMはすでに作成されています");

        $builder = new TeamGameBuilder();
        $builder->setNumberOfTeams(2);//チーム数
        $builder->setGameType(GameTypeList::TeamDeathMatch());//試合のタイプ
        $builder->setTimeLimit(400);//時間制限
        $builder->setVictoryScore(new Score(30));//勝利判定スコア
        $builder->setCanJumpIn(true);//途中参加
        $builder->selectMapByName("forTDM");//マップ名


        //$builder->setUpTeam("", 10, 0);//使用するチームをマップから選択。１つも選択しなければすべて選ばれる
        $builder->setFriendlyFire(false);//フレンドリーファイアー
        $builder->setMaxPlayersDifference(2);//チームの最大人数差
        $builder->setCanMoveTeam(true);//チーム移動

        $game = $builder->build();
        GameChef::registerGame($game);

        GameChef::startGame($game->getId());
    }

    static function join(Player $player, TeamGame $game): void {
        if (!$game->getType()->equals(GameTypeList::TeamDeathMatch())) return;

    }

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
<?php


namespace pvp;


use Exception;
use game_chef\api\GameChef;
use game_chef\api\TeamGameBuilder;
use game_chef\models\Score;
use game_chef\models\TeamGame;
use game_chef\pmmp\events\AddedScoreEvent;
use game_chef\pmmp\events\FinishedGameEvent;
use game_chef\pmmp\events\PlayerJoinedGameEvent;
use game_chef\pmmp\events\PlayerKilledPlayerEvent;
use game_chef\pmmp\events\PlayerQuitGameEvent;
use game_chef\pmmp\events\StartedGameEvent;
use game_chef\pmmp\events\UpdatedGameTimerEvent;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use pocketmine\Server;

class TeamDeathMatchListener implements Listener
{

    public function buildTeamDeathMatch() {
        try {
            $builder = new TeamGameBuilder();
            $builder->setNumberOfTeams(2);//チーム数
            $builder->setGameType(GameTypeList::TeamDeathMatch());//試合のタイプ
            $builder->setTimeLimit(400);//時間制限
            $builder->setVictoryScore(new Score(30));//勝利判定スコア
            $builder->setCanJumpIn(true);//途中参加
            $builder->selectMapByName("");//マップ名

            //マップ中から使用するチームだけをsetUpする
            $builder->setUpTeam("", 10, 0);//使用するチームをマップから選択。１つも選択しなければすべて選ばれる
            $builder->setFriendlyFire(false);//フレンドリーファイアー
            $builder->setMaxPlayersDifference(2);//チームの最大人数差
            $builder->setCanMoveTeam(true);//チーム移動

            $game = $builder->build();
            GameChef::registerGame($game);
        } catch (Exception $exception) {
            //todo:log
        }
    }


    public function onStartedGame(StartedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        /** @var TeamGame $game */
        $game = GameChef::findGameById($gameId);
        GameChef::setTeamPlayersSpawnPoint($gameId);

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $map = $game->getMap();
            $levelName = $map->getLevelName();
            $level = Server::getInstance()->getLevelByName($levelName);

            $player = Server::getInstance()->getPlayer($playerData->getName());
            //$player->teleport($level->getSpawnLocation());
            $player->teleport(Position::fromObject($player->getSpawn(), $level));
            $player->getInventory()->setContents([
                //todo:インベントリセット
            ]);
        }
    }

    public function onFinishedGame(FinishedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $level = Server::getInstance()->getLevelByName("lobby");
            $player = Server::getInstance()->getPlayer($playerData->getName());
            $player->teleport($level->getSpawnLocation());
            $player->getInventory()->setContents([
                //todo:インベントリセット
            ]);
        }
    }

    public function onPlayerJoinedGame(PlayerJoinedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            //todo:メッセージ
        }
    }

    public function onPlayerQuitGame(PlayerQuitGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        $player = $event->getPlayer();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        $level = Server::getInstance()->getLevelByName("lobby");
        $player->teleport($level->getSpawnLocation());
        $player->getInventory()->setContents([
            //todo:インベントリセット
        ]);

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $gamePlayer = Server::getInstance()->getPlayer($playerData->getName());
            //todo:メッセージ
        }
    }

    public function onPlayerKilledPlayer(PlayerKilledPlayerEvent $event) {
        //todo:実装
    }

    public function onUpdatedGameTimer(UpdatedGameTimerEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        //todo:bossbar更新
    }

    public function onAddedScore(AddedScoreEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        //todo:scoreboard更新
    }
}
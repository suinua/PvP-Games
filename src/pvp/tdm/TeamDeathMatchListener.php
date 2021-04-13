<?php


namespace pvp\tdm;


use Exception;
use game_chef\api\GameChef;
use game_chef\api\TeamGameBuilder;
use game_chef\models\GameStatus;
use game_chef\models\Score;
use game_chef\models\TeamGame;
use game_chef\pmmp\bossbar\Bossbar;
use game_chef\pmmp\events\AddedScoreEvent;
use game_chef\pmmp\events\FinishedGameEvent;
use game_chef\pmmp\events\PlayerJoinedGameEvent;
use game_chef\pmmp\events\PlayerKilledPlayerEvent;
use game_chef\pmmp\events\PlayerQuitGameEvent;
use game_chef\pmmp\events\StartedGameEvent;
use game_chef\pmmp\events\UpdatedGameTimerEvent;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pvp\BossbarTypeList;
use pvp\GameTypeList;

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
            $player = Server::getInstance()->getPlayer($playerData->getName());
            TeamDeathMatchController::go($player, $game);
        }
    }

    public function onFinishedGame(FinishedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            TeamDeathMatchController::back($player);
        }
        //TODO:演出
    }

    public function onPlayerJoinedGame(PlayerJoinedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        $player = $event->getPlayer();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        $game = GameChef::findTeamGameById($gameId);
        //試合が始まっていたら、ワールドに送る
        if ($game->getStatus()->equals(GameStatus::Started())) {
            TeamDeathMatchController::go($player, $game);
        }

        //todo:参加メッセージを送信
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
        }
    }

    public function onPlayerQuitGame(PlayerQuitGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        $player = $event->getPlayer();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        TeamDeathMatchController::back($player);
        //todo:メッセージを送信
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $gamePlayer = Server::getInstance()->getPlayer($playerData->getName());
        }
    }

    public function onPlayerKilledPlayer(PlayerKilledPlayerEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない
        $game = GameChef::findTeamGameById($gameId);

        $attacker = $event->getAttacker();
        $attackerData = GameChef::getPlayerData($attacker->getName());
        $attackerTeam = $game->findTeamById($attackerData->getBelongTeamId());

        $killedPlayer = $event->getKilledPlayer();
        $killedPlayerData = GameChef::getPlayerData($killedPlayer->getName());
        $killedPlayerTeam = $game->findTeamById($killedPlayerData->getBelongTeamId());

        //メッセージを送信
        $message = $attackerTeam->getTeamColorFormat() . "[{$attacker->getName()}]" . TextFormat::RESET .
            " killed" .
            $killedPlayerTeam->getTeamColorFormat() . " [{$killedPlayer->getName()}]";
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $gamePlayer = Server::getInstance()->getPlayer($playerData->getName());
            $gamePlayer->sendMessage($message);
        }

        //スコアの追加
        GameChef::addTeamGameScore($gameId, $attackerTeam->getId(), new Score(1));
    }

    public function onUpdatedGameTimer(UpdatedGameTimerEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        //ボスバーの更新
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            $bossbar = Bossbar::findByType($player, BossbarTypeList::TeamDeathMatch());
            if ($bossbar === null) continue;
            if ($event->getTimeLimit() === null) {
                $bossbar->updateTitle("経過時間:({$event->getElapsedTime()})");
            } else {
                $bossbar->updateTitle("{$event->getElapsedTime()}/{$event->getTimeLimit()}");
                $bossbar->updatePercentage(1 - ($event->getElapsedTime() / $event->getTimeLimit()));
            }
        }
    }

    public function onAddedScore(AddedScoreEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        $game = GameChef::findTeamGameById($gameId);
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            TeamDeathMatchScoreboard::update($player, $game);
        }
    }
}
<?php


namespace tdm;


use game_chef\api\GameChef;
use game_chef\models\GameStatus;
use game_chef\models\Score;
use game_chef\pmmp\bossbar\Bossbar;
use game_chef\pmmp\events\AddScoreEvent;
use game_chef\pmmp\events\FinishedGameEvent;
use game_chef\pmmp\events\PlayerJoinGameEvent;
use game_chef\pmmp\events\PlayerKilledPlayerEvent;
use game_chef\pmmp\events\PlayerQuitGameEvent;
use game_chef\pmmp\events\StartedGameEvent;
use game_chef\pmmp\events\UpdatedGameTimerEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class TeamDeathMatch extends PluginBase implements Listener
{
    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($label === "tdm") {
            if (count($args) === 0) return false;
            $method = $args[0];
            if ($method === "build") {
                try {
                    TeamDeathMatchController::buildTeamDeathMatch();
                } catch (\Exception $e) {
                    $this->getLogger()->error($e->getMessage());
                }
                return true;
            }

            if ($method === "join") {
                if ($sender instanceof Player) {
                    $sender->sendForm(new TDMListForm($sender));
                    return true;
                }
            }

            if ($method === "quit") {
                if ($sender instanceof Player) {
                    GameChef::quitGame($sender);
                    $sender->sendMessage("抜けました");
                }
            }

            return false;
        }
        return false;
    }

    public function onStartedGame(StartedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        $game = GameChef::findTeamGameById($gameId);
        GameChef::setTeamGamePlayersSpawnPoint($gameId);

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

    public function onPlayerJoinedGame(PlayerJoinGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        $player = $event->getPlayer();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        $game = GameChef::findTeamGameById($gameId);
        //試合が始まっていたら、ワールドに送る
        if ($game->getStatus()->equals(GameStatus::Started())) {
            TeamDeathMatchController::go($player, $game);
        }

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $gamePlayer = Server::getInstance()->getPlayer($playerData->getName());
            $gamePlayer->sendMessage($player->getName() . "が試合に参加しました");
        }
    }

    public function onPlayerQuitGame(PlayerQuitGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        $player = $event->getPlayer();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        TeamDeathMatchController::back($player);

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $gamePlayer = Server::getInstance()->getPlayer($playerData->getName());
            $gamePlayer->sendMessage($player->getName() . "が試合から去りました");
        }
    }

    public function onPlayerKilledPlayer(PlayerKilledPlayerEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        $attacker = $event->getAttacker();
        $killedPlayer = $event->getKilledPlayer();

        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない
        if ($event->isFriendlyFire()) return;//同士討ちなら(試合の設定上ありえないが、サンプルなので)

        $game = GameChef::findTeamGameById($gameId);

        $attackerData = GameChef::findPlayerData($attacker->getName());
        $attackerTeam = $game->findTeamById($attackerData->getBelongTeamId());

        $killedPlayerData = GameChef::findPlayerData($killedPlayer->getName());
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

    public function onAddedScore(AddScoreEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        $game = GameChef::findTeamGameById($gameId);
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            TeamDeathMatchScoreboard::update($player, $game);
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        if (!GameChef::isRelatedWith($player, GameTypeList::TeamDeathMatch())) return;//TDMでなければ関与しない

        //スポーン地点を再設定
        GameChef::setTeamGamePlayerSpawnPoint($event->getPlayer());
    }
}
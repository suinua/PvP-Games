<?php


namespace pvp\tdm;


use game_chef\models\TeamGame;
use game_chef\pmmp\scoreboard\Score;
use game_chef\pmmp\scoreboard\Scoreboard;
use game_chef\pmmp\scoreboard\ScoreboardSlot;
use game_chef\pmmp\scoreboard\ScoreSortType;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class TeamDeathMatchScoreboard extends Scoreboard
{
    private static function create(TeamGame $game): Scoreboard {
        $slot = ScoreboardSlot::sideBar();
        $scores = [
            new Score($slot, "Map:" . $game->getMap()->getName(), 1),
        ];

        foreach ($game->getTeams() as $team) {
            $scores[] = new Score(
                $slot,
                $team->getTeamColorFormat() . $team->getName()
                . TextFormat::RESET . ":" .
                $team->getScore()->getValue()
            );
        }
        return parent::__create($slot, "====TeamDeathMatch====", $scores, ScoreSortType::smallToLarge());
    }

    static function send(Player $player, TeamGame $game) {
        $scoreboard = self::create($game);
        parent::__send($player, $scoreboard);
    }

    static function update(Player $player, TeamGame $game) {
        $scoreboard = self::create($game);
        parent::__update($player, $scoreboard);
    }
}
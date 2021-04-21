<?php


namespace tdm;


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
        $scores = [
            new Score("Map:" . $game->getMap()->getName()),
        ];

        foreach ($game->getTeams() as $team) {
            $scores[] = new Score(
                $team->getTeamColorFormat() . $team->getName()
                . TextFormat::RESET . ":" .$team->getScore()->getValue()
            );
        }

        $slot = ScoreboardSlot::sideBar();
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
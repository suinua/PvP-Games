<?php


namespace tdm;


use form_builder\models\simple_form_elements\SimpleFormButton;
use form_builder\models\SimpleForm;
use game_chef\api\GameChef;
use pocketmine\Player;

class TDMListForm extends SimpleForm
{
    public function __construct(Player $player) {
        $buttons = [];
        foreach (GameChef::getGamesByType(GameTypeList::TeamDeathMatch()) as $game) {
            $canJoin = $game->canJoin($player->getName());
            if ($canJoin) {
                $buttons[] = new SimpleFormButton(
                    "tap to join",//todo
                    null,
                    function (Player $player) use ($game) {
                        GameChef::joinTeamGame($player, $game->getId());
                    }
                );
            }
        }
        parent::__construct("TDM一覧", "", $buttons);
    }

    function onClickCloseButton(Player $player): void {
    }
}
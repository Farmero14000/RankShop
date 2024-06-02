<?php

declare(strict_types=1);

namespace Farmero\rankshop\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

use Farmero\rankshop\RankShop;

class RankShopCommand extends Command {

    private $plugin;

    public function __construct(RankShop $plugin) {
        parent::__construct("rankshop", "Open the rank shop UI", "/rankshop", ["rs", "rshop"]);
        $this->setPermission("rankshop.cmd");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }

        if ($sender instanceof Player) {
            $this->plugin->openRankShopUI($sender);
        } else {
            $sender->sendMessage("This command can only be used in-game.");
        }

        return true;
    }
}
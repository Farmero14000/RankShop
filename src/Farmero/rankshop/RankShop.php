<?php

declare(strict_types=1);

namespace Farmero\rankshop;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\utils\Config;

use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\ModalForm;

use Farmero\ranks\Ranks;

use Farmero\rankshop\command\RankShopCommand;

use Farmero\moneysystem\MoneySystem;

class RankShop extends PluginBase {

    private $rankShopConfig;

    public function onEnable(): void {
        $this->saveResource("rank_shop.yml");
        $this->rankShopConfig = new Config($this->getDataFolder() . "rank_shop.yml", Config::YAML);
        $this->validateRankShopConfig();
        $this->getServer()->getCommandMap()->register("rankshop", new RankShopCommand($this));
    }

    private function validateRankShopConfig(): void {
        $ranksManager = Ranks::getInstance()->getRanksManager();
        $availableRanks = $ranksManager->getAllRanks();
        $rankShopConfig = $this->rankShopConfig->getAll();

        foreach ($rankShopConfig as $rankName => $rankData) {
            if (!isset($availableRanks[$rankName])) {
                $this->getLogger()->warning("The rank '$rankName' defined in rank_shop.yml does not exist in ranks.yml and will be ignored...");
                unset($rankShopConfig[$rankName]);
            }
        }
        $this->rankShopConfig->setAll($rankShopConfig);
        $this->rankShopConfig->save();
    }

    public function openRankShopUI(Player $player): void {
        $ranksManager = Ranks::getInstance()->getRanksManager();
        $availableRanks = $ranksManager->getAllRanks();
        $rankShopConfig = $this->rankShopConfig->getAll();

        $form = new SimpleForm(function (Player $player, ?int $data) use ($availableRanks, $rankShopConfig) {
            if ($data === null) {
                return;
            }

            $rankNames = array_keys($rankShopConfig);
            if (isset($rankNames[$data])) {
                $selectedRank = $rankNames[$data];
                $price = $rankShopConfig[$selectedRank]['price'] ?? 0;
                $description = $rankShopConfig[$selectedRank]['description'] ?? '';
                $this->openConfirmationUI($player, $selectedRank, $price, $description);
            }
        });

        $form->setTitle("Rank Shop");
        $form->setContent("Select a rank to purchase:");

        foreach ($rankShopConfig as $rankName => $rankData) {
            $form->addButton($availableRanks[$rankName]);
        }
        $player->sendForm($form);
    }

    public function openConfirmationUI(Player $player, string $rank, int $price, string $description): void {
        $form = new ModalForm(function (Player $player, ?bool $data) use ($rank, $price) {
            if ($data === null) {
                return;
            }

            if ($data === true) {
                $ranksManager = Ranks::getInstance()->getRanksManager();
                $moneyManager = MoneySystem::getInstance()->getMoneyManager();
                $playerMoney = $moneyManager->getMoney($player);

                if ($playerMoney >= $price) {
                    $moneyManager->removeMoney($player, $price);
                    $ranksManager->setRank($player, $rank);
                    $player->sendMessage("You have been assigned the rank: " . $rank);
                } else {
                    $player->sendMessage("You do not have enough money to purchase this rank.");
                }
            } else {
                $player->sendMessage("Purchase cancelled.");
            }
        });

        $form->setTitle("Confirm Purchase");
        $form->setContent("Are you sure you want to buy the rank: $rank?\n\nDescription: $description\n\nPrice: $price");
        $form->setButton1("Yes");
        $form->setButton2("No");
        $player->sendForm($form);
    }
}
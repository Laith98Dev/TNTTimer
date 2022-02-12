<?php

namespace Laith98Dev\TNTTimer;

/*  
 *  A plugin for PocketMine-MP.
 *  
 *	 _           _ _   _    ___   ___  _____             
 *	| |         (_) | | |  / _ \ / _ \|  __ \            
 *	| |     __ _ _| |_| |_| (_) | (_) | |  | | _____   __
 *	| |    / _` | | __| '_ \__, |> _ <| |  | |/ _ \ \ / /
 *	| |___| (_| | | |_| | | |/ /| (_) | |__| |  __/\ V / 
 *	|______\__,_|_|\__|_| |_/_/  \___/|_____/ \___| \_/  
 *	
 *	Copyright (C) 2021 Laith98Dev
 *  
 *	Youtube: Laith Youtuber
 *	Discord: Laith98Dev#0695
 *	Gihhub: Laith98Dev
 *	Email: help@laithdev.tk
 *	Donate: https://paypal.me/Laith113
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 	
 */

use pocketmine\event\Listener;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\block\TNT;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use pocketmine\utils\Random;

use Laith98Dev\TNTTimer\entity\TimeTNT;

class Main extends PluginBase implements Listener {
	
    public function onEnable(): void{
		
		EntityFactory::getInstance()->register(TimeTNT::class, function(World $world, CompoundTag $nbt) : TimeTNT{
			return new TimeTNT(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["TNTTimer"], null);
		
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function onPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if ($block instanceof TNT) {
            $event->cancel();
			$tnt = new TimeTNT(Location::fromObject($block->getPosition()->add(0.5, 0, 0.5), $block->getPosition()->getWorld()));
			$tnt->setFuse(80);
			$tnt->setWorksUnderwater(false);
			$mot = (new Random())->nextSignedFloat() * M_PI * 2;
			$tnt->setMotion(new Vector3(-sin($mot) * 0.02, 0.2, -cos($mot) * 0.02));
			// $tnt->setMotion(new Vector3(-sin(2.2431915802349) * 0.02, 0.2, -cos(2.2431915802349) * 0.02));
			$tnt->setOwningEntity($player);
			$tnt->mircotime = microtime(true) + 4.1;
            $tnt->setNameTagAlwaysVisible();
            $tnt->setNameTagVisible();
			$tnt->spawnToAll();
        }
    }
}

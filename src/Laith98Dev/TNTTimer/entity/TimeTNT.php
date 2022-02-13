<?php

namespace Laith98Dev\TNTTimer\entity;

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

use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Explosive;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\world\Explosion;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat as TF;

class TimeTNT extends Entity implements Explosive {
    
    public static function getNetworkTypeId() : string{ return EntityIds::TNT; }

    public $width = 0.98;
    public $height = 0.98;

    protected $baseOffset = 0.49;

    protected $gravity = 0.04;
    protected $drag = 0.02;

    public $mircotime;
	
    /** @var int */
	protected $fuse;

	protected bool $worksUnderwater = false;

	public $canCollide = false;

	protected function getInitialSizeInfo() : EntitySizeInfo{ return new EntitySizeInfo(0.98, 0.98); }

	public function getFuse() : int{
		return $this->fuse;
	}

    public function setFuse(int $fuse) : void{
		if($fuse < 0 || $fuse > 32767){
			throw new \InvalidArgumentException("Fuse must be in the range 0-32767");
		}
		$this->fuse = $fuse;
		$this->networkPropertiesDirty = true;
	}

	public function worksUnderwater() : bool{ return $this->worksUnderwater; }

	public function setWorksUnderwater(bool $worksUnderwater) : void{
		$this->worksUnderwater = $worksUnderwater;
		$this->networkPropertiesDirty = true;
	}

	public function attack(EntityDamageEvent $source) : void{
		if($source->getCause() === EntityDamageEvent::CAUSE_VOID){
			parent::attack($source);
		}
	}
	
	protected function initEntity(CompoundTag $nbt) : void{
		parent::initEntity($nbt);

		$this->fuse = $nbt->getShort("Fuse", 80);
	}

    public function canCollideWith(Entity $entity) : bool{
		return false;
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();
		$nbt->setShort("Fuse", $this->fuse);

		return $nbt;
	}
	
	protected function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed){
			return false;
		}
		$colors = [TF::RED, TF::BLUE, TF::GREEN, TF::YELLOW, TF::GRAY, TF::WHITE, TF::AQUA];
		$randomColor = $colors[array_rand($colors)];
		$this->setNameTag($randomColor . round($this->mircotime - microtime(true), 3));
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if(!$this->isFlaggedForDespawn()){
			$this->fuse -= $tickDiff;
			$this->networkPropertiesDirty = true;

			if($this->fuse <= 0){
				$this->flagForDespawn();
				$this->explode();
			}
		}

		return $hasUpdate || $this->fuse >= 0;
	}

    public function explode() : void{
		$ev = new ExplosionPrimeEvent($this, 4);
		$ev->call();
		if(!$ev->isCancelled()){
			//TODO: deal with underwater TNT (underwater TNT treats water as if it has a blast resistance of 0)
			$explosion = new Explosion(Position::fromObject($this->location->add(0, $this->size->getHeight() / 2, 0), $this->getWorld()), $ev->getForce(), $this);
			if($ev->isBlockBreaking()){
				$explosion->explodeA();
			}
			$explosion->explodeB();
		}
	}

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);

		$properties->setGenericFlag(EntityMetadataFlags::IGNITED, true);
		$properties->setInt(EntityMetadataProperties::VARIANT, $this->worksUnderwater ? 1 : 0);
		$properties->setInt(EntityMetadataProperties::FUSE_LENGTH, $this->fuse);
	}

	public function getOffsetPosition(Vector3 $vector3) : Vector3{
		return $vector3->add(0, 0.49, 0);
	}
}

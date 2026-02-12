<?php

declare(strict_types=1);

namespace NhanAZ\BlockData;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\event\world\ChunkUnloadEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldSaveEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\plugin\PluginBase;

/**
 * @internal Handles world/chunk lifecycle and optional auto-cleanup events.
 */
final class BlockDataListener implements Listener{

	public function __construct(
		private BlockData $blockData,
		private PluginBase $plugin,
		private bool $autoCleanup,
	){}

	// ── World & Chunk Lifecycle ──────────────────────────────────────

	/**
	 * @param WorldLoadEvent $event
	 * @priority MONITOR
	 */
	public function onWorldLoad(WorldLoadEvent $event) : void{
		$this->blockData->loadWorld($event->getWorld());
	}

	/**
	 * @param WorldUnloadEvent $event
	 * @priority MONITOR
	 */
	public function onWorldUnload(WorldUnloadEvent $event) : void{
		$this->blockData->unloadWorld($event->getWorld());
	}

	/**
	 * @param WorldSaveEvent $event
	 * @priority MONITOR
	 */
	public function onWorldSave(WorldSaveEvent $event) : void{
		$world = $event->getWorld();
		if($world->getAutoSave()){
			$this->blockData->saveWorld($world);
		}
	}

	/**
	 * @param ChunkLoadEvent $event
	 * @priority MONITOR
	 */
	public function onChunkLoad(ChunkLoadEvent $event) : void{
		$this->blockData->onChunkLoad(
			$event->getWorld(),
			$event->getChunkX(),
			$event->getChunkZ()
		);
	}

	/**
	 * @param ChunkUnloadEvent $event
	 * @priority MONITOR
	 */
	public function onChunkUnload(ChunkUnloadEvent $event) : void{
		$this->blockData->onChunkUnload(
			$event->getWorld(),
			$event->getChunkX(),
			$event->getChunkZ()
		);
	}

	/**
	 * Saves all data and releases resources when the owning plugin is disabled.
	 */
	public function onPluginDisable(PluginDisableEvent $event) : void{
		if($event->getPlugin() === $this->plugin){
			$this->blockData->closeAll();
		}
	}

	// ── Auto Cleanup (only active when $autoCleanup is true) ─────────

	/**
	 * Removes block data when a player breaks the block.
	 *
	 * @param BlockBreakEvent $event
	 * @priority MONITOR
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		if($this->autoCleanup){
			$this->blockData->remove($event->getBlock());
		}
	}

	/**
	 * Removes block data for all blocks destroyed by an explosion.
	 *
	 * @param EntityExplodeEvent $event
	 * @priority MONITOR
	 */
	public function onEntityExplode(EntityExplodeEvent $event) : void{
		if($this->autoCleanup){
			foreach($event->getBlockList() as $block){
				$this->blockData->remove($block);
			}
		}
	}

	/**
	 * Removes block data when a block is burned by fire.
	 *
	 * @param BlockBurnEvent $event
	 * @priority MONITOR
	 */
	public function onBlockBurn(BlockBurnEvent $event) : void{
		if($this->autoCleanup){
			$this->blockData->remove($event->getBlock());
		}
	}

	/**
	 * Removes block data when leaves decay naturally.
	 *
	 * @param LeavesDecayEvent $event
	 * @priority MONITOR
	 */
	public function onLeavesDecay(LeavesDecayEvent $event) : void{
		if($this->autoCleanup){
			$this->blockData->remove($event->getBlock());
		}
	}
}

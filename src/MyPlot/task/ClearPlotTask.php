<?php
declare(strict_types=1);
namespace MyPlot\task;

use MyPlot\MyPlot;
use MyPlot\Plot;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\Task;

class ClearPlotTask extends Task {
	/** @var MyPlot $plugin */
	private $plugin;
	private $plot, $level, $height, $bottomBlock, $plotFillBlock, $plotFloorBlock, $plotBeginPos, $xMax, $zMax, $maxBlocksPerTick, $pos;

	/**
	 * ClearPlotTask constructor.
	 *
	 * @param MyPlot $plugin
	 * @param Plot $plot
	 * @param int $maxBlocksPerTick
	 */
	public function __construct(MyPlot $plugin, Plot $plot, int $maxBlocksPerTick = 256) {
		$this->plugin = $plugin;
		$this->plot = $plot;
		$this->plotBeginPos = $plugin->getPlotPosition($plot);
		$this->level = $this->plotBeginPos->getLevel();
		$plotLevel = $plugin->getLevelSettings($plot->levelName);
		$plotSize = $plotLevel->plotSize;
		$this->xMax = $this->plotBeginPos->x + $plotSize; // TODO: merged plots
		$this->zMax = $this->plotBeginPos->z + $plotSize; // TODO: merged plots
		$this->height = $plotLevel->groundHeight;
		$this->bottomBlock = $plotLevel->bottomBlock;
		$this->plotFillBlock = $plotLevel->plotFillBlock;
		$this->plotFloorBlock = $plotLevel->plotFloorBlock;
		$this->maxBlocksPerTick = $maxBlocksPerTick;
		$this->pos = new Vector3($this->plotBeginPos->x, 0, $this->plotBeginPos->z);
		$this->plugin = $plugin;
		$plugin->getLogger()->debug("Clear Task started at plot {$plot->X};{$plot->Z}");
	}

	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void {
		$blocks = 0;
		while($this->pos->x < $this->xMax) {
			while($this->pos->z < $this->zMax) {
				while($this->pos->y < $this->level->getWorldHeight()) {
					if($this->pos->y === 0) {
						$block = $this->bottomBlock;
					}elseif($this->pos->y < $this->height) {
						$block = $this->plotFillBlock;
					}elseif($this->pos->y === $this->height) {
						$block = $this->plotFloorBlock;
					}else{
						$block = Block::get(Block::AIR);
					}
					$this->level->setBlock($this->pos, $block, false, false);
					$blocks++;
					if($blocks >= $this->maxBlocksPerTick) {
						$this->setHandler();
						$this->plugin->getScheduler()->scheduleDelayedTask($this, 1);
						return;
					}
					$this->pos->y++;
				}
				$this->pos->y = 0;
				$this->pos->z++;
			}
			$this->pos->z = $this->plotBeginPos->z;
			$this->pos->x++;
		}
		foreach($this->level->getTiles() as $tile) {
			if(($plot = $this->plugin->getPlotByPosition($tile)) != null) {
				if($plot->X === $this->plot->X and $plot->Z === $this->plot->Z) {
					$tile->close();
				}
			}
		}
		$this->plugin->getLogger()->debug("Clear task completed at {$this->plotBeginPos->x};{$this->plotBeginPos->z}");
	}
}

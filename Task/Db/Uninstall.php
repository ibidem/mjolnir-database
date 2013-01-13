<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Uninstall extends \app\Instantiatable implements \mjolnir\types\Task
{
	use \app\Trait_Task;

	/**
	 * Execute task.
	 */
	function run()
	{
		$schematics_config = \app\Schematic::config();

		$channel = $this->get('channel', false);

		foreach ($schematics_config['steps'] as $serial => $schematic)
		{
			if ($channel === false || $schematic['channel'] === $channel)
			{
				$worker = \call_user_func(array($schematic['class'], 'instance'));
				$this->writer->write(' Dropping '.$serial)->eol();
				$worker->down($schematic['serial']);
			}
		}
		$this->writer->eol();
		// reset channel
		if ($channel === false)
		{
			// reset all channels
			$channels = \app\Schematic::channels();
			foreach ($channels as $channel)
			{
				$this->writer->write(' Resetting channel ['.$channel.'] to 0:0-default')->eol();
				\app\Schematic::set_channel_serialversion($channel, '0:0-default');
			}
		}
		else # got channel
		{
			$this->writer->write(' Resetting channel ['.$channel.'] to 0:0-default')->eol();
			\app\Schematic::set_channel_serialversion($channel, '0:0-default');
		}

		$this->writer->eol();
	}

} # class

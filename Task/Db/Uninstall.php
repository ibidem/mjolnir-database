<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Uninstall extends \app\Task_Base
{
	use \app\Trait_Task;

	/**
	 * Execute task.
	 */
	function run()
	{
		\app\Task::consolewriter($this->writer);

		$schematics_config = \app\Schematic::config();

		$channel = $this->get('channel', false);

		foreach ($schematics_config['steps'] as $serial => $schematic)
		{
			if ($channel === false || $schematic['channel'] === $channel)
			{
				$worker = \call_user_func(array($schematic['class'], 'instance'));
				$this->writer->printf('reset');
				$this->writer->writef(' Down: '.$serial);
				$worker->down($schematic['serial']);
			}
		}

		$this->writer->printf('reset');
		if ($channel === false)
		{
			$this->writer->writef(' Removed application tables.')->eol();
		}
		else # single channel
		{
			$this->writer->writef(' Removed channel tables.')->eol();
		}

		// reset channel
		if ($channel === false)
		{
			// reset all channels
			$channels = \app\Schematic::channels();
			foreach ($channels as $channel)
			{
				$this->writer->printf('reset');
				$this->writer->writef(' Resetting: '.$channel);
				\app\Schematic::set_channel_serialversion($channel, '0:0-default');
			}
			$this->writer->printf('reset');
			$this->writer->writef(' Reset channels.');
		}
		else # got channel
		{
			\app\Schematic::set_channel_serialversion($channel, '0:0-default');
			$this->writer->writef(' Reset '.$channel);
		}
	}

} # class

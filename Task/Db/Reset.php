<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Reset extends \app\Task_Base
{
	use \app\Trait_Task_Db_Migrations;

	/**
	 * Execute Task.
	 */
	function run()
	{
		\app\Task::consolewriter($this->writer);

		$channel = $this->get('channel', false);
		$serial = $this->get('serial', false);

		\app\Task::invoke('db:uninstall')
			->set('channel', $channel)
			->writer_is($this->writer)
			->run();

		$this->writer->eol();

		if ($channel !== false)
		{
			$this->process_reset($channel, $serial);
		}
		else # process channels
		{
			$channels = \app\Schematic::channels();

			$channelorder = $this->channelorder($channels);

			foreach ($channelorder as $channel)
			{
				$this->process_reset($channel, $serial);
			}
		}

		$this->writer->printf('reset');
		$this->writer->writef(' Binding complete.')->eol();
	}

	/**
	 * Reset task.
	 */
	function process_reset($channel, $serial)
	{
		$this->verify_no_dataloss($channel);

		$trail = \app\Schematic::serial_trail($channel, '0:0-default', $serial);
		\array_unshift($trail, '0:0-default');

//		static::write_trail($this->writer, $channel, $trail);

		$bindings = [];
		$this->process_trail($channel, $trail, $bindings);

		foreach ($bindings as $binding)
		{
			$binding();
		}
	}

} # class

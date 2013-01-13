<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Install extends \app\Instantiatable implements \mjolnir\types\Task
{
	use \app\Trait_Task;

	/**
	 * Execute task.
	 */
	function execute()
	{
		\app\Task::consolewriter($this->writer);

		$channel = $this->get('channel');

		if ($channel === false)
		{
			// uninstall everything
			\app\Task::invoke('db:init')
				->set('uninstall', true)
				->writer_is($this->writer)
				->run();

			// initialize everything back
			\app\Task::invoke('db:init')
				->set('uninstall', false)
				->writer_is($this->writer)
				->execute();
		}

		\app\Task::invoke('db:uninstall')
			->set('channel', $channel)
			->writer_is($this->writer)
			->execute();

		if ($channel !== false)
		{
			$this->process($channel);
		}
		else # process channels
		{
			$channels = \app\Schematic::channels();

			$processing_list = $this->processing_list($channels);

			$bindings = [];
			foreach ($processing_list as $channel)
			{
				$this->process($channel, $bindings);
				$this->writer->eol();
			}

			$this->writer->printf('title', 'Binding');

			foreach ($bindings as $binding)
			{
				$binding();
			}
		}
	}

	/**
	 * Process a channel.
	 */
	function process($channel, array & $bindings)
	{
		$this->verify_no_dataloss($channel);

		$channel_top = \app\Schematic::top_for_channel($channel, 'default');
		$trail = \app\Schematic::serial_trail($channel, '0:0-default', $channel_top);
		\array_unshift($trail, '0:0-default');

		static::write_trail($this->writer, $channel, $trail);
		$writer = $this->writer;

		$bindings[] = function () use ($writer, $channel, $trail)
			{
				$this->writer->eol();
				\app\Task_Db_Reset::write_trail($writer, $channel, $trail);
				$this->writer->eol();
			};

		$this->process_trail($channel, $trail, $bindings);
	}

} # class

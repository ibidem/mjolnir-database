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
	use \app\Trait_Task_Db_Migrations;

	/**
	 * Execute task.
	 */
	function run()
	{
		\app\Task::consolewriter($this->writer);

		$channel = $this->get('channel');
		$show_order = $this->get('show-order', false);

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
				->run();
		}

		\app\Task::invoke('db:uninstall')
			->set('channel', $channel)
			->writer_is($this->writer)
			->run();

		if ($channel !== false)
		{
			$bindings = [];
			$this->process($channel, $bindings);

			foreach ($bindings as $binding)
			{
				$binding();
			}
		}
		else # process channels
		{
			$channels = \app\Schematic::channels();

			$channelorder = $this->channelorder($channels, $show_order);

			$bindings = [];
			foreach ($channelorder as $channel)
			{
				$this->process($channel, $bindings);
			}

			foreach ($bindings as $binding)
			{
				$binding();
			}
		}

		$this->writer->printf('reset');
		$this->writer->writef(' Binding complete.')->eol();
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

		$this->process_trail($channel, $trail, $bindings);
	}

} # class

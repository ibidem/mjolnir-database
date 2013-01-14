<?php namespace ibidem\database;

/**
 * @package    ibidem
 * @category   Task
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Reset extends \app\Task
{
	function execute()
	{
		$channel = $this->config['channel'];
		$serial = $this->config['serial'];
		
		$uninstall = Task_Db_Uninstall::instance()
			->config
			(
				array
				(
					'channel' => $channel,
				)
			);
		
		$uninstall->execute();
		
		if ($channel !== false)
		{
			$this->process_reset($channel, $serial);
		}
		else # process channels 
		{
			$channels = \app\Schematic::channels();
			foreach ($channels as $channel)
			{
				$this->process_reset($channel, $serial);
			}
		}
	}

	function process_reset($channel, $serial)
	{
		$this->verify_no_dataloss($channel);
		
		$trail = \app\Schematic::serial_trail($channel, '0:0-default', $serial);
		\array_unshift($trail, '0:0-default');
		
		$this->writer->write(' trail ('.\implode(' >> ', $trail).')')->eol();
		
		$this->process_trail($channel, $trail);
	}
	
	function verify_no_dataloss($channel)
	{
		// verify system is at 0:0-default
		$serial = \app\Schematic::get_serial_for($channel);
		
		if ($serial !== '0:0-default')
		{
			$this->writer->error('The database is not at [0:0-default]. Potential data-loss, terminating.')->eol();
			die(1);
		}
	}
	
	function process_trail($channel, $trail)
	{
		$this->writer->eol();
		
		// remove 0:0-default since it's merely an abstract serial
		if ($trail[0] === '0:0-default')
		{
			\array_shift($trail);
		}
		
		$largest = 5;
		foreach ($trail as $serial)
		{
			// retrieve all with specified serial
			$migrations = \app\Schematic::migrations_for($serial, $channel);

			// find largest nominator
			foreach ($migrations as $entry)
			{
				if ($largest < \strlen($entry['nominator']))
				{
					$largest = \strlen($entry['nominator']);
				}
			}
		}
		
		$step_format = ' %3s. %-'.($largest+1).'s %15s | ';
		
		$idx = 1;
		foreach ($trail as $serial)
		{
			// retrieve all with specified serial
			$migrations = \app\Schematic::migrations_for($serial, $channel);
			
			// execute migration
			foreach ($migrations as $entry)
			{
				$migration = $entry['object'];
				$this->writer->writef($step_format, $idx++, $entry['nominator'], $serial);
				try
				{
					$this->writer->write('up');
					$migration->up();
					$this->writer->write(' >> bind');
					$migration->bind();
					$this->writer->write(' >> build');
					$migration->build();
					$this->writer->write(' >> move');
					$migration->move();
					$this->writer->eol();
				}
				catch (\Exception $e)
				{
					$this->writer->eol()->eol();
					throw $e;
				}
			}
		}
		
		// update version
		\app\Schematic::update_channel_serial($channel, \array_pop($trail));
	}
	
} # class

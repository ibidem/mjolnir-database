<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Reset extends \app\Instantiatable implements \mjolnir\types\Task
{
	use \app\Trait_Task;

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
			->run();

		if ($channel !== false)
		{
			$this->process_reset($channel, $serial);
		}
		else # process channels
		{
			$channels = \app\Schematic::channels();

			$processing_list = $this->processing_list($channels);

			foreach ($processing_list as $channel)
			{
				$this->process_reset($channel, $serial);
			}
		}
	}


	/**
	 * @return boolean
	 */
	protected static function channel_has_dependencies( & $channel, & $dependencies, & $processing_list)
	{
		$result = isset($dependencies[$channel]) && ! empty($dependencies[$channel]);

		if ($result)
		{
			$list = \array_diff($dependencies[$channel], $processing_list);
			return ! empty($list);
		}
		else # false
		{
			return false;
		}
	}

	/**
	 * @return array
	 */
	function processing_list($channels)
	{
		// resolve interchannel dependecies
		$dependencies = \app\CFS::config('mjolnir/schematics')['dependencies'];
		$processing_list = [];
		$postponed = [];
		foreach ($channels as $channel)
		{
			if ( ! static::channel_has_dependencies($channel, $dependencies, $processing_list))
			{
				$processing_list[] = $channel;
				do
				{
					$changed = false;
					foreach ($postponed as $c)
					{
						$dependencies[$c] = \array_diff($dependencies[$c], $processing_list);
						if (empty($dependencies[$c]))
						{
							$changed = true;
							$processing_list[] = $c;
						}
					}

					$postponed = \array_diff($postponed, $processing_list);
				}
				while ($changed);
			}
			else # has dependencies
			{
				$postponed[] = $channel;
			}
		}

		$this->writer->writef(' Channel Order')->eol();
		$this->writer->writef(' -------------')->eol();
		foreach ($processing_list as $channel)
		{
			$this->writer->write(' '.$channel)->eol();
		}
		$this->writer->eol()->eol();

		if ( ! empty($postponed))
		{
			$this->writer->printf('error', ' Missing depdendencies for: '.\implode(', ', $postponed));
			exit(1);
		}

		return $processing_list;
	}

	/**
	 * Write formatted trail information.
	 */
	static function write_trail($writer, $channel, $trail)
	{
		$trail_string = \app\Arr::implode(' >> ', $trail, function ($k, $value) {
			return \preg_replace('#-default$#', '', $value);
		});

		$writer->writef(' '.$channel.' ('.$trail_string.')')->eol();
	}

	/**
	 * Reset task.
	 */
	function process_reset($channel, $serial)
	{
		$this->verify_no_dataloss($channel);

		$trail = \app\Schematic::serial_trail($channel, '0:0-default', $serial);
		\array_unshift($trail, '0:0-default');

		static::write_trail($this->writer, $channel, $trail);

		$this->process_trail($channel, $trail);
	}

	/**
	 * Verify data loss will not happened.
	 */
	function verify_no_dataloss($channel)
	{
		// verify system is at 0:0-default
		$serial = \app\Schematic::get_serial_for($channel);

		if ($serial !== '0:0-default')
		{
			$this->writer->printf('error', 'The database is not at [0:0-default]. Potential data-loss, terminating.')->eol();
			die(1);
		}
	}

	/**
	 * Process trail.
	 */
	function process_trail($channel, $trail, array & $bindings)
	{
		$this->writer->eol();

		// remove 0:0-default since it's merely an abstract serial
		if ($trail[0] === '0:0-default')
		{
			\array_shift($trail);
		}

		$largest = 35;
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
		$writer = $this->writer;
		foreach ($trail as $serial)
		{
			// retrieve all with specified serial
			$migrations = \app\Schematic::migrations_for($serial, $channel);

			// execute migration
			foreach ($migrations as $entry)
			{
				$migration = $entry['object'];
				$this->writer->writef($step_format, $idx, $entry['nominator'], $serial);

				$bindings[] = function () use ($writer, $step_format, $idx, $entry, $serial)
					{
						$writer->writef($step_format, $idx, $entry['nominator'], $serial);
					};

				$idx++;

				try
				{
					$this->writer->writef('up');
					$migration->up();

					$bindings[] = function () use ($migration, $writer)
						{
							$writer->writef('bind')->eol();
							$migration->bind();
						};

					$this->writer->writef(' >> build');
					$migration->build();
					$this->writer->writef(' >> move');
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

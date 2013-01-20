<?php namespace mjolnir\database;

/**
 * Collection of utilities for database migration (ie. schematic) related tasks.
 *
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012 Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
trait Trait_Task_Db_Migrations
{
	/**
	 * @return array
	 */
	protected function channelorder($channels, $show = false)
	{
		// resolve interchannel dependecies
		$dependencies = \app\CFS::config('mjolnir/schematics')['dependencies'];
		$channelorder = [];
		$postponed = [];
		foreach ($channels as $channel)
		{
			if ( ! static::channel_has_dependencies($channel, $dependencies, $channelorder))
			{
				$channelorder[] = $channel;
				do
				{
					$changed = false;
					foreach ($postponed as $c)
					{
						$dependencies[$c] = \array_diff($dependencies[$c], $channelorder);
						if (empty($dependencies[$c]))
						{
							$changed = true;
							$channelorder[] = $c;
						}
					}

					$postponed = \array_diff($postponed, $channelorder);
				}
				while ($changed);
			}
			else # has dependencies
			{
				$postponed[] = $channel;
			}
		}

		if ($show)
		{
			$this->writer->eol();
			$this->writer->writef(' Channel Order')->eol();
			$this->writer->writef(' -------------')->eol();
			foreach ($channelorder as $channel)
			{
				$this->writer->writef(' '.$channel)->eol();
			}
			$this->writer->writef(' -------------')->eol();
			$this->writer->eol();
		}

		if ( ! empty($postponed))
		{
			$this->writer->printf('error', ' Missing depdendencies for: '.\implode(', ', $postponed));
			exit(1);
		}

		return $channelorder;
	}

	/**
	 * @return boolean
	 */
	protected static function channel_has_dependencies( & $channel, & $dependencies, & $channelorder)
	{
		$result = isset($dependencies[$channel]) && ! empty($dependencies[$channel]);

		if ($result)
		{
			$list = \array_diff($dependencies[$channel], $channelorder);
			return ! empty($list);
		}
		else # false
		{
			return false;
		}
	}

	/**
	 * Verify data loss will not happened.
	 */
	protected function verify_no_dataloss($channel)
	{
		// verify system is at 0:0-default
		$serial = \app\Schematic::get_serial_for($channel);

		if ($serial !== '0:0-default' && $serial !== null)
		{
			$this->writer->printf('error', 'The database is not at [0:0-default]. Potential data-loss, terminating.')->eol();
			die(1);
		}
	}

	/**
	 * Process trail.
	 */
	protected function process_trail($channel, $trail, array & $bindings)
	{
		// remove 0:0-default since it's merely an abstract serial
		if ($trail[0] === '0:0-default')
		{
			\array_shift($trail);
		}

		$step_format = ' %-5s | %10s -- %s';

		$writer = $this->writer;
		foreach ($trail as $serial)
		{
			// retrieve all with specified serial
			$migrations = \app\Schematic::migrations_for($serial, $channel);

			// execute migration
			foreach ($migrations as $entry)
			{
				$migration = $entry['object'];

				try
				{
					$this->writer->printf('reset');
					$this->writer->writef($step_format, 'up', $serial, $entry['nominator']);
					$migration->up();

					$bindings[] = function () use ($migration, $writer, $step_format, $serial, $entry)
						{
							$this->writer->printf('reset');
							$this->writer->writef($step_format, 'bind', $serial, $entry['nominator']);
							$migration->bind();
						};

					$this->writer->printf('reset');
					$this->writer->writef($step_format, 'move', $serial, $entry['nominator']);
					$migration->move();

					$this->writer->printf('reset');
					$this->writer->writef($step_format, 'build', $serial, $entry['nominator']);
					$migration->build();
				}
				catch (\Exception $e)
				{
					$this->writer->eol()->eol();
					throw $e;
				}
			}
		}

		// update version
		$serial = \array_pop($trail);
		\app\Schematic::update_channel_serial($channel, $serial);

		$this->writer->printf('reset');
		$this->writer->writef(" %s now at %s", $channel, $serial)->eol();
	}

} # trait

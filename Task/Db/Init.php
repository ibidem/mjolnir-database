<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Init extends \app\Task_Base
{
	/**
	 * Execute task.
	 */
	function run()
	{
		\app\Task::consolewriter($this->writer);

		$uninstall = $this->get('uninstall', false);
		$forced = $this->get('forced', false);

		if ($uninstall)
		{
			$this->uninstall();
			return;
		}

		$channel_table = \app\Schematic::channel_table();

		// check if table exists
		$current_tables = \app\SQL::prepare
			(
				__METHOD__.':show_tables',
				'SHOW TABLES',
				'mysql'
			)
			->run()
			->fetch_all();

		$table_exists = false;

		foreach ($current_tables as $table)
		{
			\reset($table);
			if (\current($table) == $channel_table)
			{
				if ( ! $forced)
				{
					$this->writer->printf('error', 'Schematics table already exists. Use --forced to use current.')->eol();
					return;
				}
				else # forced
				{
					$this->writer->writef(' Altering existing schematics table.')->eol();
					$table_exists = true;
					break;
				}
			}
		}

		if ( ! $table_exists)
		{
			// create table
			\app\Schematic::table
				(
					$channel_table,
					'
						`channel` :title,
						`serial`  :title DEFAULT \'0:0-default\'
					'
				);

			$this->writer->writef(' Created schematics table.')->eol();
		}

		// retrieve all the channels known by the system
		$schematics_config = \app\CFS::config('mjolnir/schematics');
		$schematic_channels = [];
		foreach ($schematics_config['steps'] as $serial => $schematic)
		{
			if (\preg_match('#^(.*):#', $serial, $matches))
			{
				// get the channel
				$channel = $matches[1];
				if ( ! \in_array($channel, $schematic_channels))
				{
					$schematic_channels[] = $channel;
				}
			}
		}

		// retrieve current channels
		$all_channels = \app\Schematic::channel_list();

		$known_channels = [];
		foreach ($all_channels as $entry)
		{
			$known_channels[] = $entry['channel'];
		}

		// register any channels not currently known in the system
		foreach ($schematic_channels as $channel)
		{
			if ( ! \in_array($channel, $known_channels))
			{
				\app\SQL::prepare
					(
						__METHOD__.':init_channel',
						'
							INSERT INTO `'.\app\Schematic::channel_table().'`
							(channel, serial) VALUES (:channel, \'0:0-default\')
						',
						'mysql'
					)
					->str(':channel', $channel)
					->run();

				$this->writer->printf('reset');
				$this->writer->writef(' Initialized channel: '.$channel);
			}
		}
		$this->writer->printf('reset');
		$this->writer->writef(' Initialized application at 0:0')->eol();
	}

	/**
	 * Uninstall channel table.
	 */
	protected function uninstall()
	{
		\app\Schematic::destroy(\app\Schematic::channel_table());
			$this->writer->writef(' Schematics table removed.')->eol();
	}

} # class

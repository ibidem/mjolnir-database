<?php namespace ibidem\database;

/**
 * @package    ibidem
 * @category   Task
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Init extends \app\Task
{	
	function execute()
	{
		$uninstall = $this->config['uninstall'];
		
		if ($uninstall)
		{
			\app\Schematic::destroy(\app\Schematic::channel_table());
			$this->writer->write('Schematics table removed.')->eol();
			return;
		}
		
		$channel_table = \app\Schematic::channel_table();
		
		// check if table exists
		$existing_tables = \app\SQL::prepare(__METHOD__.':show_tables', 'SHOW TABLES', 'mysql')
			->execute()->fetch_all();
		
		$table_exists = false;
		
		foreach ($existing_tables as $existing_table)
		{
			\reset($existing_table);
			if (\current($existing_table) == $channel_table)
			{
				$this->writer->error('Table ['.$channel_table.'] already exists.')->eol();
				$table_exists = true;
				break;
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
			
			$this->writer->status('Info', 'Created schematics table.')->eol();
		}
		
		// retrieve all the channels known by the system
		$schematics_config = \app\CFS::config('ibidem/schematics');
		$schematic_channels = array('default');
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
		
		$known_channels = array();
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
								(channel, serial)
							VALUES
								(:channel, \'0:0-default\')
						',
						'mysql'
					)
					->set(':channel', $channel)
					->execute();
				
				$this->writer->status('Info', 'Initialized channel ['.$channel.'] with [0:0-default]')->eol();
			}
		}
		
		$this->writer->status('Info', 'Schematics initialized.');
	}

} # class

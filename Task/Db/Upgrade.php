<?php namespace ibidem\database;

/**
 * @package    ibidem
 * @category   Task
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Upgrade extends \app\Task_Db_Reset
{
	function execute()
	{
		$channel = $this->config['channel'];
		
		if ($channel !== false)
		{
			$this->process_upgrade($channel);
		}
		else # process channels 
		{
			$channels = \app\Schematic::channels();
			foreach ($channels as $channel)
			{
				$this->process_upgrade($channel);
			}
		}
	}

	function process_upgrade($channel)
	{		
		$channel_top = \app\Schematic::top_for_channel($channel, 'default');
		$channel_serial = \app\Schematic::get_serial_for($channel);
		$trail = \app\Schematic::serial_trail($channel, $channel_serial, $channel_top);
		
		if (empty($trail))
		{
			$this->writer->write(' System is currently on the latest version.')->eol();
			exit;
		}
		
		$this->writer->write(' trail ('.\implode(' >> ', $trail).')')->eol();
		
		$this->process_trail($channel, $trail);
	}

} # class

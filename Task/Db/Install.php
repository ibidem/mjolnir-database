<?php namespace ibidem\database;

/**
 * @package    ibidem
 * @category   Task
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Install extends \app\Task_Db_Reset
{
	function execute()
	{
		$channel = $this->config['channel'];
		
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
			$this->process($channel);
		}
		else # process channels 
		{
			$channels = \app\Schematic::channels();
			foreach ($channels as $channel)
			{
				$this->process($channel);
			}
		}
	}

	function process($channel)
	{
		$this->verify_no_dataloss($channel);
		
		$channel_top = \app\Schematic::top_for_channel($channel, 'default');
		$trail = \app\Schematic::serial_trail($channel, '0:0-default', $channel_top);
		\array_unshift($trail, '0:0-default');
		
		$this->writer->write(' trail ('.\implode(' >> ', $trail).')')->eol();
		
		$this->process_trail($channel, $trail);
	}
	
} # class

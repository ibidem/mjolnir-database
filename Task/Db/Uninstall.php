<?php namespace ibidem\database;

/**
 * @package    ibidem
 * @category   Task
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Uninstall extends \app\Task
{
	function execute()
	{
		$schematics_config = \app\Schematic::config();
			
		$channel = $this->config['channel'];
		
		foreach ($schematics_config['steps'] as $serial => $schematic)
		{
			if ($channel === false || $schematic['channel'] === $channel)
			{
				$worker = \call_user_func(array($schematic['class'], 'instance'));
				$worker->down($schematic['serial']);
			}
		}
		
		// reset channel
		if ($channel === false)
		{
			// reset all channels
			$channels = \app\Schematic::channels();
			foreach ($channels as $channel)
			{
				\app\Schematic::set_channel_serialversion($channel, '0:0-default');
			}
		}
		else # got channel
		{
			\app\Schematic::set_channel_serialversion($channel, '0:0-default');
		}
	}

} # class

<?php namespace ibidem\database;

/**
 * @package    ibidem
 * @category   Task
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Version extends \app\Task
{
	function execute()
	{
		$force_set = $this->config['force-set'];
		
		if ($force_set !== false)
		{
			$channel = $this->config['channel'];
			\app\Schematic::set_channel_serialversion($channel, $force_set);
		}
		
		$schematics = \app\Schematic::channel_list();

		$versions = array();

		foreach ($schematics as $schematic)
		{
			$versions[] = $schematic['channel'].' @ '.$schematic['serial'];
		}

		$this->writer->write(' '.\implode(', ', $versions));
		$this->writer->eol();
	}

} # class

<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Version extends \app\Instantiatable implements \mjolnir\types\Task
{
	use \app\Trait_Task;

	/**
	 * Execute task.
	 */
	function execute()
	{
		$force_set = $this->get('force-set', false);

		if ($force_set !== false)
		{
			$channel = $this->config['channel'];
			\app\Schematic::set_channel_serialversion($channel, $force_set);
		}

		$schematics = \app\Schematic::channel_list();

		$versions = [];

		foreach ($schematics as $schematic)
		{
			$versions[] = $schematic['channel'].' @ '.$schematic['serial'];
		}

		$this->writer->writef(' '.\implode($this->writer->eol_string().' ', $versions));
		$this->writer->eol();
	}

} # class

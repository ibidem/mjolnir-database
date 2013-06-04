<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Pdx_History extends \app\Task_Base
{
	/**
	 * ...
	 */
	function run()
	{
		$pdx = \app\Pdx::instance($this->writer);
		$history = $pdx->history();

		if (empty($history))
		{
			$this->writer->writef(' No history.')->eol();
		}
		else # display history
		{
			$format = ' %4s  %-20s  %-20s  %-7s  %-10s  %s';
			$this->writer->writef($format, 'id', 'channel', 'hotfix', 'version', 'timestamp', 'check')->eol();
			foreach ($history as $i)
			{
				$this->writer->writef
					(
						$format,
						$i['id'].'.',
						$i['channel'],
						$i['hotfix'] !== null ? $i['hotfix'] : 'no',
						$i['version'],
						$i['timestamp'],
						$i['check']
					)
					->eol();
			}
		}
	}

} # class

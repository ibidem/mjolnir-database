<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Pdx_Status extends \app\Task_Base
{
	/**
	 * ...
	 */
	function run()
	{
		\app\Task::consolewriter($this->writer);

		if (\app\CFS::config('mjolnir/base')['db:migrations'] !== 'paradox')
		{
			$this->writer
				->printf('error', 'System is currently setup to use ['.\app\CFS::config('mjolnir/base')['db:migrations'].'] migrations.')
				->eol()->eol();
			return;
		}

		$pdx = \app\Pdx::instance($this->writer);
		$versions = $pdx->status();

		if ( ! empty($versions))
		{
			foreach ($versions as $channel => $version)
			{
				$this->writer->writef(' %9s %s', $version, $channel)->eol();
			}
		}
		else # no versions
		{
			$this->writer->writef(' History is empty.')->eol();
		}
	}

} # class

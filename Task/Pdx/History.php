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
		\app\Task::consolewriter($this->writer);

		if (\app\CFS::config('mjolnir/base')['db:migrations'] !== 'paradox')
		{
			$this->writer
				->printf('error', 'System is currently setup to use ['.\app\CFS::config('mjolnir/base')['db:migrations'].'] migrations.')
				->eol()->eol();
			return;
		}

		$detailed = $this->get('detailed', false);
		$detailed !== null or $detailed = false;

		$signatures = $this->get('signatures', false);
		$signatures !== null or $signatures = false;

		$pdx = \app\Pdx::instance($this->writer);
		$history = $pdx->history();

		if (empty($history))
		{
			$this->writer->writef(' No history.')->eol();
		}
		else # display history
		{
			$format = ' %4s  %-10s  %-20s  %-7s  %s';

			$this->writer->writef
				(
					$format,
					'step',
					'timestamp',
					'channel',
					'version',
					'hotfix'
				)
				->eol();

			$this->writer->writef
				(
					$format,
					\str_repeat('-', 4),
					\str_repeat('-', 10),
					\str_repeat('-', 20),
					\str_repeat('-', 7),
					\str_repeat('-', 15)
				)
				->eol();

			if ($detailed)
			{
				$this->writer->eol();
			}

			foreach ($history as $i)
			{
				$this->writer->writef
					(
						$format,
						$i['id'].'.',
						\date('Y-m-d', \strtotime($i['timestamp'])),
						$i['channel'],
						$i['version'],
						$i['hotfix'] !== null ? $i['hotfix'] : 'no'
					)
					->eol();

				if ($detailed)
				{
					$this->writer
						->eol()
						->printf('wrap', $i['description'], 8)->eol()
						->eol();
				}
				else if ($signatures)
				{
					$this->writer
						->eol()
						->printf('wrap', $i['system'], 8)->eol()
						->eol();
				}
			}
		}
	}

} # class

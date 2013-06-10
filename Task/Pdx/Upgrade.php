<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Pdx_Upgrade extends \app\Task_Base
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

		$dryrun = $this->get('dry-run', false);
		$verbose = $this->get('verbose', false);

		$dryrun !== false || $dryrun = null;
		$verbose !== false || $verbose = null;

		$pdx = \app\Pdx::instance($this->writer, $verbose);

		if (($history = $pdx->upgrade($dryrun)) === false)
		{
			$this->writer->writef(' The database is locked and operation could not be performed in non-destructive manner.')->eol();
		}
		else # upgrade done
		{
			// dry run?
			if ($dryrun)
			{
				if ($verbose)
				{
					$this->writer->eol();
				}

				if ( ! empty($history))
				{
					foreach ($history as $entry)
					{
						$this->writer->writef(' %9s %s %s', $entry['version'], $entry['channel'], empty($entry['hotfix']) ? '' : '/ '.$entry['hotfix'])->eol();
					}
				}
				else # empty history
				{
					$this->writer->writef(' No changes required.')->eol();
				}
			}
			else # not dry-run
			{
				if ($verbose)
				{
					$this->writer->eol();
				}

				$this->writer
					->eol()->eol()
					->writef(' Upgrade complete.')->eol();
			}
		}
	}

} # class

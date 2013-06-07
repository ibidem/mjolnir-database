<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Pdx_Reset extends \app\Task_Base
{
	/**
	 * ...
	 */
	function run()
	{
		$pivot = $this->get('pivot', false);
		$version = $this->get('version', false);
		$dryrun = $this->get('dry-run', false);
		$verbose = $this->get('verbose', false);

		$pivot !== false || $pivot = null;
		$version !== false || $version = null;
		$dryrun !== false || $dryrun = null;
		$verbose !== false || $verbose = null;

		if ($version !== null && $pivot === null)
		{
			$this->writer->writef(' You must provide a pivot channel.')->eol();
			exit;
		}

		if ($version === null && $pivot !== null)
		{
			$this->writer->writef(' You must provide a version with the pivot channel. Use no parameters for complete install.')->eol();
			exit;
		}

		$pdx = \app\Pdx::instance($this->writer, $verbose);

		if (($history = $pdx->reset($pivot, $version, $dryrun)) === false)
		{
			$this->writer->writef(' The database is locked and operation could not be performed in non-destructive manner.')->eol();
		}
		else # uninstall done
		{
			// dry run?
			if ($dryrun)
			{
				if ($verbose)
				{
					$this->writer->eol();
				}

				foreach ($history as $entry)
				{
					$this->writer->writef(' %9s %s %s', $entry['version'], $entry['channel'], empty($entry['hotfix']) ? '' : '/ '.$entry['hotfix'])->eol();
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
					->writef(' Reset complete.')->eol();
			}
		}
	}

} # class

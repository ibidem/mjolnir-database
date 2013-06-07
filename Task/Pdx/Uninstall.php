<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Pdx_Uninstall extends \app\Task_Base
{
	/**
	 * ...
	 */
	function run()
	{
		$hard = $this->get('hard', false);
		$hard !== null or $hard = false;
		
		$pdx = \app\Pdx::instance($this->writer);

		if ( ! $pdx->uninstall($hard))
		{
			$this->writer->writef(' The database is locked; only non-destructive operations allowed.')->eol();
		}
		else # uninstall done
		{
			$this->writer
				->eol()
				->writef(' Uninstall complete.')->eol();
		}
	}

} # class

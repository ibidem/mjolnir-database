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
		\var_dump(\app\Pdx::coreversion()); die;

		if ( ! Pdx::status())
		{
			$this->writer->writef(' The database is locked; only non-destructive operations allowed.')->eol();
		}
	}

} # class

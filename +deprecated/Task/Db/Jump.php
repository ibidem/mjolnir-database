<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Jump extends \app\Instantiatable implements \mjolnir\types\Task
{
	use \app\Trait_Task;

	/**
	 * Execute task.
	 */
	function run()
	{
		// todo implement jumps
		throw new \Exception('Not Implemented.');
	}

} # class

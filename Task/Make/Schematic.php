<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Make_Schematic extends \app\Instantiatable implements \mjolnir\types\Task
{
	use \app\Trait_Task;

	/**
	 * Execute task.
	 */
	function run()
	{
		$namespace = $this->config['namespace'];
		$nominator = $this->config['schematic'];
		$forced = $this->config['forced'];

		$class_definition = \app\Schematic::parse_class($nominator);
		$class_definition = '\\'.\trim($namespace, '\\').'\\'.\preg_replace('#^.*\\\#', '', $class_definition);

		\app\Task::invoke('make:class')
			->set('class', $class_definition)
			->set('category', false)
			->set('with-tests', false)
			->set('library', false)
			->set('forced', $forced)
			->writer_is($this->writer)
			->run();
	}

} # class

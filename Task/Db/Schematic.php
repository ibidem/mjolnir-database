<?php namespace ibidem\database;

/**
 * @package    ibidem
 * @category   Task
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Schematic extends \app\Task
{
	function execute()
	{
		$namespace = $this->config['namespace'];
		$nominator = $this->config['schematic'];
		$forced = $this->config['forced'];
		
		$class_definition = \app\Schematic::parse_class($nominator);
		$class_definition = '\\'.\trim($namespace, '\\').'\\'.\preg_replace('#^.*\\\#', '', $class_definition);
		
		$make_class = \app\Task_Make_Class::instance()
			->config
			(
				array
				(
					'class' => $class_definition,
					'category' => false,
					'with-tests' => false,
					'library' => false,
					'forced' => $forced,
				)
			)
			->writer($this->writer);
		
		$make_class->execute();
	}

} # class

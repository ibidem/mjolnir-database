<?php namespace ibidem\database;

/**
 * @package    ibidem
 * @category   Schematic
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Schematic_Base extends \app\Instantiatable
{
	public $serial; # we don't actually manipulate this propety
	
	function up()
	{
		// empty
	}
	
	function move()
	{
		// empty
	}
	
	function bind()
	{
		// empty
	}

	function down()
	{
		// empty
	}
	
	function build()
	{
		// empty
	}

} # class

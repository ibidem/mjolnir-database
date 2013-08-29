<?php namespace mjolnir\database\tests;

use \mjolnir\database\Task_Sphinx_Model;

class Task_Sphinx_ModelTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\Task_Sphinx_Model'));
	}

	// @todo tests for \mjolnir\database\Task_Sphinx_Model

} # test

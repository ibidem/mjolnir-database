<?php namespace mjolnir\database\tests;

use \mjolnir\database\Task_Pdx_Reset;

class Task_Pdx_ResetTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\Task_Pdx_Reset'));
	}

	// @todo tests for \mjolnir\database\Task_Pdx_Reset

} # test

<?php namespace mjolnir\database\tests;

use \mjolnir\database\Task_Pdx_Upgrade;

class Task_Pdx_UpgradeTest extends \PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\Task_Pdx_Upgrade'));
	}

	// @todo tests for \mjolnir\database\Task_Pdx_Upgrade

} # test

<?php namespace mjolnir\database\tests;

use \mjolnir\database\Table_Snatcher;

class Table_SnatcherTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\Table_Snatcher'));
	}

	// @todo tests for \mjolnir\database\Table_Snatcher

} # test

<?php namespace mjolnir\database\tests;

use \mjolnir\database\SQLDatabase;

class SQLDatabaseTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\SQLDatabase'));
	}

	// @todo tests for \mjolnir\database\SQLDatabase

} # test

<?php namespace mjolnir\database\tests;

use \mjolnir\database\SQLStatement;

class SQLStatementTest extends \PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\SQLStatement'));
	}

	// @todo tests for \mjolnir\database\SQLStatement

} # test

<?php namespace mjolnir\database\tests;

use \mjolnir\database\SQL;

class SQLTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\SQL'));
	}

	// @todo tests for \mjolnir\database\SQL

} # test

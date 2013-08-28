<?php namespace mjolnir\database\tests;

use \mjolnir\database\SQLStash;

class SQLStashTest extends \PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\SQLStash'));
	}

	// @todo tests for \mjolnir\database\SQLStash

} # test

<?php namespace mjolnir\database\tests;

use \mjolnir\database\MarionetteDriver_Tags;

class MarionetteDriver_TagsTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\MarionetteDriver_Tags'));
	}

	// @todo tests for \mjolnir\database\MarionetteDriver_Tags

} # test

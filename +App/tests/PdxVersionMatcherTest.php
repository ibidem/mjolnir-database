<?php namespace mjolnir\database\tests;

use \mjolnir\database\PdxVersionMatcher;

class PdxVersionMatcherTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\PdxVersionMatcher'));
	}

	// @todo tests for \mjolnir\database\PdxVersionMatcher

} # test

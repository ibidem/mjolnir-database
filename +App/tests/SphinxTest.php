<?php namespace mjolnir\database\tests;

use \mjolnir\database\Sphinx;

class SphinxTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\class_exists('\mjolnir\database\Sphinx'));
	}

	// @todo tests for \mjolnir\database\Sphinx

} # test

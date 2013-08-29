<?php namespace mjolnir\database\tests;

use \mjolnir\database\Trait_Model_Automaton;

class Trait_Model_Automaton_Tester
{
	use Trait_Model_Automaton;
}

class Trait_Model_AutomatonTest extends \app\PHPUnit_Framework_TestCase
{
	/** @test */ function
	can_be_loaded()
	{
		$this->assertTrue(\trait_exists('\mjolnir\database\Trait_Model_Automaton'));
	}

	// @todo tests for \mjolnir\database\Trait_Model_Automaton

} # test

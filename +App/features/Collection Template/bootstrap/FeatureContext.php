<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

\ibidem\base\Mjolnir::behat();

// @todo LOW - convert database code to mockup when I have time

class Test_Mockup
{
	use \app\Trait_Model_Factory;
	use \app\Trait_Model_Master;
	use \app\Trait_Model_Collection;
	
	/**
	 * @var string
	 */
	protected static $table = 'test_table';
	
}

/**
 * Features context.
 */
class FeatureContext extends BehatContext
{	
    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
		$base = \app\CFS::config('ibidem/base');
		if ( ! isset($base['caching']) || ! $base['caching'])
		{
			throw new \app\Exception('Caching is not enabled.');
		}
    }
	
	/**
	 * @BeforeFeature
	 */
	static function before()
	{
		\app\SQL::database('ibidem_testing');
		
		\app\Schematic::destroy
			(
				'test_table'
			);
		
		\app\Schematic::table
			(
				'test_table', 
				'
					`id`    :key_primary,
					`title` :title,
					
					PRIMARY KEY(`id`)
				'
			);
	}
	
	/**
	 * @AfterFeature
	 */
	static function after()
	{
		\app\Schematic::destroy
			(
				'test_table'
			);
		
		\app\SQL::database('default');
	}
	
	/**
	 * @var \app\Table_Snatcher
	 */
	protected $querie, $result;

    /**
     * @Given /^a mock database with ids "([^"]*)" and titles "([^"]*)"$/
     */
    public function aMockDatabaseWithIdsAndTitles($ids, $titles)
    {
		$ids = \explode(', ', $ids);
		$titles = \explode(', ', $titles);
		
		\app\SQL::prepare
			(
				__METHOD__.':truncate',
				'
					TRUNCATE TABLE test_table
				'
			)
			->execute();
		
		\app\SQL::begin();
		
		$inserter = \app\SQL::prepare
			(
				__METHOD__,
				'
					INSERT INTO test_table
						(id, title) VALUES (:id, :title)
				'
			)
			->bind_int(':id', $id)
			->bind(':title', $title);
		
		foreach ($ids as $idx => $id)
		{
			$title = $titles[$idx];
			$inserter->execute();
		}
		
		\app\SQL::commit();
    }
	
	    /**
     * @When /^I ask for the existence of "([^"]*)"$/
     */
    public function iAskForTheExistenceOf($title)
    {
        $this->result = Test_Mockup::exists($title);
    }

    /**
     * @Then /^I should get back the boolean "([^"]*)"$/
     */
    public function iShouldGetBackTheBoolean($bool)
    {
        $bool = $bool == 'true';
		\app\expects($bool)->equals($this->result);
    }

    /**
     * @When /^I ask for the existence of "([^"]*)", under the key "([^"]*)"$/
     */
    public function iAskForTheExistenceOfUnderTheKey($value, $key)
    {
		$this->result = Test_Mockup::exists($value, $key);
    }

    /**
     * @When /^I ask for the entry "([^"]*)"$/
     */
    public function iAskForTheEntry($entry)
    {
         $this->result = Test_Mockup::entry($entry)['id'];
    }

    /**
     * @Then /^I should get the value "([^"]*)"$/
     */
    public function iShouldGetTheValue($value)
    {
        $value = $value === 'null' ? null : $value;
		\app\expects($value)->equals($this->result);
    }

    /**
     * @When /^I ask for the entry with the title "([^"]*)"$/
     */
    public function iAskForTheEntryWithTheTitle($title)
    {
        $this->result = Test_Mockup::find_entry(['title' => $title])['id'];
    }

    /**
     * @When /^I ask for the entries$/
     */
    public function iAskForTheEntries()
    {
        $this->result = Test_Mockup::entries(null, null);
    }

    /**
     * @Then /^I should get the entries "([^"]*)"$/
     */
    public function iShouldGetTheEntries($entries)
    {
		$this->result = \app\Collection::implode
			(
				', ', 
				$this->result, 
				function ($k, $i) {
					return $i['id'];
				}
			);
			
		\app\expects($entries)->equals($this->result);
    }

    /**
     * @When /^I limit entries to "(\d+)", "(\d+)", "([^"]*)"$/
     */
    public function iLimitEntriesTo($page, $limit, $offset)
    {
        $this->result = Test_Mockup::entries($page, $limit, $offset);
    }

    /**
     * @When /^I constraint entries to "([^"]*)"$/
     */
    public function iConstraintEntriesTo($conditions)
    {
        $criterias = \explode(', ', $conditions);
		$constraints = [];
		foreach ($criterias as $criteria)
		{
			$constraint = \explode(' => ', $criteria);
			
			if ($constraint[1] === 'true')
			{
				$constraint[1] = true;
			}
			
			if ($constraint[1] === 'false')
			{
				$constraint[1] = false;
			}
			
			$constraints[$constraint[0]] = $constraint[1];
		}
		
		$this->result = Test_Mockup::entries(null, null, 0, [], $constraints);
    }

	/**
     * @When /^I constraint entries to "([^"]*)" and limit entries to "(\d+)", "(\d+)", "([^"]*)"$/
     */
    public function iConstraintEntriesToAndLimitEntriesTo($conditions, $page, $limit, $offset)
    {
        $criterias = \explode(', ', $conditions);
		$constraints = [];
		foreach ($criterias as $criteria)
		{
			$constraint = \explode(' => ', $criteria);
			
			if ($constraint[1] === 'true')
			{
				$constraint[1] = true;
			}
			
			if ($constraint[1] === 'false')
			{
				$constraint[1] = false;
			}
			
			$constraints[$constraint[0]] = $constraint[1];
		}
		
		$this->result = Test_Mockup::entries($page, $limit, $offset, [], $constraints);
    }

	
    /**
     * @When /^I sort the entries to "([^"]*)"$/
     */
    public function iSortTheEntriesTo($sort)
    {
        $criterias = \explode(', ', $sort);
		$order = [];
		foreach ($criterias as $criteria)
		{
			$sort_order = \explode(' => ', $criteria);
			$order[$sort_order[0]] = $sort_order[1];
		}
		
		$this->result = Test_Mockup::entries(null, null, 0, $order);
    }

    /**
     * @When /^I ask for the count( again)?$/
     */
    public function iAskForTheCount()
    {
        $this->result = Test_Mockup::count();
    }

    /**
     * @Given /^I delete the entry "([^"]*)"$/
     */
    public function iDeleteTheEntry($id)
    {
        $this->result = Test_Mockup::delete([$id]);
    }
	
}

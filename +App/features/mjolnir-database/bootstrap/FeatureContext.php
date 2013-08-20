<?php

use Behat\Behat\Context\ClosuredContextInterface,
	Behat\Behat\Context\TranslatedContextInterface,
	Behat\Behat\Context\BehatContext,
	Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
	Behat\Gherkin\Node\TableNode;
use app\Assert;

\mjolnir\cfs\Mjolnir::behat();

// @todo LOW - convert database code to mockup

class Model_Test
{
	use \app\Trait_ModelLib;

	/**
	 * @var string
	 */
	protected static $table = 'test_table';

	static function table()
	{
		// avoid prefixing
		return static::$table;
	}

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
	function __construct(array $parameters)
	{
		$base = \app\CFS::config('mjolnir/base');
		if ( ! isset($base['caching']) || ! $base['caching'])
		{
			throw new \app\Exception('Caching not enabled.');
		}
	}

	/**
	 * @BeforeFeature
	 */
	static function before()
	{
		\app\SQL::database('mjolnir_testing');

		\app\Schematic::destroy
			(
				Model_Test::table()
			);

		\app\Stash::purge(['Test__change']);

		\app\Schematic::table
			(
				Model_Test::table(),
				'
					`id`	:key_primary,
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
				Model_Test::table()
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
	function aMockDatabaseWithIdsAndTitles($ids, $titles)
	{
		$ids = \explode(', ', $ids);
		$titles = \explode(', ', $titles);

		\app\SQL::prepare
			(
				__METHOD__.':truncate',
				'
					TRUNCATE TABLE `'.Model_Test::table().'`
				'
			)
			->run();

		\app\SQL::begin();

		$inserter = \app\SQL::prepare
			(
				__METHOD__,
				'
					INSERT INTO `'.Model_Test::table().'`
						(id, title) VALUES (:id, :title)
				'
			)
			->bindnum(':id', $id)
			->bindstr(':title', $title);

		foreach ($ids as $idx => $id)
		{
			$title = $titles[$idx];
			$inserter->run();
		}

		\app\SQL::commit();
	}

		/**
	 * @When /^I ask for the existence of "([^"]*)"$/
	 */
	function iAskForTheExistenceOf($title)
	{
		$this->result = Model_Test::exists($title);
	}

	/**
	 * @Then /^I should get back the boolean "([^"]*)"$/
	 */
	function iShouldGetBackTheBoolean($bool)
	{
		$bool = ($bool == 'true');
		Assert::that($bool)->equals($this->result);
	}

	/**
	 * @When /^I ask for the existence of "([^"]*)", under the key "([^"]*)"$/
	 */
	function iAskForTheExistenceOfUnderTheKey($value, $key)
	{
		$this->result = Model_Test::exists($value, $key);
	}

	/**
	 * @When /^I ask for the entry "([^"]*)"$/
	 */
	function iAskForTheEntry($entry)
	{
		 $this->result = Model_Test::entry($entry)['id'];
	}

	/**
	 * @Then /^I should get the value "([^"]*)"$/
	 */
	function iShouldGetTheValue($value)
	{
		$value = ($value === 'null' ? null : $value);
		Assert::that($value)->loosly_equals($this->result);
	}

	/**
	 * @When /^I ask for the entry with the title "([^"]*)"$/
	 */
	function iAskForTheEntryWithTheTitle($title)
	{
		$this->result = Model_Test::find_entry(['title' => $title])['id'];
	}

	/**
	 * @When /^I ask for the entries$/
	 */
	function iAskForTheEntries()
	{
		$this->result = Model_Test::entries(null, null);
	}

	/**
	 * @Then /^I should get the entries "([^"]*)"$/
	 */
	function iShouldGetTheEntries($entries)
	{
		$this->result = \app\Arr::implode
			(
				', ',
				$this->result,
				function ($k, $i) {
					return $i['id'];
				}
			);

		Assert::that($entries)->equals($this->result);
	}

	/**
	 * @When /^I limit entries to "(\d+)", "(\d+)", "([^"]*)"$/
	 */
	function iLimitEntriesTo($page, $limit, $offset)
	{
		$this->result = Model_Test::entries($page, $limit, $offset);
	}

	/**
	 * @When /^I constraint entries to "([^"]*)"$/
	 */
	function iConstraintEntriesTo($conditions)
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

		$this->result = Model_Test::entries(null, null, 0, [], $constraints);
	}

	/**
	 * @When /^I constraint entries to "([^"]*)" and limit entries to "(\d+)", "(\d+)", "([^"]*)"$/
	 */
	function iConstraintEntriesToAndLimitEntriesTo($conditions, $page, $limit, $offset)
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

		$this->result = Model_Test::entries($page, $limit, $offset, [], $constraints);
	}


	/**
	 * @When /^I sort the entries to "([^"]*)"$/
	 */
	function iSortTheEntriesTo($sort)
	{
		$criterias = \explode(', ', $sort);
		$order = [];
		foreach ($criterias as $criteria)
		{
			$sort_order = \explode(' => ', $criteria);
			$order[$sort_order[0]] = $sort_order[1];
		}

		$this->result = Model_Test::entries(null, null, 0, $order);
	}

	/**
	 * @When /^I ask for the count( again)?$/
	 */
	function iAskForTheCount()
	{
		$this->result = Model_Test::count();
	}

	/**
	 * @Given /^I delete the entry "([^"]*)"$/
	 */
	function iDeleteTheEntry($id)
	{
		$this->result = Model_Test::delete([$id]);
	}

	#
	# snatch-queries.feature
	#

	/**
	* @Given /^a querie "([^"]*)"$/
	*/
	function aQuerie($query)
	{
		$this->querie = \app\Table_Snatcher::instance()
			->query($query)
			->table(Model_Test::table())
			->id(666)
			->identity('i_am_a_test')
			->timers(['my_table_update']);
	}

	/**
	* @When /^I execute the querie( again)?$/
	*/
	function iExecuteTheQuerie()
	{
		$this->result = $this->querie->fetch_all();
	}

	/**
	* @Then /^I should get the ids "([^"]*)"$/
	*/
	function iShouldGetTheIds($expected)
	{
		$actual = \app\Arr::implode(', ', $this->result, function ($i, $v) {
			return $v['id'];
		});

		Assert::that($expected)->equals($actual);
	}

	/**
	* @Given /^I should get the titles "([^"]*)"$/
	*/
	function iShouldGetTheTitles($expected)
	{
		$actual = \app\Arr::implode(', ', $this->result, function ($i, $v) {
			return $v['title'];
		});

		Assert::that($expected)->equals($actual);
	}

	/**
	* @When /^I add an item with id "([^"]*)" and title "([^"]*)" to the database$/
	*/
	function iAddAnItemWithIdAndTitleToTheDatabase($id, $title)
	{
		\app\SQL::prepare
			(
				__METHOD__,
				'
					INSERT INTO '.Model_Test::table().'
						(id, title) VALUES (:id, :title)
				'
			)
			->num(':id', $id)
			->str(':title', $title)
			->run();

		\app\Stash::purge(['my_table_update']);
	}

	/**
	* @Given /^I ask for all items again$/
	*/
	function iAskForAllItemsAgain()
	{
		$this->result = $this->querie->fetch_all();
	}

	/**
	* @When /^I limit the querie to page "([^"]*)", limit "([^"]*)" and offset "([^"]*)"$/
	*/
	function iLimitTheQuerieToPageLimitAndOffset($page, $limit, $offset)
	{
		$page = (int) $page;
		$limit = (int) $limit;
		$offset = (int) $offset;
		$this->querie->page($page, $limit, $offset);
	}

	/**
	* @Given /^I sort the query by "([^"]*)"$/
	*/
	function iSortTheQueryBy($sort)
	{
		$criterias = \explode(', ', $sort);
		$order = [];
		foreach ($criterias as $criteria)
		{
			$sort_order = \explode(' => ', $criteria);
			$order[$sort_order[0]] = $sort_order[1];
		}

		$this->querie->order($order);
	}

	/**
	* @Given /^I constraint the query to "([^"]*)"$/
	*/
	function iConstraintTheQueryTo($conditions)
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

		$this->querie->constraints($constraints);
	}

}

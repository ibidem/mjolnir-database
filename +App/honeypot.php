<?php namespace app;

// This is an IDE honeypot. It tells IDEs the class hirarchy, but otherwise has
// no effect on your application. :)

// HowTo: order honeypot -n 'mjolnir\database'


class Register extends \mjolnir\database\Register
{
}

class SQL extends \mjolnir\database\SQL
{
	/** @return \app\SQLStatement */
	static function prepare($key, $statement = null, $lang = null) { return parent::prepare($key, $statement, $lang); }
	/** @return \app\SQLDatabase */
	static function begin() { return parent::begin(); }
	/** @return \app\SQLDatabase */
	static function commit() { return parent::commit(); }
	/** @return \app\SQLDatabase */
	static function rollback() { return parent::rollback(); }
}

/**
 * @method \app\SQLStatement prepare($key, $statement = null, $lang = null)
 * @method \app\SQLDatabase begin()
 * @method \app\SQLDatabase commit()
 * @method \app\SQLDatabase rollback()
 * @method \app\SQLStatement run_stored_statement($key)
 */
class SQLDatabase extends \mjolnir\database\SQLDatabase
{
	/** @return \app\SQLDatabase */
	static function instance($database = 'default') { return parent::instance($database); }
}

/**
 * @method \app\SQLStash identity($identity)
 * @method \app\SQLStash constraints(array $constraints)
 * @method \app\SQLStash is($identity)
 * @method \app\SQLStash table($table)
 * @method \app\SQLStash str($param, $value)
 * @method \app\SQLStash num($param, $value)
 * @method \app\SQLStash bool($param, $value, array $map = null)
 * @method \app\SQLStash date($param, $value)
 * @method \app\SQLStash bindstr($param,  & $variable)
 * @method \app\SQLStash bindnum($param,  & $variable)
 * @method \app\SQLStash bindbool($param,  & $variable)
 * @method \app\SQLStash binddate($param,  & $variable)
 * @method \app\SQLStash arg($param,  & $variable)
 * @method \app\SQLStash order(array  & $order)
 * @method \app\SQLStash key($partial_key)
 * @method \app\SQLStash page($page, $limit = null, $offset = 0)
 * @method \app\SQLStash fetch_object($class = 'stdClass', array $args = null)
 * @method \app\SQLStash strs(array $params, array $filter = null, $varkey = ':')
 * @method \app\SQLStash nums(array $params, array $filter = null, $varkey = ':')
 * @method \app\SQLStash bools(array $params, array $filter = null, array $map = null, $varkey = ':')
 * @method \app\SQLStash dates(array $params, array $filter = null, array $map = null, $varkey = ':')
 * @method \app\SQLStash bindstrs(array  & $params, array $filter = null, $varkey = ':')
 * @method \app\SQLStash bindnums(array  & $params, array $filter = null, $varkey = ':')
 * @method \app\SQLStash bindbools(array  & $params, array $filter = null, $varkey = ':')
 * @method \app\SQLStash binddates(array  & $params, array $filter = null, array $map = null, $varkey = ':')
 * @method \app\SQLStash args(array  & $params, array $filter = null, $varkey = ':')
 */
class SQLStash extends \mjolnir\database\SQLStash
{
	/** @return \app\SQLStash */
	static function instance() { return parent::instance(); }
}

/**
 * @method \app\SQLStatement str($parameter, $value)
 * @method \app\SQLStatement num($parameter, $value)
 * @method \app\SQLStatement bool($parameter, $value, array $map = null)
 * @method \app\SQLStatement date($parameter, $value)
 * @method \app\SQLStatement bindstr($parameter,  & $variable)
 * @method \app\SQLStatement bindnum($parameter,  & $variable)
 * @method \app\SQLStatement bindbool($parameter,  & $variable)
 * @method \app\SQLStatement binddate($parameter,  & $variable)
 * @method \app\SQLStatement arg($parameter,  & $variable)
 * @method \app\SQLStatement run()
 * @method \app\SQLStatement strs(array $params, array $filter = null, $varkey = ':')
 * @method \app\SQLStatement nums(array $params, array $filter = null, $varkey = ':')
 * @method \app\SQLStatement bools(array $params, array $filter = null, array $map = null, $varkey = ':')
 * @method \app\SQLStatement dates(array $params, array $filter = null, array $map = null, $varkey = ':')
 * @method \app\SQLStatement bindstrs(array  & $params, array $filter = null, $varkey = ':')
 * @method \app\SQLStatement bindnums(array  & $params, array $filter = null, $varkey = ':')
 * @method \app\SQLStatement bindbools(array  & $params, array $filter = null, $varkey = ':')
 * @method \app\SQLStatement binddates(array  & $params, array $filter = null, array $map = null, $varkey = ':')
 * @method \app\SQLStatement args(array  & $params, array $filter = null, $varkey = ':')
 * @method \app\SQLStatement page($page, $limit = null, $offset = 0)
 */
class SQLStatement extends \mjolnir\database\SQLStatement
{
	/** @return \app\SQLStatement */
	static function instance($statement = null, $query = null) { return parent::instance($statement, $query); }
}

class Schematic_Mjolnir_Registry extends \mjolnir\database\Schematic_Mjolnir_Registry
{
	/** @return \app\Schematic_Mjolnir_Registry */
	static function instance() { return parent::instance(); }
}

class Schematic extends \mjolnir\database\Schematic
{
}

/**
 * @method \app\Sphinx filter($attribute, $values, $exclude = false)
 * @method \app\Sphinx matchmode($matchmode)
 * @method \app\Sphinx sortmode($sortmode)
 * @method \app\Sphinx page($page, $limit = null, $offset = 0)
 */
class Sphinx extends \mjolnir\database\Sphinx
{
	/** @return \app\Sphinx */
	static function instance() { return parent::instance(); }
}

/**
 * @method \app\Table_Snatcher query($query)
 * @method \app\Table_Snatcher identity($identity)
 * @method \app\Table_Snatcher id($id)
 * @method \app\Table_Snatcher table($table)
 * @method \app\Table_Snatcher timers(array $tags)
 * @method \app\Table_Snatcher constraints(array $constraints)
 * @method \app\Table_Snatcher order(array $field_order)
 */
class Table_Snatcher extends \mjolnir\database\Table_Snatcher
{
	/** @return \app\Table_Snatcher */
	static function instance() { return parent::instance(); }
}

/**
 * @method \app\Task_Db_Init set($name, $value)
 * @method \app\Task_Db_Init add($name, $value)
 * @method \app\Task_Db_Init metadata_is(array $metadata = null)
 * @method \app\Task_Db_Init writer_is($writer)
 * @method \app\Writer writer()
 */
class Task_Db_Init extends \mjolnir\database\Task_Db_Init
{
	/** @return \app\Task_Db_Init */
	static function instance() { return parent::instance(); }
}

/**
 * @method \app\Task_Db_Install set($name, $value)
 * @method \app\Task_Db_Install add($name, $value)
 * @method \app\Task_Db_Install metadata_is(array $metadata = null)
 * @method \app\Task_Db_Install writer_is($writer)
 * @method \app\Writer writer()
 */
class Task_Db_Install extends \mjolnir\database\Task_Db_Install
{
	/** @return \app\Task_Db_Install */
	static function instance() { return parent::instance(); }
}

/**
 * @method \app\Task_Db_Reset set($name, $value)
 * @method \app\Task_Db_Reset add($name, $value)
 * @method \app\Task_Db_Reset metadata_is(array $metadata = null)
 * @method \app\Task_Db_Reset writer_is($writer)
 * @method \app\Writer writer()
 */
class Task_Db_Reset extends \mjolnir\database\Task_Db_Reset
{
	/** @return \app\Task_Db_Reset */
	static function instance() { return parent::instance(); }
}

/**
 * @method \app\Task_Db_Sphinx set($name, $value)
 * @method \app\Task_Db_Sphinx add($name, $value)
 * @method \app\Task_Db_Sphinx metadata_is(array $metadata = null)
 * @method \app\Task_Db_Sphinx writer_is($writer)
 * @method \app\Writer writer()
 */
class Task_Db_Sphinx extends \mjolnir\database\Task_Db_Sphinx
{
	/** @return \app\Task_Db_Sphinx */
	static function instance() { return parent::instance(); }
}

/**
 * @method \app\Task_Db_Uninstall set($name, $value)
 * @method \app\Task_Db_Uninstall add($name, $value)
 * @method \app\Task_Db_Uninstall metadata_is(array $metadata = null)
 * @method \app\Task_Db_Uninstall writer_is($writer)
 * @method \app\Writer writer()
 */
class Task_Db_Uninstall extends \mjolnir\database\Task_Db_Uninstall
{
	/** @return \app\Task_Db_Uninstall */
	static function instance() { return parent::instance(); }
}

/**
 * @method \app\Task_Db_Upgrade set($name, $value)
 * @method \app\Task_Db_Upgrade add($name, $value)
 * @method \app\Task_Db_Upgrade metadata_is(array $metadata = null)
 * @method \app\Task_Db_Upgrade writer_is($writer)
 * @method \app\Writer writer()
 */
class Task_Db_Upgrade extends \mjolnir\database\Task_Db_Upgrade
{
	/** @return \app\Task_Db_Upgrade */
	static function instance() { return parent::instance(); }
}

/**
 * @method \app\Task_Db_Version set($name, $value)
 * @method \app\Task_Db_Version add($name, $value)
 * @method \app\Task_Db_Version metadata_is(array $metadata = null)
 * @method \app\Task_Db_Version writer_is($writer)
 * @method \app\Writer writer()
 */
class Task_Db_Version extends \mjolnir\database\Task_Db_Version
{
	/** @return \app\Task_Db_Version */
	static function instance() { return parent::instance(); }
}

/**
 * @method \app\Task_Make_Schematic set($name, $value)
 * @method \app\Task_Make_Schematic add($name, $value)
 * @method \app\Task_Make_Schematic metadata_is(array $metadata = null)
 * @method \app\Task_Make_Schematic writer_is($writer)
 * @method \app\Writer writer()
 */
class Task_Make_Schematic extends \mjolnir\database\Task_Make_Schematic
{
	/** @return \app\Task_Make_Schematic */
	static function instance() { return parent::instance(); }
}
trait Trait_Model_Automaton { use \mjolnir\database\Trait_Model_Automaton; }
trait Trait_Model_Collection { use \mjolnir\database\Trait_Model_Collection; }
trait Trait_Model_Factory { use \mjolnir\database\Trait_Model_Factory; }
trait Trait_Model_MjolnirSphinx { use \mjolnir\database\Trait_Model_MjolnirSphinx; }
trait Trait_Model_Utilities { use \mjolnir\database\Trait_Model_Utilities; }
trait Trait_Task_Db_Migrations { use \mjolnir\database\Trait_Task_Db_Migrations; }

/**
 * @method \app\Validator rule($field, $claim, $proof = null)
 * @method \app\Validator test($field, $proof = null)
 * @method \app\Validator inheriterrors($validator)
 * @method \app\Validator adderrormessages(array $errormesssages = null)
 * @method \app\Validator adderror($field, $claim, $message = null)
 * @method \app\Validator fields_array($fields)
 * @method \app\Validator check()
 */
class Validator extends \mjolnir\database\Validator
{
	/** @return \app\Validator */
	static function instance(array $fields = null) { return parent::instance($fields); }
}

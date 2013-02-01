<?php namespace app;

// This is an IDE honeypot. It tells IDEs the class hirarchy, but otherwise has
// no effect on your application. :)

// HowTo: order honeypot -n 'mjolnir\database'

class Register extends \mjolnir\database\Register {}
class SQL extends \mjolnir\database\SQL {}
class SQLDatabase extends \mjolnir\database\SQLDatabase { /** @return \mjolnir\database\SQLDatabase */ static function instance($database = 'default') { return parent::instance($database); } }
class SQLStash extends \mjolnir\database\SQLStash { /** @return \mjolnir\database\SQLStash */ static function instance() { return parent::instance(); } }
class SQLStatement extends \mjolnir\database\SQLStatement { /** @return \mjolnir\database\SQLStatement */ static function instance($statement = null, $query = null) { return parent::instance($statement, $query); } }
class Schematic_Mjolnir_Registry extends \mjolnir\database\Schematic_Mjolnir_Registry { /** @return \mjolnir\database\Schematic_Mjolnir_Registry */ static function instance() { return parent::instance(); } }
class Schematic extends \mjolnir\database\Schematic {}
class Sphinx extends \mjolnir\database\Sphinx { /** @return \mjolnir\database\Sphinx */ static function instance() { return parent::instance(); } }
class Table_Snatcher extends \mjolnir\database\Table_Snatcher { /** @return \mjolnir\database\Table_Snatcher */ static function instance() { return parent::instance(); } }
class Task_Db_Init extends \mjolnir\database\Task_Db_Init { /** @return \mjolnir\database\Task_Db_Init */ static function instance() { return parent::instance(); } }
class Task_Db_Install extends \mjolnir\database\Task_Db_Install { /** @return \mjolnir\database\Task_Db_Install */ static function instance() { return parent::instance(); } }
class Task_Db_Reset extends \mjolnir\database\Task_Db_Reset { /** @return \mjolnir\database\Task_Db_Reset */ static function instance() { return parent::instance(); } }
class Task_Db_Sphinx extends \mjolnir\database\Task_Db_Sphinx { /** @return \mjolnir\database\Task_Db_Sphinx */ static function instance() { return parent::instance(); } }
class Task_Db_Uninstall extends \mjolnir\database\Task_Db_Uninstall { /** @return \mjolnir\database\Task_Db_Uninstall */ static function instance() { return parent::instance(); } }
class Task_Db_Upgrade extends \mjolnir\database\Task_Db_Upgrade { /** @return \mjolnir\database\Task_Db_Upgrade */ static function instance() { return parent::instance(); } }
class Task_Db_Version extends \mjolnir\database\Task_Db_Version { /** @return \mjolnir\database\Task_Db_Version */ static function instance() { return parent::instance(); } }
class Task_Make_Schematic extends \mjolnir\database\Task_Make_Schematic { /** @return \mjolnir\database\Task_Make_Schematic */ static function instance() { return parent::instance(); } }
trait Trait_Model_Automaton { use \mjolnir\database\Trait_Model_Automaton; }
trait Trait_Model_Collection { use \mjolnir\database\Trait_Model_Collection; }
trait Trait_Model_Factory { use \mjolnir\database\Trait_Model_Factory; }
trait Trait_Model_MjolnirSphinx { use \mjolnir\database\Trait_Model_MjolnirSphinx; }
trait Trait_Model_Utilities { use \mjolnir\database\Trait_Model_Utilities; }
trait Trait_Task_Db_Migrations { use \mjolnir\database\Trait_Task_Db_Migrations; }
class Validator extends \mjolnir\database\Validator { /** @return \mjolnir\database\Validator */ static function instance(array $fields = null) { return parent::instance($fields); } }

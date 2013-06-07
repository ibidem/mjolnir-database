<?php namespace app;

// This is an IDE honeypot. It tells IDEs the class hirarchy, but otherwise has
// no effect on your application. :)

// HowTo: order honeypot -n 'mjolnir\database'

class Register extends \mjolnir\database\Register {}
class SQL extends \mjolnir\database\SQL {}
class SQLDatabase extends \mjolnir\database\SQLDatabase { /** @return \mjolnir\database\SQLDatabase */ static function instance($database = 'default') { return parent::instance($database); } }
class SQLStatement extends \mjolnir\database\SQLStatement { /** @return \mjolnir\database\SQLStatement */ static function instance($statement = null, $query = null) { return parent::instance($statement, $query); } }
class Schematic_Base extends \mjolnir\database\Schematic_Base { /** @return \mjolnir\database\Schematic_Base */ static function instance() { return parent::instance(); } }
class Schematic_Mjolnir_Registry extends \mjolnir\database\Schematic_Mjolnir_Registry { /** @return \mjolnir\database\Schematic_Mjolnir_Registry */ static function instance() { return parent::instance(); } }
class Schematic extends \mjolnir\database\Schematic {}
class Sphinx extends \mjolnir\database\Sphinx { /** @return \mjolnir\database\Sphinx */ static function instance() { return parent::instance(); } }
class Table_Snatcher extends \mjolnir\database\Table_Snatcher { /** @return \mjolnir\database\Table_Snatcher */ static function instance() { return parent::instance(); } }
class Task_Db_Backup extends \mjolnir\database\Task_Db_Backup { /** @return \mjolnir\database\Task_Db_Backup */ static function instance() { return parent::instance(); } }
class Task_Db_Init extends \mjolnir\database\Task_Db_Init { /** @return \mjolnir\database\Task_Db_Init */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Install extends \mjolnir\database\Task_Db_Install { /** @return \mjolnir\database\Task_Db_Install */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Jump extends \mjolnir\database\Task_Db_Jump { /** @return \mjolnir\database\Task_Db_Jump */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Reset extends \mjolnir\database\Task_Db_Reset { /** @return \mjolnir\database\Task_Db_Reset */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Restore extends \mjolnir\database\Task_Db_Restore { /** @return \mjolnir\database\Task_Db_Restore */ static function instance() { return parent::instance(); } }
class Task_Db_Sphinx extends \mjolnir\database\Task_Db_Sphinx { /** @return \mjolnir\database\Task_Db_Sphinx */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Uninstall extends \mjolnir\database\Task_Db_Uninstall { /** @return \mjolnir\database\Task_Db_Uninstall */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Upgrade extends \mjolnir\database\Task_Db_Upgrade { /** @return \mjolnir\database\Task_Db_Upgrade */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Version extends \mjolnir\database\Task_Db_Version { /** @return \mjolnir\database\Task_Db_Version */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Make_Schematic extends \mjolnir\database\Task_Make_Schematic { /** @return \mjolnir\database\Task_Make_Schematic */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
trait Trait_Model_Automaton { use \mjolnir\database\Trait_Model_Automaton; }
trait Trait_Model_Collection { use \mjolnir\database\Trait_Model_Collection; }
trait Trait_Model_Factory { use \mjolnir\database\Trait_Model_Factory; }
trait Trait_Model_MjolnirSphinx { use \mjolnir\database\Trait_Model_MjolnirSphinx; }
trait Trait_Model_Utilities { use \mjolnir\database\Trait_Model_Utilities; }
class Validator extends \mjolnir\database\Validator { /** @return \mjolnir\database\Validator */ static function instance(array $messages = null, array $fields = null) { return parent::instance($messages, $fields); } }
class ValidatorRules extends \mjolnir\database\ValidatorRules {}

<?php namespace app;

// This is an IDE honeypot. It tells IDEs the class hirarchy, but otherwise has
// no effect on your application. :)

// HowTo: order honeypot -n 'ibidem\database'

class SQL extends \mjolnir\database\SQL {}
class SQLDatabase extends \mjolnir\database\SQLDatabase { /** @return \mjolnir\database\SQLDatabase */ static function instance($database = 'default') { return parent::instance($database); } }
class SQLStatement extends \mjolnir\database\SQLStatement { /** @return \mjolnir\database\SQLStatement */ static function instance($statement = null) { return parent::instance($statement); } }
class Table_Snatcher extends \mjolnir\database\Table_Snatcher { /** @return \mjolnir\database\Table_Snatcher */ static function instance() { return parent::instance(); } }
trait Trait_Model_Automaton { use \mjolnir\database\Trait_Model_Automaton; }
trait Trait_Model_Collection { use \mjolnir\database\Trait_Model_Collection; }
trait Trait_Model_Factory { use \mjolnir\database\Trait_Model_Factory; }
trait Trait_Model_Utilities { use \mjolnir\database\Trait_Model_Utilities; }
class Validator extends \mjolnir\database\Validator { /** @return \mjolnir\database\Validator */ static function instance(array $messages = null, array $fields = null) { return parent::instance($messages, $fields); } }
class ValidatorRules extends \mjolnir\database\ValidatorRules {}

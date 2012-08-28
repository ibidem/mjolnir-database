<?php namespace app;

// This is an IDE honeypot. It tells IDEs the class hirarchy, but otherwise has
// no effect on your application. :)

// HowTo: order honeypot -n 'ibidem\database'

class SQL extends \ibidem\database\SQL {}
class SQLDatabase extends \ibidem\database\SQLDatabase { /** @return \ibidem\database\SQLDatabase */ static function instance($database = 'default') { return parent::instance($database); } }
class SQLStatement extends \ibidem\database\SQLStatement { /** @return \ibidem\database\SQLStatement */ static function instance($statement = null) { return parent::instance($statement); } }
class Table_Snatcher extends \ibidem\database\Table_Snatcher { /** @return \ibidem\database\Table_Snatcher */ static function instance() { return parent::instance(); } }
trait Trait_Model_Automaton { use \ibidem\database\Trait_Model_Automaton; }
trait Trait_Model_Collection { use \ibidem\database\Trait_Model_Collection; }
trait Trait_Model_Factory { use \ibidem\database\Trait_Model_Factory; }
trait Trait_Model_Utilities { use \ibidem\database\Trait_Model_Utilities; }
class Validator extends \ibidem\database\Validator { /** @return \ibidem\database\Validator */ static function instance(array $messages = null, array $fields = null) { return parent::instance($messages, $fields); } }
class ValidatorRules extends \ibidem\database\ValidatorRules {}

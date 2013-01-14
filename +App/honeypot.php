<?php namespace app;

// This is an IDE honeypot. It tells IDEs the class hirarchy, but otherwise has
// no effect on your application. :)

// HowTo: order honeypot -n 'ibidem\schematics'

class Schematic_Base extends \ibidem\schematics\Schematic_Base { /** @return \ibidem\schematics\Schematic_Base */ static function instance() { return parent::instance(); } }
class Schematic extends \ibidem\schematics\Schematic {}
class Task_Db_Backup extends \ibidem\schematics\Task_Db_Backup { /** @return \ibidem\schematics\Task_Db_Backup */ static function instance() { return parent::instance(); } }
class Task_Db_Init extends \ibidem\schematics\Task_Db_Init { /** @return \ibidem\schematics\Task_Db_Init */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Install extends \ibidem\schematics\Task_Db_Install { /** @return \ibidem\schematics\Task_Db_Install */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Jump extends \ibidem\schematics\Task_Db_Jump { /** @return \ibidem\schematics\Task_Db_Jump */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Reset extends \ibidem\schematics\Task_Db_Reset { /** @return \ibidem\schematics\Task_Db_Reset */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Restore extends \ibidem\schematics\Task_Db_Restore { /** @return \ibidem\schematics\Task_Db_Restore */ static function instance() { return parent::instance(); } }
class Task_Db_Schematic extends \ibidem\schematics\Task_Db_Schematic { /** @return \ibidem\schematics\Task_Db_Schematic */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Uninstall extends \ibidem\schematics\Task_Db_Uninstall { /** @return \ibidem\schematics\Task_Db_Uninstall */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Upgrade extends \ibidem\schematics\Task_Db_Upgrade { /** @return \ibidem\schematics\Task_Db_Upgrade */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }
class Task_Db_Version extends \ibidem\schematics\Task_Db_Version { /** @return \ibidem\schematics\Task_Db_Version */ static function instance($encoded_task = null) { return parent::instance($encoded_task); } }

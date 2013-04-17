<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Task_Db_Sphinx extends \app\Task_Base
{
	use \app\Trait_Task;

	/**
	 * Execute task.
	 */
	function run()
	{
		if ($this->get('regenerate'))
		{
			$this->regenerate();
		}
		else # no command
		{
			$this->writer->writef(' No parameters provided.')->eol();
		}
	}

	/**
	 * Regenerate configuration file.
	 */
	function regenerate()
	{
		\app\Task::consolewriter($this->writer);

		$config = \app\CFS::config('mjolnir/sphinx');

		// go though all models on the system and ask them for their sphinx details
		$model_classes = \app\CFS::classmatches('#^Model_.*$#');

		$sph_conf
			= "#\n# The following is an auto-generated configuration file.\n"
			. "# Please DO NOT edit this file.\n#\n"
			. "# To update use ./order db:sphinx -r\n#\n"
			. "# @generator Mjolnir\n"
			. "# @created   ".\date_create('now')->format('Y-m-d H:i:s')."\n"
			. "# @project   ".\app\CFS::config('mjolnir/base')['system']['title']."\n"
			. "#\n\n";

		foreach ($model_classes as $classname => $namespace)
		{
			$class = '\\'.$namespace.'\\'.$classname;

			// we need to build a source and corresponding indexes; sphinx
			// requires these seperatly because the source can be something
			// other then a sql database — this goes with out saying, that the
			// model itself can very well be something other then a SQL database
			// as well

			// does the class support sphinx?
			if (\method_exists($class, 'sph_source') && $class::sph_source() !== null)
			{
				$sph_name = $class::sph_name(); # for sql models, it's table()

				$is_rtindex = $class::sph_is_rtindex();
				if ( ! $is_rtindex)
				{
					$source
						= 'source '.$sph_name."\n{\n"
						. $class::sph_source()
						. "}\n\n"
						;

					$sph_conf .= $source;
				}

				$sph_name = ($is_rtindex ? 'rt_' : '').$sph_name;

				$index = 'index '.$sph_name."\n{\n";

				if ( ! $is_rtindex)
				{
					$index .= "\tsource           = ".$sph_name."\n";
				}
				else # rt index
				{
					$index .= "\ttype             = rt\n";
				}

				$index
					.= "\tpath              = {$config['index']['default.path-prefix']}{$sph_name}\n"
					. "\tdocinfo           = {$config['index']['default.docinfo']}\n"
					. "\tcharset_type      = {$config['index']['default.charset_type']}\n"
					. "\tmin_word_len      = {$config['index']['default.min_word_len']}\n"
					. "\tmin_prefix_len    = {$config['index']['default.min_prefix_len']}\n"
					. "\tmin_infix_len     = {$config['index']['default.min_infix_len']}\n"
					. "\tmin_stemming_len  = {$config['index']['default.min_stemming_len']}\n"
					. "\tindex_exact_words = {$config['index']['default.index_exact_words']}\n"
					;

				if ($is_rtindex)
				{
					$index .= "\n".$class::sph_rtindex();
				}

				$index .= "}\n\n";

				$sph_conf .= $index;
			}
		}

		$config = \app\CFS::config('mjolnir/sphinx');

		$indexer = "indexer\n{\n"
		         . "\tmem_limit = ".$config['indexer']['mem_limit']."\n"
		         . "}\n\n"
				 ;

		$sph_conf .= $indexer;

		$searchd = "searchd\n{\n";

		foreach ($config['searchd']['listen'] as $key => $listen)
		{
			$searchd .= "\tlisten          = ".$listen."\n";
		}

		$searchd .= "\tlog             = ".$config['searchd']['log']."\n";
		$searchd .= "\tquery_log       = ".$config['searchd']['query_log']."\n";
		$searchd .= "\tread_timeout    = ".$config['searchd']['read_timeout']."\n";
		$searchd .= "\tmax_children    = ".$config['searchd']['max_children']."\n";
		$searchd .= "\tpid_file        = ".$config['searchd']['pid_file']."\n";
		$searchd .= "\tmax_matches     = ".$config['searchd']['max_matches']."\n";
		$searchd .= "\tseamless_rotate = ".$config['searchd']['seamless_rotate']."\n";
		$searchd .= "\tpreopen_indexes = ".$config['searchd']['preopen_indexes']."\n";
		$searchd .= "\tunlink_old      = ".$config['searchd']['unlink_old']."\n";
		$searchd .= "\tworkers         = ".$config['searchd']['workers']."\n";
		$searchd .= "\tbinlog_path     = ".$config['searchd']['binlog_path']."\n";

		$searchd .= "}\n\n";

		$sph_conf .= $searchd;

		$sph_configuration_file = \app\Env::key('etc.path').'tmp/sphinx.conf.mj';
		\app\Filesystem::puts($sph_configuration_file, $sph_conf);

		$this->writer->writef(' Created Sphinx configuration in: '.$sph_configuration_file)->eol()->eol();

		$this->writer->writef(' Tip: To customize various generated values overwrite the [mjolnir/sphinx] configuration.')->eol();
	}

} # class

<?php namespace mjolnir\database;

/**
 * This class contains utilities to be used with the Paradox migration system.
 *
 * The object interface exposes the main commands (reset, uninstall, etc).
 * The static interface exposes helpers to be used when writing the migrations.
 *
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Pdx /* "Paradox" */ extends \app\Instantiatable
{
	/**
	 * @var string version table base name
	 */
	protected static $table = 'mjolnir__paradox';

	/**
	 * @return string version table
	 */
	static function table()
	{
		return \app\CFS::config('mjolnir/database')['table_prefix'].static::$table;
	}

	/**
	 * @return string database used for version table
	 */
	static function database()
	{
		return 'default';
	}

	// Migration Utilities & Helpers
	// ------------------------------------------------------------------------

	/**
	 * When converting from one database structure to another it is often
	 * required to translate one structure to another, which involves going
	 * though all the entries in a central table; this method abstracts the
	 * procedure for you.
	 *
	 * Batch reads with batch commits for changes is generally the fastest way
	 * to perform the operations.
	 */
	static function processor($table, $count, $callback, $reads = 1000, \mjolnir\types\SQLDatabase $db = null)
	{
		$db !== null or $db = \app\SQLDatabase::instance();

		$pages = ((int) ($count / $reads)) + 1;

		for ($page = 1; $page <= $pages; ++$page)
		{
			$db->begin();

			$entries = $db->prepare
				(
					__METHOD__.':read_entries',
					'
						SELECT *
						  FROM `'.$table.'`
						 LIMIT :limit OFFSET :offset
					'
				)
				->page($page, $reads)
				->run()
				->fetch_all();

			foreach ($entries as $entry)
			{
				$callback($db, $entry);
			}

			$db->commit();
		}
	}

	// ------------------------------------------------------------------------
	// Migration Command Interface

	/**
	 * @var \mjolnir\types\SQLDatabase
	 */
	protected $db = null;

	/**
	 * @var \mjolnir\types\Writer
	 */
	protected $writer = null;

	/**
	 * @return static
	 */
	static function instance(\mjolnir\types\SQLDatabase $db = null, \mjolnir\types\Writer $writer = null)
	{
		if ($db === null)
		{
			$this->db = \app\SQLDatabase::instance();
		}
		else # $db != null
		{
			$this->db = $db;
		}

		if ($writer === null)
		{
			$this->writer = \app\SilentWriter::instance();
		}
		else # writer != null
		{
			$this->writer = $writer;
		}
	}

	/**
	 * Removes all tables. Will not work if database is not set to
	 *
	 * @return boolean true if successful, false if not permitted
	 */
	function uninstall()
	{
		$locked = \app\CFS::config('mjolnir/base')['db:lock'];

		if ($locked)
		{
			return false;
		}
		else # database is not locked
		{
			// load configuration
			$pdx = \app\CFS::config('mjolnir/paradox');

			// configure channels
			$resolution = [];
			foreach ($pdx as $channelname => $channel)
			{
				if (isset($channel['database']))
				{
					$db = \app\SQLDatabase::instance($channel['database']);
					unset($channel['database']);
				}
				else # default database
				{
					$db = \app\SQLDatabase::instance();
				}

				\ksort($channel);

				$resolution[$channelname] = array
					(
						'current' => null,
						'db' => $db,
						'versions' => $channel,
					);

				// @todo processing based on least influence model
			}
		}
	}

	// ------------------------------------------------------------------------
	// Helpers



} # class

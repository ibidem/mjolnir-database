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
	protected static $table = 'mjolnir__timeline';

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
	 * Loads a paradox file from the path config/paradox/$filepath and merges
	 * require array into it before outputting. The default EXT will be used.
	 * 
	 * This function is meant to be used inside the main pradox files to keep 
	 * everything readable; in particular to keep require statements readable.
	 * 
	 * Please do not add functionality to the method, simply create your own
	 * version that's called by another name; this is why the method not named
	 * load and so forth.
	 * 
	 * @return array configuration
	 */
	static function gate($filepath, $require = null, $ext = EXT)
	{
		$require != null or $require = [];
		return \app\Arr::merge(\app\CFS::config("timeline/$filepath".$ext), ['require' => $require]);
	}
	
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
	
	/**
	 * Performs safe insert into table given values and keys. This is a very 
	 * primitive function, which gurantees the integrity of the operation 
	 * inside the migration.
	 * 
	 * Do not use api powered insertion commands since they will break as the 
	 * source code changes. Since the migration gurantees the integrity of the
	 * api commands, the migration can not rely on them, since that would cause
	 * a circular dependency chain.
	 * 
	 * Fortunately since insert operations in migrations are unlikely to pull 
	 * any user data hardcoding them like this is very straight forward and 
	 * safe.
	 * 
	 * @return int ID
	 */
	static function insert($key, \mjolnir\types\SQLDatabase $db, $table, array $values, $map = null)
	{
		$map !== null or $map = [];
		$map['nums'] !== null or $map['nums'] = [];
		$map['bools'] !== null or $map['bools'] = [];
		$map['dates'] !== null or $map['dates'] = [];
		
		$rawkeys = \array_keys($values);
		$keys = \app\Arr::implode(', ', $rawkeys, function ($i, $key) {
			return "`$key`";
		});
		$refs = \app\Arr::implode(', ', $rawkeys, function ($i, $key) {
			return ":$key";
		});
		
		$statement = $db->prepare
			(
				$key,
				'
					INSERT INTO `'.$table.'` ('.$keys.') VALUES ('.$refs.')
				'
			);
		
		// populate statement
		foreach ($values as $key => $value)
		{
			if (\in_array($key, $map['nums']))
			{
				$statement->num(":$key", $value);
			}
			else if (\in_array($key, $map['bools']))
			{
				$statement->bool(":$key", $value);
			}
			else if (\in_array($key, $map['dates']))
			{
				$statement->date(":$key", $value);
			}
			else # assume string
			{
				$statement->str(":$key", $value);
			}
		}
		
		$statement->run();
	}
	
	/**
	 * ...
	 */
	static function create_table(\mjolnir\database\SQLDatabase $db, $table, $definition, $engine, $charset)
	{
		return; // @todo remove
		
		$shorthands = \app\CFS::config('mjolnir/paradox-sql-definitions');
		$shorthands = $shorthands + [':engine' => $engine, ':default_charset' => $charset];
		
		try
		{
			$db->prepare
				(
					__METHOD__,
					\strtr
						(
							'
								CREATE TABLE `'.$table.'`
									(
										'.$definition.'
									)
								ENGINE=:engine DEFAULT CHARSET=:default_charset
							',
							$shorthands
						),
					'mysql'
				)
				->run();
		}
		catch (\Exception $e)
		{
			if (\php_sapi_name() === 'cli')
			{
				$this->write->eol()->eol();
				$this->writer->writef(' SQL: ')->eol();
				
				$this->writer->writef
					(
						\strtr
							(
								\app\Text::baseindent($definition),
								$shorthands
							)
					);

				$this->writer->eol()->eol();
			}

			throw $e;
		}
	}
	
	/**
	 * Remove specified bindings.
	 */
	static function remove_bindings(\mjolnir\database\SQLDatabase $db, $table, array $bindings)
	{
		return; // @todo remove
		
		foreach ($bindings as $key)
		{
			$db->prepare
				(
					__METHOD__, 
					'
						ALTER TABLE `'.$table.'` 
						 DROP FOREIGN KEY `'.$key.'`
					'
				)
				->run();
		}
	}

	// ------------------------------------------------------------------------
	// Migration Operations
	
	/**
	 * Performs any necesary migration configuration.
	 */
	protected static function migration_configure(\mjolnir\types\SQLDatabase $db, array $handlers, array & $state)
	{
		if ( ! isset($handlers['configure']))
		{
			return;
		}

		if (\is_array($handlers['configure']))
		{
			if (isset($handlers['configure']['tables']))
			{
				foreach ($handlers['configure']['tables'] as $table)
				{
					if ( ! \in_array($state['tables']))
					{
						$state['tables'][] = $table;
					}
				}
			}
		}
		else if (\is_callable($handlers['configure']))
		{
			$handlers['configure']($state);
		}
		
		// else: unsuported format
	}
	
	/**
	 * Perform removal operations.
	 */
	protected static function migration_cleanup(\mjolnir\types\SQLDatabase $db, array $handlers, array & $state)
	{
		if ( ! isset($handlers['cleanup']))
		{
			return;
		}
		
		if (\is_array($handlers['cleanup']))
		{
			if (isset($handlers['cleanup']['bindings']))
			{
				foreach ($handlers['cleanup']['bindings'] as $table => $constraints)
				{
					static::remove_bindings($table, $constraints);
				}
			}
		}
		else if (\is_callable($handlers['cleanup']))
		{
			$handlers['cleanup']($state);
		}
		
		// else: unsuported format
	}
	
	/**
	 * Table creation operations
	 */
	protected static function migration_tables(\mjolnir\types\SQLDatabase $db, array $handlers, array & $state)
	{
		if ( ! isset($handlers['tables']))
		{
			return;
		}
		
		if (\is_array($handlers['tables']))
		{
			foreach ($handlers['tables'] as $table => $def)
			{
				if (\is_string($def))
				{
					static::create_table($db, $table, $def, $state['sql']['default']['engine'], $state['sql']['default']['charset']);
				}
				else if (\is_array($def))
				{
					static::create_table($db, $table, $def['definition'], $def['engine'], $def['charset']);
				}
				else if (\is_callable($def))
				{
					$def($state);
				}				
			}
		}
		else if (\is_callable($handlers['tables']))
		{
			$handlers['tables']($state);
		}
		
		// else: unsuported format
	}
	
	/**
	 * Table creation operations
	 */
	protected static function migration_modify(\mjolnir\types\SQLDatabase $db, array $handlers, array & $state)
	{
		if ( ! isset($handlers['modify']))
		{
			return;
		}
		
		if (\is_array($handlers['modify']))
		{
			$definitions = \app\CFS::config('mjolnir/paradox-sql-definitions');
			foreach ($handlers['modify'] as $table => $def)
			{
				$db->prepare
					(
						__METHOD__,
						\strtr
						(
							'
								ALTER TABLE `'.$table.'`
								'.$def.'
							',
							$definitions
						)
					)
					->run();
			}
		}
		else if (\is_callable($handlers['tables']))
		{
			$handlers['modify']($state);
		}
		
		// else: unsuported format
	}
	
	// ------------------------------------------------------------------------
	// Migration Command Interface

	/**
	 * Formatting for step information in verbose output.
	 *
	 * @var string
	 */
	protected static $lingo = ' %10s | %6s %s %s';

	/**
	 * @var \mjolnir\types\Writer
	 */
	protected $writer = null;

	/**
	 * Show debug messages?
	 *
	 * @var boolean
	 */
	protected $verbose = false;

	/**
	 * @return static
	 */
	static function instance(\mjolnir\types\Writer $writer = null, $verbose = null)
	{
		$i = parent::instance();

		$verbose !== null or $verbose = false;
		$i->verbose = $verbose;

		if ($writer === null)
		{
			$i->writer = \app\SilentWriter::instance();
		}
		else # writer != null
		{
			$i->writer = $writer;
		}

		return $i;
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
			$channels = $this->channels();
			$history = $this->history();

			// generate table list
			$config = [ 'tables' => [] ];
			foreach ($history as $i)
			{
				if ($i['hotfix'] === null)
				{
					$handlers = $channels[$i['channel']]['versions'][$i['version']];
				}
				else # hotfix
				{
					$handlers = $channels[$i['channel']]['versions'][$i['version']]['hotfixes'][$i['hotfix']];
				}

				if (isset($handlers['configure']))
				{
					$conf = $handlers['configure'];
					if (\is_array($conf))
					{
						if (isset($conf['tables']))
						{
							foreach ($conf['tables'] as $table)
							{
								$config['tables'][] = $table;
							}
						}
					}
					else # callback
					{
						$config = $conf($config);
					}
				}
			}

			if ( ! empty($config['tables']))
			{
				$db = \app\SQLDatabase::instance(static::database());

				$db->prepare
					(
						__METHOD__.':fk_keys_off',
						'SET foreign_key_checks = FALSE'
					)
					->run();

				foreach ($config['tables'] as $table)
				{
					$db->prepare
						(
							__METHOD__.':drop_table',
							'DROP TABLE IF EXISTS `'.$table.'`'
						)
						->run();
				}

				$db->prepare
					(
						__METHOD__.':fk_keys_on',
						'SET foreign_key_checks = TRUE'
					)
					->run();
			}
		}

		return true;
	}

	/**
	 * Reset the database.
	 */
	function reset($pivot = null, $version = null, $dryrun = false)
	{
		$locked = \app\CFS::config('mjolnir/base')['db:lock'];
		$exists = $this->has_pradox_table();

		if ($locked && $exists && ! $dryrun)
		{
			// operation is destructive and database is locked
			return false;
		}
		else # database is not locked
		{
			if ($pivot === null)
			{
				$channels = $this->channels();

				if ($exists)
				{
					$this->uninstall();
				}

				$status = array
					(
						// ordered list of versions in processing order
						'history' => [],
						// current version for each channel
						'state' => [],
						// active channels
						'active' => [],
						// checklist of version requirements
						'checklist' => $this->generate_checklist($channels)
					);

				// generate version history for full reset
				foreach ($channels as $channel => & $timeline)
				{
					\uksort
						(
							$timeline['versions'],
							function ($a, $b) use ($channel)
								{
									// split version
									$version1 = \explode('.', $a);
									$version2 = \explode('.', $b);

									if (\count($version1) !== 3)
									{
										throw new \app\Exception('Invalid version: '.$channel.' '.$a);
									}

									if (\count($version2) !== 3)
									{
										throw new \app\Exception('Invalid version: '.$channel.' '.$b);
									}

									if (\intval($version1[0]) - \intval($version2[0]) == 0)
									{
										if (\intval($version1[1]) - \intval($version2[1]) == 0)
										{
											return \intval($version1[2]) - \intval($version2[2]);
										}
										else # un-equal
										{
											return \intval($version1[1]) - \intval($version2[1]);
										}
									}
									else # un-equal
									{
										return \intval($version1[0]) - \intval($version2[0]);
									}
								}
						);

					if (\count($timeline['versions']) > 0)
					{
						\end($timeline['versions']);
						$last_version = \key($timeline['versions']);
						$this->processhistory($channel, $last_version, $status, $channels);
					}
				}

				// dry run?
				if ($dryrun)
				{
					// just return the step history
					return $status['history'];
				}
				
				// execute the history
				foreach ($status['history'] as $entry)
				{
					// execute migration
					$this->processmigration($channels, $entry['channel'], $entry['version'], $entry['hotfix']);					
				}
			}
			else # pivot !== null
			{
				// @todo pivot based reset
			}

			// operation complete
			return true;
		}
	}

	/**
	 * @return array history table
	 */
	function history()
	{
		if ( ! $this->has_pradox_table())
		{
			$db = \app\SQLDatabase::instance(static::database());

			return $db->prepare
				(
					__METHOD__,
					'
						SELECT entry.*
						  FROM `'.static::table().'` entry
					'
				)
				->fetch_all();
		}
		else # no database
		{
			return [];
		}
	}

	// ------------------------------------------------------------------------
	// Helpers

	/**
	 * Step information for verbose output
	 */
	protected function shout($op, $channel, $version, $note = null)
	{
		! $this->verbose or $this->writer->writef(static::$lingo, $op, $version, $channel, $note)->eol();
	}

	/**
	 * @return array
	 */
	protected function generate_checklist($channels)
	{
		$checklist = [];

		foreach ($channels as $channelname => $channelinfo)
		{
			foreach ($channelinfo['versions'] as $version => $handlers)
			{
				if (isset($handlers['require']))
				{
					foreach ($handlers['require'] as $reqchan => $reqver)
					{
						isset($checklist[$reqchan]) or $checklist[$reqchan] = [];
						isset($checklist[$reqchan][$reqver]) or $checklist[$reqchan][$reqver] = [];

						// save a copy of what channels and versions depend on
						// the specific required version so we can reference it
						// back easily in processing and satisfy those
						// requirements to avoid process order induced loops
						$checklist[$reqchan][$reqver][] = array
							(
								'channel' => $channelname,
								'version' => $version,
							);
					}
				}
			}
		}

		return $checklist;
	}

	/**
	 * @return int binary version
	 */
	protected function binversion($channel, $version)
	{
		// split version
		$v = \explode('.', $version);

		if (\count($v) !== 3)
		{
			throw new \app\Exception('Invalid version: '.$channel.' '.$version);
		}

		// 2 digits for patch versions, 3 digits for fixes
		$binversion = \intval($v[0]) * 100000 + \intval($v[1]) * 1000 + \intval($v[2]);

		if ($binversion == 0)
		{
			throw new \app\Exception('The version of 0 is reserved.');
		}

		return $binversion;
	}

	/**
	 * @return array
	 */
	protected function processhistory($channel, $target_version, array & $status, array & $channels)
	{
		$this->shout('fulfilling', $channel, $target_version);

		if ( ! isset($channels[$channel]))
		{
			throw new \app\Exception('Required channel ['.$channel.'] not available.');
		}

		if ( ! isset($channels[$channel]['versions'][$target_version]))
		{
			throw new \app\Exception('Required version ['.$target_version.'] in channel ['.$channel.'] not available.');
		}

		// recursion detection
		if (\in_array($channel, \app\Arr::gather($status['active'], 'channel')))
		{
			// provide feedback on loop
			! $this->verbose or $this->writer->eol();
			$this->writer->writef(' Loop backtrace:')->eol();
			foreach ($status['active'] as $activeinfo)
			{
				$this->writer->writef('  - '.$activeinfo['channel'].' '.$activeinfo['version'])->eol();
			}
			$this->writer->eol();

			throw new \app\Exception('Recursive dependency detected on '.$channel.' '.$target_version);
		}

		$timeline = $channels[$channel];

		if ( ! isset($status['state'][$channel]))
		{
			$status['state'][$channel] = 0;
		}

		$status['active'][] = [ 'channel' => $channel, 'version' => $target_version ];
		$targetver = $this->binversion($channel, $target_version);

		// verify state
		if ($targetver < $status['state'][$channel])
		{
			return; // version already satisfied in the timeline; skipping...
		}

		// process versions
		foreach ($timeline['versions'] as $litversion => $version)
		{
			if ($version['binversion'] <= $status['state'][$channel])
			{
				continue; // version already processed; skipping...
			}

			if (isset($version['require']) && ! empty($version['require']))
			{
				foreach ($version['require'] as $required_channel => $required_version)
				{
					if (isset($status['state'][$required_channel]))
					{
						// check if version is satisfied
						$versionbin = $this->binversion($required_channel, $required_version);
						if ($status['state'][$required_channel] == $versionbin)
						{
							continue; // dependency satisfied
						}
						else if ($status['state'][$required_channel] > $versionbin)
						{
							// the required version has been passed; since the 
							// state of the channel may change from even the 
							// smallest of changes; versions being passed is 
							// not acceptable

							$this->dependency_race_error
								(
									// the scene
									$status, 
									// the victim
									$channel, 
									$target_version
								);
						}

						// else: version is lower, pass through
					}

					$this->shout('require', $required_channel, $required_version, '>> '.$channel.' '.$litversion);
					$this->processhistory($required_channel, $required_version, $status, $channels);
				}
			}

			// requirements have been met
			$status['history'][] = array
				(
					'hotfix'  => null,
					'channel' => $channel,
					'version' => $litversion,
				);

			// update state
			$status['state'][$channel] = $version['binversion'];
			$this->shout('completed', $channel, $litversion);

			// the channel is at a new version, but before continuing to the
			// next version we need to check if any channel requirements have
			// been satisfied in the process, if they have that channel needs
			// to be bumped to this version; else we enter an unnecesary loop
			// generated by processing order--we use the checklist generated
			// at the start of the process for this purpose
			if (isset($status['checklist'][$channel]) && isset($status['checklist'][$channel][$litversion]))
			{
				foreach ($status['checklist'][$channel][$litversion] as $checkpoint)
				{
					// we skip over actively processed requirements

					$skip = false;
					foreach ($status['active'] as $active)
					{
						$active_version = $this->binversion($active['channel'], $active['version']);
						$checkpoint_version = $this->binversion($checkpoint['channel'], $checkpoint['version']);
						// we test with >= on the version because we know that
						// if a channel did require that specific version then
						// they would have initiated the process, thereby
						// rendering it impossible to cause conflict, ie.
						// requirement should have been satisfied already
						if ($active['channel'] == $checkpoint['channel'] && $active_version >= $checkpoint_version)
						{
							$skip = true;
							break;
						}
					}

					if ($skip)
					{
						$this->shout('pass:point', $checkpoint['channel'], $checkpoint['version'], '-- '.$channel.' '.$litversion);
						continue; // requested version already being processed
					}
					
					// are all requirements of given checkpoint complete? if 
					// the checkpoint starts resolving requirements of it's own
					// it's possible for it to indirectly loop back
					
					$cp = $channels[$checkpoint['channel']]['versions'][$checkpoint['version']];
					$skip_checkpoint = false;
					if (isset($cp['require']) && ! empty($cp['require']))
					{
						foreach ($cp['require'] as $required_channel => $required_version)
						{
							if (isset($status['state'][$required_channel]))
							{
								// check if version is satisfied
								$versionbin = $this->binversion($required_channel, $required_version);
								if ($status['state'][$required_channel] == $versionbin)
								{
									continue; // dependency satisfied
								}
								else if ($status['state'][$required_channel] > $versionbin)
								{
									// the required version has been passed; since the state 
									// of the channel may change from even the smallest of 
									// changes; versions being passed is not acceptable

									$this->dependency_race_error
										(
											// the scene
											$status, 
											// the victim
											$checkpoint['channel'], 
											$checkpoint['version']
										);
								}

								// else: version is lower, pass through
							}

							$skip_checkpoint = true;
						}
					}
					
					if ($skip_checkpoint)
					{
						$this->shout('hold:point', $checkpoint['channel'], $checkpoint['version'], '-- '.$channel.' '.$litversion);
						continue; // checkpoint still has unfilled requirements
					}

					$this->shout('checklist', $checkpoint['channel'], $checkpoint['version'], '<< '.$channel.' '.$litversion);
					$this->processhistory($checkpoint['channel'], $checkpoint['version'], $status, $channels);
				}
			}

			// has target version been satisfied?
			if ($targetver === $version['binversion'])
			{
				break; // completed required version
			}
		}

		// remove channel from active information
		$new_active = [];
		foreach ($status['active'] as $active)
		{
			if ($active['channel'] !== $channel)
			{
				$new_active[] = $active;
			}
		}

		$status['active'] = $new_active;
	}

	/**
	 * Error report for situation where dependencies race against each other 
	 * and a channels fall behind another in the requirement war.
	 */
	protected function dependency_race_error(array $status, $channel, $version)
	{
		// provide feedback on loop
		! $this->verbose or $this->writer->eol();
		$this->writer->writef(' Race backtrace:')->eol();
		foreach ($status['active'] as $activeinfo)
		{
			$this->writer->writef('  - '.$activeinfo['channel'].' '.$activeinfo['version'])->eol();
		}
		$this->writer->eol();

		throw new \app\Exception('Target version breached by race condition on '.$channel.' '.$version);
	}
	
	/**
	 * @return array
	 */
	protected function channels()
	{
		// load configuration
		$pdx = \app\CFS::config('mjolnir/paradox');

		// configure channels
		$channels = [];
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

			foreach ($channel as $version => & $handler)
			{
				$handler['binversion'] = $this->binversion($channelname, $version);
			}

			\uksort
				(
					$channel,
					function ($a, $b) use ($channel)
						{
							// split version
							$version1 = \explode('.', $a);
							$version2 = \explode('.', $b);

							if (\count($version1) !== 3)
							{
								throw new \app\Exception('Invalid version: '.$channel.' '.$a);
							}

							if (\count($version2) !== 3)
							{
								throw new \app\Exception('Invalid version: '.$channel.' '.$b);
							}

							if (\intval($version1[0]) - \intval($version2[0]) == 0)
							{
								if (\intval($version1[1]) - \intval($version2[1]) == 0)
								{
									return \intval($version1[2]) - \intval($version2[2]);
								}
								else # un-equal
								{
									return \intval($version1[1]) - \intval($version2[1]);
								}
							}
							else # un-equal
							{
								return \intval($version1[0]) - \intval($version2[0]);
							}
						}
				);

			// generate normalized version of channel info
			$channels[$channelname] = array
				(
					'current' => null,
					'db' => $db,
					'versions' => $channel,
				);
		}

		return $channels;
	}

	/**
	 * @return boolean
	 */
	protected function has_pradox_table()
	{
		$db = \app\SQLDatabase::instance(static::database());

		$tables = $db->prepare
			(
				__METHOD__,
				'
					SHOW TABLES LIKE :table
				'
			)
			->str(':table', static::table())
			->run()
			->fetch_all();

		return ! empty($tables);
	}
	
	/**
	 * Hook.
	 * 
	 * @return array state
	 */
	protected function initialize_migration_state(array & $channelinfo, $channel, $version, $hotfix)
	{
		return array
			(
				'channelinfo' => & $channelinfo, 
				'tables' => [],
				'identity' => array
					(
						'channel' => $channel,
						'version' => $version,
						'hotfix'  => $hotfix,
					),
				'sql' => array
					(
						'default' => array
							(
								'engine' => 'InnoDB',
								'charset' => 'utf8',
							),
					),
			);
	}
	
	/**
	 * Performs migration steps and creates entry in timeline.
	 * 
	 * To add steps add them under the configuration mjolnir/paradox-steps and 
	 * overwrite this class accordingly. See: [migration_configure] for an 
	 * example.
	 */
	protected function processmigration(array $channels, $channel, $version, $hotfix)
	{
		$this->writer->eol();
		
		$steps = \app\CFS::config('mjolnir/paradox-steps');
		
		\asort($steps);
		
		$chaninfo = $channels[$channel];
		$state = $this->initialize_migration_state($chaninfo, $channel, $version, $hotfix);
		
		foreach ($steps as $step => $priority)
		{
			$this->writer->writef(\str_repeat(' ', 78)."\r");
			
			$this->writer->writef
				(
					' %15s %-9s %s %s', 
					$step,
					$version, 
					$channel, 
					empty($hotfix) ? '' : '/ '.$hotfix
				)
				->eol();
			
			$stepmethod = "migration_$step";
			static::{$stepmethod}($chaninfo['db'], $chaninfo['versions'][$version], $state);
		}
		
		$this->writer->writef(\str_repeat(' ', 78)."\r");
		
		// @todo save to database
	}
	
} # class

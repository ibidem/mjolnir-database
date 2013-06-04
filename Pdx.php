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
							// the required version has been passed; this is
							// horrible; since the state of the channel may
							// change from even the smallest of changes
							// versions being passed is not acceptable

							// provide feedback on loop
							! $this->verbose or $this->writer->eol();
							$this->writer->writef(' Race backtrace:')->eol();
							foreach ($status['active'] as $activeinfo)
							{
								$this->writer->writef('  - '.$activeinfo['channel'].' '.$activeinfo['version'])->eol();
							}
							$this->writer->eol();

							throw new \app\Exception('Target version breached by race condition on '.$channel.' '.$target_version);
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
						$this->shout('Skipping', $checkpoint['channel'], $checkpoint['version'], '-- '.$channel.' '.$litversion);
						continue; // requested version already being processed
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

		$status['active'] = $new_active;;
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
			->run();

		return ! empty($tables);
	}

} # class

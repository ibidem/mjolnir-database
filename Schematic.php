<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Database
 * @author     Ibidem Team
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Schematic
{
	/**
	 * @var string
	 */
	protected static $channel_table = 'mjolnir__schematics';

	/**
	 * @var string table name
	 */
	static function channel_table()
	{
		return \app\CFS::config('mjolnir/database')['table_prefix'].static::$channel_table;
	}

	/**
	 * List of channels, complete with current serial version.
	 *
	 * @return array
	 */
	static function channel_list()
	{
		return \app\SQL::prepare
			(
				__METHOD__,
				'
					SELECT *
					  FROM `'.static::channel_table().'`
				',
				'mysql'
			)
			->run()
			->fetch_all();
	}

	/**
	 * Normalized configuration.
	 *
	 * @return array
	 */
	static function config()
	{
		$config = \app\CFS::config('mjolnir/schematics');

		foreach ($config['steps'] as $nominator => &$schematic)
		{
			if ( ! \preg_match('#(.*)-(.*)#', $schematic['serial']))
			{
				$schematic['serial'] .= '-default';
			}

			$schematic['channel'] = static::parse_channel($nominator);
			$schematic['class'] = static::parse_class($nominator);
		}

		return $config;
	}



	// ------------------------------------------------------------------------
	// Toolset

	/**
	 * Drop given tables. Foreign key checks will be ignored.
	 *
	 * @param ...
	 */
	static function destroy()
	{
		$args = \func_get_args();

		\app\SQL::prepare
			(
				__METHOD__.':migration_template_droptable_fkcheck',
				'SET foreign_key_checks = FALSE',
				'mysql'
			)
			->run();

		foreach ($args as $table)
		{
			\app\SQL::prepare
				(
					__METHOD__,
					'DROP TABLE IF EXISTS `'.$table.'`',
					'mysql'
				)
				->run();
		}

		\app\SQL::prepare
			(
				__METHOD__.':migration_template_reapply_table_fkcheck',
				'SET foreign_key_checks = TRUE',
				'mysql'
			)
			->run();
	}

	/**
	 * Set version with out performing migrations.
	 */
	static function set_channel_serialversion($channel, $serial)
	{
		\app\SQL::prepare
			(
				__METHOD__,
				'
					UPDATE `'.static::channel_table().'`
					   SET serial = :serial
					 WHERE channel = :channel
				',
				'mysql'
			)
			->str(':serial', $serial)
			->str(':channel', $channel)
			->run();
	}

	// ------------------------------------------------------------------------
	// Utilities

	/**
	 * Retrieve top serial for channel, given specified tag.
	 *
	 * @return string
	 */
	static function top_for_channel($channel, $tag = 'default')
	{
		$config = static::config();

		$serial_list = array();
		foreach ($config['steps'] as $schematic)
		{
			if ($schematic['channel'] === $channel)
			{
				$current = static::decompile($schematic['serial']);
				if ($current['tag'] === $tag && ! \in_array($current['version'], $serial_list))
				{
					$serial_list[] = $current['version'];
				}
			}
		}

		static::sort_serial_list($serial_list);

		return \array_pop($serial_list).'-'.$tag;
	}

	// ------------------------------------------------------------------------

	static function processor($table, $count, $callback, $reads = 1000)
	{
		$pages = ((int) ($count / $reads)) + 1;

		for ($page = 1; $page <= $pages; ++$page)
		{
			\app\SQL::begin();

			$patients = \app\SQL::prepare
				(
					__METHOD__.':read_patients',
					'
						SELECT *
						  FROM `'.$table.'`
						 LIMIT :limit OFFSET :offset
					'
				)
				->page($page, $reads)
				->run()
				->fetch_all();

			foreach ($patients as &$patient)
			{
				$callback($patient);
			}

			\app\SQL::commit();
		}
	}

	static function table($table, $schematic)
	{
		try
		{
			$schematics_config = \app\CFS::config('mjolnir/schematics');
			\app\SQL::prepare
				(
					__METHOD__,
					\strtr
						(
							'
								CREATE TABLE IF NOT EXISTS `'.$table.'`
									(
										'.$schematic.'
									)
								ENGINE=:engine
								DEFAULT CHARSET=:default_charset
							',
							$schematics_config['definitions']
						),
					'mysql'
				)
				->run();
		}
		catch (\Exception $e)
		{
			if (\php_sapi_name() === 'cli')
			{
				echo PHP_EOL.PHP_EOL.' Schematic: '.PHP_EOL;
				echo \strtr
					(
						\app\Text::baseindent($schematic),
						$schematics_config['definitions']
					);

				echo PHP_EOL.PHP_EOL;
			}

			throw $e;
		}
	}

	static function alter($table, $updates)
	{
		$schematics_config = \app\CFS::config('mjolnir/schematics');

		\app\SQL::prepare
			(
				__METHOD__,
				\strtr
					(
						'
							ALTER TABLE `'.$table.'`
							'.$updates.'
						',
						$schematics_config['definitions']
					),
				'mysql'
			)
			->run();
	}

	/**
	 * Definition format: Table => [ FkTable, On DELETE, On UPDATE ],
	 */
	static function constraints(array $defintions)
	{
		foreach ($defintions as $table => $constraints)
		{
			$query = "ALTER TABLE `".$table."` ";

			$idx = 0;
			$count = \count($constraints);
			foreach ($constraints as $key => $constraint)
			{
				++$idx;
				$query .=
					"
						ADD CONSTRAINT `".$table."_mjolnirfk_".$idx."`
						   FOREIGN KEY (`".$key."`)
							REFERENCES `".$constraint[0]."` (`id`)
							 ON DELETE ".$constraint[1]."
							 ON UPDATE ".$constraint[2]."
					";

				if ($idx < $count)
				{
					$query .= ', ';
				}
				else # last element
				{
					$query .= ';';
				}
			}

			try
			{
				\app\SQL::prepare(__METHOD__, $query, 'mysql')->run();
			}
			catch (\Exception $e)
			{
				if (\php_sapi_name() === 'cli')
				{
					echo PHP_EOL.PHP_EOL.' Query: '.PHP_EOL;
					echo \app\Text::baseindent($query);
					echo PHP_EOL.PHP_EOL;
				}

				throw $e;
			}
		}
	}







	static function channels()
	{
		$channel_list = static::channel_list();
		$channels = array();

		foreach ($channel_list as $entry)
		{
			$channels[] = $entry['channel'];
		}

		return $channels;
	}

	static function decompile($version)
	{
		if ( ! \preg_match('#^(.*)-(.*)$#', $version, $matches))
		{
			return array
				(
					'serial' => $version,
					'version' => $version,
					'tag' => 'default'
				);
		}
		else # we got match on #(.*)-(.*)#
		{
			return array
				(
					'serial' => $version,
					'version' => $matches[1],
					'tag' => $matches[2]
				);
		}
	}

	static function serial_trail($channel, $start_serial, $end_serial)
	{
		$config = static::config();

		$start = static::decompile($start_serial);
		$end = static::decompile($end_serial);

		if ($start['tag'] !== $end['tag'])
		{
			throw new \app\Exception
				('Implied jump in serialization from ['.$start_serial.'] to ['.$end_serial.'].');
		}

		$serial_list = [];
		foreach ($config['steps'] as $schematic)
		{
			if ($schematic['channel'] === $channel)
			{
				$current = static::decompile($schematic['serial']);
				if (static::in_interval($current, $start, $end))
				{
					if ( ! \in_array($schematic['serial'], $serial_list))
					{
						$serial_list[] = $schematic['serial'];
					}
				}
			}
		}

		static::sort_serial_list($serial_list);

		return $serial_list;
	}

	private static function in_interval($current, $start, $end)
	{
		return $current['tag'] === $start['tag']
			&& static::compare_serials($current['serial'], $start['serial']) === 1
			&& static::compare_serials($current['serial'], $end['serial']) !== 1
			;

	}

	static function compare_serials($a, $b)
	{
		if ($a === $b)
		{
			return 0;
		}

		$a_versions = \explode(':', \preg_replace('#-.*$#', '', $a));
		$b_versions = \explode(':', \preg_replace('#-.*$#', '', $b));

		$x = (int) \array_shift($a_versions);
		$y = (int) \array_shift($b_versions);

		if ($x === $y)
		{

			\reset($a_versions);
			\reset($b_versions);

			while (($x = \current($a_versions)) !== false && ($y = \current($b_versions)) !== false)
			{
				$x = (int) $x;
				$y = (int) $y;

				if ($x < $y)
				{
					// if x < y then y has higher priority; therefore comes
					// before x and thus x comes after
					return +1;
				}
				else if ($x > $y)
				{
					// if x > y then y has lower priority; therefore comes
					// after x and thus x comes before
					return -1;
				}

				\next($a_versions);
				\next($b_versions);
			}

			// if we didn't hit any priorities before one finished then the
			// longest wins since the shorter has implied priority 0
			if (\count($a_versions) > \count($b_versions))
			{
				return +1;
			}
			else # size(a) <= size(b)
			{
				return -1;
			}
		}
		else if ($x < $y)
		{
			// version go lowest value first
			return -1;
		}
		else # x > y
		{
			// version go lowest value first
			return +1;
		}
	}

	static function sort_serial_list(&$list)
	{
		// order list
		if (\count($list) > 1)
		{
			\usort($list, array('\app\Schematic', 'compare_serials'));
		}
	}

	static function migrations_for($serial, $channel = 'default')
	{
		$config = static::config();
		$migrations = array();
		foreach ($config['steps'] as $nominator => $schematic)
		{
			if ($schematic['channel'] === $channel && $schematic['serial'] === $serial)
			{
				$migration = \call_user_func([ $schematic['class'], 'instance' ]);
				$migrations[] = array
					(
						'object' => $migration,
						'nominator' => $nominator,
					);
			}
		}

		return $migrations;
	}





	static function get_serial_for($channel)
	{
		$list = static::channel_list();
		foreach ($list as $entry)
		{
			if ($entry['channel'] === $channel)
			{
				return $entry['serial'];
			}
		}

		return null;
	}

	static function update_channel_serial($channel, $serial)
	{
		\app\SQL::prepare
			(
				__METHOD__,
				'
					UPDATE `'.static::channel_table().'`
					   SET serial = :serial
					 WHERE channel = :channel
				',
				'mysql'
			)
			->str(':channel', $channel)
			->str(':serial', $serial)
			->run();
	}

	// ------------------------------------------------------------------------
	// Helpers

	/**
	 * Retrieve channel from nominator.
	 *
	 * @return string
	 */
	static function parse_channel($nominator)
	{
		if (\preg_match('#^(.*):#', $nominator, $matches))
		{
			return $matches[1];
		}

		// we return default
		return 'default';
	}

	/**
	 * Retrieve class from nominator.
	 *
	 * @return string
	 */
	static function parse_class($nominator)
	{
		$channel = static::parse_channel($nominator);

		\preg_match('#^(.*:)?(.*)$#', $nominator, $matches);

		$class_parts = \explode('-', $matches[2]);
		$channel = \app\Arr::implode('_', \explode('-', $channel), function ($i, $v) {
			return \ucfirst($v);
		});

		\array_unshift($class_parts, 'Schematic', $channel);

		return '\app\\'.\app\Arr::implode('_', $class_parts, function ($k, $value) {
			return \ucfirst($value);
		});
	}

} # class

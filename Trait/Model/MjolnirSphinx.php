<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Library
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
trait Trait_Model_MjolnirSphinx
{
	/**
	 * @return string
	 */
	static function sph_name()
	{
		return static::table();
	}
	
	/**
	 * @return array
	 */
	static function sph_config()
	{
		return \app\CFS::config('mjolnir/sphinx')['default.src.config'];
	}
	
	/**
	 * @return string source for the given table
	 */
	static function sph_source()
	{
		$config = static::sph_config();
		
		$sph_source = '';
		
		foreach ($config as $key => $value)
		{
			$sph_source .= "\t".\sprintf('%-10s', $key)." = $value\n";
		}
		
		$sph_source
			.= "\n"
			. "\tsql_query = \\\n"
			. "\t\tSELECT "
			;
		
		// compute select fields
		$fields = [static::unique_key() => static::unique_key()];
		
		if (isset(static::$sph_fields))
		{
			// we must gurantee id field remains first
			foreach (static::$sph_fields as $key => $value)
			{
				$fields[$key] = $value;
			}
		}
		else # no fields defined; defaulting to all string fields and id
		{
			$fields[static::unique_key()] = static::unique_key();
			
			$fieldlist = static::fieldlist();

			if (isset($fieldlist['string']))
			{
				foreach (static::fieldlist()['string'] as $field)
				{
					$fields[$field] = $field;
				}
			}
		}
		
		$source_fields = \app\Arr::implode(', ', $fields, function ($key, $definition) {
			if ($key === $definition)
			{
				return '`'.$definition.'`';
			}
			else # key !== definition
			{
				return '`'.$definition.'` AS `'.$key.'`';
			}
			
		});
		
		$sph_source
			.= $source_fields." \\\n"
			. "\t\tFROM `".static::table()."`\n\n"
			. "\tsql_query_info = SELECT * FROM `".static::table()."` WHERE `".static::unique_key()."` = \$".static::unique_key()."\n"
			;	
		
		return $sph_source;
	}

	/**
	 * @return array entries
	 */
	static function sph_entries($search, $page, $limit, $offset = 0, $order = [], $constraints = [])
	{
		return static::statement
			(
				__METHOD__,
				'
					SELECT *
					  FROM `'.static::sph_name().'`
				     WHERE MATCH(:search)
				'
			)
			->set(':search', $search)
			->fetch_all();
	}
	
} # trait

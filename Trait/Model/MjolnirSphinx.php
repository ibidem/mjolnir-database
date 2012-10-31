<?php namespace mjolnir\database;

require_once \app\CFS::dir('vendor/sphinx').'sphinxapi'.EXT;

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
			if (\is_array($value))
			{
				foreach ($value as $subvalue)
				{
					$sph_source .= "\t".\sprintf('%-13s', $key)." = {$subvalue}\n";
				}	
			}
			else # non array
			{
				$sph_source .= "\t".\sprintf('%-13s', $key)." = $value\n";
			}
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
	static function sph_entries($search, $page, $limit, $offset = 0, $order = [])
	{
		$config = \app\CFS::config('mjolnir/sphinx');
		
		try
		{
			$sphinx = new \SphinxClient();
			$sphinx->SetServer($config['searchd']['host'], $config['searchd']['listen']['api']);
			$sphinx->SetLimits($offset + ($page - 1) * $limit, $limit);
			$sphinx->SetMatchMode(SPH_MATCH_ANY);
			$sphinx->SetSelect('*');
			$sphinx->SetSortMode(SPH_SORT_RELEVANCE);

			$result = $sphinx->Query($search, static::sph_name());
		}
		catch (\Exception $exception)
		{
			\mjolnir\log_exception($exception);
			
			// potential failed connection
			return [];
		}
		
		if (empty($result['error']))
		{
			\var_dump($result['matches']); die;
			return empty($result['matches']) ? [] : $result['matches'];	
		}
		else # got error
		{
			throw new \Exception('Sphinx: '.$result['error']);
		}
	}
	
} # trait

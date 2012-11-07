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
	 * @return boolean
	 */
	static function sph_is_rtindex()
	{
		if (isset(static::$sph_rt_index))
		{
			return static::$sph_rt_index;
		}
		else # default to false
		{
			return false;
		}
	}
	
	/**
	 * @return string source
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
			;
		
		// attributes
		if (isset(static::$sph_attributes) && static::$sph_attributes !== null)
		{
			foreach (static::$sph_attributes as $attr => $type)
			{
				$sph_source
					.= "\tsql_attr_{$type} = {$attr}\n"
					;
			}
			$sph_source .= "\n";
		}
		
		$sph_source
			.= "\tsql_query_info = SELECT * FROM `".static::table()
			. "` WHERE `".static::unique_key()."` = \$".static::unique_key()."\n"
			;	
		
		return $sph_source;
	}
	
	/**
	 * @return string index rt_ field configuration
	 */
	static function sph_rtindex()
	{
		$sph_index = '';
		
		$fieldlist = static::fieldlist();
		
		if ( ! isset($fieldlist['string']))
		{
			$string_fields = [];
		}
		else # string fields set
		{
			$string_fields = $fieldlist['string'];
		}
		
		foreach (static::$sph_fields as $field => $value)
		{
			if (\in_array($field, $string_fields))
			{
				$sph_index .= "\trt_field = $field\n";
			}
		}
		
		// attributes
		if (isset(static::$sph_attributes) && static::$sph_attributes !== null)
		{
			foreach (static::$sph_attributes as $attr => $type)
			{
				$sph_index
					.= "\trt_attr_{$type} = {$attr}\n"
					;
			}
			$sph_index .= "\n";
		}
		
		return $sph_index;
	}

	/**
	 * @return array entries
	 */
	static function sph_entries($search, $page, $limit, $offset = 0, array $attributes = null)
	{
		$search = \trim($search);
		if (empty($search))
		{
			return [];
		}
		
		try
		{
			$sphinx = \app\Sphinx::instance()
				->page($page, $limit, $offset);
			
			// process attributes
			if ($attributes !== null)
			{
				foreach ($attributes as $attr => $attr_value)
				{
					$sphinx->set_filter($attr, $attr_value);
				}
			}
			
			$ids = $sphinx->fetch_ids($search, static::sph_name());
			
			return static::select_entries($ids);
		}
		catch (\Exception $exception)
		{
			\mjolnir\log_exception($exception);
			
			// potential failed connection
			return [];
		}
	}
	
} # trait

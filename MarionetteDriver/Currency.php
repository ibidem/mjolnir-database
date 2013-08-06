<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   MarionetteDriver
 * @author     Ibidem Team
 * @copyright  (c) 2013, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class MarionetteDriver_Currency extends \app\Instantiatable implements \mjolnir\types\MarionetteDriver
{
	use \app\Trait_MarionetteDriver;

	/**
	 * @var boolean
	 */
	protected $patched = false;

	/**
	 * On POST, resolve input dependencies (happens before validation).
	 *
	 * @return array updated entry
	 */
	function post_compile(array $input)
	{
		$conf = $this->config;
		$field = $this->field;

		if (empty($input[$field]))
		{
			$input[$field.'_value'] = 0.00;
			$input[$field.'_type'] = 'USD';
		}
		else # got entry
		{
			$input[$field.'_value'] = $input[$field]['value'];
			$input[$field.'_type'] = ! empty($input[$field]['type']) ? $input[$field]['type'] : 'USD';
		}

		unset($input[$field]);
		return $input;
	}

	/**
	 * On POST, field processing before POST database communication.
	 *
	 * @return array updated fieldlist
	 */
	function post_compilefields(array $fieldlist)
	{
		$fieldlist['nums'][] = $this->field.'_value';
		$fieldlist['strs'][] = $this->field.'_type';

		return $fieldlist;
	}

	/**
	 * On PATCH, resolve input dependencies (happens before validation).
	 *
	 * @return array updated entry
	 */
	function patch_compile($id, array $input)
	{
		if (isset($input[$this->field]))
		{
			$this->patched = true;
			return $this->post_compile($input);
		}

		return $input;
	}

	/**
	 * On PATCH, field processing before database communication.
	 *
	 * @return array updated fieldlist
	 */
	function patch_compilefields(array $fieldlist)
	{
		if ($this->patched)
		{
			$fieldlist['nums'][] = $this->field.'_value';
			$fieldlist['strs'][] = $this->field.'_type';
		}

		return $fieldlist;
	}

	/**
	 * On GET, manipulate execution plan.
	 *
	 * @return array updated execution plan
	 */
	function inject(array $plan)
	{
		$field = $this->field;

		$plan['fields'][] = $field.'_type';
		$plan['fields'][] = $field.'_value';

		$plan['postprocessors'][] = function ($entry) use ($field)
			{
				$entry[$field] = array
					(
						'value' => $entry[$field.'_value'],
						'type' => $entry[$field.'_type'],
					);

				unset($entry[$field.'_value']);
				unset($entry[$field.'_type']);

				return $entry;
			};

		return $plan;
	}

} # class

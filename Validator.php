<?php namespace mjolnir\database;

/**
 * @package    mjolnir
 * @category   Base
 * @author     Ibidem Team
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Validator extends \app\Instantiatable implements \mjolnir\types\Validator
{
	use \app\Trait_Validator;

	/**
	 * @return \mjolnir\database\Validator
	 */
	static function instance(array $fields = null)
	{
		$instance = parent::instance();
		$fields === null or $instance->fields_array($fields);

		return $instance;
	}

	/**
	 * A field will be tested against a claim and validated by the proof, or if
	 * the proof is null the claim will provide the proof itself.
	 *s
	 * Field may be an array, and proof may be a function.
	 *
	 * eg.
	 *
	 *	   // check a password is not empty
	 *     $validator->rule('password', 'not_empty');
	 *
	 *     // check both a password and title are not empty
	 *	   $validator->rule(['title', 'password'], 'not_empty');
	 *
	 *     // check a title is unique; two equivalent methods
	 *     $validator->rule('title', 'valid', ! static::exists($field['title'], 'title', $context));
	 *     $validator->test('title', ! static::exists($field['title'], 'title', $context));
	 *
	 *     // check multiple fields
	 *     $validator->rule
	 *         (
	 *             ['given_name', 'family_name'],
	 *             function ($fields, $field)) use ($context)
	 *             {
	 *                 return ! static::exists($field[$field], $field, $context);
	 *             }
	 *         );
	 *
	 *
	 * @return static $this
	 */
	function rule($field, $claim, $proof = null)
	{
		if (\is_array($field))
		{
			foreach ($field as $fieldname)
			{
				$this->rule($fieldname, $claim, $proof);
			}
		}
		else # field is non-array
		{
			if ($proof === null)
			{
				$rules = \app\CFS::config('mjolnir/validator')['rules'];
				$rules[$claim]($this->fields, $field);
			}
			else if (\is_bool($proof))
			{
				if ( ! $proof)
				{
					$this->adderror($field, $claim);
				}
			}
			else # callback
			{
				if ( ! $proof($this->fields, $field))
				{
					$this->adderror($field, $claim);
				}
			}
		}

		return $this;
	}

} # class

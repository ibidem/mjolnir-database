<?php namespace ibidem\database;

/**
 * @package    ibidem
 * @category   Base
 * @author     Ibidem
 * @copyright  (c) 2012, Ibidem Team
 * @license    https://github.com/ibidem/ibidem/blob/master/LICENSE.md
 */
class Validator extends \app\Instantiatable
{
	/**
	 * @var array 
	 */
	protected $fields;
	
	/**
	 * @var array
	 */
	protected $errors;
	
	/**
	 * @var array 
	 */
	protected $rules;
	
	/**
	 * @var array
	 */
	protected $extra_errors = [];

	/**
	 * @var array
	 */
	protected $not_rules = [];

	/**
	 * @var string or null
	 */
	protected $rules_class = null;
	
	/**
	 * @var array 
	 */
	protected $errors_cache = null;
	
	/**
	 * Extras specifies a class which will be used to resolve colon rules. 
	 * Typically this is the model class, so that :exists, etc can resolve to
	 * exists of the model.
	 */
	function extras($rules_class)
	{
		$this->rules_class = $rule_class;
	}

	/**
	 * Recieves a boolean value as check. If the check is true it sets the
	 * error `$error` for the field `$field`.
	 *
	 * @return \app\Validator $this
	 */
	function test($field, $error, $check)
	{
		if ($check)
		{
			$this->extra_errors[] = [$field, $error];
		}

		return $this;
	}

	/**
	 * @return \app\Validator $this
	 */
	function not($args)
	{
		$args = \func_get_args();
		
		$this->not_rules[] = $args;

		return $this;
	}

	/**
	 * @return \app\Validator $this
	 */
	function ruleset($check, array $fields)
	{
		foreach ($fields as $field)
		{
			$this->rule($field, $check);
		}

		return $this;
	}
	
	/**
	 * @param string config with errors (should contain "errors" on route)
	 * @param array fields
	 * @return \ibidem\base\Validator 
	 */
	static function instance(array $messages = null, array $fields = null)
	{
		$instance = parent::instance();
		$instance->rules = array();
		
		if ($messages === null)
		{
			$instance->$messages(array());
		}
		else # errors not null
		{
			$instance->messages($messages);
		}
		
		if ($fields === null)
		{
			$instance->fields(array());
		}
		else # errors not null
		{
			$instance->fields($fields);
		}
		
		return $instance;
	}
	
	/**
	 * @param array fields
	 * @return \ibidem\base\Validator $this
	 */
	function fields(array $fields)
	{
		$this->fields = $fields;
		return $this;
	}
	
	/**
	 * @param array errors
	 * @return \ibidem\base\Validator $this
	 */
	function messages(array $errors)
	{
		$this->errors = $errors;
		return $this;
	}
	
	/**
	 * @param array $args
	 * @return \ibidem\base\Validator $this
	 */
	function rule($args)
	{
		$args = \func_get_args();
		
		$this->rules[] = $args;

		return $this;
	}
	
	/**
	 * @return array|null array of errors on failure, null on success
	 */
	function errors()
	{
		// calculated?
		if ($this->errors_cache === null)
		{
			$this->errors_cache = array();
			foreach ($this->rules as $args)
			{
				$field = \array_shift($args);
				
				if ( ! isset($this->fields[$field]))
				{
					throw new \app\Exception_NotAllowed
						('Inconsistent fields passed to validation. Missing field: '.$field);
				}
				
				$callback = \array_shift($args);
				\array_unshift($args, $this->fields[$field]);

				if (\strpos($callback, '::') === false)
				{
					// default to generic rule set
					$callfunc = '\app\ValidatorRules::'.$callback;
				}
				else if (\strpos($callback, ':') === false)
				{
					// model rule set
					$callfunc = $this->rules_class.'::'.$callback;
				}
				else
				{
					$callfunc = $callback;
				}

				if ( ! \call_user_func_array($callfunc, $args))
				{
					// gurantee error field exists as an array
					isset($this->errors_cache[$field]) or $this->errors_cache[$field] = array();
					
					if ( ! isset($this->errors[$field][$callback]))
					{
						// try to use general ruleset
						$general_errors = \app\CFS::config('ibidem/general-errors');
						if (isset($general_errors[$callback]))
						{
							// get the general message
							$this->errors_cache[$field][$callback] = $general_errors[$callback];
						}
						else # not a general rule
						{
							// generic rules won't work since everything will just look
							// wrong if we print the same message two or three times as
							// a consequence of the user getting several things wrong 
							// for the same field
							throw new \app\Exception_NotFound
								("Missing error message for when [$field] fails [$callback].");
						}
					}
					else # errors are set
					{
						$this->errors_cache[$field][$callback] = $this->errors[$field][$callback];
					}

					
					// add errors based on error field
					//$this->errors_cache[$field][$callback] = $errors[$field][$callback];
				}
			}
			
			if ( ! empty($this->extra_errors))
			{
				foreach ($this->extra_errors as $errors)
				{
					list($field, $callback) = $errors;
					
					if ( ! isset($this->errors[$field][$callback]))
					{
						// try to use general ruleset
						$general_errors = \app\CFS::config('ibidem/general-errors');
						if (isset($general_errors[$callback]))
						{
							// get the general message
							$this->errors_cache[$field][$callback] = $general_errors[$callback];
						}
						else # not a general rule
						{
							// generic rules won't work since everything will just look
							// wrong if we print the same message two or three times as
							// a consequence of the user getting several things wrong 
							// for the same field
							throw new \app\Exception_NotFound
								("Missing error message for when [$field] fails [$callback].");
						}
					}
					else # errors are set
					{
						$this->errors_cache[$field][$callback] = $this->errors[$field][$callback];
					}
				}
			}
		}
		
		// return null if no errors or array with error messages
		return empty($this->errors_cache) ? null : $this->errors_cache;
	}
	
	/**
	 * This method is designed for unit testing.
	 * 
	 * @return array 
	 */
	function all_errors()
	{
		// calculated?
		$errors = array();
		foreach ($this->rules as $args)
		{
			$field = \array_shift($args);

			if ( ! isset($this->fields[$field]))
			{
				throw new \app\Exception_NotAllowed
					('Inconsistent fields passed to validation. Missing field: '.$field);
			}

			$callback = \array_shift($args);
			\array_unshift($args, $this->fields[$field]);

			if (\strpos($callback, '::') === false)
			{
				// default to generic rule set
				$callfunc = '\app\ValidatorRules::'.$callback;
			}
			else
			{
				$callfunc = $callback;
			}

			// gurantee error field exists as an array
			isset($errors[$field]) or $errors[$field] = array();

			isset($this->errors[$field]) or $this->errors[$field] = array();

			if ( ! isset($this->errors[$field][$callback]))
			{
				// try to use general ruleset
				$general_errors = \app\CFS::config('ibidem/general-errors');
				if (isset($general_errors[$callback]))
				{
					// get the general message
					$errors[$field][$callback] = $general_errors[$callback];
				}
				else # not a general rule
				{
					// generic rules won't work since everything will just look
					// wrong if we print the same message two or three times as
					// a consequence of the user getting several things wrong 
					// for the same field
					throw new \app\Exception_NotFound
						("Missing error message for when [$field] fails [$callback].");
				}
			}
			else # callback is defined
			{
				// add errors based on error field
				$errors[$field][$callback] = $this->errors[$field][$callback];
			}

			// check if rule is callable
			$class_method = \explode('::', $callback);
			if (\count($class_method) == 1) 
			{
				\array_unshift($class_method, '\app\ValidatorRules');
			}
			
			if ( ! \method_exists($class_method[0], $class_method[1]))
			{
				throw new \app\Exception_NotApplicable
					('The method ['.$callback.'] is not defined.');
			}
		}
		
		return $errors;
	}

} # class

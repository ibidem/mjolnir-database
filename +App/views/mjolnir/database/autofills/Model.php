	use \app\Trait_Model_Factory;
	use \app\Trait_Model_Utilities;
	use \app\Trait_Model_Collection;
	use \app\Trait_Model_Automaton;

	/**
	 * @var string
	 */
	protected static $table = 'table_name';

	/**
	 * @var array
	 */
	protected static $fieldformat = [];

	/**
	 * @var array
	 */
	protected static $automaton = array
		(
			'fields' => [ 'title' ],
			'unique' => [ 'title' ],
			'errors' => array
				(
					'title' => [ ':unique' => 'Entry with the same title already exists.' ],
				)
		);

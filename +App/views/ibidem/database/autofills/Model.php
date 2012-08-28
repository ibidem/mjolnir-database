	# last_inserted_id, table, push, update, update_check
	use \app\Trait_Model_Factory;
	# stash, statement, snatch, inserter, updater
	use \app\Trait_Model_Utilities;
	# entries, entry, find, find_entry, clear_entry_cache, delete, count, exists
	use \app\Trait_Model_Collection;
	# check, process, update_process
	use \app\Trait_Model_Automaton;
	
	/**
	 * @var array
	 */
	protected static $table = 'table_name';
	
	/**
	 * @var array
	 */
	protected static $timers = ['change'];
	
	/**
	 * @var array
	 */
	protected static $field_format = [];
	
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

	function down()
	{
		\app\Schematic::destroy
			(
//				\app\Model_Example::table()
			);
	}
	
	function up()
	{
//		\app\Schematic::table
//			(
//				\app\Model_Example::table(),
//				'
//					`id`    :key_primary,
//					`user`  :key_foreign,
//					`title` :title,
//					
//					PRIMARY KEY (`id`)
//				'
//			);
	}
	
	function move()
	{
		// empty
	}
	
	function bind()
	{
//		\app\Schematic::constraints
//			(
//				[
//					\app\Model_Example::table() => array
//						(
//							'user' => [\app\Model_User::table(), 'SET NULL', 'CASCADE'],
//						),
//				]
//			);
	}
	
	function build()
	{
		// empty
	}

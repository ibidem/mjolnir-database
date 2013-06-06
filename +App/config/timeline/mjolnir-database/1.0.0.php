<?php require array
	(
		// please place 'require' inside the main paradox configuration file
		// this file should only contain information required by the operation

		'description' => 'Install for Register.',

		'configure' => array
			(
				'tables' => array
					(
						\app\Register::table(),
					),
			),

		'tables' => array
			(
				\app\Register::table() =>
					'
						`key`   :title,
						`value` :block,

						PRIMARY KEY (`key`)
					',
			),

		// no `bindings` required

		'populate' => function (\mjolnir\types\SQLDatabase $db, array $state)
			{
				// populate register
				$key = null;
				$value = null;
				$statement = $db->prepare
					(
						__METHOD__,
						'
							INSERT INTO `'.\app\Register::table().'`
							(`key`, `value`) VALUES (:key, :value)
						',
						'mysql'
					)
					->bindstr(':key', $key)
					->bindstr(':value', $value);

				foreach (\app\CFS::configfile('mjolnir/register')['keys'] as $target => $default)
				{
					$key = $target;
					$value = $default;
					$statement->run();
				}
			},

	); # config
<?php namespace mjolnir\theme;

return array
	(
		'mjolnir\database' => array
			(
				 'extension=php_pdo_mysql' => function ()
					{
						if (\extension_loaded('pdo_mysql'))
						{
							return 'satisfied';
						}

						return 'failed';
					},
				'database keys' => function ()
					{
						$database = \app\CFS::config('mjolnir/database')['databases']['default'];
						if ($database['connection']['username'] !== null && $database['connection']['password'] !== null)
						{
							return 'satisfied';
						}

						return 'error';
					}
			),
	);

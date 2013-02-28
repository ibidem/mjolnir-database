<?php namespace mjolnir\theme;

return array
	(
		'mjolnir\theme' => array
			(
				 'extension=php_pdo_mysql' => function ()
					{
						if (\extension_loaded('pdo_mysql'))
						{
							return 'available';
						}

						return 'failed';
					}
			),
	);

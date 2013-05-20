<?php return array
	(
		'pdx:reset' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Reset database. Latest version if no version is provided.',
						'When resetting to a specific version you must provide the pivot '.
						'channel to be used when determining which channels need to be '.
						'at which version for the migration.'
					),
				'flags' => array
					(
						'version' => array
							(
								'type' => 'text',
								'description' => 'Channel version; pivot channel must also be provided.',
								'short' => 'v',
								'default' => false,
							),
						'app' => array
							(
								'type' => 'text',
								'description' => 'Pivot channel, should be main application channel.',
								'short' => 'a',
								'default' => false,
							),
					),
			),
		'pdx:uninstall' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Uninstalls all database.',
					),
				'flags' => array
					(
						// empty
					),
			),
		'pdx:upgrade' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Upgrades all channels to latest version.',
					),
				'flags' => array
					(
						// empty
					),
			),
		'pdx:status' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Displays version information.',
					),
				'flags' => array
					(
						// empty
					),
			),
		'db:sphinx' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Provides helpers for working with sphinx.'
					),
				'flags' => array
					(
						'regenerate' => array
							(
								'description' => 'Regenerate sphinx configuration file.',
								'short' => 'r',
							),
					),
			)
	);

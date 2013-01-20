<?php return array
	(
		'make:schematic' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Create a schematic class.'
					),
				'flags' => array
					(
						'schematic' => array
							(
								'description' => 'The schematic key as mentioned in the configuration.',
								'short' => 's',
								'type' => 'text',
							),
						'namespace' => array
							(
								'description' => 'Namespace in which to place class.',
								'short' => 'n',
								'type' => 'text',
							),
						'forced' => array
							(
								'description' => 'Overwrites output file(s).',
							),
					),
			),
		'db:init' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Initialize database schematic.'
					),
				'flags' => array
					(
						'uninstall' => array
							(
								'description' => 'Uninstalls serial/channel tables.',
								'short' => 'u',
								'default' => false,
							),
						'forced' => array
							(
								'description' => 'Force operations.',
								'short' => 'f',
								'default' => false,
							),
					),
			),
		'db:install' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Cleans up database and re-installs channels.'
					),
				'flags' => array
					(
						'channel' => array
							(
								'type' => 'text',
								'description' => 'Specified a channel when setting version.',
								'short' => 'c',
								'default' => false,
							),
						'all' => array
							(
								'description' => 'Processes all channels.',
								'short' => 'a'
							),
						'show-order' => array
							(
								'description' => 'Show order in which channels are processed.',
								'short' => 's'
							),
					),
			),
		'db:upgrade' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Upgrade to new serial version. (Auto-detects current.)'
					),
				'flags' => array
					(
						'channel' => array
							(
								'type' => 'text',
								'description' => 'Specified a channel when setting version.',
								'short' => 'c',
								'default' => false,
							),

						'all' => array
							(
								'description' => 'Processes all channels.',
								'short' => 'a'
							),
					),
			),
		'db:reset' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Reset database to a specified serial version.'
					),
				'flags' => array
					(
						'channel' => array
							(
								'type' => 'text',
								'description' => 'Specified a channel when setting version.',
								'short' => 'c',
								'default' => false,
							),
						'serial' => array
							(
								'type' => 'text',
								'description' => 'Specified a channel when setting version.',
								'short' => 'v',
							),
						'forced' => array
							(
								'description' => 'Forces reset even when database is not clean.',
								'short' => 'f',
							),
					),
			),
		'db:uninstall' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Resets database to 0:0.'
					),
				'flags' => array
					(
						'channel' => array
							(
								'type' => 'text',
								'description' => 'Specified a channel when setting version.',
								'short' => 'c',
								'default' => false,
							),
						'all' => array
							(
								'description' => 'Processes all channels.',
								'short' => 'a'
							),
					),
			),
		'db:version' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Shows version numbers for channels.'
					),
				'flags' => array
					(
						'force-set' => array
							(
								'type' => 'text',
								'description' => 'Set the version to a specified serial.',
								'default' => false,
							),
						'channel' => array
							(
								'type' => 'text',
								'description' => 'Specified a channel when setting version.',
								'short' => 'c',
								'default' => 'default',
							)
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

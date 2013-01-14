<?php return array
	(
		'db:schematic' => array
			(
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
								'type' => '\ibidem\base\Flags::text',
							),
						'namespace' => array
							(
								'description' => 'Namespace in which to place class.',
								'short' => 'n',
								'type' => '\ibidem\base\Flags::text',
							),
						'forced' => array
							(
								'description' => 'Overwrites output file(s).',
							),
					),
			),
		'db:init' => array
			(
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
							)
					),
			),
		'db:upgrade' => array
			(
				'description' => array
					(
						'Upgrade to new serial version.'
					),
				'flags' => array
					(
						'channel' => array
							(
								'type' => '\ibidem\base\Flags::text',
								'description' => 'Specified a channel when setting version.',
								'short' => 'c',
								'default' => 'default',
							),
					),
			),
		'db:jump' => array
			(
				'description' => array
					(
						'Jump to new serial version (cross-channel, downgrades, etc).'
					),
				'flags' => array
					(
						// none
					),
			),
		'db:reset' => array
			(
				'description' => array
					(
						'Reset database to a specified serial version (data loss!).'
					),
				'flags' => array
					(
						'channel' => array
							(
								'type' => '\ibidem\base\Flags::text',
								'description' => 'Specified a channel when setting version.',
								'short' => 'c',
								'default' => 'default',
							),
						'serial' => array
							(
								'type' => '\ibidem\base\Flags::text',
								'description' => 'Specified a channel when setting version.',
								'short' => 'v',
							)
							
					),
			),
		'db:install' => array
			(
				'description' => array
					(
						'Resets database to latest serial version.'
					),
				'flags' => array
					(
						'channel' => array
							(
								'type' => '\ibidem\base\Flags::text',
								'description' => 'Specified a channel when setting version.',
								'short' => 'c',
								'default' => 'default',
							),
					),
			),
		'db:uninstall' => array
			(
				'description' => array
					(
						'Resets database to 0:0'
					),
				'flags' => array
					(
						'channel' => array
							(
								'type' => '\ibidem\base\Flags::text',
								'description' => 'Specified a channel when setting version.',
								'short' => 'c',
								'default' => false,
							)
					),
			),
		'db:backup' => array
			(
				'description' => array
					(
						'Creates a backup for the current database.'
					),
				'flags' => array
					(
						// none
					),
			),
		'db:restore' => array
			(
				'description' => array
					(
						'Restores a saved backup.'
					),
				'flags' => array
					(
						// none
					),
			),
		'db:version' => array
			(
				'description' => array
					(
						'Shows version.'
					),
				'flags' => array
					(
						'force-set' => array
							(
								'type' => '\ibidem\base\Flags::text',
								'description' => 'Set the version to a specified serial.',
								'default' => false,
							),
						'channel' => array
							(
								'type' => '\ibidem\base\Flags::text',
								'description' => 'Specified a channel when setting version.',
								'short' => 'c',
								'default' => 'default',
							)
					),
			),
	);

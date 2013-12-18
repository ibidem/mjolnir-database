<?php return array
	(
		'pdx:reset' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Reset/Install database.',
						'Latest version if no version is provided.',
						'When resetting to a specific version you must provide the pivot '.
						'channel to be used when determining which channels needs to be '.
						'at which version for the migration.'
					),
				'flags' => array
					(
						'version' => array
							(
								'type' => 'text',
								'description' => 'Channel version; pivot channel required.',
								'short' => 'v',
								'default' => false,
							),
						'pivot' => array
							(
								'type' => 'text',
								'description' => 'Pivot channel, should be main application channel.',
								'short' => 'p',
								'default' => false,
							),
						'dry-run' => array
							(
								'description' => 'Generate history but don\'t process.',
								'short' => 'y',
							),
						'verbose' => array
							(
								'description' => 'Verbose debug output.',
							),
					),
			),
		'pdx:uninstall' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Uninstalls database.',
					),
				'flags' => array
					(
						'hard' => array
							(
								'description' => 'Attempts to uninstalls every table, ever.',
								'short' => 'h',
							),
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
						'dry-run' => array
							(
								'description' => 'Generate history but don\'t process.',
								'short' => 'y',
							),
						'verbose' => array
							(
								'description' => 'Verbose debug output.',
							),
					),
			),
		'pdx:history' => array
			(
				'category' => 'Database',
				'description' => array
					(
						'Displays migration history.',
					),
				'flags' => array
					(
						'detailed' => array
							(
								'description' => 'Show description.',
							),
						'signatures' => array
							(
								'description' => 'Show migration system signature.',
							)
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
		'sphinx:model' => array
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
			),
	);

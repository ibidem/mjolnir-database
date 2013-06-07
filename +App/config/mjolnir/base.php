<?php return array
	(
		// In a locked state:
		// uninstall - never allowed
		//     reset - not allowed if the database exists; and only to latest
		//   upgrade - allowed
		//    status - allowed
		'db:lock' => true,
	
		// Default migration system
		'db:migrations' => 'paradox', # options: schematic, paradox

	); # config
<?php return array
	(
		// setup the environment
		'configure' => 100,
		// remove deprecated elements; such as bindings
		'cleanup'   => 200,
		// create tables
		'tables'    => 300,
		// modify existing tables
		'modify'    => 400,
	
	// ------------------------------------------------------------------------

		// perform bindings to other tables
		'bindings'  => 1000,
		// perform post-cleanup; invokes populate directives
		'normalize' => 2000,
		// populate tables with data
		'populate'  => 3000,
	
	); # config

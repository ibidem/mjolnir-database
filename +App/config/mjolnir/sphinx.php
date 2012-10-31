<?php namespace app;

$base_config = CFS::config('mjolnir/base');
$offset = SQLDatabase::default_timezone_offset();

return array
	(	
		'default.src.config' => array
			(
				'type'     => 'mysql',
				'sql_host' => 'localhost',
				'sql_user' => null,
				'sql_pass' => null,
				'sql_db'   => null,
				'sql_port' => 3306, # optional, default is 3306
			
				'sql_query_pre' => array
					(
						"SET CHARACTER SET '{$base_config['charset']}';",
						"SET NAMES '{$base_config['charset']}';",
						"SET time_zone='$offset';"
					),
			),
	
		'index' => array
			(
				'default.path-prefix' => '@CONFDIR@/data/',
				'default.docinfo' => 'extern',
				'default.charset_type' => 'utf-8',
			),
	
		'indexer' => array
			(
				'mem_limit' => '32M',
			),
	
		'searchd' => array
			(
				'host' => 'localhost',
			
				'listen' => array
					(
						'api' => '9312', 
						'mysql' => '9306:mysql41',
					),
			
				'log' => '@CONFDIR@/log/searchd.log',
				'query_log' => '@CONFDIR@/log/query.log',
				'read_timeout' => 5,
				'max_children' => 30,
				'pid_file' => '@CONFDIR@/log/searchd.pid',
				'max_matches' => 1000,
				'seamless_rotate' => 1,
				'preopen_indexes' => 1,
				'unlink_old' => 1,
				'workers' => 'threads # for RT to work',
				'binlog_path' => '@CONFDIR@/data',
			),	
	);

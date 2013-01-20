<?php require_once \app\CFS::dir('vendor/sphinx/').'sphinxapi'.EXT;

$base_config = \app\CFS::config('mjolnir/base');
$offset = \app\Date::default_timezone_offset();

return array
	(
		'timeout' => 1,

		'default.matchmode' => SPH_MATCH_ANY,

		'default.sortmode' => SPH_SORT_RELEVANCE,

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
				'default.min_word_len' => 2,
				'default.min_prefix_len' => 0,
				'default.min_infix_len' => 2,
				'default.min_stemming_len' => 3,
				'default.index_exact_words' => 0,
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

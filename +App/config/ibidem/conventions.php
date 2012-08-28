<?php return array
	(
		'autofills' => array
			(
				'#^Model_.*$#' => \app\View::instance('ibidem/database/autofills/Model')->render(),
			),
	);

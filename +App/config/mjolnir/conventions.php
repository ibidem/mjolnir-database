<?php return array
	(
		'autofills' => array
			(
				'#^Model_.*$#' => \app\View::instance('mjolnir/database/autofills/Model')->render(),
			),
	);

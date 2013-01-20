<?php return array
	(
		'base_classes' => array
			(
				'#^Schematic_.*$#' => '\app\Instantiatable implements \mjolnir\types\Schematic',
			),

		'autofills' => array
			(
				'#^Model_.*$#' => \app\View::instance('mjolnir/database/autofills/Model')->render(),
				'#^Schematic_.*$#' => \app\View::instance('mjolnir/database/autofills/Schematic')->render(),
			),
	);

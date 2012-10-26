<?php return array
	(
		'base_classes' => array
			(
				'#^Schematic_.*$#' => '\app\Schematic_Base',
			),
	
		'autofills' => array
			(
				'#^Model_.*$#' => \app\View::instance('mjolnir/database/autofills/Model')->render(),
				'#^Schematic_.*$#' => \app\View::instance('mjolnir/database/autofills/Schematic')->render(),
			),
	);

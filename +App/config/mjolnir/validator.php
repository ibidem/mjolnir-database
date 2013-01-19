<?php return array
	(
		'errors' => array
			(
				'not_empty' => 'Field is required.',
				'valid' => 'Invalid value.'
			),

		'rules' => array
			(
				'not_empty' => function ($fields, $field)
					{
						return $fields[$field] === 0
							|| $fields[$field] === '0'
							|| ! empty($fields[$field]);
					},

				'valid_number' => function ($fields, $field)
					{
						return \is_numeric($fields[$field]);
					},
			),
	);

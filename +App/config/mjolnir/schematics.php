<?php return array
	(
		'steps' => array
			(
				'mjolnir:registry' => [ 'serial' => '1:0-default' ],
			),
	
		'dependencies' => array
			(
				// empty
			),

		'definitions' => array
			(
				// common

				':key_primary' => "bigint(20) unsigned NOT NULL AUTO_INCREMENT",
				':key_foreign' => "bigint(20) unsigned",
				':counter' => "bigint(20) unsigned NOT NULL DEFAULT '0'",
				':title' => "varchar(255)",
				':description' => "varchar(1000)",
				':block' => "varchar(10000)",
				':datetime_required' => 'datetime NOT NULL',
				':datetime_optional' => 'datetime',
				':timestamp' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
				':boolean' => 'boolean',
				':currency' => 'decimal(17, 2)', # +/- 999 billion

				// access control
				':access' => 'boolean DEFAULT FALSE NOT NULL',

				// uncommon

				# systems usually handle paths of 32,000, but 260 is the recomended safe limit for cross-platform compatibility
				':path' => 'varchar(260)',
				# RFC 5321; we subtract 2 characters for angle brackets -- yes it''s not (64 + 1 + 255 =) 320
				':email' => 'varchar(254) CHARACTER SET latin1 COLLATE latin1_general_ci',
				# address
				':address' => 'varchar(255)',
				# close to the shorhand version of the longest name on record
				':name' => 'varchar(80)',
				':username' => 'varchar(80) NOT NULL',
				':titlename' => 'varchar(80)',
				# IPv6 length 39 + tunneling IPv4 = 45
				':ipaddress' => 'varchar(45) CHARACTER SET latin1 COLLATE latin1_bin',
				# Secure Hash Algorthm (sha512)
				':secure_hash' => 'char(128) CHARACTER SET latin1 COLLATE latin1_bin',
				# telephone number
				':telephone' => 'varchar(255)',
				# sex as m / f
				':sex' => 'varchar(1)',
				# universal social security number field; use of localized ssn's is not
				# advised since a lot of the time the systems in question need to allow
				# foreigners to register as well, and the ssn format is inconsistent
				':ssn' => 'varchar(20)',
			
				# general purpose ID field; the field is not numeric because IDs
				# in the real world tend to be things like 999-9999-99 etc.
				':identifier' => 'varchar(100) DEFAULT \'\'',

				# zipcodes usually go for around 4 to 9
				# 16 used for safety (assuming misc characters)
				':zipcode' => 'varchar(16)',

				// general

				':engine' => 'InnoDB',
				':default_charset' => 'utf8',
			),
	
	);
<?php

/**
 * This file is part of the ZfcUserLdap Module (https://github.com/Nitecon/zfcuser-ldap.git)
 *
 * Copyright (c) 2013 Will Hattingh (https://github.com/Nitecon/zfcuser-ldap)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 */
return array(
		'doctrine' => array(
				'driver' => array(
						'zfcuserldap_entities' => array(
								'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
								'cache' => 'array',
								'paths' => array(__DIR__ . '/../src/ZfcUserLdap/Entity'),
						),
						'orm_default' => array(
								'drivers' => array(
										'ZfcUserLdap\Entity' => 'zfcuserldap_entities',
								),
						),
				),
		),
);
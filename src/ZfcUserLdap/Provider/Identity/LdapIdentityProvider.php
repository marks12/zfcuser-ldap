<?php

/**
 * Copyright (c) 2013 Will Hattingh (https://github.com/Nitecon
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 * 
 * @author Will Hattingh <w.hattingh@nitecon.com>
 *
 * 
 */
namespace ZfcUserLdap\Provider\Identity;
use BjyAuthorize\Exception\InvalidRoleException;
use Zend\Permissions\Acl\Role\RoleInterface;
use Zend\Session\Container;
use ZfcUserLdap\Entity\Role;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;


class LdapIdentityProvider implements \BjyAuthorize\Provider\Identity\ProviderInterface, ServiceLocatorAwareInterface{
    /**
     * @var User
     */
    protected $userService;

    /**
     * @var string|\Zend\Permissions\Acl\Role\RoleInterface
     */
    protected $defaultRole;
    
    protected $config;
    /**
     * @param \ZfcUser\Service\User    $userService
     */

    protected $services;
    
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
    	$this->services = $serviceLocator;
    }
    
    public function getServiceLocator()
    {
    	return $this->services;
    }
    
    
    public function __construct($userService,$config)
    {
        $this->userService = $userService;
        $this->config = $config;
    }

    
    
    /**
     * {@inheritDoc}
     */
    public function getIdentityRoles()
    {
        $authService = $this->userService;
//         $definedRoles = $this->config['role_providers']['BjyAuthorize\Provider\Role\Config']['user']['children'];
        $roleKey = $this->config['ldap_role_key'];
        
//         $em = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        
//         $definedRoles = $em->getRepository("ZfcUserLdap\Entity\Role")->findAll();
        
//         var_dump($role);
//         exit();
        
        if (! $authService->getAuthService()->hasIdentity()) {
            return array($this->getDefaultRole());
        }
        $session = new Container('ZfcUserLdap');
        if (!$session->offsetExists('ldapObj')){
            return array($this->getDefaultRole());
        }
        
//         var_dump($roleKey);
        
        $user = $session->offsetGet('ldapObj');
        $roles     = array();
        
//         var_dump($user);
//         var_dump($definedRoles);
//         exit();
        
        foreach ($user->getRoles() as $role) {
//             if (isset($definedRoles[$role]))
                $roles[] = $role->getRoleId();
        }
        return $roles;
//         $session = new Container('ZfcUserLdap');
//         $user = $session->offsetGet('ldapObj');
// 		$em = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
// // 		$Roles = $em->getRepository("ZfcUserLdap\Entity\User")->find();
// // 		var_dump($authService->getAuthService()->getIdentity());
// 		var_dump($user);
		
// 		exit();
        
    }

    /**
     * @return string|\Zend\Permissions\Acl\Role\RoleInterface
     */
    public function getDefaultRole()
    {
        return $this->defaultRole;
    }

    /**
     * @param string|\Zend\Permissions\Acl\Role\RoleInterface $defaultRole
     *
     * @throws \BjyAuthorize\Exception\InvalidRoleException
     */
    public function setDefaultRole($defaultRole)
    {
        if (! ($defaultRole instanceof RoleInterface || is_string($defaultRole))) {
            throw InvalidRoleException::invalidRoleInstance($defaultRole);
        }

        $this->defaultRole = $defaultRole;
    }
}
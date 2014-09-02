<?php
/**
 * This file is part of the ZfcUserLdap Module (https://github.com/Nitecon/zfcuser-ldap.git)
 *
 * Copyright (c) 2013 Will Hattingh (https://github.com/Nitecon/zfcuser-ldap)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.txt that was distributed with this source code.
 */
namespace ZfcUserLdap\Authentication\Adapter;

use Zend\Authentication\Storage;
use Zend\Authentication\Result as AuthenticationResult;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use ZfcUserLdap\Mapper\User as UserMapperInterface;
use ZfcUser\Options\AuthenticationOptionsInterface;
use ZfcUser\Authentication\Adapter\ChainableAdapter as AdapterChain;
use ZfcUser\Authentication\Adapter\AdapterChainEvent as AuthEvent;
use Zend\Session\Container;
use BjyAuthorize\Acl\Role;

class Ldap implements AdapterChain, ServiceManagerAwareInterface {

    /**
     * @var UserMapperInterface
     */
    protected $mapper;

    /**
     * @var closure / invokable object
     */
    protected $credentialPreprocessor;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var AuthenticationOptionsInterface
     */
    protected $options;

    /**
     * @var Storage\StorageInterface
     */
    protected $storage;

    public function authenticate(AuthEvent $e) {
        $mapper = new \ZfcUserLdap\Mapper\User(
                $this->getServiceManager()->get('ldap_interface'), $this->getServiceManager()->get('zfcuser_module_options')
        );
        $this->setMapper($mapper);
        if ($this->isSatisfied()) {
            $storage = $this->getStorage()->read();

            $e->setIdentity($storage['identity'])
                    ->setCode(AuthenticationResult::SUCCESS)
                    ->setMessages(array('Authentication successful.'));
            return;
        }
        $identity = $e->getRequest()->getPost()->get('identity');
        $credential = $e->getRequest()->getPost()->get('credential');

        $userObject = NULL;
        /*
         * In some special case scenarios some LDAP providers allow LDAP
         * logins via email address both as uid or as mail address lookup,
         * so to provide an interface to both we do a validator instead of
         * a loop to verify if it's an email address or not and pull the user.
         *
         * Authentication will then be done on the *actual* username set in LDAP
         * which in some cases may be case sensitive which could cause an issue
         * where users do not exist if their email was created with upper case
         * letters and the user types in lower case.
         *
         * $fields = $this->getOptions()->getAuthIdentityFields();
         */

        $zulConfig = $this->getServiceManager()->get('config')['ZfcUserLdap'];
        
//         var_dump($zulConfig);
//         var_dump($zulConfig['auto_insertion']['enabled']);
//         exit();
        

        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_default');
        
        
        $validator = new \Zend\Validator\EmailAddress();
        if ($validator->isValid($identity)) {
            $userObject = $this->getMapper()->findByEmail($identity);
        } else {
            $userObject = $this->getMapper()->findByUsername($identity);
        }

        if (!$userObject) {
            $e->setCode(AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND)
                    ->setMessages(array('A record with the supplied identity could not be found.'));
            $this->setSatisfied(false);
            return false;
        }

        if ($this->getOptions()->getEnableUserState()) {
            // Don't allow user to login if state is not in allowed list
            if (!in_array($userObject->getState(), $this->getOptions()->getAllowedLoginStates())) {
                $e->setCode(AuthenticationResult::FAILURE_UNCATEGORIZED)
                        ->setMessages(array('A record with the supplied identity is not active.'));
                $this->setSatisfied(false);
                return false;
            }
        }
//         if ($auth = $this->getMapper()->authenticate($userObject->getUsername(), $credential) !== TRUE) {
// //         	var_dump($this->getMapper()->authenticate($userObject->getUsername(), $credential));
// //         	exit();
//             // Password does not match
//             $e->setCode(AuthenticationResult::FAILURE_CREDENTIAL_INVALID)
//                     ->setMessages(array($auth));
//             $this->setSatisfied(false);
//             return false;
//         }



        // If auto insertion is on, we will check against DB for existing user,
        // then will create or update user depending on results and settings
        if ($zulConfig['auto_insertion']['enabled']) {
        	$validator = new \Zend\Validator\EmailAddress();
        	if ($validator->isValid($identity)) {
        		$userDbObject = $em->getRepository('ZfcUserLdap\Entity\User')->findOneBy(array('mail' => $identity));
           	} else {
        		$userDbObject = $em->getRepository('ZfcUserLdap\Entity\User')->findOneBy(array('username' => $identity));
        	}

        	
        	if ($userDbObject === NULL) {
        		
        		$supportedRoles = array();
        		$roles = $em->getRepository('ZfcUserLdap\Entity\Role')->findAll();
        		
        		foreach ($roles as $role)
        			$supportedRoles[$role->getRoleId()] = $role->getId();
        		
        		$em->persist($userObject);
        		
        		foreach ($userObject->getMemberof() as $k=>$v)
        		{
        			foreach ($roles as $r)
        			{
        				if($v==$r->getRoleId())
        				{
        					$userObject->addRole($r);
        				}
        			}
        		}

        		$roles = $em->getRepository('ZfcUserLdap\Entity\Role')->findBy(array("roleId"=>"user"));
        		
        		if(count($roles))
        			$userObject->addRole($roles[0]);
        		
//         		var_dump($roles);
//         		exit();
        		
        		
        		$em->persist($userObject);
        		$em->flush();

        	} elseif ($zulConfig['auto_insertion']['auto_update']) {
        		

        		
        		$supportedRoles = array();
        		$roles = $em->getRepository('ZfcUserLdap\Entity\Role')->findAll();
        		
        		foreach ($roles as $role)
        			$supportedRoles[$role->getRoleId()] = $role->getId();

        		$member_off = array();
        		foreach ($userObject->getMemberof() as $k=>$v)
        			if(isset($supportedRoles[$v]))
        				$member_off[] = $v;
        		
        		$exists_roles = array();
        		foreach ($userDbObject->getRoles() as $k=>$v)
        				$exists_roles[] = $v;
        		
        		/**
        		 * Удаляем все роли, которые пропали из мемберофф
        		 */
        		foreach ($userDbObject->getRolesObj() as $role1)
        		{
        			if(!in_array($role1->getRoleId(), $member_off) && $role1->getRoleId()!='user')
        			{
        				$userDbObject->getRolesObj()->removeElement($role1);
        			}
        		}
        		
        		/**
        		 * Добавляем роли, которые появились для данного пользователя
        		 */
        		
        		foreach ($userObject->getMemberof() as $k=>$v)
        		{
        			foreach ($roles as $r)
        			{
        				if($v==$r->getRoleId())
        				{
        					$userObject->addRole($r);
        				}
        			}
        		}
        		
        		/**
        		 * Обновляем ФИО, Email
        		 */
        		$userDbObject->setDisplayName($userObject->getDisplayName());
        		$userDbObject->setEmail($userObject->getEmail());
        		
        		$em->persist($userDbObject);
        		$em->flush();
        		
        		$userObject = $userDbObject;
        		
        	} else {
        		$userObject = $userDbObject;
        	}
        }
        
        
//         var_dump($userObject);
//         exit();
        
        
        // Success!
        $e->setIdentity($userObject);

//         var_dump($e->getIdentity());
//         exit();
        
        $this->setSatisfied(true);
        $storage = $this->getStorage()->read();
        $storage['identity'] = $e->getIdentity();
        $session = new Container('ZfcUserLdap');
//         $session->offsetSet('ldapObj', $this->getServiceManager()->get('ldap_interface')->findById($userObject->getId()));
        $session->offsetSet('ldapObj', $userObject);
        $this->getStorage()->write($storage);
        $e->setCode(AuthenticationResult::SUCCESS)->setMessages(array('Authentication successful.'));
    }

    /**
     * Returns the persistent storage handler
     *
     * Session storage is used by default unless a different storage adapter has been set.
     *
     * @return Storage\StorageInterface
     */
    public function getStorage() {
        if (null === $this->storage) {
            $this->setStorage(new Storage\Session(get_called_class()));
        }

        return $this->storage;
    }

    /**
     * Sets the persistent storage handler
     *
     * @param  Storage\StorageInterface $storage
     * @return AbstractAdapter Provides a fluent interface
     */
    public function setStorage(Storage\StorageInterface $storage) {
        $this->storage = $storage;
        return $this;
    }

    /**
     * Check if this adapter is satisfied or not
     *
     * @return bool
     */
    public function isSatisfied() {
        $storage = $this->getStorage()->read();
        return (isset($storage['is_satisfied']) && true === $storage['is_satisfied']);
    }

    /**
     * Set if this adapter is satisfied or not
     *
     * @param bool $bool
     * @return AbstractAdapter
     */
    public function setSatisfied($bool = true) {
        $storage = $this->getStorage()->read() ? : array();
        $storage['is_satisfied'] = $bool;
        $this->getStorage()->write($storage);
        return $this;
    }

    public function preprocessCredential($credential) {
        $processor = $this->getCredentialPreprocessor();
        if (is_callable($processor)) {
            return $processor($credential);
        }
        return $credential;
    }

    /**
     * getMapper
     *
     * @return UserMapperInterface
     */
    public function getMapper() {
        if (null === $this->mapper) {
            $this->mapper = $this->getServiceManager()->get('zfcuser_user_mapper');
        }
        return $this->mapper;
    }

    /**
     * setMapper
     *
     * @param UserMapperInterface $mapper
     * @return Db
     */
    public function setMapper(UserMapperInterface $mapper) {
        $this->mapper = $mapper;
        return $this;
    }

    /**
     * Get credentialPreprocessor.
     *
     * @return \callable
     */
    public function getCredentialPreprocessor() {
        return $this->credentialPreprocessor;
    }

    /**
     * Set credentialPreprocessor.
     *
     * @param $credentialPreprocessor the value to be set
     */
    public function setCredentialPreprocessor($credentialPreprocessor) {
        $this->credentialPreprocessor = $credentialPreprocessor;
        return $this;
    }

    /**
     * Retrieve service manager instance
     *
     * @return ServiceManager
     */
    public function getServiceManager() {
        return $this->serviceManager;
    }

    /**
     * Set service manager instance
     *
     * @param ServiceManager $locator
     * @return void
     */
    public function setServiceManager(ServiceManager $serviceManager) {
        $this->serviceManager = $serviceManager;
    }

    /**
     * @param AuthenticationOptionsInterface $options
     */
    public function setOptions(AuthenticationOptionsInterface $options) {
        $this->options = $options;
    }

    /**
     * @return AuthenticationOptionsInterface
     */
    public function getOptions() {
        if (!$this->options instanceof AuthenticationOptionsInterface) {
            $this->setOptions($this->getServiceManager()->get('zfcuser_module_options'));
        }
        return $this->options;
    }

}
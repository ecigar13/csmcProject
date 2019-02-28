<?php

namespace App\Security\User;

use App\Entity\User\Role;
use App\Entity\User\User;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;

class LdapEntityUserProvider extends EntityUserProvider {

    private $manager;

    public function __construct(ManagerRegistry $registry, $classOrAlias, $property = null, $managerName = null) {
        parent::__construct($registry, $classOrAlias, $property, $managerName);
        $this->manager = $registry->getManager($managerName);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username) {
        try {
            $user = parent::loadUserByUsername($username);
            if($user->getProfile() == null) {
                $user->createProfile();

                $this->manager->flush();
            }

            if($user->getInfo() == null) {
                $user->createInfo();

                $this->manager->flush();
            }

            return $user;
        } catch (UsernameNotFoundException $exception) {
            $adapter = new Adapter(array(
                'host' => 'nsldap.utdallas.edu',
                'port' => 389,
                'version' => 3,
                'referrals' => false
            ));

            $ldap = new Ldap($adapter);

            $ldap->bind();
            $q = $ldap->query('ou=people,dc=utdallas,dc=edu', '(uid=' . $username . ')')->execute();
            if (!$q->offsetExists(0)) {
                throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
            }

            $entry = $q->offsetGet(0);
            $first_name = $entry->getAttribute('givenName')[0];
            $last_name = $entry->getAttribute('sn')[0];

            $user = new User($first_name, $last_name, $username);
            $role = $this->manager
                ->getRepository(Role::class)
                ->findOneByName('student');
            $user->addRole($role);

            $this->manager->persist($user);
            $this->manager->flush();

            return $user;
        }
    }
}
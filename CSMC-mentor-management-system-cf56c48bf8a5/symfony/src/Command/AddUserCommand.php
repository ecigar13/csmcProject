<?php

namespace App\Command;

use App\Entity\User\Role;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class AddUserCommand extends Command {
    protected static $defaultName = 'app:add-user';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure() {
        $this->setDescription('Add a role to user')
            ->addArgument('user', InputArgument::REQUIRED, 'NetId of user')
            ->addArgument('role', InputArgument::REQUIRED, 'Role of user');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('user');
        $role_name = $input->getArgument('role');

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneByUsername($username);

        if (!$user) {
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

            $this->entityManager->persist($user);
        }

        $role = $this->entityManager
            ->getRepository(Role::class)
            ->findOneByName($role_name);

        if (!$role) {
            $role = new Role($role_name);
            $this->entityManager->persist($role);
        }

        $user->addRole($role);

        $this->entityManager->flush();

        $io->success('Role "' . $role_name . '" added to ' . $username);
    }
}

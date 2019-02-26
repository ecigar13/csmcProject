<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security\Authentication;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginLdapFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FormLoginLdapSearchFactory extends FormLoginLdapFactory {
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId) {
        $provider = 'app.security.authentication.provider.ldap_search.' . $id;
        $definition = $container
            ->setDefinition($provider, new ChildDefinition('app.security.authentication.provider.ldap_search'))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(1, new Reference('security.user_checker.' . $id))
            ->replaceArgument(2, $id)
            ->replaceArgument(3, new Reference($config['service']))
            ->replaceArgument(4, $config['dn_string']);

        if (!empty($config['query_string'])) {
            $definition->addMethodCall('setQueryString', array($config['query_string']));
        }

        return $provider;
    }

    public function getKey() {
        return 'form-login-ldap-search';
    }
}

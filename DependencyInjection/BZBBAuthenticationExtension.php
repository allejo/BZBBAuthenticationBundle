<?php

namespace allejo\BZBBAuthenticationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class BZBBAuthenticationExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('bzbb_authentication.user_class', $config['user_class']);
        $container->setParameter('bzbb_authentication.routes.login_route', $config['routes']['login_route']);
        $container->setParameter('bzbb_authentication.routes.success_route', $config['routes']['success_route']);
        $container->setParameter('bzbb_authentication.groups', $config['groups']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yml');
    }
}

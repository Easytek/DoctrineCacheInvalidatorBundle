<?php

namespace Easytek\DoctrineCacheInvalidatorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class CacheInvalidatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('easytek.doctrine_cache_invalidator');

        foreach ($container->findTaggedServiceIds('easytek.doctrine_cache_invalidation') as $id => $attributes) {
            $definition->addMethodCall('addService', array(new Reference($id)));
        }
    }
}

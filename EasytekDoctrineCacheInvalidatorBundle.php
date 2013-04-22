<?php

namespace Easytek\DoctrineCacheInvalidatorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Easytek\DoctrineCacheInvalidatorBundle\DependencyInjection\Compiler\CacheInvalidatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EasytekDoctrineCacheInvalidatorBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CacheInvalidatorPass());
    }
}

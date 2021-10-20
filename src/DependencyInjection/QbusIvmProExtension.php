<?php

declare(strict_types=1);

/*
 * This file is part of IVM Professional Bundle for Contao.
 *
 * (c) Qbus Internetagentur
 *
 * @license LGPL-3.0-or-later
 */

namespace Qbus\IvmProBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class QbusIvmProExtension extends Extension
{
    public function load(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');
    }
}

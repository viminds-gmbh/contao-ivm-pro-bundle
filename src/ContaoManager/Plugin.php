<?php

declare(strict_types=1);

/*
 * This file is part of IVM Professional Bundle for Contao.
 *
 * (c) Qbus Internetagentur
 *
 * @license LGPL-3.0-or-later
 */

namespace Qbus\IvmProBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Qbus\IvmProBundle\QbusIvmProBundle;
use Qbus\IvmProClientBundle\IvmProClientBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(QbusIvmProBundle::class)
                ->setLoadAfter([IvmProClientBundle::class]),
        ];
    }
}

<?php

declare (strict_types=1);
namespace ECSPrefix20211007\Symplify\EasyTesting\HttpKernel;

use ECSPrefix20211007\Symfony\Component\Config\Loader\LoaderInterface;
use ECSPrefix20211007\Symplify\SymplifyKernel\HttpKernel\AbstractSymplifyKernel;
final class EasyTestingKernel extends \ECSPrefix20211007\Symplify\SymplifyKernel\HttpKernel\AbstractSymplifyKernel
{
    /**
     * @param \Symfony\Component\Config\Loader\LoaderInterface $loader
     */
    public function registerContainerConfiguration($loader) : void
    {
        $loader->load(__DIR__ . '/../../config/config.php');
    }
}

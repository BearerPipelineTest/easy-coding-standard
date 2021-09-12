<?php

declare (strict_types=1);
namespace ECSPrefix20210912\Symplify\PackageBuilder\Contract\HttpKernel;

use ECSPrefix20210912\Symfony\Component\HttpKernel\KernelInterface;
use ECSPrefix20210912\Symplify\SmartFileSystem\SmartFileInfo;
interface ExtraConfigAwareKernelInterface extends \ECSPrefix20210912\Symfony\Component\HttpKernel\KernelInterface
{
    /**
     * @param string[]|SmartFileInfo[] $configs
     */
    public function setConfigs($configs) : void;
}

<?php

declare (strict_types=1);
namespace ECSPrefix20220604\Symplify\RuleDocGenerator\Contract;

use ECSPrefix20220604\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
interface RuleCodeSamplePrinterInterface
{
    public function isMatch(string $class) : bool;
    /**
     * @return string[]
     */
    public function print(\ECSPrefix20220604\Symplify\RuleDocGenerator\Contract\CodeSampleInterface $codeSample, \ECSPrefix20220604\Symplify\RuleDocGenerator\ValueObject\RuleDefinition $ruleDefinition) : array;
}

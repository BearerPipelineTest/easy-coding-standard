<?php

declare (strict_types=1);
/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpCsFixer\FixerDefinition;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
interface FixerDefinitionInterface
{
    public function getSummary() : string;
    /**
     * @return string|null
     */
    public function getDescription();
    /**
     * @return null|string null for non-risky fixer
     */
    public function getRiskyDescription();
    /**
     * Array of samples, where single sample is [code, configuration].
     *
     * @return CodeSampleInterface[]
     */
    public function getCodeSamples() : array;
}

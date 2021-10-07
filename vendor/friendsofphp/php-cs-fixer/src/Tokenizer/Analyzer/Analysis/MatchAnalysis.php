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
namespace PhpCsFixer\Tokenizer\Analyzer\Analysis;

/**
 * @internal
 */
final class MatchAnalysis extends \PhpCsFixer\Tokenizer\Analyzer\Analysis\AbstractControlCaseStructuresAnalysis
{
    /**
     * @var null|DefaultAnalysis
     */
    private $defaultAnalysis;
    public function __construct(int $index, int $open, int $close, ?\PhpCsFixer\Tokenizer\Analyzer\Analysis\DefaultAnalysis $defaultAnalysis)
    {
        parent::__construct($index, $open, $close);
        $this->defaultAnalysis = $defaultAnalysis;
    }
    public function getDefaultAnalysis() : ?\PhpCsFixer\Tokenizer\Analyzer\Analysis\DefaultAnalysis
    {
        return $this->defaultAnalysis;
    }
}

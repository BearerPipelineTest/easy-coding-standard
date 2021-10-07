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
namespace PhpCsFixer\Tokenizer\Transformer;

use PhpCsFixer\Tokenizer\AbstractTransformer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * Transform `&` operator into CT::T_RETURN_REF in `function & foo() {}`.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class ReturnRefTransformer extends \PhpCsFixer\Tokenizer\AbstractTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getRequiredPhpVersionId() : int
    {
        return 50000;
    }
    /**
     * {@inheritdoc}
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @param \PhpCsFixer\Tokenizer\Token $token
     * @param int $index
     */
    public function process($tokens, $token, $index) : void
    {
        $prevKinds = [\T_FUNCTION];
        if (\PHP_VERSION_ID >= 70400) {
            $prevKinds[] = \T_FN;
        }
        if (($token->equals('&') || \defined('T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG') && $token->isGivenKind(T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG)) && $tokens[$tokens->getPrevMeaningfulToken($index)]->isGivenKind($prevKinds)) {
            $tokens[$index] = new \PhpCsFixer\Tokenizer\Token([\PhpCsFixer\Tokenizer\CT::T_RETURN_REF, '&']);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function getCustomTokens() : array
    {
        return [\PhpCsFixer\Tokenizer\CT::T_RETURN_REF];
    }
}

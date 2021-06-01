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
namespace PhpCsFixer\Tokenizer\Analyzer;

use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * @author Kuba Werłos <werlos@gmail.com>
 * @author SpacePossum
 *
 * @internal
 */
final class CommentsAnalyzer
{
    const TYPE_HASH = 1;
    const TYPE_DOUBLE_SLASH = 2;
    const TYPE_SLASH_ASTERISK = 3;
    public function isHeaderComment(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index) : bool
    {
        if (!$tokens[$index]->isGivenKind([\T_COMMENT, \T_DOC_COMMENT])) {
            throw new \InvalidArgumentException('Given index must point to a comment.');
        }
        if (null === $tokens->getNextMeaningfulToken($index)) {
            return \false;
        }
        $prevIndex = $tokens->getPrevNonWhitespace($index);
        if ($tokens[$prevIndex]->equals(';')) {
            $braceCloseIndex = $tokens->getPrevMeaningfulToken($prevIndex);
            if (!$tokens[$braceCloseIndex]->equals(')')) {
                return \false;
            }
            $braceOpenIndex = $tokens->findBlockStart(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $braceCloseIndex);
            $declareIndex = $tokens->getPrevMeaningfulToken($braceOpenIndex);
            if (!$tokens[$declareIndex]->isGivenKind(\T_DECLARE)) {
                return \false;
            }
            $prevIndex = $tokens->getPrevNonWhitespace($declareIndex);
        }
        return $tokens[$prevIndex]->isGivenKind(\T_OPEN_TAG);
    }
    /**
     * Check if comment at given index precedes structural element.
     *
     * @see https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md#3-definitions
     */
    public function isBeforeStructuralElement(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index) : bool
    {
        $token = $tokens[$index];
        if (!$token->isGivenKind([\T_COMMENT, \T_DOC_COMMENT])) {
            throw new \InvalidArgumentException('Given index must point to a comment.');
        }
        $nextIndex = $index;
        do {
            $nextIndex = $tokens->getNextMeaningfulToken($nextIndex);
            // @TODO: drop condition when PHP 8.0+ is required
            if (\defined('T_ATTRIBUTE')) {
                while (null !== $nextIndex && $tokens[$nextIndex]->isGivenKind(\T_ATTRIBUTE)) {
                    $nextIndex = $tokens->findBlockEnd(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_ATTRIBUTE, $nextIndex);
                    $nextIndex = $tokens->getNextMeaningfulToken($nextIndex);
                }
            }
        } while (null !== $nextIndex && $tokens[$nextIndex]->equals('('));
        if (null === $nextIndex || $tokens[$nextIndex]->equals('}')) {
            return \false;
        }
        $nextToken = $tokens[$nextIndex];
        if ($this->isStructuralElement($nextToken)) {
            return \true;
        }
        if ($this->isValidControl($tokens, $token, $nextIndex)) {
            return \true;
        }
        if ($this->isValidVariable($tokens, $nextIndex)) {
            return \true;
        }
        if ($this->isValidLanguageConstruct($tokens, $token, $nextIndex)) {
            return \true;
        }
        return \false;
    }
    /**
     * Return array of indices that are part of a comment started at given index.
     *
     * @param int $index T_COMMENT index
     * @return mixed[]|null
     */
    public function getCommentBlockIndices(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index)
    {
        if (!$tokens[$index]->isGivenKind(\T_COMMENT)) {
            throw new \InvalidArgumentException('Given index must point to a comment.');
        }
        $commentType = $this->getCommentType($tokens[$index]->getContent());
        $indices = [$index];
        if (self::TYPE_SLASH_ASTERISK === $commentType) {
            return $indices;
        }
        $count = \count($tokens);
        ++$index;
        for (; $index < $count; ++$index) {
            if ($tokens[$index]->isComment()) {
                if ($commentType === $this->getCommentType($tokens[$index]->getContent())) {
                    $indices[] = $index;
                    continue;
                }
                break;
            }
            if (!$tokens[$index]->isWhitespace() || $this->getLineBreakCount($tokens, $index, $index + 1) > 1) {
                break;
            }
        }
        return $indices;
    }
    /**
     * @see https://github.com/phpDocumentor/fig-standards/blob/master/proposed/phpdoc.md#3-definitions
     */
    private function isStructuralElement(\PhpCsFixer\Tokenizer\Token $token) : bool
    {
        static $skip = [\T_PRIVATE, \T_PROTECTED, \T_PUBLIC, \T_VAR, \T_FUNCTION, \T_ABSTRACT, \T_CONST, \T_NAMESPACE, \T_REQUIRE, \T_REQUIRE_ONCE, \T_INCLUDE, \T_INCLUDE_ONCE, \T_FINAL, \T_STATIC];
        return $token->isClassy() || $token->isGivenKind($skip);
    }
    /**
     * Checks control structures (for, foreach, if, switch, while) for correct docblock usage.
     *
     * @param Token $docsToken    docs Token
     * @param int   $controlIndex index of control structure Token
     */
    private function isValidControl(\PhpCsFixer\Tokenizer\Tokens $tokens, \PhpCsFixer\Tokenizer\Token $docsToken, int $controlIndex) : bool
    {
        static $controlStructures = [\T_FOR, \T_FOREACH, \T_IF, \T_SWITCH, \T_WHILE];
        if (!$tokens[$controlIndex]->isGivenKind($controlStructures)) {
            return \false;
        }
        $index = $tokens->getNextMeaningfulToken($controlIndex);
        $endIndex = $tokens->findBlockEnd(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);
        $docsContent = $docsToken->getContent();
        for ($index = $index + 1; $index < $endIndex; ++$index) {
            $token = $tokens[$index];
            if ($token->isGivenKind(\T_VARIABLE) && \false !== \strpos($docsContent, $token->getContent())) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Checks variable assignments through `list()`, `print()` etc. calls for correct docblock usage.
     *
     * @param Token $docsToken              docs Token
     * @param int   $languageConstructIndex index of variable Token
     */
    private function isValidLanguageConstruct(\PhpCsFixer\Tokenizer\Tokens $tokens, \PhpCsFixer\Tokenizer\Token $docsToken, int $languageConstructIndex) : bool
    {
        static $languageStructures = [\T_LIST, \T_PRINT, \T_ECHO, \PhpCsFixer\Tokenizer\CT::T_DESTRUCTURING_SQUARE_BRACE_OPEN];
        if (!$tokens[$languageConstructIndex]->isGivenKind($languageStructures)) {
            return \false;
        }
        $endKind = $tokens[$languageConstructIndex]->isGivenKind(\PhpCsFixer\Tokenizer\CT::T_DESTRUCTURING_SQUARE_BRACE_OPEN) ? [\PhpCsFixer\Tokenizer\CT::T_DESTRUCTURING_SQUARE_BRACE_CLOSE] : ')';
        $endIndex = $tokens->getNextTokenOfKind($languageConstructIndex, [$endKind]);
        $docsContent = $docsToken->getContent();
        for ($index = $languageConstructIndex + 1; $index < $endIndex; ++$index) {
            $token = $tokens[$index];
            if ($token->isGivenKind(\T_VARIABLE) && \false !== \strpos($docsContent, $token->getContent())) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Checks variable assignments for correct docblock usage.
     *
     * @param int $index index of variable Token
     */
    private function isValidVariable(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index) : bool
    {
        if (!$tokens[$index]->isGivenKind(\T_VARIABLE)) {
            return \false;
        }
        $nextIndex = $tokens->getNextMeaningfulToken($index);
        return $tokens[$nextIndex]->equals('=');
    }
    private function getCommentType(string $content) : int
    {
        if ('#' === $content[0]) {
            return self::TYPE_HASH;
        }
        if ('*' === $content[1]) {
            return self::TYPE_SLASH_ASTERISK;
        }
        return self::TYPE_DOUBLE_SLASH;
    }
    private function getLineBreakCount(\PhpCsFixer\Tokenizer\Tokens $tokens, int $whiteStart, int $whiteEnd) : int
    {
        $lineCount = 0;
        for ($i = $whiteStart; $i < $whiteEnd; ++$i) {
            $lineCount += \PhpCsFixer\Preg::matchAll('/\\R/u', $tokens[$i]->getContent());
        }
        return $lineCount;
    }
}

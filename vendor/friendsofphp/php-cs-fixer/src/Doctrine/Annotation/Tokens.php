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
namespace PhpCsFixer\Doctrine\Annotation;

use ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token as PhpToken;
/**
 * A list of Doctrine annotation tokens.
 *
 * @internal
 *
 * @extends \SplFixedArray<Token>
 */
final class Tokens extends \SplFixedArray
{
    /**
     * @param string[] $ignoredTags
     *
     * @throws \InvalidArgumentException
     */
    public static function createFromDocComment(\PhpCsFixer\Tokenizer\Token $input, array $ignoredTags = []) : self
    {
        if (!$input->isGivenKind(\T_DOC_COMMENT)) {
            throw new \InvalidArgumentException('Input must be a T_DOC_COMMENT token.');
        }
        $tokens = [];
        $content = $input->getContent();
        $ignoredTextPosition = 0;
        $currentPosition = 0;
        $token = null;
        while (\false !== ($nextAtPosition = \strpos($content, '@', $currentPosition))) {
            if (0 !== $nextAtPosition && !\PhpCsFixer\Preg::match('/\\s/', $content[$nextAtPosition - 1])) {
                $currentPosition = $nextAtPosition + 1;
                continue;
            }
            $lexer = new \ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer();
            $lexer->setInput(\substr($content, $nextAtPosition));
            $scannedTokens = [];
            $index = 0;
            $nbScannedTokensToUse = 0;
            $nbScopes = 0;
            while (null !== ($token = $lexer->peek())) {
                if (0 === $index && \ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_AT !== $token['type']) {
                    break;
                }
                if (1 === $index) {
                    if (\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_IDENTIFIER !== $token['type'] || \in_array($token['value'], $ignoredTags, \true)) {
                        break;
                    }
                    $nbScannedTokensToUse = 2;
                }
                if ($index >= 2 && 0 === $nbScopes && !\in_array($token['type'], [\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_NONE, \ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_OPEN_PARENTHESIS], \true)) {
                    break;
                }
                $scannedTokens[] = $token;
                if (\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_OPEN_PARENTHESIS === $token['type']) {
                    ++$nbScopes;
                } elseif (\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_CLOSE_PARENTHESIS === $token['type']) {
                    if (0 === --$nbScopes) {
                        $nbScannedTokensToUse = \count($scannedTokens);
                        break;
                    }
                }
                ++$index;
            }
            if (0 !== $nbScopes) {
                break;
            }
            if (0 !== $nbScannedTokensToUse) {
                $ignoredTextLength = $nextAtPosition - $ignoredTextPosition;
                if (0 !== $ignoredTextLength) {
                    $tokens[] = new \PhpCsFixer\Doctrine\Annotation\Token(\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_NONE, \substr($content, $ignoredTextPosition, $ignoredTextLength));
                }
                $lastTokenEndIndex = 0;
                foreach (\array_slice($scannedTokens, 0, $nbScannedTokensToUse) as $token) {
                    if (\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_STRING === $token['type']) {
                        $token['value'] = '"' . \str_replace('"', '""', $token['value']) . '"';
                    }
                    $missingTextLength = $token['position'] - $lastTokenEndIndex;
                    if ($missingTextLength > 0) {
                        $tokens[] = new \PhpCsFixer\Doctrine\Annotation\Token(\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_NONE, \substr($content, $nextAtPosition + $lastTokenEndIndex, $missingTextLength));
                    }
                    $tokens[] = new \PhpCsFixer\Doctrine\Annotation\Token($token['type'], $token['value']);
                    $lastTokenEndIndex = $token['position'] + \strlen($token['value']);
                }
                $currentPosition = $ignoredTextPosition = $nextAtPosition + $token['position'] + \strlen($token['value']);
            } else {
                $currentPosition = $nextAtPosition + 1;
            }
        }
        if ($ignoredTextPosition < \strlen($content)) {
            $tokens[] = new \PhpCsFixer\Doctrine\Annotation\Token(\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_NONE, \substr($content, $ignoredTextPosition));
        }
        return self::fromArray($tokens);
    }
    /**
     * Create token collection from array.
     *
     * @param Token[] $array       the array to import
     * @param ?bool   $saveIndices save the numeric indices used in the original array, default is yes
     */
    public static function fromArray($array, $saveIndices = null) : self
    {
        $tokens = new self(\count($array));
        if (null === $saveIndices || $saveIndices) {
            foreach ($array as $key => $val) {
                $tokens[$key] = $val;
            }
        } else {
            $index = 0;
            foreach ($array as $val) {
                $tokens[$index++] = $val;
            }
        }
        return $tokens;
    }
    /**
     * Returns the index of the closest next token that is neither a comment nor a whitespace token.
     */
    public function getNextMeaningfulToken(int $index) : ?int
    {
        return $this->getMeaningfulTokenSibling($index, 1);
    }
    /**
     * Returns the index of the closest previous token that is neither a comment nor a whitespace token.
     */
    public function getPreviousMeaningfulToken(int $index) : ?int
    {
        return $this->getMeaningfulTokenSibling($index, -1);
    }
    /**
     * Returns the index of the last token that is part of the annotation at the given index.
     */
    public function getAnnotationEnd(int $index) : ?int
    {
        $currentIndex = null;
        if (isset($this[$index + 2])) {
            if ($this[$index + 2]->isType(\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_OPEN_PARENTHESIS)) {
                $currentIndex = $index + 2;
            } elseif (isset($this[$index + 3]) && $this[$index + 2]->isType(\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_NONE) && $this[$index + 3]->isType(\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_OPEN_PARENTHESIS) && \PhpCsFixer\Preg::match('/^(\\R\\s*\\*\\s*)*\\s*$/', $this[$index + 2]->getContent())) {
                $currentIndex = $index + 3;
            }
        }
        if (null !== $currentIndex) {
            $level = 0;
            for ($max = \count($this); $currentIndex < $max; ++$currentIndex) {
                if ($this[$currentIndex]->isType(\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_OPEN_PARENTHESIS)) {
                    ++$level;
                } elseif ($this[$currentIndex]->isType(\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_CLOSE_PARENTHESIS)) {
                    --$level;
                }
                if (0 === $level) {
                    return $currentIndex;
                }
            }
            return null;
        }
        return $index + 1;
    }
    /**
     * Returns the code from the tokens.
     */
    public function getCode() : string
    {
        $code = '';
        foreach ($this as $token) {
            $code .= $token->getContent();
        }
        return $code;
    }
    /**
     * Inserts a token at the given index.
     */
    public function insertAt(int $index, \PhpCsFixer\Doctrine\Annotation\Token $token) : void
    {
        $this->setSize($this->getSize() + 1);
        for ($i = $this->getSize() - 1; $i > $index; --$i) {
            $this[$i] = $this[$i - 1] ?? new \PhpCsFixer\Doctrine\Annotation\Token();
        }
        $this[$index] = $token;
    }
    public function offsetSet($index, $token) : void
    {
        // @phpstan-ignore-next-line as we type checking here
        if (null === $token) {
            throw new \InvalidArgumentException('Token must be an instance of PhpCsFixer\\Doctrine\\Annotation\\Token, "null" given.');
        }
        if (!$token instanceof \PhpCsFixer\Doctrine\Annotation\Token) {
            $type = \gettype($token);
            if ('object' === $type) {
                $type = \get_class($token);
            }
            throw new \InvalidArgumentException(\sprintf('Token must be an instance of PhpCsFixer\\Doctrine\\Annotation\\Token, "%s" given.', $type));
        }
        parent::offsetSet($index, $token);
    }
    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException
     */
    public function offsetUnset($index) : void
    {
        if (!isset($this[$index])) {
            throw new \OutOfBoundsException(\sprintf('Index "%s" is invalid or does not exist.', $index));
        }
        $max = \count($this) - 1;
        while ($index < $max) {
            $this[$index] = $this[$index + 1];
            ++$index;
        }
        parent::offsetUnset($index);
        $this->setSize($max);
    }
    private function getMeaningfulTokenSibling(int $index, int $direction) : ?int
    {
        while (\true) {
            $index += $direction;
            if (!$this->offsetExists($index)) {
                break;
            }
            if (!$this[$index]->isType(\ECSPrefix20220604\Doctrine\Common\Annotations\DocLexer::T_NONE)) {
                return $index;
            }
        }
        return null;
    }
}

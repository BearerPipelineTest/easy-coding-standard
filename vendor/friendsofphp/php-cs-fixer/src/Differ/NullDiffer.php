<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpCsFixer\Differ;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class NullDiffer implements \PhpCsFixer\Differ\DifferInterface
{
    /**
     * {@inheritdoc}
     * @param \SplFileInfo|null $file
     * @param string $old
     * @param string $new
     * @return string
     */
    public function diff($old, $new, $file = null)
    {
        $old = (string) $old;
        $new = (string) $new;
        return '';
    }
}
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20210724\Symfony\Component\HttpFoundation\Session;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
final class SessionBagProxy implements \ECSPrefix20210724\Symfony\Component\HttpFoundation\Session\SessionBagInterface
{
    private $bag;
    private $data;
    private $usageIndex;
    private $usageReporter;
    /**
     * @param int|null $usageIndex
     * @param callable|null $usageReporter
     */
    public function __construct(\ECSPrefix20210724\Symfony\Component\HttpFoundation\Session\SessionBagInterface $bag, array &$data, &$usageIndex, $usageReporter)
    {
        $this->bag = $bag;
        $this->data =& $data;
        $this->usageIndex =& $usageIndex;
        $this->usageReporter = $usageReporter;
    }
    public function getBag() : \ECSPrefix20210724\Symfony\Component\HttpFoundation\Session\SessionBagInterface
    {
        ++$this->usageIndex;
        if ($this->usageReporter && 0 <= $this->usageIndex) {
            ($this->usageReporter)();
        }
        return $this->bag;
    }
    public function isEmpty() : bool
    {
        if (!isset($this->data[$this->bag->getStorageKey()])) {
            return \true;
        }
        ++$this->usageIndex;
        if ($this->usageReporter && 0 <= $this->usageIndex) {
            ($this->usageReporter)();
        }
        return empty($this->data[$this->bag->getStorageKey()]);
    }
    /**
     * {@inheritdoc}
     */
    public function getName() : string
    {
        return $this->bag->getName();
    }
    /**
     * {@inheritdoc}
     * @param mixed[] $array
     * @return void
     */
    public function initialize(&$array)
    {
        ++$this->usageIndex;
        if ($this->usageReporter && 0 <= $this->usageIndex) {
            ($this->usageReporter)();
        }
        $this->data[$this->bag->getStorageKey()] =& $array;
        $this->bag->initialize($array);
    }
    /**
     * {@inheritdoc}
     */
    public function getStorageKey() : string
    {
        return $this->bag->getStorageKey();
    }
    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->bag->clear();
    }
}

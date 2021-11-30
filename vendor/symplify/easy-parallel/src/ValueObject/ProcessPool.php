<?php

declare (strict_types=1);
namespace ECSPrefix20211130\Symplify\EasyParallel\ValueObject;

use ECSPrefix20211130\React\Socket\TcpServer;
use ECSPrefix20211130\Symplify\EasyParallel\Exception\ParallelShouldNotHappenException;
/**
 * Used from https://github.com/phpstan/phpstan-src/blob/master/src/Parallel/ProcessPool.php
 */
final class ProcessPool
{
    /**
     * @var array<string, ParallelProcess>
     */
    private $processes = [];
    /**
     * @var \React\Socket\TcpServer
     */
    private $tcpServer;
    public function __construct(\ECSPrefix20211130\React\Socket\TcpServer $tcpServer)
    {
        $this->tcpServer = $tcpServer;
    }
    public function getProcess(string $identifier) : \ECSPrefix20211130\Symplify\EasyParallel\ValueObject\ParallelProcess
    {
        if (!\array_key_exists($identifier, $this->processes)) {
            throw new \ECSPrefix20211130\Symplify\EasyParallel\Exception\ParallelShouldNotHappenException(\sprintf('Process "%s" not found.', $identifier));
        }
        return $this->processes[$identifier];
    }
    public function attachProcess(string $identifier, \ECSPrefix20211130\Symplify\EasyParallel\ValueObject\ParallelProcess $parallelProcess) : void
    {
        $this->processes[$identifier] = $parallelProcess;
    }
    public function tryQuitProcess(string $identifier) : void
    {
        if (!\array_key_exists($identifier, $this->processes)) {
            return;
        }
        $this->quitProcess($identifier);
    }
    public function quitProcess(string $identifier) : void
    {
        $parallelProcess = $this->getProcess($identifier);
        $parallelProcess->quit();
        unset($this->processes[$identifier]);
        if ($this->processes !== []) {
            return;
        }
        $this->tcpServer->close();
    }
    public function quitAll() : void
    {
        foreach (\array_keys($this->processes) as $identifier) {
            $this->quitProcess($identifier);
        }
    }
}
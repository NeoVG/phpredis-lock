<?php

declare(strict_types=1);

namespace NeoVg\PhpRedisLock\Test;

use NeoVg\PhpRedisLock\ConfigStruct;
use NeoVg\PhpRedisLock\Lock;
use NeoVg\PhpRedisLock\LockStruct;
use PHPUnit\Framework\TestCase;

require_once 'config.php';

/**
 * Class LockTest
 */
class LockTest extends TestCase
{
    /**
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testGetConfig(): void
    {
        $config = Lock::getInstance()->getConfig();

        $this->assertInstanceOf(ConfigStruct::class, $config);
        $this->assertNotNull($config->host);
        $this->assertNotNull($config->port);
        $this->assertNotNull($config->database);
    }

    /**
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testGetClient(): void
    {
        $client = Lock::getInstance()->getClient();

        $this->assertInstanceOf('Redis', $client);
        $this->assertTrue($client->isConnected());
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testAcquireAndRelease(): void
    {
        $name = 'acquireAndRelease';

        Lock::getInstance()->getClient()->del($name);

        $this->assertTrue(Lock::getInstance()->acquire($name));
        $this->assertTrue(Lock::getInstance()->acquire($name));
        $this->assertTrue(Lock::getInstance()->release($name));
        $this->assertTrue(Lock::getInstance()->release($name));
        $this->assertFalse(Lock::getInstance()->release($name));
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testIsAcquired(): void
    {
        $name = 'isAcquired';

        Lock::getInstance()->getClient()->del($name);

        $this->assertFalse(Lock::getInstance()->isAcquired($name));
        Lock::getInstance()->acquire($name);
        $this->assertTrue(Lock::getInstance()->isAcquired($name));
        Lock::getInstance()->release($name);
        $this->assertFalse(Lock::getInstance()->isAcquired($name));
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testIsLocked(): void
    {
        $name = 'isLocked';

        Lock::getInstance()->getClient()->del($name);

        $this->assertFalse(Lock::getInstance()->isLocked($name));
        Lock::getInstance()->acquire($name);
        $this->assertTrue(Lock::getInstance()->isLocked($name));
        Lock::getInstance()->release($name);
        $this->assertFalse(Lock::getInstance()->isLocked($name));
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testAcquireFail(): void
    {
        $name = 'acquireFail';

        if (!($pid = pcntl_fork())) {
            Lock::getInstance()->getClient()->del($name);

            usleep(20000);
            Lock::getInstance()->acquire($name);
            usleep(500000);
            Lock::getInstance()->release($name);
            exit;
        }

        $this->assertFalse(Lock::getInstance()->isLocked($name));
        usleep(200000);
        $this->assertTrue(Lock::getInstance()->isLocked($name));
        $this->assertFalse(Lock::getInstance()->acquire($name, 0));
        usleep(500000);
        $this->assertFalse(Lock::getInstance()->isLocked($name));
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testAcquireWait(): void
    {
        $name = 'acquireWait';

        Lock::getInstance()->getClient()->del($name);
//        Lock::getInstance()->getClient()->close();

        if (!($pid = pcntl_fork())) {
            Lock::getInstance()->acquire($name);
            usleep(500000);
            Lock::getInstance()->release($name);
        }

        usleep(100000);
        $this->assertFalse(Lock::getInstance()->acquire($name, 0));
        $this->assertTrue(Lock::getInstance()->acquire($name, 2));
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testTtl(): void
    {
        $name = 'ttl';

        Lock::getInstance()->getClient()->del($name);
//        Lock::getInstance()->getClient()->close();

        if (!($pid = pcntl_fork())) {
            Lock::getInstance()->acquire($name, null, 1);
            exit;
        }

        usleep(100000);
        $this->assertFalse(Lock::getInstance()->acquire($name, 0));
        $this->assertTrue(Lock::getInstance()->acquire($name, 2));
    }

    /**
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testGet(): void
    {
        $name = 'get';

        Lock::getInstance()->getClient()->del($name);

        Lock::getInstance()->acquire($name);
        $lock = Lock::getInstance()->get($name);
        $this->assertInstanceOf(LockStruct::class, $lock);
        $this->assertEquals('get', $lock->name);
        $this->assertEquals(Lock::DEFAULT_TTL, $lock->ttl);
        Lock::getInstance()->release($name);
    }
}

<?php

declare(strict_types=1);

namespace NeoVg\PhpRedisLock;

use FlorianWolters\Component\Util\Singleton\SingletonTrait;

/**
 * Class Lock
 *
 * @method static $this getInstance()
 */
class Lock
{
    use SingletonTrait;

    const POLL_EVERY_MICROSECONDS = 50000;
    const DEFAULT_TIMEOUT         = 60;
    const DEFAULT_TTL             = 120;

    /** @var ConfigStruct */
    private ConfigStruct $_config;

    /** @var \Redis */
    private \Redis $_client;

    /** @var LockStruct[] */
    private array $_acquiredLocks = [];

    ####################################################################################################################
    # Config
    ####################################################################################################################

    /**
     * @param ConfigStruct $config
     */
    public function setConfig(ConfigStruct $config): void
    {
        $this->_config = $config;
        $this->_checkConfig();
    }

    /**
     * @return ConfigStruct|null
     */
    public function getConfig(): ?ConfigStruct
    {
        return $this->_config ?? null;
    }

    /**
     *
     */
    private function _checkConfig(): void
    {
        if (!isset($this->_config)) {
            trigger_error('Config is not set', E_USER_ERROR);
        }
        if (empty($this->_config->host)) {
            trigger_error('Config directive host is missing', E_USER_ERROR);
        }
        if (empty($this->_config->port)) {
            trigger_error('Config directive port is missing', E_USER_ERROR);
        }
        if (empty($this->_config->database)) {
            trigger_error('Config directive database is missing', E_USER_ERROR);
        }
    }

    ####################################################################################################################
    # Client
    ####################################################################################################################

    /**
     * @return \Redis
     */
    public function getClient(): \Redis
    {
        $this->_checkConfig();

        if (!isset($this->_client)) {
            $this->_client = new \Redis();
            if (!$this->_client->connect($this->_config->host, $this->_config->port)) {
                trigger_error(sprintf('Cannot connect to Redis at %s:%d', $this->_config->host, $this->_config->port), E_USER_ERROR);
            }
            if (!$this->_client->select($this->_config->database)) {
                trigger_error(sprintf('Cannot select Redis database %d at %s:%d', $this->_config->database, $this->_config->host, $this->_config->port), E_USER_ERROR);
            }
        }

        return $this->_client;
    }

    ####################################################################################################################
    # Main Functions
    ####################################################################################################################

    /**
     * @param string   $name
     * @param int      $timeout
     * @param int|null $ttl
     *
     * @return bool
     */
    public function acquire(string $name, ?int $timeout = null, ?int $ttl = null): bool
    {
        $timeout ??= static::DEFAULT_TIMEOUT;
        $ttl ??= static::DEFAULT_TTL;

        if (!$this->isAcquired($name)) {
            $lock = (new LockStruct())
                ->withName($name)
                ->withAcquired(new Lib\JsonTime())
                ->withTtl($ttl);

            if ($timeout === 0) {
                if (!$this->_acquire($lock)) {
                    return false;
                }
            } else {
                $acquired = false;
                $triedToAcquireAt = time();
                while (time() < ($triedToAcquireAt + $timeout)) {
                    if ($this->_acquire($lock)) {
                        $acquired = true;
                        break;
                    }
                    usleep(static::POLL_EVERY_MICROSECONDS);
                }
                if (!$acquired) {
                    return false;
                }
            }

            $this->_acquiredLocks[$name] = $lock;
        }

        $this->_acquiredLocks[$name]->count ??= 0;
        $this->_acquiredLocks[$name]->count++;

        return true;
    }

    /**
     * @param LockStruct $lock
     *
     * @return bool
     */
    private function _acquire(LockStruct $lock): bool
    {
        return (bool)$this->getClient()->set($lock->name, (string)$lock, ['nx', 'ex' => $lock->ttl]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function release(string $name): bool
    {
        if (!$this->isAcquired($name)) {
            return false;
        }

        $this->_acquiredLocks[$name]->count--;

        if ($this->_acquiredLocks[$name]->count === 0) {
            $this->getClient()->del($name);

            unset($this->_acquiredLocks[$name]);
        }

        return true;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isAcquired(string $name): bool
    {
        if (!array_key_exists($name, $this->_acquiredLocks)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isLocked(string $name): bool
    {
        return $this->getClient()->get($name) !== false;
    }

    /**
     * @param $name
     *
     * @return LockStruct|null
     */
    public function get($name): ?LockStruct
    {
        return LockStruct::createFromJsonNullOnError(
                $this->getClient()->get($name)
            )->clean() ?? null;
    }
}

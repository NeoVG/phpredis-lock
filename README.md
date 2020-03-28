# phpredis-lock
Simple mutex locking class with support for TTL using PHPRedis as backend.

## Setup

~~~~php
\NeoVg\PhpRedisLock\Lock::getInstance()->setConfig(
    (new \NeoVg\PhpRedisLock\ConfigStruct())
        ->withHost('127.0.0.1')
        ->withPort(6379)
        ->withDatabase(11)
);
~~~~

## Usage

### Acquire and release

~~~~php
if (!\NeoVg\PhpRedisLock\Lock::getInstance()->acquire('name')) {
    echo 'could not acquire lock';
}

if (!\NeoVg\PhpRedisLock\Lock::getInstance()->release('name')) {
    echo 'could not release lock';
}
~~~~

#### Non blocking acquire

~~~~php
\NeoVg\PhpRedisLock\Lock::getInstance()->acquire('name', 0);
~~~~

#### Custom wait time (120 seconds)

~~~~php
\NeoVg\PhpRedisLock\Lock::getInstance()->acquire('name', 120);
~~~~

#### TTL (60 seconds)

~~~~php
\NeoVg\PhpRedisLock\Lock::getInstance()->acquire('name', null, 60);
~~~~

### Check if already acquired (by this process)

~~~~php
\NeoVg\PhpRedisLock\Lock::getInstance()->isAcquired('name');
~~~~

### Check if already locked (by another process)

~~~~php
\NeoVg\PhpRedisLock\Lock::getInstance()->isLocked('name');
~~~~

### Get info about existing lock

~~~~php
$lockInfo = \NeoVg\PhpRedisLock\Lock::getInstance()->get('name;);
~~~~

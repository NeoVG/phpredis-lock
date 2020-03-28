<?php

declare(strict_types=1);

namespace NeoVg\PhpRedisLock\Test;

use NeoVg\PhpRedisLock\ConfigStruct;
use NeoVg\PhpRedisLock\Lock;

Lock::getInstance()->setConfig(
    (new ConfigStruct())
        ->withHost('127.0.0.1')
        ->withPort(6379)
        ->withDatabase(11)
);

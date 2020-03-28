<?php

declare(strict_types=1);

namespace NeoVg\PhpRedisLock;

use NeoVg\Struct\StructAbstract;

/**
 * @property string       $name
 * @property Lib\JsonTime $acquired
 * @property int          $ttl
 * @property int          $count
 *
 * @method $this withName(string $value)
 * @method $this withAcquired(Lib\JsonTime $value)
 * @method $this withTtl(int $value)
 * @method $this withCount(int $value)
 */
class LockStruct extends StructAbstract
{
}

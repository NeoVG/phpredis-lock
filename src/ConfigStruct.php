<?php

declare(strict_types=1);

namespace NeoVg\PhpRedisLock;

use NeoVg\Struct\StructAbstract;

/**
 * @property string $host
 * @property int    $port
 * @property int    $database
 *
 * @method $this withHost(string $value)
 * @method $this withPort(int $value)
 * @method $this withDatabase(int $value)
 */
class ConfigStruct extends StructAbstract
{
}

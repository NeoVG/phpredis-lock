<?php

declare(strict_types=1);

namespace NeoVg\PhpRedisLock\Lib;

use Cake\Chronos\Chronos;

/**
 * Class JsonTime
 *
 * @package GPortal\Shared\Cake\I18n
 */
class JsonTime extends Chronos implements \JsonSerializable
{
    protected const ISO8601_JSON = 'Y-m-d\TH:i:s.uP';

    protected const ISO8601_JSON_PATTERN = '/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(\.\d+)?(Z|[\+-]\d{2}:\d{2})$/';

    /**
     * Returns a new JsonTime instance created from a string in ISO8601 format.
     *
     * @param string $dateTimeString
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public static function createFromString(string $dateTimeString): self
    {
        return static::createFromFormat(
            static::ISO8601_JSON,
            static::normalizeDateTimeString($dateTimeString)
        );
    }

    /**
     * Returns a new JsonTime instance created from any DateTime object.
     *
     * @param \DateTime $dateTime
     *
     * @return $this
     */
    public static function createFromDateTime(\DateTime $dateTime): self
    {
        try {
            return static::createFromString($dateTime->format(static::ISO8601_JSON));
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Checks if a string looks like an ISO8601 datetime.
     *
     * @param string $potentialDateTimeString
     *
     * @return bool
     */
    public static function looksLikeDateTimeString(string $potentialDateTimeString): bool
    {
        return (bool)preg_match(static::ISO8601_JSON_PATTERN, $potentialDateTimeString);
    }

    /**
     * Normalizes a string containing a datetime by adding microseconds if needed.
     *
     * @param string $dateTimeString
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function normalizeDateTimeString(string $dateTimeString): string
    {
        if (!static::looksLikeDateTimeString($dateTimeString)) {
            throw new \InvalidArgumentException(sprintf('%s does not match to %s', $dateTimeString, static::ISO8601_JSON_PATTERN));
        }

        if (empty(preg_replace(static::ISO8601_JSON_PATTERN, '\\2', $dateTimeString))) {
            $dateTimeString = preg_replace(static::ISO8601_JSON_PATTERN, '\\1.000\\2\\3', $dateTimeString);
        }

        return $dateTimeString;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->format(static::ISO8601_JSON);
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->format(static::ISO8601_JSON);
    }

    /**
     * @return array|mixed
     */
    public function swaggerSerialize()
    {
        return [
            'type' => 'dateTime',
        ];
    }
}

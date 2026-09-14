<?php

/**
 * Normalize a request date to the SQL DATE format.
 *
 * Accepted formats are the Norwegian DD.MM.YYYY format and canonical
 * YYYY-MM-DD. The format is deliberately strict; DateTime's permissive
 * parser is not used because it accepts impossible calendar dates.
 *
 * @param mixed $value
 * @param string $fieldName Norwegian field name used in validation errors.
 * @param bool $allowNull Empty values are returned as null when true.
 * @return string|null
 * @throws InvalidArgumentException
 */
function normalizeRequestDate($value, string $fieldName = 'Dato', bool $allowNull = true): ?string
{
    if ($value === null || ($allowNull && $value === '')) {
        if ($allowNull) {
            return null;
        }

        throw new InvalidArgumentException($fieldName . ' er påkrevd');
    }

    if (!is_string($value)) {
        throw new InvalidArgumentException($fieldName . ' er ugyldig. Bruk DD.MM.YYYY eller YYYY-MM-DD');
    }

    if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $value, $parts)) {
        $day = (int)$parts[1];
        $month = (int)$parts[2];
        $year = (int)$parts[3];
    } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)) {
        $year = (int)$parts[1];
        $month = (int)$parts[2];
        $day = (int)$parts[3];
    } else {
        throw new InvalidArgumentException($fieldName . ' er ugyldig. Bruk DD.MM.YYYY eller YYYY-MM-DD');
    }

    if (!checkdate($month, $day, $year)) {
        throw new InvalidArgumentException($fieldName . ' er ugyldig. Bruk en gyldig kalenderdato');
    }

    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

/**
 * Normalize a request time to the SQL TIME format.
 *
 * Accepted formats are HH:mm and HH:mm:ss, using a strict 24-hour clock.
 *
 * @param mixed $value
 * @param string $fieldName Norwegian field name used in validation errors.
 * @param bool $allowNull Empty values are returned as null when true.
 * @return string|null
 * @throws InvalidArgumentException
 */
function normalizeRequestTime($value, string $fieldName = 'Tid', bool $allowNull = true): ?string
{
    if ($value === null || ($allowNull && $value === '')) {
        if ($allowNull) {
            return null;
        }

        throw new InvalidArgumentException($fieldName . ' er påkrevd');
    }

    if (!is_string($value)) {
        throw new InvalidArgumentException($fieldName . ' er ugyldig. Bruk HH:mm eller HH:mm:ss');
    }

    if (preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $value, $parts)) {
        $hour = (int)$parts[1];
        $minute = (int)$parts[2];
        $second = isset($parts[3]) && $parts[3] !== '' ? (int)$parts[3] : 0;
    } else {
        throw new InvalidArgumentException($fieldName . ' er ugyldig. Bruk HH:mm eller HH:mm:ss');
    }

    if ($hour > 23 || $minute > 59 || $second > 59) {
        throw new InvalidArgumentException($fieldName . ' er ugyldig. Bruk en gyldig 24-timers klokkeslett');
    }

    return sprintf('%02d:%02d:%02d', $hour, $minute, $second);
}
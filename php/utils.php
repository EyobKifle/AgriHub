<?php

/**
 * Escapes a string for safe HTML output to prevent XSS.
 *
 * @param string|null $string The string to escape.
 * @return string The escaped string.
 */
function e(?string $string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Converts a timestamp into a human-readable "time ago" format.
 *
 * @param string $datetime The timestamp string (e.g., from the database).
 * @param bool $full If true, returns the full date if older than a week.
 * @return string The formatted time string.
 */
function time_ago(string $datetime, bool $full = false): string
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    $string_parts = [];
    if ($diff->y) $string_parts[] = $diff->y . ' ' . $string['y'] . ($diff->y > 1 ? 's' : '');
    if ($diff->m) $string_parts[] = $diff->m . ' ' . $string['m'] . ($diff->m > 1 ? 's' : '');
    if ($weeks)   $string_parts[] = $weeks . ' ' . $string['w'] . ($weeks > 1 ? 's' : '');
    if ($days)    $string_parts[] = $days . ' ' . $string['d'] . ($days > 1 ? 's' : '');
    if ($diff->h) $string_parts[] = $diff->h . ' ' . $string['h'] . ($diff->h > 1 ? 's' : '');
    if ($diff->i) $string_parts[] = $diff->i . ' ' . $string['i'] . ($diff->i > 1 ? 's' : '');
    if ($diff->s) $string_parts[] = $diff->s . ' ' . $string['s'] . ($diff->s > 1 ? 's' : '');

    if (!$full) $string_parts = array_slice($string_parts, 0, 1);
    return $string_parts ? implode(', ', $string_parts) . ' ago' : 'just now';
}
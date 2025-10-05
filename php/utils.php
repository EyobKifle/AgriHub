<?php

/**
 * A collection of shared utility functions for the AgriHub application.
 */

/**
 * Converts a timestamp into a human-readable "time ago" format.
 * @param string $ts The timestamp string (e.g., from the database).
 * @return string The formatted time string (e.g., "5m ago").
 */
function time_ago($ts) {
    if (empty($ts)) return '';
    $t = strtotime($ts);
    if (!$t) return '';
    $diff = time() - $t;
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    return floor($diff/86400) . 'd ago';
}

/**
 * A shorthand function for htmlspecialchars to prevent XSS.
 * @param string|null $string The string to escape.
 * @return string The escaped string.
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
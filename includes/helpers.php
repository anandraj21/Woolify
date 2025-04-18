<?php
// Woolify/includes/helpers.php

if (!function_exists('getQualityLabel')) {
    function getQualityLabel($grade) {
        return match(strtoupper($grade ?? '')) {
            'A' => 'Premium',
            'B' => 'High',
            'C' => 'Standard',
            default => 'Unknown'
        };
    }
}

if (!function_exists('getStatusClass')) {
    function getStatusClass($status) {
        return match(strtolower($status ?? '')) {
            // Transaction statuses
            'completed' => 'success',
            'pending' => 'warning',
            'failed' => 'danger',
            // Batch statuses
            'available' => 'info',
            'sold' => 'secondary', // Or maybe 'success' depending on context?
            default => 'light'
        };
    }
}

if (!function_exists('getQualityClass')) {
    function getQualityClass($grade) {
        return match(strtoupper($grade ?? '')) {
            'A' => 'success',
            'B' => 'primary',
            'C' => 'info',
            default => 'secondary'
        };
    }
}

/**
 * Convert timestamp to "time ago" format
 */
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $time_diff = time() - $timestamp;

    if ($time_diff < 60) {
        return "Just now";
    } elseif ($time_diff < 3600) {
        $mins = floor($time_diff / 60);
        return $mins . " minute" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($time_diff < 604800) {
        $days = floor($time_diff / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } elseif ($time_diff < 2592000) {
        $weeks = floor($time_diff / 604800);
        return $weeks . " week" . ($weeks > 1 ? "s" : "") . " ago";
    } else {
        return date("M j, Y", $timestamp);
    }
}

/**
 * Format currency amount
 */
function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Sanitize output
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Add other common helper functions here as needed...

?> 
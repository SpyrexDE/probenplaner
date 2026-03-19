<?php
/**
 * HORIZONTAL SCROLL CONTAINER
 *
 * Wraps content in a horizontally scrollable area with
 * fade-in/out arrow buttons that only appear when scrolling is possible.
 *
 * Usage:
 *   $hScrollId    = 'my-id';       // required - unique DOM id
 *   $hScrollStep  = 200;           // optional - px per arrow click (default: 200)
 *   $hScrollClass = 'my-class';    // optional - extra classes on the scroll div
 *   include 'h-scroll-begin.php';
 *   // ... inner HTML content ...
 *   include 'h-scroll-end.php';
 *
 * Or pass $hScrollContent directly and include this file once:
 *   $hScrollContent = '<button>…</button>';
 *   include 'h-scroll-container.php';
 */

if (isset($hScrollContent)) {
    // Single-include mode: render begin + content + end
    $hScrollStep  = $hScrollStep  ?? 200;
    $hScrollClass = $hScrollClass ?? '';
    include __DIR__ . '/h-scroll-begin.php';
    echo $hScrollContent;
    include __DIR__ . '/h-scroll-end.php';
    unset($hScrollContent, $hScrollStep, $hScrollClass);
} else {
    // Split include mode: caller must include h-scroll-begin.php / h-scroll-end.php directly
    include __DIR__ . '/h-scroll-begin.php';
}

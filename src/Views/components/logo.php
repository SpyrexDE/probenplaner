<?php

/**
 * Logo Component
 */
?>
<div class="app-logo-wrap">
  <?php
  // We use the Label SVG which contains both icon and text
  // Using file_get_contents to inline the SVG for better styling control
  $svgPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/icons/branding/Probenplaner Label.svg';
  if (file_exists($svgPath)) {
    $svgContent = file_get_contents($svgPath);
    // Clean up the SVG for inlining
    $svgContent = preg_replace('/<\?xml.*?\?>/i', '', $svgContent);
    $svgContent = preg_replace('/<!DOCTYPE.*?>/i', '', $svgContent);
    echo $svgContent;
  } else {
    // Fallback to img tag if file_get_contents fails (e.g. path issues)
    echo '<img src="/assets/icons/branding/Probenplaner Label.svg" alt="Probenplaner Logo" class="app-logo-img">';
  }
  ?>
</div>

<style>
  .app-logo-wrap {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: var(--space-8);
    padding: var(--space-2);
  }

  .app-logo-wrap svg,
  .app-logo-wrap img {
    height: 144px;
    width: auto;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.08));
  }

  /* Ensure text is visible in dark mode if needed */
  [data-current-theme="dark"] .app-logo-wrap text {
    fill: #f8fafc !important;
    /* light color for dark mode */
  }

  /* Ensure font fallback feels premium */
  .app-logo-wrap text {
    font-family: 'Poppins', 'Segoe UI', sans-serif !important;
  }
</style>
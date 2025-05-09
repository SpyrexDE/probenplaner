<?php
/**
 * Reusable Code Viewer Component
 * 
 * Usage:
 * include_once __DIR__ . '/components/code_viewer.php';
 * 
 * echo codeViewer([
 *     'code' => 'Your code here',
 *     'language' => 'sql',           // Programming language for syntax highlighting
 *     'title' => 'Optional title',   // Optional: defaults to 'Code Viewer'
 *     'theme' => 'a11y-light'        // Optional: defaults to 'a11y-light'
 * ]);
 */

function codeViewer($options) {
    $code = $options['code'] ?? '';
    $language = $options['language'] ?? 'plaintext';
    $title = $options['title'] ?? 'Code Viewer';
    $theme = $options['theme'] ?? 'a11y-light';
    
    // Use base64 encoding to preserve line breaks
    $encodedCode = base64_encode($code);
    
    ob_start();
    ?>
    <div class="code-viewer">
        <iframe 
            title="<?= htmlspecialchars($title) ?>"
            src="https://highlight-embed.u-yas.dev/embed/?code=<?= $encodedCode ?>&lang=<?= htmlspecialchars($language) ?>&theme=<?= htmlspecialchars($theme) ?>&base64=true"
            style="width: 100%; height: 400px; border: none;"
            loading="lazy"
        ></iframe>
    </div>
    <?php
    return ob_get_clean();
}
?> 
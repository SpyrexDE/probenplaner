<?php
/**
 * H-SCROLL END
 *
 * Closes the horizontal scroll wrapper opened by h-scroll-begin.php.
 * Must be preceded by h-scroll-begin.php with $hScrollId set.
 */
?>
    </div><!-- /.h-scroll -->
    <button type="button" class="h-scroll-arrow right" aria-hidden="true" tabindex="-1">
        <i class="fas fa-chevron-right"></i>
    </button>
</div><!-- /.h-scroll-wrap -->

<script>
window.initHScroll(document.getElementById(<?= json_encode($hScrollId) ?>), <?= (int)$hScrollStep ?>);
</script>

<?php unset($hScrollId, $hScrollStep, $hScrollClass); ?>

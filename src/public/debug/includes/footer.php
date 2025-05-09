    <footer style="margin-top: 20px; text-align: center; color: #666;">
        Debug Dashboard | <span class="timestamp"><?= date('Y-m-d H:i:s') ?></span>
    </footer>
    
    <script src="assets/debug.js"></script>
    <script>
        // Initialize highlight.js
        document.addEventListener('DOMContentLoaded', (event) => {
            document.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightBlock(block);
            });
        });
    </script>
</body>
</html> 
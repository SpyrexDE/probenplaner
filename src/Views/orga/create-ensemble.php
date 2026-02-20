<?php $this->layout('layouts/default', ['title' => 'Ensemble erstellen', 'currentPage' => 'orga_panel', 'isFluid' => true]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
$renderComponent = true;
$orgSlug = htmlspecialchars($org['slug'] ?? '');
?>

<?php ob_start(); ?>

<?php ob_start(); ?>
<form method="POST" action="/orga/ensembles/store">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <div class="form-group-modern">
        <label class="form-label-modern">Name</label>
        <input type="text" name="name" id="ensembleName" required autofocus class="form-input-modern" placeholder="z.B. JSO Bremen">
    </div>

    <div class="form-group-modern">
        <label class="form-label-modern">Kürzel (URL)</label>
        <input type="text" name="slug" id="ensembleSlug" class="form-input-modern" placeholder="z.B. jso-bremen"
            pattern="[a-z0-9\-]+" title="Nur Kleinbuchstaben, Zahlen und Bindestriche">
        <div class="slug-preview text-subtle" style="font-size: var(--font-size-xs); margin-top: var(--space-1);">
            → <span id="slugPreviewUrl">/<?= $orgSlug ?>/...</span>
        </div>
    </div>

    <div class="form-help-text mb-4">Ein Einladungslink wird automatisch generiert.</div>
    <button type="submit" class="btn-base btn-md btn-primary" style="width: 100%;">Erstellen</button>
</form>

<script>
    (function() {
        var nameInput = document.getElementById('ensembleName');
        var slugInput = document.getElementById('ensembleSlug');
        var preview = document.getElementById('slugPreviewUrl');
        var orgSlug = '<?= $orgSlug ?>';
        var userEdited = false;

        slugInput.addEventListener('input', function() {
            userEdited = slugInput.value !== '';
            updatePreview();
        });

        nameInput.addEventListener('input', function() {
            if (!userEdited) {
                var slug = nameInput.value.toLowerCase()
                    .replace(/[äÄ]/g, 'ae').replace(/[öÖ]/g, 'oe').replace(/[üÜ]/g, 'ue').replace(/ß/g, 'ss')
                    .replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '').replace(/-+/g, '-').replace(/^-|-$/g, '');
                slugInput.value = slug;
            }
            updatePreview();
        });

        function updatePreview() {
            var val = slugInput.value || '...';
            preview.textContent = '/' + orgSlug + '/' + val;
        }
    })();
</script>
<?php
$sectionContent = ob_get_clean();
$sectionTitle = 'Neues Ensemble';
$sectionIcon = 'fa-music';
$sectionVariant = 'default';
include __DIR__ . '/../components/panel-section.php';
?>

<?php
$panelContent = ob_get_clean();
$panelTitle = htmlspecialchars($org['name'] ?? 'Organisation');
$panelBadge = 'Orga';
$panelVariant = 'orga';
$panelMaxWidth = '600px';
$panelBackUrl = '/orga/dashboard';
include __DIR__ . '/../components/panel-shell.php';
?>
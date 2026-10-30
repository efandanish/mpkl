<?php if (!empty($validationErrors)): ?>
<div class="alert alert-danger" role="alert">
    <strong>Maklumat belum disimpan. Sila semak:</strong>
    <ul class="mb-0">
        <?php foreach ($validationErrors as $message): ?>
        <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

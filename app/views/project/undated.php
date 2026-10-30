<?php if (!empty($undatedProjects)): ?>
<div class="card mt-4">
    <div class="card-header"><strong>Projek Belum Bertarikh</strong></div>
    <div class="card-body">
        <p>Projek ini belum mempunyai Tarikh Mula dan tidak termasuk dalam laporan tahunan. Lengkapkan tarikh sebenar selepas disahkan.</p>
        <div class="table-responsive"><table class="table">
            <thead><tr><th>ID Projek</th><th>Nama Projek</th><th>Jabatan</th><th>Tindakan</th></tr></thead>
            <tbody><?php foreach ($undatedProjects as $undated): ?>
                <tr>
                    <td><?= htmlspecialchars($undated['project_id'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($undated['project_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($undated['department'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><a href="<?= htmlspecialchars(ReportingYear::url('project/finance/' . $undated['id']), ENT_QUOTES, 'UTF-8') ?>">Butiran / Kewangan</a></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </div>
</div>
<?php endif; ?>

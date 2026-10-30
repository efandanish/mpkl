<?php
// The verified aggregates are DECIMAL strings. Group digits without float conversion.
$formatDashboardMoney = static function ($amount) {
    $parts = explode('.', (string) $amount, 2);
    return preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $parts[0]) . '.'
        . str_pad(isset($parts[1]) ? $parts[1] : '', 2, '0');
};
?>
<div class="dashboard-summary-grid" role="group" aria-label="Ringkasan kewangan">
    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Baki Bawa Hadapan</div>
        </div>

        <div class="stat-value">Belum Disahkan</div>
        <div class="stat-footer">Baki bawa hadapan belum disahkan.</div>
        <div class="stat-icon stat-icon-blue" aria-hidden="true"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#arrow-down-left"></use></svg></div>
    </div>

    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Jumlah Caj Pemajuan</div>
        </div>
        <div class="stat-value">RM <?= $formatDashboardMoney($totalAssessedCharges) ?></div>
        <div class="stat-footer">
            Jumlah caj ditaksir bagi tahun rekod <?= (int) $selectedYear ?>, termasuk belum dibayar.
            <?php if ($provisionalChargeCount > 0): ?>
                <span class="d-block text-warning">Sementara: termasuk <?= (int) $provisionalChargeCount ?> rekod lama dengan tahun belum disahkan; menggunakan tahun pendaftaran (created_at).</span>
            <?php endif; ?>
        </div>
        <div class="stat-icon stat-icon-blue" aria-hidden="true"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#wallet"></use></svg></div>
    </div>

    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Jumlah Kutipan</div>
        </div>

        <div class="stat-value">RM <?= $formatDashboardMoney($totalCollection) ?></div>
        <div class="stat-footer">Bayaran diterima, 1 Januari–31 Disember <?= (int) $selectedYear ?></div>
        <div class="stat-icon stat-icon-green" aria-hidden="true"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#arrow-up-right"></use></svg></div>
    </div>

    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Jumlah Perbelanjaan</div>
        </div>

        <div class="stat-value"><?= $totalExpenditure === null ? 'Belum Disahkan' : 'RM ' . $formatDashboardMoney($totalExpenditure) ?></div>
        <div class="stat-footer">
            <?php if ($totalExpenditure === null): ?>
                Perbelanjaan belum dapat disahkan: <?= (int) $unverifiedExpenseCount ?> rekod mempunyai tarikh atau amaun yang tidak sah. Tiada nilai dianggarkan.
            <?php else: ?>
                Perbelanjaan direkodkan, 1 Januari–31 Disember <?= (int) $selectedYear ?>, berdasarkan tarikh bayaran; termasuk projek tahun terdahulu.
            <?php endif; ?>
        </div>
        <div class="stat-icon stat-icon-red" aria-hidden="true"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#arrow-down"></use></svg></div>
    </div>

    <div class="dashboard-summary-pane">
        <div class="stat-heading">
            <div class="stat-label">Baki Akhir Semasa</div>
        </div>

        <div class="stat-value">Belum Tersedia</div>
        <div class="stat-footer">Baki akhir semasa memerlukan baki bawa hadapan yang disahkan, kutipan sebenar dan perbelanjaan yang sah.</div>
        <div class="stat-icon stat-icon-purple" aria-hidden="true"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#wallet"></use></svg></div>
    </div>
</div>

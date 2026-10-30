<div class="dashboard-overview">
    <div class="dashboard-page-header">
        <img class="dashboard-brand-logo" src="<?= PUBLIC_ROOT ?>img/mpkl.png"
            alt="Logo Majlis Perbandaran Kuala Langat">
        <div class="dashboard-brand-copy">
            <h2 class="dashboard-heading mb-1">
                Papan Pemuka Kewangan
            </h2>

            <p class="dashboard-subtitle text-muted mb-0">
                Ringkasan Akaun Caj Pemajuan bagi tahun <?= (int) $selectedYear ?>
            </p>
            <p class="dashboard-authority mb-0">Majlis Perbandaran Kuala Langat</p>
        </div>
    </div>

    <div class="dashboard-summary-wrapper">
    <?php
    require Config::get('VIEWS_PATH') .
        'dashboard/partials/summary-cards.php';
    ?>
    </div>
</div>

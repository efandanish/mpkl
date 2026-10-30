<aside class="app-sidebar">
    <div class="sidebar-brand">

        <div class="brand-left">

            <img class="brand-logo" src="<?= PUBLIC_ROOT ?>img/mpkl.png" alt="Logo Majlis Perbandaran Kuala Langat">

            <div class="brand-text">

                <div class="brand-title">
                    Akaun Caj
                </div>

                <div class="brand-subtitle">
                    Pemajuan
                </div>

            </div>

        </div>


        <button type="button" id="sidebarToggle" class="sidebar-toggle" title="Buka / Tutup Sidebar" aria-label="Buka / Tutup Sidebar">
            <svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#menu"></use></svg>
        </button>

    </div>


    <?php $activeModule = strtolower($this->controller->request->param('controller')); ?>
    <nav class="sidebar-nav">
<a href="<?= PUBLIC_ROOT ?>dashboard<?= ReportingYear::suffix() ?>" title="Dashboard" class="sidebar-link <?= in_array($activeModule, ['dashboardcontroller'], true) ? 'active' : '' ?>"><span class="sidebar-icon"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#dashboard"></use></svg></span><span>Dashboard</span></a>
<div class="sidebar-divider"></div><div class="sidebar-section-title">MODUL</div>
<a href="<?= PUBLIC_ROOT ?>charge<?= ReportingYear::suffix() ?>" title="Caj Pemajuan" class="sidebar-link <?= in_array($activeModule, ['chargecontroller'], true) ? 'active' : '' ?>"><span class="sidebar-icon"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#cash"></use></svg></span><span>Caj Pemajuan</span></a>
<a href="<?= PUBLIC_ROOT ?>project<?= ReportingYear::suffix() ?>" title="Projek Pembangunan" class="sidebar-link <?= in_array($activeModule, ['projectcontroller'], true) ? 'active' : '' ?>"><span class="sidebar-icon"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#buildings"></use></svg></span><span>Projek Pembangunan</span></a>
<?php if ($_SESSION['user_role'] === 'admin'): ?><div class="sidebar-divider"></div><div class="sidebar-section-title">PENTADBIRAN</div>
<a href="<?= PUBLIC_ROOT ?>user<?= ReportingYear::suffix() ?>" title="Pengurusan Pengguna" class="sidebar-link <?= in_array($activeModule, ['usercontroller'], true) ? 'active' : '' ?>"><span class="sidebar-icon"><svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#people"></use></svg></span><span>Pengurusan Pengguna</span></a>
<?php endif; ?>
</nav>

    <div class="sidebar-footer">

        <form method="POST" action="<?= PUBLIC_ROOT ?>auth/logout">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

            <button type="submit" class="sidebar-logout" title="Log Keluar" aria-label="Log Keluar">
                <span class="sidebar-icon">
                    <svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#logout"></use></svg>
                </span>

                <span>
                    Log Keluar
                </span>
            </button>

        </form>

    </div>
</aside>

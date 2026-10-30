<?php
$selectedYear = ReportingYear::selected();

$currentUserName =
    isset($_SESSION['user_name'])
    ? $_SESSION['user_name']
    : 'Pengguna';


$currentUserRole =
    isset($_SESSION['user_role'])
    ? $_SESSION['user_role']
    : 'user';


$roleLabel =
    $currentUserRole === 'admin'
    ? 'Admin'
    : 'User';


$avatarLetter =
    !empty($currentUserName)
    ? strtoupper(substr($currentUserName, 0, 1))
    : 'P';

?>

<header class="app-topbar">

    <div>

        <h4 class="topbar-title">
            Sistem Akaun Caj Pemajuan
        </h4>

        <div class="topbar-subtitle">
            Pengurusan kewangan dan projek pembangunan
        </div>

    </div>


    <div class="topbar-actions">

        <form method="GET" action="<?= PUBLIC_ROOT ?><?= isset($yearModule) ? htmlspecialchars($yearModule) : (strpos(strtolower($this->controller->request->param('controller')), 'charge') === 0 ? 'charge' : (strpos(strtolower($this->controller->request->param('controller')), 'project') === 0 ? 'project' : 'dashboard')) ?>" class="d-flex gap-2">

            <select class="form-select topbar-select" disabled>
                <option selected>
                    <?= htmlspecialchars($roleLabel) ?>
                </option>
            </select>


            <select name="year" class="form-select topbar-select" onchange="this.form.submit()">

                <?php
                $yearOptions = array_unique([(int) date('Y'), (int) date('Y') - 1, (int) date('Y') - 2, 2024, 2025, 2026, $selectedYear]);
                rsort($yearOptions);
                foreach ($yearOptions as $optionYear): ?>
                    <option value="<?= $optionYear ?>" <?= $selectedYear === $optionYear ? 'selected' : '' ?>><?= $optionYear ?></option>
                <?php endforeach; ?>

            </select>

        </form>

        <div class="dropdown">

            <button type="button" class="btn btn-light border notification-button position-relative"
                data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                <svg class="ui-icon" aria-hidden="true" focusable="false"><use href="<?= PUBLIC_ROOT ?>img/ui-icons.svg#bell"></use></svg>

                <?php if (!empty($unreadNotificationCount)): ?>

                    <span class="notification-count">
                        <?= $unreadNotificationCount ?>
                    </span>

                <?php endif; ?>

            </button>


            <div class="dropdown-menu dropdown-menu-end notification-dropdown">

                <div class="notification-header">

                    <div>

                        <div class="fw-bold">
                            Notifikasi
                        </div>

                        <div class="small text-muted">

                            <?php if (!empty($unreadNotificationCount)): ?>

                                <?= $unreadNotificationCount ?>
                                belum dibaca

                            <?php else: ?>

                                Tiada notifikasi belum dibaca

                            <?php endif; ?>

                        </div>

                    </div>


                    <?php if (!empty($unreadNotificationCount)): ?>

                        <form method="POST" action="<?= PUBLIC_ROOT ?>notification/markAllRead<?= ReportingYear::suffix() ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

                            <button type="submit" class="btn btn-sm btn-link text-decoration-none">
                                Tanda semua dibaca
                            </button>

                        </form>

                    <?php endif; ?>

                </div>


                <div class="notification-list">

                    <?php if (!empty($notifications)): ?>

                        <?php foreach ($notifications as $notification): ?>

                            <form method="POST" action="<?= PUBLIC_ROOT ?>notification/read/<?= $notification['id'] ?><?= ReportingYear::suffix() ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

                                <button type="submit"
                                    class="notification-item <?= !$notification['is_read'] ? 'notification-unread' : '' ?>">

                                    <div
                                        class="notification-icon notification-icon-<?= htmlspecialchars($notification['category']) ?>">

                                        <?php if ($notification['category'] === 'project'): ?>

                                            P

                                        <?php elseif ($notification['category'] === 'charge'): ?>

                                            C

                                        <?php elseif ($notification['category'] === 'payment'): ?>

                                            RM

                                        <?php elseif ($notification['category'] === 'variation'): ?>

                                            V

                                        <?php else: ?>

                                            !

                                        <?php endif; ?>

                                    </div>


                                    <div class="notification-content">

                                        <div class="d-flex align-items-center gap-2">

                                            <div class="notification-title">
                                                <?= htmlspecialchars($notification['title']) ?>
                                            </div>

                                            <?php if (!$notification['is_read']): ?>

                                                <span class="notification-dot"></span>

                                            <?php endif; ?>

                                        </div>


                                        <div class="notification-message">
                                            <?= htmlspecialchars($notification['message']) ?>
                                        </div>


                                        <div class="notification-time">
                                            <?= date(
                                                'd/m/Y h:i A',
                                                strtotime($notification['created_at'])
                                            ) ?>
                                        </div>

                                    </div>

                                </button>

                            </form>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="notification-empty">

                            <div class="notification-empty-icon">
                                ✓
                            </div>

                            <div class="fw-semibold">
                                Tiada notifikasi
                            </div>

                            <div class="small mt-1">
                                Tiada aktiviti baru direkodkan.
                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <div class="notification-footer">

                    <a href="<?= PUBLIC_ROOT ?>notification<?= ReportingYear::suffix() ?>" class="btn btn-sm btn-outline-primary w-100">
                        Lihat Semua Notifikasi
                    </a>

                </div>

            </div>

        </div>


        <?php

        $currentUserName =
            isset($_SESSION['user_name'])
            ? $_SESSION['user_name']
            : 'Pengguna';


        $currentUserRole =
            isset($_SESSION['user_role'])
            ? $_SESSION['user_role']
            : 'user';


        $roleLabel =
            $currentUserRole === 'admin'
            ? 'Pentadbir'
            : 'Pengguna Biasa';


        $avatarLetter =
            strtoupper(
                substr(
                    $currentUserName,
                    0,
                    1
                )
            );

        ?>


        <div class="topbar-user">

            <div class="topbar-avatar">
                <?= htmlspecialchars($avatarLetter) ?>
            </div>


            <div class="d-none d-md-block">

                <div class="fw-semibold">
                    <?= htmlspecialchars($currentUserName) ?>
                </div>

                <div class="small text-muted">
                    <?= htmlspecialchars($roleLabel) ?>
                </div>

            </div>

        </div>

    </div>

</header>

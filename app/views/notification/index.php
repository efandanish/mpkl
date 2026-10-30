<div class="d-flex justify-content-between align-items-center mb-4">

    <div>
        <h2 class="mb-1 fw-bold">
            Notifikasi
        </h2>

        <div class="text-muted">
            Sejarah aktiviti dan perubahan sistem
        </div>
    </div>


    <div class="d-flex align-items-center gap-2">

        <form method="POST" action="<?= PUBLIC_ROOT ?>notification/markAllRead<?= ReportingYear::suffix() ?>" class="m-0">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">
            <button type="submit" class="btn btn-outline-primary">
                Tanda Semua Dibaca
            </button>
        </form>


        <?php if ($_SESSION['user_role'] === 'admin'): ?>
        <form method="POST" action="<?= PUBLIC_ROOT ?>notification/clearAll<?= ReportingYear::suffix() ?>" class="clear-notifications-form m-0">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">
            <button type="submit" class="btn btn-outline-danger notification-trash-btn" title="Kosongkan Notifikasi"
                aria-label="Kosongkan Notifikasi">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"></path>
                    <path d="M8 6V4h8v2"></path>
                    <path d="M19 6l-1 14H6L5 6"></path>
                    <path d="M10 11v6"></path>
                    <path d="M14 11v6"></path>
                </svg>
            </button>
        </form>
        <?php endif; ?>

    </div>

</div>


<div class="card">

    <div class="card-body p-0">

        <?php if (!empty($notifications)): ?>

            <?php foreach ($notifications as $notification): ?>

                <div class="notification-page-item <?= !$notification['is_read'] ? 'notification-page-unread' : '' ?>">

                    <div class="notification-icon notification-icon-<?= htmlspecialchars($notification['category']) ?>">

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


                    <div class="flex-grow-1">

                        <div class="d-flex justify-content-between gap-3">

                            <div>

                                <div class="fw-bold">
                                    <?= htmlspecialchars($notification['title']) ?>

                                    <?php if (!$notification['is_read']): ?>

                                        <span class="notification-dot d-inline-block ms-1"></span>

                                    <?php endif; ?>

                                </div>

                                <div class="text-muted mt-1">
                                    <?= htmlspecialchars($notification['message']) ?>
                                </div>

                            </div>


                            <div class="small text-muted text-nowrap">

                                <?= date(
                                    'd/m/Y h:i A',
                                    strtotime(
                                        $notification['created_at']
                                    )
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="text-center text-muted py-5">

                Tiada notifikasi direkodkan.

            </div>

        <?php endif; ?>

    </div>

</div>

<a class="btn btn-primary mb-3" href="<?= PUBLIC_ROOT ?>user/create<?= ReportingYear::suffix() ?>">+ Tambah Pengguna</a>
<div class="d-flex gap-2 mb-3"><a class="btn btn-outline-secondary" href="<?= PUBLIC_ROOT ?>user<?= ReportingYear::suffix() ?>">Kembali ke Senarai</a></div>
<?php if (
    isset($_SESSION['user_role']) &&
    $_SESSION['user_role'] === 'admin'
): ?>

    <div class="module-page">

        <div class="module-container">

            <div class="card">

                <div class="card-header">

                    <div>

                        <h5 class="card-title fw-bold">
                            Pengurusan Pengguna
                        </h5>

                        <div class="small text-muted">
                            Jumlah Pengguna:
                            <?= count($users) ?>
                        </div>

                    </div>




                </div>


                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>
                                        Nama
                                    </th>

                                    <th>
                                        Email
                                    </th>

                                    <th>
                                        Peranan
                                    </th>

                                    <th>Jabatan</th>

                                    <th class="text-end">
                                        Tindakan
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if (!empty($users)): ?>

                                    <?php foreach ($users as $user): ?>

                                        <tr>

                                            <td class="fw-semibold">
                                                <?= htmlspecialchars($user['name']) ?>
                                            </td>


                                            <td>
                                                <?= htmlspecialchars($user['email']) ?>
                                            </td>


                                            <td>

                                                <?php if ($user['role'] === 'admin'): ?>

                                                    <span class="badge text-bg-primary">
                                                        Admin
                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge bg-info-subtle text-primary">
                                                        User
                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <td><?= htmlspecialchars($user['department'] === null ? 'Belum ditetapkan' : $user['department']) ?></td>

                                            <td class="text-end">

                                                <a class="btn btn-sm btn-outline-primary" href="<?= PUBLIC_ROOT ?>user/edit/<?= $user['id'] ?><?= ReportingYear::suffix() ?>">Edit</a>


                                                <?php if (
                                                    (int) $user['id']
                                                    !==
                                                    (int) $_SESSION['user_id']
                                                ): ?>

                                                    <form method="POST" action="<?= PUBLIC_ROOT ?>user/delete/<?= $user['id'] ?><?= ReportingYear::suffix() ?>"
                                                        class="delete-user-form d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            Padam
                                                        </button>

                                                    </form>

                                                <?php endif; ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>


                                <?php else: ?>

                                    <tr>

                                        <td colspan="5" class="text-center text-muted py-4">
                                            Tiada pengguna direkodkan.
                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>


                <div class="card-footer">



                </div>

            </div>

        </div>

    </div>

<?php endif; ?>

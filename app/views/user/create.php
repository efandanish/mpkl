<div class="d-flex gap-2 mb-3"><a class="btn btn-outline-secondary" href="<?= PUBLIC_ROOT ?>user<?= ReportingYear::suffix() ?>">Kembali ke Senarai</a></div>
<div class="module-page">

    <div class="module-container">

        <div class="card">

            <form method="POST" action="<?= PUBLIC_ROOT ?>user/create<?= ReportingYear::suffix() ?>" id="addUserForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

                <div class="card-header">

                    <h5 class="card-title fw-bold">
                        Daftar Pengguna Baru
                    </h5>



                </div>


                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="user-department">Jabatan</label>
                        <select name="department" id="user-department" class="form-select">
                            <option value="">Belum ditetapkan</option>
                            <?php foreach (Departments::options() as $department): ?>
                                <option value="<?= htmlspecialchars($department) ?>" <?= false ? 'selected' : '' ?>><?= htmlspecialchars($department) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Wajib dipilih untuk pendaftaran pengguna biasa.</div>
                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Nama Penuh
                        </label>

                        <input type="text" name="name" class="form-control" placeholder="cth: Ahmad Bin Ali" required>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Email
                        </label>

                        <input type="email" name="email" class="form-control" placeholder="cth: ahmad@mpkl.gov.my"
                            required>

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Kata Laluan
                        </label>

                        <input type="password" name="password" class="form-control" minlength="6" placeholder="******"
                            required>

                        <div class="form-text">
                            Sekurang-kurangnya 6 aksara.
                        </div>

                    </div>


                    <div>

                        <label class="form-label">
                            Peranan
                        </label>

                        <select name="role" class="form-select" required>

                            <option value="user">
                                Pengguna Biasa
                            </option>

                            <option value="admin">
                                Pentadbir (Admin)
                            </option>

                        </select>

                    </div>

                </div>


                <div class="card-footer">



                    <button type="submit" class="btn btn-primary">
                        Daftar
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

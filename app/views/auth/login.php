<div class="login-wrapper">

    <div class="login-card">

        <div class="login-brand">

            <div class="login-logo">
                $
            </div>

            <div>

                <h4>
                    Akaun Caj
                </h4>

                <span>
                    Pemajuan
                </span>

            </div>

        </div>


        <div class="login-heading">

            <h2>
                Selamat Datang
            </h2>

            <p>
                Log masuk untuk mengakses Sistem Akaun Caj Pemajuan
            </p>

        </div>


        <?php
        $error =
            isset($_GET['error'])
            ? $_GET['error']
            : '';
        ?>


        <?php if ($error === 'invalid'): ?>

            <div class="alert alert-danger">

                E-mel atau kata laluan tidak sah.

            </div>

        <?php endif; ?>


        <?php if ($error === 'empty'): ?>

            <div class="alert alert-warning">

                Sila masukkan e-mel dan kata laluan.

            </div>

        <?php endif; ?>


        <form method="POST" action="<?= PUBLIC_ROOT ?>auth/login">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-3">

                <label class="form-label">
                    E-mel
                </label>

                <input type="email" name="email" class="form-control login-input" placeholder="nama@example.com"
                    autocomplete="email" required>

            </div>


            <div class="mb-2">

                <label class="form-label">
                    Kata Laluan
                </label>

                <div class="password-field">

                    <input type="password" name="password" id="loginPassword" class="form-control login-input"
                        placeholder="Masukkan kata laluan" autocomplete="current-password" required>

                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        👁
                    </button>

                </div>

            </div>


            <div class="d-flex justify-content-between align-items-center mb-4">

                <div class="form-check">

                    <input class="form-check-input" type="checkbox" id="remember">

                    <label class="form-check-label small" for="remember">
                        Ingat saya
                    </label>

                </div>

            </div>


            <button type="submit" class="btn btn-primary w-100 login-button">
                Log Masuk
            </button>

        </form>


        <div class="login-footer">

            Sistem Akaun Caj Pemajuan

        </div>

    </div>

</div>


<script>

    function togglePassword() {
        const password =
            document.getElementById(
                'loginPassword'
            );

        if (password.type === 'password') {
            password.type = 'text';
        } else {
            password.type = 'password';
        }
    }

</script>

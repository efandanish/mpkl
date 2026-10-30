<!DOCTYPE html>

<html lang="ms">

<head>

    <meta charset="UTF-8">
    <meta name="reporting-year" content="<?= ReportingYear::selected() ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Sistem Akaun Caj Pemajuan
    </title>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


    <link rel="stylesheet" href="<?= PUBLIC_ROOT ?>css/app.css">


    <link rel="stylesheet" href="<?= PUBLIC_ROOT ?>css/project-finance.css">

    <link rel="icon" type="image/x-icon" href="<?= PUBLIC_ROOT ?>favicon.ico">

</head>


<body>

    <div class="app-shell">


        <?php
        require Config::get('VIEWS_PATH') .
            'layout/cajpemajuan/sidebar.php';
        ?>


        <div class="app-main">


            <?php
            require Config::get('VIEWS_PATH') .
                'layout/cajpemajuan/topbar.php';
            ?>


            <main class="app-content">

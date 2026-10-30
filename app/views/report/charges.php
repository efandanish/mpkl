<!DOCTYPE html>

<html lang="ms">

<head>

    <meta charset="UTF-8">

    <title>
        Laporan Senarai Kutipan Caj Pemajuan - Tahun
        <?= $year ?>
    </title>


    <style>
        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;

            padding: 32px 40px;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: #111;

            background: #ffffff;

            font-size: 12px;
        }


        h1 {
            margin: 0 0 18px;

            font-size: 22px;
            font-weight: 700;
        }


        table {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }


        th,
        td {
            border: 1px solid #d5d9df;

            padding: 10px 12px;

            vertical-align: top;

            text-align: left;
        }


        th {
            font-size: 10px;

            text-transform: uppercase;

            font-weight: 700;

            background: #ffffff;
        }


        td {
            font-size: 11px;
            line-height: 1.45;
        }


        .developer {
            font-weight: 700;
        }


        .project {
            margin-top: 3px;
        }


        .reference div {
            margin-bottom: 2px;
        }


        .money {
            white-space: nowrap;
        }


        .empty {
            padding: 25px;

            text-align: center;

            color: #666;
        }


        @page {
            size: A4 landscape;
            margin: 15mm;
        }


        @media print {

            body {
                padding: 0;
            }

        }
    </style>

</head>


<body>
<?php if (array_filter($reportRows, function ($row) { return $row['record_year'] === null; })): ?>
<p>Rekod bertanda “Tahun belum disahkan” menggunakan tahun pendaftaran asal buat sementara dan memerlukan semakan dokumen.</p>
<?php endif; ?>


    <?php

    $statusTitle =
        !empty($status)
        ? $status
        : 'Semua Status';

    ?>


    <h1>
        Laporan Senarai Kutipan Caj Pemajuan
        (
        <?= htmlspecialchars($statusTitle) ?>)
        - Tahun
        <?= (int) $year ?>
    </h1>


    <table>

        <thead>

            <tr>

                <th style="width: 31%;">
                    Pemaju / Projek
                </th>

                <th style="width: 22%;">
                    Rujukan
                </th>

                <th style="width: 12%;">
                    Jumlah Caj (RM)
                </th>

                <th style="width: 11%;">
                    Telah Bayar (RM)
                </th>

                <th style="width: 12%;">
                    Baki (RM)
                </th>

                <th style="width: 12%;">
                    Status
                </th>

            </tr>

        </thead>


        <tbody>

            <?php if (!empty($reportRows)): ?>

                <?php foreach ($reportRows as $charge): ?>

                    <tr>

                        <td>

                            <div class="developer">
                                <?= htmlspecialchars(
                                    $charge['developer_name']
                                ) ?>
                            </div>

                            <div class="project">
                                <?= htmlspecialchars(
                                    $charge['project_name']
                                ) ?>
                            </div>

                        </td>


                        <td class="reference">

                            <div>
                                ID OSC:
                                <?= htmlspecialchars(
                                    (string) $charge['osc_id']
                                ) ?>
                            </div>

                            <div>
                                No. Lot/PT:
                                <?= htmlspecialchars(
                                    (string) $charge['lot_number']
                                ) ?>
                            </div>

                            <div>
                                Mukim:
                                <?= htmlspecialchars(
                                    (string) $charge['mukim']
                                ) ?>
                            </div>

                        </td>


                        <td class="money">
                            <?= number_format(
                                $charge['charge_amount'],
                                2,
                                '.',
                                ''
                            ) ?>
                        </td>


                        <td class="money">
                            <?= number_format(
                                $charge['total_paid'],
                                2,
                                '.',
                                ''
                            ) ?>
                        </td>


                        <td class="money">
                            <?= number_format(
                                $charge['balance'],
                                2,
                                '.',
                                ''
                            ) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars(
                                $charge['report_status']
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td colspan="6" class="empty">
                        Tiada rekod ditemui.
                    </td>

                </tr>

            <?php endif; ?>

        </tbody>

    </table>


    <script>

        window.addEventListener(
            'load',
            function () {

                setTimeout(
                    function () {
                        window.print();
                    },
                    300
                );

            }
        );

    </script>


</body>

</html>

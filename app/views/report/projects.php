<!DOCTYPE html>

<html lang="ms">

<head>

    <meta charset="UTF-8">

    <title>
        Laporan Senarai Projek Pembangunan - Tahun
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
            font-size: 9px;

            text-transform: uppercase;

            font-weight: 700;
        }


        td {
            font-size: 10px;
            line-height: 1.4;
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


    <h1>
        Laporan Senarai Projek Pembangunan
        - Tahun
        <?= (int) $year ?>
    </h1>


    <table>

        <thead>

            <tr>

                <th style="width: 12%;">
                    ID Projek
                </th>

                <th style="width: 23%;">
                    Nama Projek
                </th>

                <th style="width: 18%;">
                    Jabatan
                </th>

                <th style="width: 14%;">
                    Nilai Kelulusan Mesyuarat (RM)
                </th>

                <th style="width: 11%;">
                    Telah Bayar (RM)
                </th>

                <th style="width: 11%;">
                    Baki (RM)
                </th>

                <th style="width: 11%;">
                    Status Projek
                </th>

            </tr>

        </thead>


        <tbody>

            <?php if (!empty($reportRows)): ?>

                <?php foreach ($reportRows as $project): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars(
                                $project['project_id']
                            ) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars(
                                $project['project_name']
                            ) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars(
                                $project['department']
                            ) ?>
                        </td>


                        <td class="money">
                            <?= number_format(
                                $project[
                                    'meeting_approval_value'
                                ],
                                2,
                                '.',
                                ''
                            ) ?>
                        </td>


                        <td class="money">
                            <?= number_format(
                                $project['total_paid'],
                                2,
                                '.',
                                ''
                            ) ?>
                        </td>


                        <td class="money">
                            <?= number_format(
                                $project['balance'],
                                2,
                                '.',
                                ''
                            ) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars(
                                $project['report_status']
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>


            <?php else: ?>

                <tr>

                    <td colspan="7" class="empty">
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

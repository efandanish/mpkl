<?php

class ReportController extends ModuleController
{
    public function initialize()
    {
        $this->loadComponents([]);

        $this->loadModel('DevelopmentCharge');
        $this->loadModel('Project');

        $this->requireLogin();
        $this->requireYearContext();

    }


    /*
    |--------------------------------------------------------------------------
    | DEVELOPMENT CHARGE REPORT
    |--------------------------------------------------------------------------
    */

    public function charges()
    {
        $year = ReportingYear::selected();


        $status =
            isset($_GET['status'])
            ? trim($_GET['status'])
            : '';


        $charges =
            $this->developmentCharge
                ->getAllByYear($year);


        $reportRows = [];


        foreach ($charges as $charge) {

            $totalPaid =
                (float) $this->developmentCharge
                    ->getTotalPaid(
                        $charge['id']
                    );


            $chargeAmount =
                (float) $charge['charge_amount'];


            $balance =
                $chargeAmount -
                $totalPaid;


            if ($totalPaid <= 0) {

                $paymentStatus =
                    'Belum Dibayar';

                $reportStatus =
                    'Belum Selesai';

            } elseif (
                $totalPaid <
                $chargeAmount
            ) {

                $paymentStatus =
                    'Bayaran Sebahagian';

                $reportStatus =
                    'Belum Selesai';

            } else {

                $paymentStatus =
                    'Selesai Dibayar';

                $reportStatus =
                    'Selesai';
            }


            /*
            |--------------------------------------------------------------------------
            | FILTER
            |--------------------------------------------------------------------------
            */

            if (
                !empty($status) &&
                $paymentStatus !== $status
            ) {
                continue;
            }


            $charge['total_paid'] =
                $totalPaid;

            $charge['balance'] =
                $balance;

            $charge['report_status'] =
                $reportStatus;


            $reportRows[] =
                $charge;
        }


        require
            Config::get('VIEWS_PATH') .
            'report/charges.php';
    }


    /*
    |--------------------------------------------------------------------------
    | PROJECT REPORT
    |--------------------------------------------------------------------------
    */

    public function projects()
    {
        $year = ReportingYear::selected();


        $department =
            isset($_GET['department'])
            ? trim($_GET['department'])
            : '';


        $projectStatus =
            isset($_GET['project_status'])
            ? trim($_GET['project_status'])
            : '';


        $paymentStatus =
            isset($_GET['payment_status'])
            ? trim($_GET['payment_status'])
            : '';


        $projects =
            $this->project
                ->getAllByYear($year);


        $reportRows = [];


        foreach ($projects as $project) {

            $approvedValue =
                (float)
                $project[
                    'meeting_approval_value'
                ];


            $totalPaid =
                (float)
                $this->project
                    ->getTotalExpenses(
                        $project['id']
                    );


            $balance =
                $approvedValue -
                $totalPaid;


            if ($totalPaid <= 0) {

                $currentPaymentStatus =
                    'Belum Dibayar';

            } elseif (
                $totalPaid <
                $approvedValue
            ) {

                $currentPaymentStatus =
                    'Bayaran Sebahagian';

            } else {

                $currentPaymentStatus =
                    'Selesai Dibayar';
            }


            /*
            |--------------------------------------------------------------------------
            | FILTERS
            |--------------------------------------------------------------------------
            */

            if (
                !empty($department) &&
                $project['department']
                !==
                $department
            ) {
                continue;
            }


            if (
                !empty($projectStatus) &&
                $project['project_status']
                !==
                $projectStatus
            ) {
                continue;
            }


            if (
                !empty($paymentStatus) &&
                $currentPaymentStatus
                !==
                $paymentStatus
            ) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | STATUS FOR REPORT
            |--------------------------------------------------------------------------
            */

            switch (
                $project['project_status']
            ) {

                case 'Dalam Proses':

                    $reportProjectStatus =
                        'IN_PROGRESS';

                    break;


                case 'Selesai':

                    $reportProjectStatus =
                        'COMPLETED';

                    break;


                case 'Dibatalkan':

                    $reportProjectStatus =
                        'CANCELLED';

                    break;


                default:

                    $reportProjectStatus =
                        'NOT_STARTED';

                    break;
            }


            $project['total_paid'] =
                $totalPaid;

            $project['balance'] =
                $balance;

            $project['report_status'] =
                $reportProjectStatus;


            $reportRows[] =
                $project;
        }


        require
            Config::get('VIEWS_PATH') .
            'report/projects.php';
    }
}

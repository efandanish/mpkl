<?php

class DashboardController extends ModuleController
{
    public function initialize()
    {
        $this->loadComponents([]);

        $this->loadModel('DevelopmentCharge');
        $this->loadModel('Project');
        $this->loadModel('Notification');

        $this->requireLogin();
        $this->requirePostCsrf();
        $this->requireYearContext();

    }

    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | CURRENT USER
        |--------------------------------------------------------------------------
        */

        $currentUserId =
            isset($_SESSION['user_id'])
            ? (int) $_SESSION['user_id']
            : 1;


        /*
        |--------------------------------------------------------------------------
        | GENERATE AUTOMATIC REMINDERS FIRST
        |--------------------------------------------------------------------------
        */

        $this->notification
            ->generateChargePaymentReminders(
                $this->developmentCharge
            );


        $this->notification
            ->generateProjectPaymentReminders(
                $this->project
            );


        $this->notification->generateProjectOverdueReminders($this->project);

        /*
        |--------------------------------------------------------------------------
        | THEN LOAD NOTIFICATIONS
        |--------------------------------------------------------------------------
        */

        $notifications =
            $this->notification
                ->getRecentForUser(
                    $currentUserId
                );


        $unreadNotificationCount =
            $this->notification
                ->getUnreadCount(
                    $currentUserId
                );


        $year = ReportingYear::selected();

        $totalAssessedCharges =
            $this->developmentCharge
                ->getTotalChargesByYear($year);

        $totalCollection = $this->developmentCharge->getTotalCollectionsByYear($year);
        $provisionalChargeCount = $this->developmentCharge->getProvisionalChargeCountByYear($year);

        $expenditure = $this->project->getExpenditureByPaymentYear($year);
        $totalExpenditure = $expenditure['total'];
        // Opening balance has no approved source; do not calculate a closing balance.

        $chargeSummary = $this->developmentCharge->getDashboardSummaryByYear($year);
        $projectSummary = $this->project->getDashboardSummaryByYear($year);


        $this->view->renderWithLayouts(

            Config::get('VIEWS_PATH') .
            'layout/cajpemajuan/',

            Config::get('VIEWS_PATH') .
            'dashboard/index.php',

            [
                'selectedYear' =>
                    $year,

                'totalAssessedCharges' => $totalAssessedCharges,
                'provisionalChargeCount' => $provisionalChargeCount,

                'totalCollection' =>
                    $totalCollection,

                'totalExpenditure' =>
                    $totalExpenditure,

                'unverifiedExpenseCount' => $expenditure['unverified_count'],

                'notifications' =>
                    $notifications,

                'unreadNotificationCount' =>
                    $unreadNotificationCount,

                'currentUserId' =>
                    $currentUserId,

                'chargeSummary' => $chargeSummary,
                'projectSummary' => $projectSummary,

            ]
        );
    }
}

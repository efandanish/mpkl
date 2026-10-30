<?php
if (!isset($testName,$pdo,$charges,$projects) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/',$testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn()!==$testName) {
    throw new RuntimeException('F-01 requires the disposable database.');
}
$f01Projects=[];$f01Charges=[];
$f01Project=function($status='Dalam Proses') use ($projects,$projectData,&$f01Projects) {
    $id=$projects->create(array_replace($projectData,['project_id'=>'F01-P-'.count($f01Projects),'project_name'=>'F01 Expense fixture','start_date'=>'1970-01-01','end_date'=>'2099-01-01','project_status'=>$status]));
    $f01Projects[]=$id;return $id;
};
$f01Charge=function($amount,$recordYear,$paymentDate=null) use ($charges,$chargeData,&$f01Charges) {
    $id=$charges->create(array_replace($chargeData,['osc_id'=>'F01-C-'.count($f01Charges),'charge_amount'=>$amount,'payment_plan'=>'Bayaran Penuh','record_year'=>$recordYear]));$f01Charges[]=$id;
    $charges->addPaymentSchedule($id,1,$amount,null);$row=$charges->getPayments($id)[0];
    if($paymentDate!==null){$charges->markPaymentPaid($row['id'],$paymentDate,null,'F01-FIXTURE');}
    return [$id,$row['id']];
};
$f01AddSchedule=function($projectId,$date,$amount) use ($projects,$pdo) {
    $projects->addSchedule($projectId,$date,$amount,'Ansuran');
    $statement=$pdo->prepare('SELECT id FROM project_payment_schedules WHERE project_id=? ORDER BY id DESC LIMIT 1');$statement->execute([$projectId]);
    return $statement->fetchColumn();
};
$f01Total=function($year,$expected,$label) use ($projects) {
    $result=$projects->getExpenditureByPaymentYear($year);
    moduleExpect($result['total']===$expected&&$result['unverified_count']===0,'F01 '.$label);
};
$f01Balances=function($cards,$label) {
    moduleExpect($cards['Baki Bawa Hadapan']==='Belum Disahkan'&&$cards['Baki Akhir Semasa']==='Belum Tersedia','F01 opening/closing not fabricated: '.$label);
};
try {
    // Exact F-01 aggregate scenario, with two legitimate full-payment obligations.
    $f01Paid=$f01Charge('100000.00',1996,'1996-01-01');$f01Unpaid=$f01Charge('200000.00',1996);
    $f01Pid=$f01Project();
    modulePost('project/addManualExpense/'.$f01Pid.'?year=1996',['year'=>'1996','amount'=>'20000.00','payment_date'=>'1996-01-02','description'=>'F01 actual expenditure'],$adminCookie,'project/finance/');
    $f01Expense=$projects->getExpenses($f01Pid)[0];
    $f01Total(1996,'20000.00','actual dated expense from an earlier-year project');
    moduleExpect($charges->getTotalChargesByYear(1996)==='300000.00'&&$charges->getTotalCollectionsByYear(1996)==='100000.00','F01 unpaid RM200000 excluded from RM100000 collections');
    foreach([$adminCookie,$userCookie] as $i=>$cookie){
        $page=moduleRequest('dashboard?year=1996',null,$cookie);$cards=moduleDashboardCards($page['body']);
        moduleExpect($page['status']===200&&$cards===['Baki Bawa Hadapan'=>'Belum Disahkan','Jumlah Caj Pemajuan'=>'RM 300,000.00','Jumlah Kutipan'=>'RM 100,000.00','Jumlah Perbelanjaan'=>'RM 20,000.00','Baki Akhir Semasa'=>'Belum Tersedia'],'F01 original acceptance scenario passes for role '.$i);
        moduleExpect(strpos($page['body'],'baki bawa hadapan yang disahkan, kutipan sebenar dan perbelanjaan yang sah')!==false,'F01 Malay closing-balance dependency explanation');
        moduleExpect(strpos($page['body'],'1 Januari–31 Disember 1996')!==false&&strpos($page['body'],'projek tahun terdahulu')!==false,'F01 explicit expense cash-year basis and historical-project inclusion');
        moduleExpect(strpos($page['body'],'dashboard?year=1996')!==false&&substr_count($page['body'],'class="dashboard-summary-pane"')===5,'F01 year navigation and five-card layout retained');
        if($i===0){echo 'F01 AFTER '.json_encode(['php'=>PHP_VERSION,'recorded_expense'=>$projects->getExpenditureByPaymentYear(1996)['total'],'cards'=>$cards])."\n";}
    }
    // No expense is inferred from a schedule, even if its status says paid.
    $f01Empty=$f01Project();$f01NoExpenseSchedule=$f01AddSchedule($f01Empty,'1990-01-01','999.99');$projects->markSchedulePaid($f01NoExpenseSchedule);
    $f01Total(1990,'0.00','verified zero without expense rows; schedules are not cash outflows');
    $f01EmptyPage=moduleRequest('dashboard?year=1990',null,$adminCookie);$f01EmptyCards=moduleDashboardCards($f01EmptyPage['body']);
    moduleExpect($f01EmptyCards['Jumlah Perbelanjaan']==='RM 0.00','F01 empty valid expense period displays verified zero');$f01Balances($f01EmptyCards,'empty year');
    // Both boundaries, exact cents, identical amounts and several schedules on one project.
    $f01Closed=$f01Project('Selesai');$f01Schedule=$f01AddSchedule($f01Closed,'2026-01-01','10.10');
    foreach([['0.01','1998-01-01',null],['0.02','1998-12-31',null],['5.05','1998-05-01',$f01Schedule],['5.05','1998-05-02',$f01Schedule],['13.13','1998-06-01',null],['7.07','1997-12-31',null],['8.08','1999-01-01',null]] as $entry){$projects->addExpense($f01Closed,$entry[2],$entry[0],$entry[1],'F01 dated fixture');}
    $projects->markSchedulePaid($f01Schedule);
    $f01Cancelled=$f01Project('Dibatalkan');$projects->addExpense($f01Cancelled,null,'2.02','1998-07-01','Recorded before cancellation');
    $f01Total(1998,'25.28','January 1/December 31 and exact cents; equal recorded expenses counted separately');
    $f01Total(1997,'7.07','previous calendar year independent of project start/creation year');
    $f01Total(1999,'8.08','next calendar year independent of claim date');
    moduleExpect($projects->getDashboardSummaryByYear(1998)['expenditure_amount']==0,'F01 existing project-start-year summary is preserved as a distinct basis');
    $f01Total(1998,'25.28','completed/cancelled project status does not erase retained actual expenses');
    // Existing scheduled payment and cancellation routes remain unchanged.
    $f01Scheduled=$f01AddSchedule($f01Pid,'2026-12-01','30.03');
    modulePost('project/recordPayment/'.$f01Scheduled.'?year=1998',['year'=>'1998','amount'=>'30.03','payment_date'=>'1998-11-15','description'=>'F01 scheduled payment'],$adminCookie,'project/finance/');
    $f01Total(1998,'55.31','scheduled payment contributes by actual payment date, not claim date');
    moduleExpect(moduleRequest('project/recordPayment/'.$f01Scheduled.'?year=1998',['year'=>'1998','amount'=>'30.03','payment_date'=>'1998-11-15'],$adminCookie)['status']===422,'F01 duplicate scheduled-payment POST is rejected');
    $f01Total(1998,'55.31','duplicate submission creates no extra recorded expense');
    $statement=$pdo->prepare('SELECT id FROM project_expenses WHERE schedule_id=?');$statement->execute([$f01Scheduled]);$f01ScheduledExpense=$statement->fetchColumn();
    modulePost('project/cancelPayment/'.$f01ScheduledExpense.'?year=1998',['year'=>'1998'],$adminCookie,'project/finance/');
    $f01Total(1998,'25.28','cancelled expense row no longer contributes');
    moduleExpect($projects->getScheduleById($f01Scheduled)['status']==='Menunggu','F01 existing schedule reopening on cancellation preserved');
    modulePost('project/cancelPayment/'.$f01Expense['id'].'?year=1996',['year'=>'1996'],$adminCookie,'project/finance/');
    $f01Total(1996,'0.00','zero after actual expense cancellation is query-established');
    // Collections retain their separate cash-year basis and existing cancellation behavior.
    $f01Older=$f01Charge('40.04',2007,'2008-01-01');$f01Charge('50.05',2008);
    moduleExpect($charges->getTotalCollectionsByYear(2008)==='40.04'&&$charges->getTotalChargesByYear(2008)==='50.05','F01 actual receipt from an older charge included; current unpaid charge excluded');
    modulePost('charge/cancelPayment/'.$f01Older[1].'?year=2008',['year'=>'2008'],$adminCookie,'charge/payment/');
    moduleExpect($charges->getTotalCollectionsByYear(2008)==='0.00','F01 cancelled charge payment excluded from collections');
    // Invalid legacy financial data produces an explicit unavailable result, not a partial/zero total.
    $f01Invalid=$f01Project();$projects->addExpense($f01Invalid,null,'-1.00','2103-01-01','Unclassified legacy negative amount');
    $f01Negative=$projects->getExpenditureByPaymentYear(2103);
    moduleExpect($f01Negative['total']===null&&$f01Negative['unverified_count']===1,'F01 unknown negative-expense meaning is not invented');
    $f01Total(1998,'25.28','bad amount in another known year does not contaminate selected year');
    $f01Mode=$pdo->query('SELECT @@SESSION.sql_mode')->fetchColumn();
    foreach(['0000-00-00','1996-00-12','1996-02-31'] as $badDate){
        $badProject=$f01Project();
        try {
            $pdo->exec("SET SESSION sql_mode='ALLOW_INVALID_DATES'");
            $statement=$pdo->prepare('INSERT INTO project_expenses(project_id,amount,payment_date,description) VALUES(?,?,?,?)');$statement->execute([$badProject,'3.03',$badDate,'Invalid historical-date fixture']);
        } finally {$pdo->exec('SET SESSION sql_mode='.$pdo->quote($f01Mode));}
        $badResult=$projects->getExpenditureByPaymentYear(1998);
        moduleExpect($badResult['total']===null&&$badResult['unverified_count']===1,'F01 cannot assign invalid date to an invented accounting year: '.$badDate);
        $badPage=moduleRequest('dashboard?year=1998',null,$userCookie);$badCards=moduleDashboardCards($badPage['body']);
        moduleExpect($badPage['status']===200&&$badCards['Jumlah Perbelanjaan']==='Belum Disahkan'&&strpos($badPage['body'],'Tiada nilai dianggarkan')!==false,'F01 invalid dates display unavailable instead of RM0: '.$badDate);
        $f01Balances($badCards,'invalid expense date');
        $projects->delete($badProject);
    }
    $f01Total(1998,'25.28','valid-period total remains exact after invalid test fixtures removed');
    // Aggregate display must not round away cents by converting DECIMAL to a float.
    $f01Render=function() {
        $selectedYear=1998;$provisionalChargeCount=0;$unverifiedExpenseCount=0;
        $totalAssessedCharges='90071992547409.91';$totalCollection='90071992547409.93';$totalExpenditure='90071992547409.95';
        ob_start();require BASE_DIR.'/app/views/dashboard/partials/summary-cards.php';return ob_get_clean();
    };
    $f01LargeCards=moduleDashboardCards($f01Render());
    moduleExpect($f01LargeCards['Jumlah Caj Pemajuan']==='RM 90,071,992,547,409.91'&&$f01LargeCards['Jumlah Kutipan']==='RM 90,071,992,547,409.93'&&$f01LargeCards['Jumlah Perbelanjaan']==='RM 90,071,992,547,409.95','F01 decimal aggregate formatting preserves every cent without float conversion');
    $f01Balances($f01LargeCards,'large verified aggregates');
} finally {
    foreach($f01Charges as $id){$charges->delete($id);}
    foreach($f01Projects as $id){$projects->delete($id);}
}
echo "F-01 financial dashboard checks completed against disposable data.\n";

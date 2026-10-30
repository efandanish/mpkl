<?php
if (!isset($testName,$pdo,$fixture) || !preg_match('/\Acajpemajuan_modules_[a-f0-9]+_test\z/',$testName)
    || $pdo->query('SELECT DATABASE()')->fetchColumn() !== $testName) {
    throw new RuntimeException('F-02 checks require the disposable database.');
}
$f02Ids=[];
$f02Seed=function($amount,$plan,array $amounts,$dates=null) use ($charges,$chargeData,&$f02Ids) {
    $data=array_replace($chargeData,['charge_amount'=>$amount,'payment_plan'=>$plan,'record_year'=>2026,'osc_id'=>'F02-'.count($f02Ids)]);
    $id=$charges->create($data);$f02Ids[]=$id;
    foreach($amounts as $i=>$value){$charges->addPaymentSchedule($id,$i+1,$value,$dates===null?'2026-12-0'.($i+1):$dates[$i]);}
    return $id;
};
$f02Input=function($id,array $override=[]) use ($charges) {
    $data=$charges->getById($id);$data['year']='2026';
    $pending=array_values(array_filter($charges->getPayments($id),function($row){return $row['status']==='Menunggu';}));
    if($pending){$data['payment_schedule_id']=array_column($pending,'id');$data['payment_schedule_amount']=array_column($pending,'amount');$data['payment_schedule_due_date']=array_map(function($row){return $row['due_date']===null?'':$row['due_date'];},$pending);}
    return array_replace($data,$override);
};
$f02Snapshot=function($id) use ($charges,$pdo,$fixture) {
    $statement=$pdo->prepare("SELECT * FROM notifications WHERE entity_type='development_charge' AND entity_id=? ORDER BY id");$statement->execute([$id]);
    $files=[];foreach(glob($fixture.'/receipts/*') as $file){if(is_file($file)){$files[basename($file)]=hash_file('sha256',$file);}}
    return [$charges->getById($id),$charges->getPayments($id),$statement->fetchAll(PDO::FETCH_ASSOC),$files];
};
$f02Reject=function($id,array $input,$label) use ($adminCookie,$f02Snapshot) {
    $before=$f02Snapshot($id);$r=moduleRequest('charge/update/'.$id.'?year=2026',$input,$adminCookie);
    moduleExpect($r['status']===422,'F02 reject '.$label);
    moduleExpect($f02Snapshot($id)===$before,'F02 entire edit/notifications/receipt history unchanged: '.$label);
    return $r;
};
// Exact failed audit scenario, using actual automatic creation rather than a manual seed.
$f02Create=array_replace($chargeData,['osc_id'=>'F02-EXACT-AFTER','charge_amount'=>'150000.00','payment_plan'=>'3 Ansuran','first_installment_due_date'=>'2024-01-31']);
modulePost('charge/create?year=2026',$f02Create,$adminCookie,'charge?success=charge_added');
$f02Id=$pdo->query("SELECT id FROM development_charges WHERE osc_id='F02-EXACT-AFTER'")->fetchColumn();$f02Ids[]=$f02Id;
$f02Original=$charges->getPayments($f02Id);
moduleExpect(array_column($f02Original,'amount')===['100000.00','25000.00','25000.00'],'F02 exact original automatic schedule');
$f02Bad=$f02Input($f02Id,['charge_amount'=>'180000.00','developer_name'=>'F02 Unsaved developer']);
$f02Response=$f02Reject($f02Id,$f02Bad,'RM150000 to RM180000 with unchanged installments');
moduleExpect(strpos($f02Response['body'],'mesti sepadan dengan Jumlah Caj')!==false,'F02 meaningful Malay principal reconciliation explanation');
moduleExpect(strpos($f02Response['body'],'value="180000.00"')!==false&&strpos($f02Response['body'],'F02 Unsaved developer')!==false&&strpos($f02Response['body'],'year=2026')!==false,'F02 rejected input and selected year retained');
moduleExpect($charges->getById($f02Id)['charge_amount']==='150000.00'&&$charges->getPayments($f02Id)===$f02Original,'F02 parent remains RM150000 and all installment fields unchanged');
echo 'F02 AFTER '.json_encode(['php'=>PHP_VERSION,'edit_http'=>$f02Response['status'],'saved_parent'=>$charges->getById($f02Id)['charge_amount'],'schedule'=>array_column($charges->getPayments($f02Id),'amount'),'schedule_unchanged'=>$charges->getPayments($f02Id)===$f02Original])."\n";
// Explicit correction succeeds; no derived amounts/dates are silently changed.
$f02Good=$f02Input($f02Id,['charge_amount'=>'180000.00','payment_schedule_amount'=>['100000.00','40000.00','40000.00'],'interest'=>'999999.99']);
modulePost('charge/update/'.$f02Id,$f02Good,$adminCookie,'charge?success=charge_updated');
moduleExpect($charges->getById($f02Id)['charge_amount']==='180000.00'&&array_column($charges->getPayments($f02Id),'amount')===['100000.00','40000.00','40000.00'],'F02 explicit consistent correction persists principal only');
moduleExpect(array_column($charges->getPayments($f02Id),'due_date')===array_column($f02Original,'due_date'),'F02 dates never silently recalculated');
$f02Reject($f02Id,$f02Input($f02Id,['payment_schedule_amount'=>['100000','40000','46400']]),'reference interest added as principal');
// IDs must include every existing pending row exactly once; no client-supplied row set is trusted.
$f02Valid=$f02Input($f02Id);$f02RowIds=$f02Valid['payment_schedule_id'];
$f02Missing=$f02Valid;unset($f02Missing['payment_schedule_id'],$f02Missing['payment_schedule_amount'],$f02Missing['payment_schedule_due_date']);
$f02Reject($f02Id,$f02Missing,'all pending arrays omitted');
foreach([
 ['payment_schedule_id'=>array_slice($f02RowIds,0,2),'payment_schedule_amount'=>['100000','40000'],'payment_schedule_due_date'=>array_slice($f02Valid['payment_schedule_due_date'],0,2)],
 ['payment_schedule_id'=>[$f02RowIds[0],$f02RowIds[0],$f02RowIds[2]]],
 ['payment_schedule_id'=>array_merge($f02RowIds,[999999]),'payment_schedule_amount'=>['100000','40000','40000','1'],'payment_schedule_due_date'=>array_merge($f02Valid['payment_schedule_due_date'],['2026-12-31'])],
 ['payment_schedule_amount'=>[['nested'],'40000','40000']]
] as $i=>$bad){$f02Reject($f02Id,array_replace($f02Valid,$bad),'missing/duplicate/extra/malformed rows '.$i);}
$f02Before=$f02Snapshot($f02Id);
modulePost('charge/update/'.$f02Id,$f02Good,$userCookie,'unauthorized');
moduleExpect(moduleRequest('charge/update/'.$f02Id,$f02Good,$adminCookie,false,false)['status']===403,'F02 edit retains CSRF enforcement');
moduleExpect($f02Snapshot($f02Id)===$f02Before,'F02 ordinary/CSRF attempts cannot mutate any financial history');
// Explicit correction to the supported RM100,000 boundary may supply two zero rows.
modulePost('charge/update/'.$f02Id,$f02Input($f02Id,['charge_amount'=>'100000.00','payment_schedule_amount'=>['100000.00','0.00','0.00']]),$adminCookie,'charge?success=charge_updated');
moduleExpect(array_column($charges->getPayments($f02Id),'amount')===['100000.00','0.00','0.00'],'F02 explicit RM100000 correction retains supported zero-principal rows');
// Exact cents, including a one-cent mismatch.
$f02Cent=$f02Seed('150000.01','3 Ansuran',['100000.00','25000.01','25000.00']);
modulePost('charge/update/'.$f02Cent,$f02Input($f02Cent,['charge_amount'=>'150000.03','payment_schedule_amount'=>['100000.00','25000.02','25000.01']]),$adminCookie,'charge?success=charge_updated');
moduleExpect(array_sum(array_map(['ChargeInstallments','cents'],array_column($charges->getPayments($f02Cent),'amount')))===15000003,'F02 fractional percentage residual conserves exact cents');
$f02Reject($f02Cent,$f02Input($f02Cent,['charge_amount'=>'150000.04']),'one cent discrepancy');
// Full payment and manual four-installment plans use their own counts, never a three-row formula.
$f02Full=$f02Seed('900.00','Bayaran Penuh',['900.00'],[null]);
$f02Reject($f02Full,$f02Input($f02Full,['charge_amount'=>'1000.00']),'full payment unmatched correction');
modulePost('charge/update/'.$f02Full,$f02Input($f02Full,['charge_amount'=>'1000.00','payment_schedule_amount'=>['1000.00']]),$adminCookie,'charge?success=charge_updated');
moduleExpect($charges->getPayments($f02Full)[0]['due_date']===null,'F02 full-payment correction preserves historical NULL deadline');
$f02Four=$f02Seed('900.00','4 Ansuran',['100.00','200.00','300.00','300.00']);
modulePost('charge/update/'.$f02Four,$f02Input($f02Four,['charge_amount'=>'901.00','payment_schedule_amount'=>['100.00','201.00','300.00','300.00']]),$adminCookie,'charge?success=charge_updated');
moduleExpect(array_column($charges->getPayments($f02Four),'amount')===['100.00','201.00','300.00','300.00'],'F02 manual four installments remain nonuniform and editable');
$f02Reject($f02Four,$f02Input($f02Four,['charge_amount'=>'902.00']),'manual four installment mismatch');
// Replacement plans must reconcile as well as existing-plan edits.
$f02Switch=$f02Seed('900.00','Bayaran Penuh',['900.00'],[null]);
$f02SwitchInput=$f02Input($f02Switch,['payment_plan'=>'4 Ansuran','new_installment_no'=>[1,2,3,4],'new_installment_amount'=>['100','200','300','301'],'new_installment_due_date'=>['2026-12-01','2026-12-02','2026-12-03','2026-12-04']]);
unset($f02SwitchInput['payment_schedule_id'],$f02SwitchInput['payment_schedule_amount'],$f02SwitchInput['payment_schedule_due_date']);
$f02Reject($f02Switch,$f02SwitchInput,'replacement plan principal mismatch');
$f02UnknownHistory=$charges->getPayments($f02Switch);$f02UnknownHistory[0]['status']='Unknown historical state';
$f02UnknownInput=array_replace($f02SwitchInput,['new_installment_amount'=>['100','200','300','300']]);
$f02UnknownResult=ChargeValidation::validate($f02UnknownInput,$charges->getById($f02Switch),$f02UnknownHistory);
moduleExpect((bool)$f02UnknownResult['errors'],'F02 unknown historical payment state cannot be replaced or guessed unpaid');
$f02SwitchInput['new_installment_amount']=['100','200','300','300'];modulePost('charge/update/'.$f02Switch,$f02SwitchInput,$adminCookie,'charge?success=charge_updated');
moduleExpect(count($charges->getPayments($f02Switch))===4,'F02 consistent explicit replacement plan remains supported');
// Unchanged legacy discrepancies/missing data are not automatically repaired and do not block metadata.
foreach([
 ['900.00','3 Ansuran',['100.00','200.00','300.00'],[null,null,null]],
 ['900.00','3 Ansuran',[],[]],
 ['900.00','4 Ansuran',['100.00','200.00','300.00','299.99'],[null,null,null,null]],
 ['900.00','3 Ansuran',['-1.00','300.00','600.00'],[null,null,null]]
] as $i=>$legacy){
    $id=$f02Seed($legacy[0],$legacy[1],$legacy[2],$legacy[3]);$original=$charges->getPayments($id);
    moduleExpect(moduleRequest('charge/edit/'.$id,null,$adminCookie)['status']===200,'F02 inconsistent/missing legacy schedule remains readable '.$i);
    modulePost('charge/update/'.$id,$f02Input($id,['developer_name'=>'F02 Metadata only '.$i]),$adminCookie,'charge?success=charge_updated');
    moduleExpect($charges->getPayments($id)===$original&&$charges->getById($id)['charge_amount']==='900.00','F02 legacy metadata edit preserves every historical schedule field '.$i);
    $f02Reject($id,$f02Input($id,['charge_amount'=>'901.00']),'unsafe legacy financial change '.$i);
}
// Existing duplicate installment numbers are tolerated only when the schedule remains untouched.
$f02Duplicate=$f02Seed('900.00','3 Ansuran',['300','300','300']);$f02DupRows=$charges->getPayments($f02Duplicate);
$pdo->exec('UPDATE development_charge_payments SET installment_no=1 WHERE id='.(int)$f02DupRows[1]['id']);
modulePost('charge/update/'.$f02Duplicate,$f02Input($f02Duplicate,['project_name'=>'F02 Unchanged legacy numbering']),$adminCookie,'charge?success=charge_updated');
$f02Reject($f02Duplicate,$f02Input($f02Duplicate,['payment_schedule_amount'=>['299','301','300']]),'duplicate historical installment numbers during financial edit');
// Paid principal/date/status/reference/file remain immutable, including during a valid pending redistribution.
$f02Paid=$f02Seed('900.00','3 Ansuran',['300.00','300.00','300.00']);$f02PaidRows=$charges->getPayments($f02Paid);
$f02Receipt='uploads/receipts/receipt_'.$f02PaidRows[0]['id'].'_f02history.pdf';file_put_contents($fixture.'/receipts/'.basename($f02Receipt),"%PDF-1.4\nF02 historical receipt\n");
$charges->markPaymentPaid($f02PaidRows[0]['id'],'2026-10-11',$f02Receipt,'F02-PAID-REFERENCE');$f02PaidOriginal=$charges->getPayments($f02Paid)[0];$f02ReceiptHash=hash_file('sha256',$fixture.'/receipts/'.basename($f02Receipt));
$f02Reject($f02Paid,$f02Input($f02Paid,['charge_amount'=>'901.00','payment_schedule_amount'=>['301','300']]),'parent change after payment even if sum matches');
$f02Reject($f02Paid,$f02Input($f02Paid,['payment_schedule_amount'=>['350','300']]),'paid plus pending principal mismatch');
$f02Forged=$f02Input($f02Paid,['payment_schedule_id'=>array_column($f02PaidRows,'id'),'payment_schedule_amount'=>['1','449','450'],'payment_schedule_due_date'=>['2026-12-01','2026-12-02','2026-12-03']]);
$f02Reject($f02Paid,$f02Forged,'paid row included among editable IDs');
modulePost('charge/update/'.$f02Paid,$f02Input($f02Paid,['payment_schedule_amount'=>['350','250']]),$adminCookie,'charge?success=charge_updated');
moduleExpect($charges->getPayments($f02Paid)[0]===$f02PaidOriginal&&hash_file('sha256',$fixture.'/receipts/'.basename($f02Receipt))===$f02ReceiptHash,'F02 valid pending redistribution never alters paid fields or receipt content');
moduleExpect(array_column($charges->getPayments($f02Paid),'amount')===['300.00','350.00','250.00'],'F02 locked paid principal included in total exactly once');
foreach($f02Ids as $id){$charges->delete($id);}
echo "F-02 reconciliation checks completed against disposable data.\n";

<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Sgcalc extends CI_Model{

	public function __construct() {
		Requests::register_autoloader();
		$this->load->library( ['deflib'] );
	}

	function paycount($paymentid){
		$data = [
			'transcount' => 0,
      'chw' => 0,
      'total_timbun' => 0,
      'subtotal' => 0,
      'bubu_charge' => 0,
      'opr_charge' => 0,
    ];
    $pay = $this->deflib->getOne('t_payment',['payment_id'=>$paymentid]);
    $dat = $this->deflib->getData('t_payment_detail', ['payment_id'=>$paymentid]);
    $cust= $this->deflib->getOne('m_customer',['customer_id'=>$pay->customer_id]);
    
    foreach ($dat['rows'] as $v) {
      $v = (array)$v;
      $data['transcount']++;
      $data['chw'] += $v['t_payment_detail.chw'];
      $data['total_timbun'] += $v['t_payment_detail.total_timbun'];
    }


    if($data['transcount']==0){
      $data['trantipe'] = 0;
      $data['dom_int'] = 0;
      $data['customer_id'] = 0;
      $data['cargo_charge'] = 0;
      $data['payment_charge'] = 0;
      $data['bubu_charge'] = 0;
      $data['opr_charge'] = 0;
      $data['bubu_paytipe'] = 0;
      $data['opr_paytipe'] = 0;
			$data['cdc_qty'] = 0;
			$data['cdc_amt'] = 0;
      $data['amount'] = 0;
      $data['tax'] = 0;
      $data['amount_withtax'] = 0;
    }else{
      $paydata = $this->deflib->getOne('t_payment', ['payment_id'=>$paymentid]);
      $rate = $this->deflib->getData('z_rate', ["buid"=>$paydata->buid, "rate_date"=>"<<$paydata->trandate"])['rows'][0];
      //ppn 0% APLOG
      if(strtolower($cust->name) == 'pt. angkasa pura logistik'){ 
        $tax = 0;
      }else{
        $tax = $rate->tax;
      }
      /* use below if charge_count() use 'value' instead of 'amount'
        cause: value is before tax
               amount is after tax
      */
      // $data['bubu_charge'] = $paydata->bubu_charge + ($paydata->bubu_charge * $rate->tax);
      // $data['opr_charge'] = ($paydata->opr_charge + $data['total_timbun']) + (($paydata->opr_charge + $data['total_timbun']) * $rate->tax);
      // $data['amount'] = $data['subtotal'] + $data['total_timbun'] + $paydata->cargo_charge + $paydata->payment_charge;
      // $data['tax'] = $rate->tax;
      // $data['amount_withtax'] = ($data['amount'] + ($data['amount'] * $data['tax']) );
      
      // if($paydata->customer_id == '24'){ //ppn 0% APLOG
      //   $rate->tax = 0;
      // }
			$data['cdc_qty'] = $rate->cdc_rate;
      $data['bubu_charge'] = $paydata->bubu_charge ;
      $data['opr_charge'] = $paydata->opr_charge + ($data['total_timbun'] * (1+$tax));

      //CDC for SUB
			$data['cdc_amt']=0;
			if($paydata->dom_int==2 && $paydata->opr_charge!=0 && $pay->buid=='CTSUB'){
        $materai = $this->deflib->getData('z_charges',['buid'=>$pay->buid,'name'=>'materai','trantipe'=>$pay->trantipe,'dom_int'=>$pay->dom_int])['rows'][0];
        $pay_chg = $this->deflib->getData('t_payment_charges',['payment_id'=>$pay->payment_id])['rows'];
        $amo_materai = 0;
        foreach ($pay_chg as $v) {
          if(strtolower($v->charge_name)=="materai"){
            $amo_materai = (($materai->amount/(1+$tax)) * $data['cdc_qty']);
          }
        }
        $data['cdc_amt'] = (($data['opr_charge']/(1+$tax)) * $data['cdc_qty']) - $amo_materai;
        // $data['cdc_amt'] = ($data['opr_charge']/(1+$tax)) * $data['cdc_qty'];
        $data['opr_charge'] = $data['opr_charge'] + ($data['cdc_amt'] * (1+$tax));
			}
      $data['amount'] = ($data['bubu_charge']/(1+$rate->tax)) + ($data['opr_charge']/(1+$tax));
      $data['tax'] = $rate->tax;
      $data['amount_withtax'] = $data['opr_charge'] + $data['bubu_charge'];
    }

    $r=$this->deflib->update('t_payment', $data, ['payment_id'=>$paymentid] );
    return $data;
  }

	function payment_detail($cargoid,$paymentid){ //detail_calc
    /*untuk membuat timbun per 24 jam
      1. $fd = 'Y-m-d H:i:s';
      2. timbun 1 : datediff -> (+1 dihilangkan)
    */
    $cargo = $this->deflib->getOne('t_cargo',['cargo_id'=>$cargoid]);
    $payment = $this->deflib->getOne('t_payment',['payment_id'=>$paymentid]);
    $rate = $this->deflib->getData('z_rate', ["buid"=>$payment->buid, "rate_date"=>"<<$payment->trandate"])['rows'][0];
		$c_charge = $this->deflib->getData('z_charges', [
      'buid'=>$cargo->buid,
      'modul'=>1,
      'trantipe'=>$cargo->trantipe,
      'dom_int'=>$cargo->dom_int,
      'name'=>'PJKP2U', /* tarif timbun % dari PJKP2U */
      'active'=>1
    ]);

    $timbun1_increment = 1;
    if($cargo->dom_int == 1 && $rate->timbun_24hr_dom == 1){
      $fd = 'Y-m-d H:i:s';
      $timbun1_increment = 0 - ( $rate->{'24hr_days_dom'} - 1 );
    }elseif($cargo->dom_int == 2 && $rate->timbun_24hr_int == 1){
      $fd = 'Y-m-d H:i:s';
      $timbun1_increment = 0 - ( $rate->{'24hr_days_int'} - 1 );
    }else{
      $fd = 'Y-m-d';
    }

    if($c_charge['total'] == 0){
      $c_charge = (object)$c_charge;
      $c_charge->amount = 0;
      $c_charge->kurs = 1;
    }else{
      $c_charge = $c_charge['rows'][0];
    }

    $data['minweight'] = $rate->minweight;

    $datein = fdate($cargo->trandate,$fd);
    $datepay = fdate($payment->trandate,$fd);

    $tarif_timbun = $c_charge->amount * $c_charge->kurs;
    if($payment->dom_int == 1){ // domestic
      $p1  = $rate->p1;
      $p2  = $rate->p2;
      $p3  = $rate->p3;
      $p4  = $rate->p4;
      $p1r = ($rate->p1_rate / 100) * $tarif_timbun;
      $p2r = ($rate->p2_rate / 100) * $tarif_timbun;
      $p3r = ($rate->p3_rate / 100) * $tarif_timbun;
      $p4r = ($rate->p4_rate / 100) * $tarif_timbun;
    }else{ // international
      $p1  = $rate->p1_int;
      $p2  = $rate->p2_int;
      $p3  = $rate->p3_int;
      $p4  = $rate->p4_int;
      $p1r = ($rate->p1_int_rate / 100) * $tarif_timbun;
      $p2r = ($rate->p2_int_rate / 100) * $tarif_timbun;
      $p3r = ($rate->p3_int_rate / 100) * $tarif_timbun;
      $p4r = ($rate->p4_int_rate / 100) * $tarif_timbun;
    }

    $d1 = date($fd, strtotime($datein.'+'.($p1-1).' days'));
    $d2 = date($fd, strtotime($datein.'+'.($p2-1).' days'));
    $d3 = date($fd, strtotime($datein.'+'.($p3-1).' days'));
    $d4 = date($fd, strtotime($datein.'+'.($p4-1).' days'));

    $tglmasuk = date_create($datein);
    $tglbayar = date_create($datepay);

    // timbun 1
    if($datepay <= $d1){
      $rd1 = date_diff($tglmasuk,$tglbayar)->days + $timbun1_increment;
      $rd1 = $rd1 < 0 ? 0 : $rd1;
    }else { $rd1 = intval($p1); }

    // timbun 2
    if($datepay >= $d1){
      $rd2 = $datepay >= $d2 ? $p2-$p1 : date_diff(date_create($d1),$tglbayar)->days;
    }else { $rd2 = 0; }

    // timbun 3
    if($datepay >= $d2){
      $rd3 = $datepay >= $d3 ? $p3-$p2 : date_diff(date_create($d2),$tglbayar)->days;
    }else { $rd3 = 0; }

    // timbun 4
    if($datepay >= $d3){
      $rd4 = $datepay >= $d4 ? $p4-$p3 : date_diff(date_create($d3),$tglbayar)->days;
    }else { $rd4 = 0; }

    return [
      'minweight' => $rate->minweight,
      'gwvol' => $cargo->chw,
      'chw' => $rate->minweight > $cargo->chw ? $rate->minweight : $cargo->chw,
      'sp1' => $rd1, 'sp2' => $rd2, 'sp3' => $rd3, 'sp4' => $rd4,
      'p1price' => $p1r, 'p2price' => $p2r, 'p3price' => $p3r, 'p4price' => $p4r,
      'total_timbun' => (($rd1 * $p1r) + ($rd2 * $p2r) + ($rd3 * $p3r) + ($rd4 * $p4r)) * ($rate->minweight > $cargo->chw ? $rate->minweight : $cargo->chw)
    ];
  }

  // called when inserting charges
  function payment_charge($paymentid,$chargeid,$qty){ //charge_calc
    $pay=$this->deflib->getOne('t_payment',['payment_id'=>$paymentid]);
    $chg=$this->deflib->getOne('z_charges',['id'=>$chargeid]);
    $rate=$this->deflib->getData('z_rate', ["buid"=>$pay->buid, "rate_date"=>"<<$pay->trandate"])['rows'][0];
    $cust=$this->deflib->getOne('m_customer',['customer_id'=>$pay->customer_id]);

    switch ($chg->value_factor) {
      case 1: $charge_qty = 1; break;
      case 2: $charge_qty = $pay->transcount; break;
      case 3: $charge_qty = $pay->chw > $rate->minweight ? $pay->chw : $rate->minweight; break;
      case 4: $charge_qty = $qty; break;
      default: $charge_qty = 0; break;
    }

    $chg->amount = $chg->amount * $chg->kurs;

    //Diskon 20% PT. Pos
    if(substr($pay->buid,0,2)=="RA"){
      if($pay->customer_id == '20' || $pay->customer_id == '34'){
        if($chg->name == "PJPK2P"){
          $diskon = ($chg->amount * 20) / 100; 
          $chg->amount = $chg->amount - $diskon;          
        }       
      }
    }

    //ppn 0% APLOG
    if(strtolower($cust->name) == 'pt. angkasa pura logistik' && $chg->org_id == 2){ 
      $rate->tax = 0;
    }

    if($chg->vat==1){ // include tax
      $val = $charge_qty * ($chg->amount / (1+$rate->tax) );
      $vat = $val * $rate->tax;
      $amo = $val + $vat;
    }else if($chg->vat==2){ // exclude tax
      $val = $charge_qty * $chg->amount;
      $vat = $val * $rate->tax;
      $amo = $val + $vat;
    }else{ // no tax
      $val = $charge_qty * $chg->amount;
      $vat = 0;
      $amo = $val;
    }

    return [
      'qty' => $charge_qty,
      'value' => $val,
      'vat' => $vat,
      'amount' => $amo
    ];
  }

  // first step to calculate, then -> paycount
  function charge_count($paymentid){
    $DD=["cargo_charge"=> 0,"payment_charge"=>0, "bubu_charge"=>0, "opr_charge"=>0];
    $det = $this->deflib->getData('t_payment_detail', ['payment_id'=>$paymentid]);
    $cid=[];
    foreach ($det['rows'] as $v) {
      array_push($cid,$v->{'t_payment_detail.cargo_id'});
    }
    $n_cid = implode(',',$cid);

    if($cid != []){
      $cch = $this->deflib->getData('t_cargo_charges', ['*'=>"cargo_id in($n_cid)"])['rows'];
      /* Cargo Charges */
      foreach ($cch as $c) {
        $DD["cargo_charge"] += $c->{'t_cargo_charges.amount'};
        /*AP1*/ if($c->org_id==1){ $DD["bubu_charge"] += $c->{'t_cargo_charges.amount'}; }
        /*APL*/ if($c->org_id==2){ $DD["opr_charge"] += $c->{'t_cargo_charges.amount'}; }
      }
    }

    /* Payment Charges */
    $dat = $this->deflib->getData('t_payment_charges', ['payment_id'=>$paymentid]);
    foreach ($dat['rows'] as $v) { $v=(array)$v;
      $DD['payment_charge'] += $v['t_payment_charges.amount'];
      /*AP1*/ if($v['org_id']==1){ $DD["bubu_charge"] += $v['t_payment_charges.amount']; }
      /*APL*/ if($v['org_id']==2){ $DD["opr_charge"] += $v['t_payment_charges.amount']; }
    }
    $this->deflib->update('t_payment', $DD, ['payment_id'=>$paymentid] );
    $this->paycount($paymentid);
    return $DD;
  }

  function paycharge_mandatory($paymentid,$buid,$trantipe,$domint){ //charge_mandatory
    $dat = $this->deflib->getData('z_charges', [
      'buid'=>$buid,
      'modul'=>2,
      'trantipe'=>$trantipe,
      'dom_int'=>$domint,
      'mandatory'=>1,
      'active'=>1
    ]);
    foreach ($dat['rows'] as $k => $v) {
      $chg = $this->payment_charge($paymentid,$v->id,1);
      $data = [ 'payment_id'=>$paymentid, 'charge_id'=>$v->id ];
      $data = array_merge($data, $chg);

      $this->deflib->create('t_payment_charges', $data);
    }
    $this->charge_count($paymentid);
  }

  function deposit($paymentid,$customerid){ //deposit_calc
    $pay=$this->deflib->getOne('t_payment',['payment_id'=>$paymentid]);
    $cust=$this->deflib->getOne('m_customer',['customer_id'=>$customerid]);

    $data = [
      'modul' => 'Payment',
      'modul_id' => $paymentid,
      'buid' => $pay->buid,
      'trandate' => $pay->trandate,
      'customer_id' => $customerid,
      'tipe' => 2,
      'amount' => 0,
      'ending_balance' => 0
    ];

    $cust_balance = 0;

    // deposit ap1
    if($pay->bubu_paytipe==2){
      $fltr = [
        'buid'=> $pay->buid,
        'customer_id'=> $customerid,
        'account_org'=> 1,
        'orby'=> 'deposit_id', 'ordr'=> 'desc'
      ];
      $deposit=$this->deflib->getData('t_deposit',$fltr);
      $data['account_org'] = 1;
      $data['amount'] = $pay->bubu_charge;
      if($deposit['total'] > 0){
        $deposit=(array)$deposit['rows'][0];
        $data['ending_balance'] = $deposit['t_deposit.ending_balance'] - $data['amount'];
      }elseif($deposit['total'] == 0){
        $data['ending_balance'] = 0 - $data['amount'];
      }
      $cust_balance += $data['ending_balance'];
      $this->deflib->create('t_deposit', $data);
    }

    // deposit aplog
    if($pay->opr_paytipe==2){
      $fltr = [
        'buid'=> $pay->buid,
        'customer_id'=> $customerid,
        'account_org'=> 2,
        'orby'=> 'deposit_id', 'ordr'=> 'desc'
      ];
      $deposit=$this->deflib->getData('t_deposit', $fltr);
      $data['account_org'] = 2;
      $data['amount'] = $pay->opr_charge;
      if($deposit['total'] > 0){
        $deposit=(array)$deposit['rows'][0];
        $data['ending_balance'] = $deposit['t_deposit.ending_balance'] - $data['amount'];
      }elseif($deposit['total'] == 0){
        $data['ending_balance'] = 0 - $data['amount'];
      }
      $cust_balance += $data['ending_balance'];
      $this->deflib->create('t_deposit', $data);
    }

    // $r=$this->deflib->update('m_customer', ['balance'=>$cust_balance], ['customer_id'=>$customerid] );
  }

  function deposit_ap1($bppid){
    $bpp = $this->deflib->getOne('ap1_bpp',['bpp_id'=>$bppid]);

    $data = [
      'modul' => 'BPP',
      'modul_id' => $bppid,
      'buid' => $bpp->buid,
      'trandate' => $bpp->payment_date,
      'customer_id' => $bpp->customer_id,
      'tipe' => 2,
      'amount' => 0,
      'ending_balance' => 0,
    ];

    if($bpp->bubu_paytipe==2){
      $fltr = [
        'buid'=> $bpp->buid,
        'customer_id'=> $bpp->customer_id,
        'account_org'=> 1,
        'orby'=> 'deposit_id', 'ordr'=> 'desc'
      ];
      $deposit=$this->deflib->getData('t_deposit',$fltr);
      $deposit=(array)$deposit['rows'][0];
      $data['account_org'] = 1;
      $data['amount'] = $bpp->total;
      $data['ending_balance'] = $deposit['t_deposit.ending_balance'] - $data['amount'];
      $this->deflib->create('t_deposit', $data);
    }

  }

  function reversal_deposit($reversalid){
    $rev =$this->deflib->getOne('t_reversal',['reversal_id'=>$reversalid]);
    $pay =$this->deflib->getOne('t_payment', ['payment_id'=>$rev->payment_id]);

    $data = [
      'modul' => 'Reversal',
      'modul_id' => $reversalid,
      'buid' => $rev->buid,
      'trandate' => $rev->trandate,
      'customer_id' => $rev->customer_id,
      'tipe' => 1,
      'amount' => 0,
      'ending_balance' => 0
    ];

    if($pay->bubu_paytipe==2){
      $fltr = [
        'buid'=> $rev->buid,
        'customer_id'=> $rev->customer_id,
        'account_org'=> 1,
        'orby'=> 'deposit_id', 'ordr'=> 'desc'
      ];
      $deposit=$this->deflib->getData('t_deposit',$fltr);
      $deposit=(array)$deposit['rows'][0];
      $data['account_org'] = 1;
      $data['amount'] = $rev->bubu_amount;
      $data['ending_balance'] = $deposit['t_deposit.ending_balance'] + $data['amount'];
      $this->deflib->create('t_deposit', $data);
    }
    if($pay->opr_paytipe==2){
      $fltr = [
        'buid'=> $rev->buid,
        'customer_id'=> $rev->customer_id,
        'account_org'=> 2,
        'orby'=> 'deposit_id', 'ordr'=> 'desc'
      ];
      $deposit=$this->deflib->getData('t_deposit', $fltr);
      $deposit=(array)$deposit['rows'][0];
      $data['account_org'] = 2;
      $data['amount'] = $rev->opr_amount;
      $data['ending_balance'] = $deposit['t_deposit.ending_balance'] + $data['amount'];
      $this->deflib->create('t_deposit', $data);
    }
  }

	function cargo_charge($cargoid,$chargeid,$qty){ //charge_calc
    $crg=$this->deflib->getOne('t_cargo',['cargo_id'=>$cargoid]);
    $chg=$this->deflib->getOne('z_charges',['id'=>$chargeid]);
    $rate=$this->deflib->getData('z_rate', ["buid"=>$crg->buid, "rate_date"=>"<<$crg->trandate"])['rows'][0];
    $cust=$this->deflib->getOne('m_customer',['customer_id'=>$crg->customer_id]);
    switch ($chg->value_factor) {
      case 1: $charge_qty = 1; break;
      case 2: $charge_qty = $crg->qty; break;
      case 3:
        if($chg->no_minweight == 1){
          $charge_qty = $crg->chw;
        }else{
          $charge_qty = $crg->chw > $rate->minweight ? $crg->chw : $rate->minweight;
        }
        break;
      case 4: $charge_qty = $qty; break;
      default: $charge_qty = 0; break;
    }

    $chg->amount = $chg->amount * $chg->kurs;
    
    //Diskon 20% PT. Pos
    if(substr($crg->buid,0,2)=="RA" && ($crg->customer_id == '20' || $crg->customer_id == '34')){
      if ($chg->name == "PJPK2P"){ //PJPK2P
        $diskon = ($chg->amount * 20) / 100; 
        $chg->amount = $chg->amount - $diskon;          
      }
    }

    //ppn 0% APLOG
    $tax_ori = $rate->tax;
    if(strtolower($cust->name) == 'pt. angkasa pura logistik' && $chg->org_id == 2){
      $rate->tax = 0;
    }

    //KDI tarif perorangan
    if($crg->buid=='CTKDI'){
      if($chargeid==259 || $chargeid==260 || $chargeid==261 || $chargeid==262){
        if($crg->customer_id == 1343){
          if($crg->trantipe == 1){
            $chg->amount = 1281;
          }else{
            $chg->amount = 1317.3;
          }
        }
      }
    }

    if($chg->vat==1){ // include tax
      $val = $charge_qty * ($chg->amount / (1+$tax_ori) );
      $vat = $val * $rate->tax;
      $amo = $val + $vat;
    }else if($chg->vat==2){ // exclude tax
			if ($crg->buid=='CTSUB') {//if CTSUB
				if ($chargeid==87 || $chargeid==88) {//if charges is INT Cargo Hendling
					if ($charge_qty < '35') {//if chw less than 35
						$val = '25000';
					}else{
						$val = $charge_qty * $chg->amount;
					}
				}else{
					$val = $charge_qty * $chg->amount;
				}
			}else {
				$val = $charge_qty * $chg->amount;
			}
      $vat = $val * $rate->tax;
      $amo = $val + $vat;
    }else{ // no tax
      $val = $charge_qty * $chg->amount;
      $vat = 0;
      $amo = $val;
    }
    return [
      'qty' => $charge_qty,
      'value' => $val,
      'vat' => $vat,
      'amount' => $amo
    ];
  }

  function cargocharge_mandatory($cargoid,$buid,$trantipe,$domint){ //charge_mandatory
    $cg = $this->deflib->getOne('t_cargo', ['cargo_id'=>$cargoid]); unset($cg->DEFINE);
    $cus = $this->deflib->getOne('m_customer', ['customer_id'=>$cg->customer_id]); unset($cus->DEFINE);
    $dat = $this->deflib->getData('z_charges', [
      'buid'=>$buid,
      'modul'=>1,
      'trantipe'=>$trantipe,
      'dom_int'=>$domint,
      'active'=>1
    ]);

    $all_airline = TRUE;

    $ready_to_insert = [];

    foreach ($dat['rows'] as $k=>$v) {
      /* mandatory */
      if($v->mandatory == 1 && $v->airline_id == 0){
        array_push($ready_to_insert, array_merge((array)$v,['cargoid'=>$cargoid]) );
      }
      /* Airline */
      elseif($v->airline_id == $cg->airline_id){
        if($v->freighter){ $all_airline = FALSE; }
        if($v->buid=='CTBDJ' && $cg->airline_id==1112){// rimbun
          // 65,1978 CKL
          // 67 Jasperindo
          // 63 GES
          // 71 APL
          // 58 DBM
          if($cg->customer_id==65 || $cg->customer_id==67 || $cg->customer_id==1978){
            array_push($ready_to_insert, array_merge((array)$v,['cargoid'=>$cargoid]) );
          }
        }elseif($v->buid=='CTBDJ' && $cg->airline_id==1163){// BBN
          if($cg->customer_id==65 || $cg->customer_id==1978 || $cg->customer_id==63 || $cg->customer_id==71 || $cg->customer_id==58){
            array_push($ready_to_insert, array_merge((array)$v,['cargoid'=>$cargoid]) );
          }
        }else{
          array_push($ready_to_insert, array_merge((array)$v,['cargoid'=>$cargoid]) );
        }
      }
      /* insert mandatory yg all airline */
      // elseif(!($v->airline_id == $cg->airline_id) && $v->airline_id == -1){
      //   array_push($ready_to_insert, array_merge((array)$v,['cargoid'=>$cargoid]) );
      // }
      /* Goodgroup */
      elseif($v->goodgroup == $cg->goodgroup){
        array_push($ready_to_insert, array_merge((array)$v,['cargoid'=>$cargoid]) );
      }
      /* Split AWB */
      elseif($cg->house == 1 && $cg->trantipe == 2){
        if(strtolower($v->name) == 'split awb'){
          array_push($ready_to_insert, array_merge((array)$v,['cargoid'=>$cargoid]) );
        }
      }

    }// end of fereach

    if($all_airline){
      foreach ($dat['rows'] as $k=>$v) {
        if($v->airline_id == -1){
          array_push($ready_to_insert, array_merge((array)$v,['cargoid'=>$cargoid]) );
        }
      }
    }

    // calculate
    foreach ($ready_to_insert as $v) {
      $chg = $this->cargo_charge($v['cargoid'],$v['id'],1);
      $data = ['cargo_id'=>$v['cargoid'], 'charge_id'=>$v['id']];
      $data = array_merge($data,$chg);

      /* tier calculation
      * 0 = default
      * 1 = tier charges, but customer not tier
      * 2 = tier charges, customer tier
      */
      if($cus->isTier == 1){
        $tgl_masuk = strtotime($cg->trandate);
        $tgl_keluar = strtotime($cg->trantipe==1?$cg->flight_date : $cg->tranout);
        $diff = floor(($tgl_keluar - $tgl_masuk) / 60 / 60);
        if($v['tier'] == 2){
          if($diff >= $v['tierStartHour'] && $diff < $v['tierEndHour']){
            $this->deflib->create('t_cargo_charges', $data);
          }
        }elseif($v['tier'] == 0){
          $this->deflib->create('t_cargo_charges', $data);
        }
      }else{
        if($v['tier'] == 0 || $v['tier'] == 1){
          $this->deflib->create('t_cargo_charges', $data);
        }
      }
    }
  }

  function item_calc($data){
    $list = $this->deflib->getData("t_cargo_item", ['cargo_id'=>$data['cargo_id'] ]);
    $obj_upd = array(
      'qty' => 0,
      'gw'  => 0,
      'vol' => 0,
      'chw' => 0
    );

    foreach ($list['rows'] as $v) {
      $obj_upd['qty'] += $v->qty;
      $obj_upd['gw']  += $v->kg;
      $obj_upd['vol'] += $v->vw;
    }
    $obj_upd['chw'] = $obj_upd['gw'] > $obj_upd['vol'] ? $obj_upd['gw'] : $obj_upd['vol'];
    // $obj_upd['chw'] = round($obj_upd['chw'],0,PHP_ROUND_HALF_DOWN);
    $x = strval($obj_upd['chw']);
    if(str_contains($x, '.')){
      $y = explode('.',$x);
      $z = strlen($y[1]) == 1 ? intval($y[1])*10 : ( strlen($y[1])>2 ? substr($y[1],0,2) : $y[1] );
      $z = intval($z) < 60 ? intval($y[0]) : intval($y[0])+1;
    }else{
      $z = round($obj_upd['chw'],0,PHP_ROUND_HALF_DOWN);
    }
    
    $obj_upd['chw'] = $z;
    $this->deflib->update("t_cargo", $obj_upd, ['cargo_id'=>$data['cargo_id']]);
  }

  function additional_calc($aid){
    $hed = $this->deflib->getOne('t_payment_additional',['additional_id'=>$aid]);
    $det = $this->deflib->getData('t_payment_additional_detail',['additional_id'=>$aid]);
    $cus = $this->deflib->getOne('m_customer', ['customer_id'=>$hed->customer_id]);
    $rate = $this->deflib->getData('z_rate', ["buid"=>$hed->buid, "rate_date"=>"<<$hed->trandate"])['rows'][0];
    $data = [
      'amount'=> 0,
      'tax'=> $rate->tax,
      'amount_withtax'=> 0
    ];
    foreach ($det['rows'] as $v) {
      $data['amount'] += $v->adet_amount;
    }

    //ppn 0% aplog
    if( strtolower($cus->name) == "pt. angkasa pura logistik"){
      $data['tax'] = 0;
    }else{
      $data['tax'] = $rate->tax;
    }

    $data['amount_withtax'] = $data['amount'] + ($data['amount']*$data['tax']);
    $this->deflib->update('t_payment_additional', $data, ['additional_id'=>$aid]);

    $hed_all = $this->deflib->getData('t_payment_additional',['payment_id'=>$hed->payment_id]);
    $addi_amount=0;
    foreach ($hed_all['rows'] as $v) {
      $addi_amount += $v->amount_withtax;
    }
    $r = $this->deflib->update('t_payment',['additional_amount'=>$addi_amount], ['payment_id'=>$hed->payment_id]);
    if($r->errmsg==""){
      $this->deposit_additional($hed->additional_id);
    }
  }

  function deposit_additional($additional_id){
    $hed=$this->deflib->getOne('t_payment_additional',['additional_id'=>$additional_id]);
    $det=$this->deflib->getData('t_payment_additional_detail',['additional_id'=>$additional_id]);
    $cus = $this->deflib->getOne('m_customer', ['customer_id'=>$hed->customer_id]);
    $rate = $this->deflib->getData('z_rate', ["buid"=>$hed->buid, "rate_date"=>"<<$hed->trandate"])['rows'][0];

    $data = [
      'modul' => 'Additional',
      'modul_id' => $additional_id,
      'buid' => $hed->buid,
      'trandate' => $hed->trandate,
      'customer_id' => $hed->customer_id,
      'tipe' => 2,
      'amount' => 0,
      'ending_balance' => 0
    ];

    $bubu_subtotal=0; $bubu_total=0;
    $opr_subtotal=0; $opr_total=0;

    foreach ($det['rows'] as $v) {
      if($v->paytipe == 2 && $v->org_id == 1){
        $bubu_subtotal += $v->adet_amount;
      }
      if($v->paytipe == 2 && $v->org_id == 2){
        $opr_subtotal += $v->adet_amount;
      }
    }

    $bubu_total = $bubu_subtotal * (1+$rate->tax);

    //ppn 0% aplog
    if( strtolower($cus->name) == "pt. angkasa pura logistik"){
      $opr_total = $opr_subtotal;
    }else{
      $opr_total = $opr_subtotal * (1+$rate->tax);
    }

    if($bubu_total > 0){
      $fltr = [
        'buid'=> $hed->buid,
        'customer_id'=> $hed->customer_id,
        'account_org'=> 1,
        'orby'=> 'deposit_id', 'ordr'=> 'desc'
      ];
      $deposit=$this->deflib->getData('t_deposit',$fltr);
      $deposit=(array)$deposit['rows'][0];
      $data['account_org'] = 1;
      $data['trannbr'] = $hed->trannbr;
      $data['amount'] = $bubu_total;
      $data['ending_balance'] = $deposit['t_deposit.ending_balance'] - $data['amount'];
      $this->deflib->create('t_deposit', $data);
    }
    if($opr_total > 0){
      $fltr = [
        'buid'=> $hed->buid,
        'customer_id'=> $hed->customer_id,
        'account_org'=> 2,
        'orby'=> 'deposit_id', 'ordr'=> 'desc'
      ];
      $deposit=$this->deflib->getData('t_deposit',$fltr);
      $deposit=(array)$deposit['rows'][0];
      $data['account_org'] = 2;
      $data['trannbr'] = $hed->trannbr;
      $data['amount'] = $opr_total;
      $data['ending_balance'] = $deposit['t_deposit.ending_balance'] - $data['amount'];
      $this->deflib->create('t_deposit', $data);
    }
  }

  function reversal_calc($reversalid){
    $rev = $this->deflib->getOne('t_reversal',['reversal_id'=>$reversalid]);
    $pay = $this->deflib->getOne('t_payment',['payment_id'=>$rev->payment_id]);
    $cus = $this->deflib->getOne('m_customer',['customer_id'=>$rev->customer_id]);
    $DD=["chw"=>0, "total_timbun"=>0, "bubu_amount"=>0, "opr_amount"=>0, "adminfee"=>0, "amount"=>0, "tax"=>$pay->tax, "amount_withtax"=>0];
    $det = $this->deflib->getData('t_reversal_detail', ['reversal_id'=>$reversalid]);

    /* Cargo Charges */
    foreach ($det['rows'] as $v) { $v = (array)$v;
      $crg = $this->deflib->getData('t_cargo_charges', ['cargo_id'=>$v['t_reversal_detail.cargo_id']]);
      foreach ($crg['rows'] as $c) { $c = (array)$c;
        /*AP1*/ if($c['org_id']==1){ $DD["bubu_amount"] += $c['t_cargo_charges.amount']; }
        /*APL*/ if($c['org_id']==2){ $DD["opr_amount"] += $c['t_cargo_charges.amount']; }
        $DD["amount"] += $c['t_cargo_charges.amount'];
      }
      $pd = $this->deflib->getOne('t_payment_detail',['cargo_id'=>$v['t_reversal_detail.cargo_id']]);
      $DD["chw"] += $pd->chw;
      $DD["total_timbun"] += $pd->total_timbun;
    }

    /* Payment Charges */
    $dat = $this->deflib->getData('t_payment_charges', ['payment_id'=>$rev->payment_id]);
    foreach ($dat['rows'] as $v) { $v=(array)$v;
      if($v['org_id']==2){
        if(strtolower($v['charge_name']) == 'admin fee' || strtolower($v['charge_name']) == 'doc fee'){
          $DD["opr_amount"] += ($v['t_payment_charges.amount'] / $pay->transcount) * $det["total"];
          $DD["amount"] += ($v['t_payment_charges.amount'] / $pay->transcount) * $det["total"];
        }else{
          $DD["opr_amount"] += $v['t_payment_charges.amount'];
          $DD["amount"] += $v['t_payment_charges.amount'];
        }
      }
    }
    if(strtolower($cus->name) == 'pt. angkasa pura logistik'){ 
      $DD["tax"] = 0;
    }


    $DD['transcount'] = $det["total"];
    $DD["opr_amount"] = $DD["opr_amount"] + ($DD["total_timbun"] * (1+$DD["tax"]));
    $DD["amount"] = ($DD["bubu_amount"] / (1+$pay->tax)) + ($DD["opr_amount"] / (1+$DD["tax"]));
    $DD["tax"] = $pay->tax;
    $DD["amount_withtax"] = $DD["opr_amount"] + $DD["bubu_amount"];

    $r = $this->deflib->update('t_reversal', $DD, ['reversal_id'=>$reversalid] );
    return $r;
  }

  function bpp_calc($bppid){
    $det = $this->deflib->getData('ap1_bpp_detail', ['bpp_id'=>$bppid])['rows'];
    $count_awb=0; $sum_chw=0; $subtotal_pjkp2u=0; $subtotal_pjkp2u_tax=0; $subtotal_pjkp2u_amount=0;
    foreach ($det as $v) {
      $count_awb = $count_awb + 1;
      $sum_chw += $v->chw;
      $subtotal_pjkp2u += $v->value;
      $subtotal_pjkp2u_tax += $v->tax;
      $subtotal_pjkp2u_amount += $v->amount;
    }

    $upd_header = [
      'count_awb'=>$count_awb,
      'sum_chw'=>$sum_chw,
      'subtotal'=>$subtotal_pjkp2u,
      'tax'=>$subtotal_pjkp2u_tax,
      'total'=>$subtotal_pjkp2u_amount,
    ];
    $this->deflib->update('ap1_bpp', $upd_header, ['bpp_id'=>$bppid]);
  }

}// end of class

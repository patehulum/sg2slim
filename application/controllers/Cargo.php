<?php defined('BASEPATH') or exit('No direct script access allowed');
require APPPATH.'/libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class Cargo extends REST_Controller {
  const tCRG  = 't_cargo';
  const tCTM  = 't_cargo_item';
  const tCHG  = 't_cargo_charges';

	public function __construct() {
		parent::__construct();
		$this->load->library( ['deflib'] );
		$this->load->model( ['sgcalc'] );
    // tesx
	}

	function header_get() {
		$u3=$this->uri->segment(3);
		if($u3!=NULL){ $prm=["cargo_id" => $u3]; }
		else{ $prm=$this->deflib->mkFilterPrm(self::tCRG, $this->query() )["pk"];  }
		$r=$this->deflib->getOne(self::tCRG,$prm);
		$this->response($r);
	}
  function header_post() { //:get pagination
  	try{
  		$this->response( $this->deflib->getData(self::tCRG, $this->post()) );
  	} catch(Exception $e){ $this->response(["errmsg"=>$e->getMessage()],500); }
  }
  function header_put() {
  	try{
  		$data= $this->put();

      if(array_key_exists("trandate",$data)){
        if($data["trandate"] == ""){
          $data["trandate"] = date('Y-m-d H:i:s', fsvr_tz($_SERVER["HTTP_SG2BU"]));
        }
      }

      if(array_key_exists("trannbr",$data)){
        if($data['trannbr'] == ""){
          $data['trannbr'] = '_blank'.$data['buid'].date('dHis', fsvr_tz($_SERVER["HTTP_SG2BU"]));
        }
      }
      $res = $this->deflib->create(self::tCRG, $data);

  		$this->response( $res );
  	} catch(Exception $e){ $this->response(["errmsg"=>$e->getMessage()],500); }
  }
  function header_patch() {
  	try{
  		$u3=$this->uri->segment(3);
  		if($u3!=NULL){ $prm=["cargo_id" => $u3]; }
  		else{ $prm=$this->deflib->mkFilterPrm(self::tCRG, $this->query() )["pk"]; }
  		if($prm==[]){ $this->response(["errmsg"=>"No Object key, cek uri parameters"], 400); }
  		else{
  			$data= $this->patch(); $modifupd=TRUE;
        $getcgo = $this->deflib->getOne('t_cargo',$prm);
  			$lock = $this->deflib->locked(self::tCRG, $prm);
        $FSU='';
  			if($lock->errmsg!=""){
          // don't include this key in web form
          if(array_key_exists('btb_date', $data)){
            $data['btb_date'] = date('Y-m-d H:i:s',fsvr_tz($_SERVER["HTTP_SG2BU"]));
          }
          if(array_key_exists('released', $data)){
            $data['released'] = date('Y-m-d H:i:s',fsvr_tz($_SERVER["HTTP_SG2BU"]));
          }
					if(!array_key_exists('bast', $data) && !array_key_exists('csd', $data) && !array_key_exists('released', $data) && !array_key_exists('withdrawn', $data) && !array_key_exists('btb_date', $data)){
            $this->response($lock);
          }
          $modifupd=FALSE;
        }else{
          if(array_key_exists('locked',$data)){
            if($data['locked'] == 2){
              $area = $data['dom_int'] == 1 ? 'D' : 'I';
              if(array_key_exists('master',$data)){
                $tipe = ['','consol_o','consol_i'][$data['trantipe']];
              }else{
                $tipe = ['','outgoing','incoming','transit','ra'][$data['trantipe']];
              }
              $thenum = $this->deflib->makenum($data['buid'],$data['trandate'],$tipe,$area);
              $data['trannbr'] = $thenum;
              $data['tranout'] = date('Y-m-d H:i:s',fsvr_tz($_SERVER["HTTP_SG2BU"]));
            }
          }
        }// end of else

        $r=$this->deflib->update(self::tCRG, $data, $prm, [], $modifupd);

				if(array_key_exists('locked',$data)) {
					if($data['locked'] == 2){
	          $this->sgcalc->cargocharge_mandatory($data['cargo_id'],$data['buid'],$data['trantipe'],$data['dom_int']);

            // TK update data ke RA setelah BTB
            $tkra = ['CTSUB','CTBPN'];
	          if( in_array($data['buid'],$tkra) ){
	            $awb_ra = $this->deflib->getData('t_cargo',['buid'=>'RA'.substr($data['buid'],2),'awb'=>$data['awb'],'ordr'=>'cargo_id','orby'=>'desc']);
	            if($awb_ra['total'] > 0){
	              $awb_ra = $awb_ra['rows'][0];
                if($awb_ra->locked==1 || $awb_ra->locked==2){
                  // $this->deflib->update('t_cargo',['qty'=>$data['qty'], 'total_qty'=>$data['total_qty'], 'gw'=>$data['gw'], 'chw'=>$data['gw'] ], ['cargo_id'=>$awb_ra->cargo_id]);
                  
                  $this->deflib->remove('t_cargo_item', ['cargo_id'=>$awb_ra->cargo_id]);
                  $this->deflib->create('t_cargo_item', ['cargo_id'=>$awb_ra->cargo_id, 'qty'=>$data['qty'], 'kg'=>$data['gw'] ]);
                  $ra_obj['cargo_id'] = $awb_ra->cargo_id;
                  $this->sgcalc->item_calc($ra_obj);

                  $this->deflib->remove('t_cargo_charges', ['cargo_id'=>$awb_ra->cargo_id]);
                  $this->sgcalc->cargocharge_mandatory($awb_ra->cargo_id,$awb_ra->buid,$awb_ra->trantipe,$awb_ra->dom_int);
                }
	            }
	          }

	        }
				}

  			$this->response($r);
  		}
  	} catch(Exception $e){ $this->response(["errmsg"=>$e->getMessage()],500); }
  }

  /* void cargo */
  function void_patch(){
    try {
      $u3=$this->uri->segment(3);
      if($u3!=NULL){ $prm=["cargo_id" => $u3]; }
      else{ $prm=$this->deflib->mkFilterPrm(self::tCRG, $this->query() )["pk"]; }
      if($prm==[]){ $this->response(["errmsg"=>"No Object key, cek uri parameters"], 400); }
      else{
        $x = $this->patch();
        $user_cancel = $this->input->server('PHP_AUTH_USER');
        if(array_key_exists('modified_by',$x)){
          if($x['modified_by']!=""){
            $user_cancel = $x['modified_by'];
          }
        }
        $data = [
          'locked'=> 4, 'modified_by'=>'', 'modified_date'=>'',
          'info'=> 'Cancel by: '.$user_cancel.' '.date('d M Y H:i',fsvr_tz($_SERVER["HTTP_SG2BU"])).'. Reason: '.$x['reason']
        ];
        $r=$this->deflib->update(self::tCRG, $data, $prm);
        $this->response($r);
      }
    }catch(Exception $e){ $this->response(["errmsg"=>$e->getMessage()],500); }
  }

  function item_get() {
  	$u3=$this->uri->segment(3);
  	if($u3!=NULL){ $prm=["id" => $u3]; }
  	else{ $prm=$this->deflib->mkFilterPrm(self::tCTM, $this->query() )["pk"];  }
  	$r=$this->deflib->getOne(self::tCTM,$prm);
  	$this->response($r);
  }
  function item_post() { //:get pagination
  	try{
  		$this->response( $this->deflib->getData(self::tCTM, $this->post()) );
  	} catch(Exception $e){ $this->response(["errmsg"=>$e->getMessage()],500); }
  }
  function item_put() {
  	try{
      $data = $this->put();
      $data['cm3'] = ($data['d'] * $data['w'] * $data['h']) * $data['qty'];
      $data['vw'] = $data['cm3'] / 6000;
      $res = $this->deflib->create(self::tCTM, $data);
      $this->sgcalc->item_calc($data);
  		$this->response( $res );
  	} catch(Exception $e){ $this->response(["errmsg"=>$e->getMessage()],500); }
  }
  function item_patch() {
  	try{
  		$u3=$this->uri->segment(3);
  		if($u3!=NULL){ $prm=["id" => $u3]; }
  		else{ $prm=$this->deflib->mkFilterPrm(self::tCTM, $this->query() )["pk"]; }
  		if($prm==[]){ $this->response(["errmsg"=>"No Object key, cek uri parameters"], 400); }
  		else{
        $data = $this->patch();
        $data['cm3'] = ($data['d'] * $data['w'] * $data['h']) * $data['qty'];
        $data['vw'] = $data['cm3'] / 6000;
  			$r=$this->deflib->update(self::tCTM, $data, $prm);
        $this->sgcalc->item_calc($data);
  			$this->response($r);
  		}
  	} catch(Exception $e){ $this->response(["errmsg"=>$e->getMessage()],500); }
  }
  function item_delete(){
  	$prm=$this->deflib->mkFilterPrm(self::tCTM, $this->query() )["pk"];
    $res = $this->deflib->remove(self::tCTM,$prm);
    $this->sgcalc->item_calc($this->query());
  	$this->response( $res );
  }

  function charge_get() {
  	$u3=$this->uri->segment(3);
  	if($u3!=NULL){ $prm=["id" => $u3]; }
  	else{ $prm=$this->deflib->mkFilterPrm(self::tCHG, $this->query() )["pk"];  }
  	$r=$this->deflib->getOne(self::tCHG,$prm);
  	$this->response($r);
  }
  function charge_post() { //:get pagination
  	try{
  		$this->response( $this->deflib->getData(self::tCHG, $this->post()) );
  	} catch(Exception $e){ $this->response(["errmsg"=>$e->getMessage()],500); }
  }
  function charge_put() {
  	try{
  		$x = $this->put();
  		$chg = $this->sgcalc->cargo_charge($x['cargo_id'],$x['charge_id'],$x['charge_qty']);
  		$pk = ['cargo_id'=>$x['cargo_id'],'charge_id'=>$x['charge_id']];
  		$data = array_merge($pk, $chg);
  		$this->response( $this->deflib->create(self::tCHG, $data) );
  	} catch(Exception $e){ $this->response(["errmsg"=>$e->getMessage()],500); }
  }
  function charge_patch() {
  	try{
  		$u3=$this->uri->segment(3);
  		if($u3!=NULL){ $prm=["id" => $u3]; }
  		else{ $prm=$this->deflib->mkFilterPrm(self::tCHG, $this->query() )["pk"]; }
  		if($prm==[]){ $this->response(["errmsg"=>"No Object key, cek uri parameters"], 400); }
  		else{
  			$r=$this->deflib->update(self::tCHG, $this->patch(), $prm);
  			$this->response($r);
  		}
  	} catch(Exception $e){ $this->response(["errmsg"=>$e->getMessage()],500); }
  }
  function charge_delete(){
  	$prm=$this->deflib->mkFilterPrm(self::tCHG, $this->query() )["pk"];
  	$this->response( $this->deflib->remove(self::tCHG,$prm) );
  }

}// end

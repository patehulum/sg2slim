<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include_once APPPATH.'/libraries/Requests.php';
require_once 'SplClassLoader.php';
$classLoader = new SplClassLoader('PhpAmqpLib', APPPATH."/libraries/php-amqplib/");
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Deflib{
	public function __construct() {
		Requests::register_autoloader();
		$this->C = & get_instance();
		$this->C->load->helper(['defapp']);
		$this->CL =strtolower($this->C->router->fetch_class());
		$this->FN =strtolower($this->C->router->fetch_method());

		$this->MT= strtolower($_SERVER["REQUEST_METHOD"]);
		$this->CES= "$this->CL/$this->FN";
		$this->CEM= "$this->CL/$this->FN/$this->MT";
		$this->otr=["N","R","W","S"];
	}
	function getAccess($uname=NULL){
		$prm=["username" => $uname==NULL?$this->C->input->server('PHP_AUTH_USER'):$uname ];
		$user=$this->getOne("z_user",$prm); unset($user->DEFINE);
		if($user->id==""){ return $user;}
		else{
			$ubr=$this->getData("z_user_bu", ["userid"=>$user->id])["rows"];
			$user->sg2bu=[];
			foreach ($ubr as $i) { $i=(array)$i;
				$rid=$i["z_user_bu.roleid"];
				$buid=$i["z_user_bu.buid"];
				$bux=$this->getOne("m_bu",["buid"=>$buid]); $bux->butype=$bux->type; $bux->buname=$bux->name; unset($bux->DEFINE); unset($bux->type);unset($bux->name);
				$axx=$this->getOne("z_role", ["roleid"=>$rid]);  unset($axx->DEFINE);
				$user->sg2bu[$buid]=(object)array_merge( (array)$bux, (array)$axx );
				$user->sg2bu[$buid]->access=[];
				$accs=$this->getData("z_role_det", ["roleid"=>$rid])["rows"];
				foreach ($accs as $itm) { $user->sg2bu[$buid]->access[$itm->modul]=$itm->nrw; }
			} return $user;
		}
	}
	function authentic($id,$pass){
		if(confval("authentication_enable")){
			$this->C->load->model(['mdcrud']); $MD=$this->C->mdcrud;
			$MD->init('z_user');
			return $MD->exist(["username" => $id, "passkey" => md5($pass), "active" => 1]);
		}else{ return TRUE; }
	}
	function authorize(){
		if(confval("authorization_enable")){
			$user=$this->getAccess();
			if($user->usertype==1){ return TRUE; } // user type 1=SUPER USER
			else{
				if(array_key_exists($this->CL,["setting"=>"","master"=>""]) &&
					array_key_exists($this->FN,['user'=>'','userrl'=>'','role'=>'','rolemn'=>'','roledet'=>'',
						'customer'=>'','airline'=>'','area'=>'','good'=>'','country'=>'','city'=>'','port'=>'','bu'=>'']) &&
					$this->MT=="get" ){ // allow read to master and setting
					return true;
			}
			foreach ($user->sg2bu as $k => $v) {
				$access=$v->access;
				$nrw=array_key_exists($this->CES,$access)?$access[$this->CES]:"N";
				$clearance=array_keys($this->otr, $nrw);
				$clearance=$clearance==[] ? 0: $clearance[0];
				if(array_key_exists($this->CES,confval("endpoints"))){
					$ep=confval("endpoints")[$this->CES];
				}else{$ep=["U"=>0];}
				switch($this->MT){
					case "patch":$ox=array_key_exists("U",$ep) ? $ep["U"]: 2; break;
					case "put":  $ox=array_key_exists("I",$ep) ? $ep["I"]: 2; break;
					case "post": $ox=array_key_exists("L",$ep) ? $ep["L"]: 1; break;
					default: 		 $ox=array_key_exists("V",$ep) ? $ep["V"]: 1; break;
				}
				return $clearance >= $ox;
			}
		}
	}else{ return TRUE; }
}
function ep_access($t){
	if(array_key_exists("HTTP_SG2BU",$_SERVER)){
		if(!$this->authorize()){ $t->response(["errmsg"=>" Insuficient role access: "],401); die();  }
	}else{ $t->response(["header 'SG2BU' not detected"],401); die();  }
}
function bu_tz($buid){
	$MD=$this->C->mdcrud;
	$bux=$MD->query("select timezone from m_bu where buid='$buid'");
	$bux=@$bux[0]->timezone;
	$bux=$bux==NULL?7:$bux;
	return $bux;
}
function islogin($id,$pass) {
		$ipByPass=[ //"localhost","::1","127.0.0.1"
	];
	$auth=in_array($_SERVER['REMOTE_ADDR'], $ipByPass)
	? 1
	: $this->authentic($id,$pass);
	return ($auth==1 ? TRUE : FALSE);
}

function required_exist($req, $arr){
	$missing=[];
	foreach ($req as $p) { if(! array_key_exists($p,$arr)){ array_push($missing,$p); } }
	return (object)[ "result"=> count($missing)==0, "missing"=>$missing ];
}
function mkFilterPrm($tblName, $fltArr){
	$this->C->load->model(['mdcrud']); $MD=$this->C->mdcrud;
	$MD->init($tblName);
	return $MD->mk_filter($fltArr);
}
function locked($tblName, $prm){
	$R=["id"=>$prm,"errmsg"=>""];
	$this->C->load->model(['mdcrud']); $MD=$this->C->mdcrud;
	$MD->init($tblName);
	$lock=$MD->line($prm)->locked;
	if((int)$lock > 1){
		switch ($lock) {
			case 2: $locked_text = 'Released'; break;
			case 3: $locked_text = 'Closed'; break;
			case 4: $locked_text = 'Canceled'; break;
			default: break;
		}
		$R["errmsg"]="Transaction already {$locked_text}!";
	} return (object)$R;
}
function getData($tblName, $fltArr){
	$this->C->load->model(['mdcrud']); $MD=$this->C->mdcrud;
	$MD->init($tblName);
	$F=$MD->mk_filter($fltArr);
	foreach ($F["cfg"]["filter"] as $k => $v) {
		// if(count($F["cfg"]["filter"])==1 && $v=="" && !endsWith($k,'null') ){ return ["rows"=>[$MD->make_empty()]]; die(); }
	}
	return $MD->get($F["cfg"]);//$x["pk"] !== [] ? $MD->line($x["pk"]) : $MD->get($x["cfg"]) ;
}
function getOne($tblName, $pk){
	$this->C->load->model(['mdcrud']); $MD=$this->C->mdcrud;
	$MD->init($tblName);
	return $MD->line($pk);
}
function save($tbl, $data){
	$this->C->load->model(['mdcrud']); $MD=$this->C->mdcrud;
	$MD->init($tbl);
	$MD->tbl_dml($data);
	$mdr=$MD->insorupd($data);
	return $mdr;
}
function create($tblName, $data){
	$this->C->load->model(['mdcrud']); $MD=$this->C->mdcrud;
	$MD->init($tblName);
	$DD=$MD->def_getall($data, true);
	return $MD->ins($DD);
}
function update($tblName, $data, $pk, $pkupdate=[], $modifupd=TRUE){
	$this->C->load->model(['mdcrud']); $MD=$this->C->mdcrud;
	$MD->init($tblName);
	$DD=$MD->def_getall($data, false);
	return $MD->upd($DD, $pk, $pkupdate, $modifupd);
}
function remove($tblName, $pk){
	$this->C->load->model(['mdcrud']); $MD=$this->C->mdcrud;
	$MD->init($tblName);
	return $MD->remove($pk);
}
function makenum($buid,$date,$modul,$area){
	$this->C->load->model(['mdcrud']); $MD=$this->C->mdcrud;
	$modul = strtolower($modul);
	switch ($modul) {
		case 'billing': 	$doc = 'BILL'; break;
		case 'booking': 	$doc = 'BK'; break;
		case 'consol_o': 	$doc = 'MO'; break;
		case 'consol_i': 	$doc = 'MI'; break;
		case 'bast': 			$doc = 'BA'; break;
		case 'csd': 			$doc = 'CS'; break;
		case 'outgoing': 	$doc = 'CO'; break;
		case 'incoming': 	$doc = 'CI'; break;
		case 'transit': 	$doc = 'CT'; break;
		case 'ra': 				$doc = 'RA'; break;
		case 'hvo': 			$doc = 'HO'; break;
		case 'hvi': 			$doc = 'HI'; break;

		case 'payment_o': $doc = 'PO'; break;
		case 'payment_i': $doc = 'PI'; break;
		case 'payment_t': $doc = 'PT'; break;
		case 'payment_r': $doc = 'PR'; break;
		case 'payment_a': $doc = 'PA'; break;

		case 'payment_o_p': $doc = 'PO'; break; // Payment Outgoing PJKP2U
		case 'payment_i_p': $doc = 'PI'; break;

		case 'reversal_ct': $doc = 'RC'; break;
		case 'reversal_ra': $doc = 'RR'; break;
		case 'deposit': $doc = 'DT'; break;

		case 'bpp': 	$doc = 'BPP'; break;
		case 'closing_ra': 	$doc = 'CLRA'; break;
		case 'closing_ct': 	$doc = 'CLCT'; break;
		case 'cl_daily_ra': $doc = 'DLRA'; break;
		case 'cl_daily_ct': $doc = 'DLCT'; break;

		/* pos empu */
		case 'emp_payment': $doc = 'EPA'; break;
		case 'e_closing': $doc = 'CLO'; break;
		case 'emp_reversal': $doc = 'ERV'; break;

    default: $doc = $modul; break; // customer
  }
  $d = date_parse_from_format('Y-m-d', $date);
  $dt = substr($d['year'].($d['month']<10?'0'.$d['month']:$d['month']),2);

  $MD->init('m_bu');
  $bu = $MD->line(['buid'=>$buid]);
  if(strlen($doc)>2){
    $pad_len = 6; // running number length
    // $cust_letter = strtoupper(substr(str_replace(' ','',$doc),0,1));
    if($doc=='BPP'){
			$MD->init('ap1_bpp');
			$ret = substr($buid,2).$area.$dt;
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(trannbr),".$pad_len.") AS INT)),0) + 1 as 'max' FROM ap1_bpp WHERE trannbr LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
		}elseif($doc=='BILL'){
			$MD->init('t_billing');
			$ret = substr($buid,2).$dt."PB".substr("000000$area",-6);
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(billnbr),$pad_len) AS UNSIGNED)),0) + 1 as 'max' FROM t_billing WHERE billnbr LIKE '$ret%' ");
			$num = (int) $num[0]->max;
		}elseif($doc=='EPA'){
			$MD->init('emp_payment');
			$ret = $buid.'-'.$area.'-';
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(trannbr),".$pad_len.") AS INT)),0) + 1 as 'max' FROM emp_payment WHERE trannbr LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
		}elseif($doc=='CLO'){
			$MD->init('emp_closing');
			$ret = substr($buid,2).$doc.$dt;
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(trannbr),".$pad_len.") AS INT)),0) + 1 as 'max' FROM emp_closing WHERE trannbr LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
		}elseif($doc=='ERV'){
			$MD->init('emp_reversal');
			$ret = $area.'RV';
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(trannbr),".$pad_len.") AS INT)),0) + 1 as 'max' FROM emp_reversal WHERE trannbr LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
		}elseif($doc=='CLRA'){
			$MD->init('t_closing');
			$ret = $area.substr($buid,2).'CLRA'.$dt;
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(closing_no),".$pad_len.") AS INT)),0) + 1 as 'max' FROM t_closing WHERE closing_no LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
		}elseif($doc=='CLCT'){
			$MD->init('t_closing');
			$ret = $area.substr($buid,2).'CLCT'.$dt;
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(closing_no),".$pad_len.") AS INT)),0) + 1 as 'max' FROM t_closing WHERE closing_no LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
		}elseif($doc=='DLRA'){
			$MD->init('t_closing_daily');
			$ret = $area.substr($buid,2).'DLRA'.$dt;
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(closing_daily_no),".$pad_len.") AS INT)),0) + 1 as 'max' FROM t_closing_daily WHERE closing_daily_no LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
		}elseif($doc=='DLCT'){
			$MD->init('t_closing_daily');
			$ret = $area.substr($buid,2).'DLCT'.$dt;
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(closing_daily_no),".$pad_len.") AS INT)),0) + 1 as 'max' FROM t_closing_daily WHERE closing_daily_no LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
		}else{
			$ret = $bu->portid.$cust_letter;
			$MD->init('m_customer');
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(customer_id),".$pad_len.") AS INT)),0) + 1 as 'max' FROM m_customer WHERE customer_id LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
		}
		return $ret.str_pad($num,$pad_len,"0",STR_PAD_LEFT);
  }else{ //transaction
    $pad_len = 7; // running number
    $ret = $bu->portid.$doc.$area.$dt;
    if($doc=='MO' || $doc=='MI' || $doc=='CO' || $doc=='CI' || $doc=='CT' || $doc=='RA'){
    	$MD->init('t_cargo');
    	$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(trannbr),".$pad_len.") AS INT)),0) + 1 as 'max' FROM t_cargo WHERE trannbr LIKE CONCAT('".$ret."', '%')");
    	$num = (int) $num[0]->max;
    }elseif($modul=='payment_o_p' || $modul=='payment_i_p'){
  		$MD->init('ap1_bpp');
  		$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(paynum),$pad_len) AS INT)),0) + 1 as 'max' FROM ap1_bpp WHERE paynum LIKE CONCAT('%', '".$ret."', '%')");
  		$num = (int) $num[0]->max.'P';
    }elseif($doc=='PO' || $doc=='PI' || $doc=='PT' || $doc=='PR'){
  		$MD->init('t_payment');
  		$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(trannbr),".$pad_len.") AS INT)),0) + 1 as 'max' FROM t_payment WHERE trannbr LIKE CONCAT('%', '".$ret."', '%')");
  		$num = (int) $num[0]->max;
    }elseif($doc=='PA'){
  		$MD->init('t_payment_additional');
  		$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(trannbr),".$pad_len.") AS INT)),0) + 1 as 'max' FROM t_payment_additional WHERE trannbr LIKE CONCAT('%', '".$ret."', '%')");
  		$num = (int) $num[0]->max;
    }elseif($doc=='RC' || $doc=='RR'){
			$MD->init('t_reversal');
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(trannbr),".$pad_len.") AS INT)),0) + 1 as 'max' FROM t_reversal WHERE trannbr LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
		}elseif($doc=='BA'){
			$MD->init('t_bast');
			$ret = $doc.substr($buid,-2).'-'.$dt;
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(trannbr),".$pad_len.") AS INT)),0) + 1 as 'max' FROM t_bast WHERE trannbr LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
			return $ret.str_pad($num,$pad_len,"0",STR_PAD_LEFT);
		}elseif($doc=='BK'){
			$MD->init('t_booking');
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(booknumber),".$pad_len.") AS INT)),0) + 1 as 'max' FROM t_booking WHERE booknumber LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
		}elseif($doc=='CS'){
			$MD->init('t_csd');
			$ret = $doc.substr($buid,-2).'-'.$dt;
			$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(trannbr),".$pad_len.") AS INT)),0) + 1 as 'max' FROM t_csd WHERE trannbr LIKE CONCAT('%', '".$ret."', '%')");
			$num = (int) $num[0]->max;
			return $ret.str_pad($num,$pad_len,"0",STR_PAD_LEFT);
		}elseif($doc=='DT'){
    	$ret = $bu->portid.$doc.$dt;
    	$MD->init('t_deposit');
    	$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(trannbr),".$pad_len.") AS UNSIGNED)),0) + 1 as 'max' FROM t_deposit WHERE trannbr LIKE CONCAT('%', '".$ret."', '%')");
    	$num = $num[0]->max;
    }elseif($doc=='HO' || $doc=='HI'){
  		$MD->init('t_manifest');
  		$num = $MD->query("SELECT IFNULL(MAX(CAST(RIGHT(RTRIM(handover_num),".$pad_len.") AS INT)),0) + 1 as 'max' FROM t_manifest WHERE handover_num LIKE CONCAT('%', '".$ret."', '%')");
  		$num = (int) $num[0]->max;
    }
		return $ret.str_pad($num,$pad_len,"0",STR_PAD_LEFT);
	}
}
function mq($action, $date, $data, $cName="", $active=TRUE){
	if($active){
		try{
			$rb=confval("bunny");
			$cName = $cName=="" ? $rb["tochannel"] : $cName;
			$connection = new AMQPStreamConnection($rb["host"], $rb["port"], $rb["user"], $rb["pass"], $rb["vh"]);
			$channel = $connection->channel();
			$channel->exchange_declare($cName, 'fanout', false, false, false);

				$msg = new AMQPMessage(json_encode(["action"=>$action,"date"=>$date,"data"=>$data]));
				$channel->basic_publish($msg, $cName);

			$channel->close();
			$connection->close();
		}catch(Exception $e){ return $e->getMessage();}
		finally{ return TRUE;}
	}
}

function cis2_auth(){
	$req_auth = Requests::post("https://cis-dashboard.ap1.co.id/service/sitek/api/auth", [], ["client_id"=>"APL","client_secret"=>"647bdbf6ee5bd67d1818c611"]);
	$auth = json_decode($req_auth->body);
	return $auth->data->access_token;
}

function cis2_checkpaid($obj){
	$token = $this->cis2_auth();
	$status = Requests::get("https://cis-dashboard.ap1.co.id/service/sitek/api/incoming/cargo?cargo_id=".$obj['cargo_id']."&station=".$obj['station'], ["Authorization"=>"Bearer $token"]);
	$res = json_decode($status->body);
	if($res->error==0){
		return $res->data->payment_status;
	}else{
		return false;
	}
}

}// end of class

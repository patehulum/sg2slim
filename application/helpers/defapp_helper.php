<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
function erd($s){ $CI=& get_instance(); $CI->config->load('apperd',TRUE); return $CI->config->item($s,'apperd'); }
function confval($s){ $CI=& get_instance(); $CI->config->load('appcfg',TRUE); return $CI->config->item($s,'appcfg'); }
function app_name(){ return confval("app_name"); }
function org_name(){ return confval("org_name"); }

function api($s){ $apl=confval('api'); return $apl[$s]; }
function api_url($s){ $apl=confval('api'); return $apl[$s]["uri"]; }
function api_auth($s){ $apl=confval('api'); return ["auth"=>$apl[$s]["auth"]]; }

function parse_req($P,$T=[]){ $aj="";
  $CI =& get_instance(); $CI->load->library('deflib');
  return $CI->deflib->parse_rest($P,$T->input->is_ajax_request());
}
function session_uservar($k){ return confval("session_user_var")[$k]; }
function notsess(){ $CI =& get_instance(); $CI->load->library('deflib'); return $CI->deflib->notsess(); }
function usernow(){ $CI =& get_instance(); $CI->load->library('deflib'); return $CI->deflib->getLogin(); }

function fsvr_tz($buid,$datestr=NULL){ $CI =& get_instance(); $CI->load->library('deflib');
  $bt=$CI->deflib->bu_tz($buid); $add=$bt-7; $add=$add<0?"-$add":"+$add";
  $str = $datestr==NULL ? date("Y-m-d H:i:s") : $datestr;
  return strtotime("$str $add hours");
}

if(!function_exists('appsend_mail')) {
function appsend_mail($x){
  $CI =& get_instance(); $CI->config->load('app',TRUE); $C = $CI->config->item("email",'app');
  $CI->load->library('email');
  $CI->email->initialize($C);
  //EMAIL NOTIFICATION
  $CI->email->set_mailtype($C['mailtype']);
  $CI->email->set_newline("\r\n");
  $CI->email->from($C['smtp_user'], $C['app_name']);
  $CI->email->to($x['to']);
  $CI->email->subject($x['subject']);

  $x['signature']=$C['org_name'];
  $r = $CI->load->view('shr/email_template', $x, true);
  // var_dump($r);die();
  $CI->email->message($r);
  return $CI->email->send();
}}

/* HTML bootstrap */

function ep_menu(){ $arr=confval("menu_define"); $menu='';
  foreach($arr as $k=>$v){
    $r[$k]=$v['name']." : [".$k."]";
  } return $r;
}

/* EDIFLY */
function to_json_fsu($fsu,$d){
  $data = [
    "buid" => $d['buid'],
    "smi" => "FSU",
    "items" => [
      [
        "awb" => $d['awb'],
        "ori" => $d['ori'],
        "dst" => $d['dst'],
        "qty" => $d['qty'],
        "wg"  => $d['gw'],
        "mc"  => ($d['vol']*6000)/1000000,
        "uom" => "K",
        "tot" => $d['total_qty'],
        "chw" => $d['chw'],
        "fsu" => $fsu,
        "flight" => $d['flight'],
        "flightdate" => $d['flight_date'],
        "shp" => $d['shp_name'],
        "cne" => $d['cne_name'],
        "actdate" => date('Y-m-d H:i:s',fsvr_tz($d['buid'])),
      ]
    ]
  ];
  return $data;
}

function to_json_fwb($d){
  $data = [
    "buid"=> $d['buid'],
    "smi"=> "FWB",
    "awb"=> $d['awb'],
    "ori"=> $d['ori'],
    "dst"=> $d['dst'],
    "qty"=> $d['qty'],
    "wg"=> $d['gw'],
    "vol"=> ($d['vol']*6000) / 1000000,
    "chw"=> $d['chw'],
    "uom"=> "K",
    "flight"=> $d['flight'],
    "flightdate"=> $d['flight_date'],
    "group"=> $d['goodgroup'],
    "goods"=> $d['good'],
    "ssr"=>"",
    "shp"=> $d['shp_name'],
    "shpadr"=> $d['shp_addr'],
    "shpcity"=> $d['shp_loc'],
    "shploc"=> $d['shp_loc'],
    "shpctr"=> $d['shp_country'],
    "shppos"=> "",
    "shpte"=> $d['shp_telp'],
    "cne"=> $d['cne_name'],
    "cneadr"=> $d['cne_addr'],
    "cnecity"=> $d['cne_loc'],
    "cneloc"=> $d['cne_loc'],
    "cnectr"=> $d['cne_country'],
    "cnepos"=> "",
    "cnete"=> $d['cne_telp']
  ];
  return $data;
}

/* endof: HTML bootstrap */
/*

*/
/* GENERAL HELPER */
function damn_var(...$x){
  foreach ($x as $i) {
    ob_start(); var_dump($i); $d=ob_get_contents(); ob_end_clean();
    $em= preg_replace_callback('/(\]=>\n\s*string.*?\s*")(.+?)("\n\s*\[|"\n\s+})/s',
      function ($m) {
        if(strpos($m[2],"=>")){
          $mx= preg_replace('/=>\n\s+/'," => ",$m[2]);
          $r="$m[1]$mx$m[3]";
          return preg_replace('/ (".*?")/', " <span style='color:blue'>$1</span>", $r);
        }
        else{ return "$m[1]<span style='color:blue'>$m[2]</span>$m[3]"; }
      }, $d);
    $em= preg_replace('/=>\n\s+/'," => ",$em);
    $em= preg_replace('/=>\n\t*/'," => ",$em);
    $em= preg_replace('/\] => \"\"\n/'," => ",$em);
    $em= preg_replace('/(\{\n\s+\})/'," {}\n",$em);
    $em= preg_replace('/(\[".*?":*.*\])/', "<b style='color:green'>".'$1'."</b>",$em);
    echo "<pre>$em</pre>";
  } //die();
}
function damn_fun(...$x){
  foreach ($x as $i) {
    ob_start(); var_export($i);  $d=ob_get_contents(); ob_end_clean();
    $em= preg_replace_callback('/(\s*=>\s*\')(.+?)(\',\n\s+)/s',
      function ($m) { return "$m[1]<span style='color:blue'>".htmlspecialchars($m[2])."</span>$m[3]";}
      , $d);
    $em= preg_replace('/\s*=>\s*\n\s+/'," => ",$em);
    $em= preg_replace('/(\s+\(\n\s+\),)/',"(),",$em);
    $em= preg_replace('/(\'.*?\')(\s*=>\s*)/', "<b style='color:green'>".'$1'."</b>$2",$em);
    echo "<pre>$em</pre>";
  } //die();
}

function ifnull($x,$y){return in_array( $x, ['0',0,'',null] ) ? $y : $x; }
function prior(...$s) { foreach ($s as $i) { if($i!==null &&trim($i)!=""){return $i; break;} } }
function containsWith($haystack, $needle){ return strpos($haystack, $needle)!==false && strpos($haystack, $needle)>=0; }
function startsWith($haystack, $needle){ return (substr($haystack, 0, strlen($needle) ) === $needle); }
function endsWith($haystack, $needle){
  $length = strlen($needle);
  if ($length == 0) { return true; }
  return ( substr($haystack, -$length) === $needle );
}
function gexpath($x,$q){
  $dom = new DOMDocument(); $dom->loadHTML($x);
  $xp=new DOMXPath($dom);
  $r = ''; foreach($xp->evaluate($q) as $childNode) { $r .= $dom->saveHtml($childNode); }
  return $r;
}
function comet(){$C =& get_instance(); return $C->router->fetch_class()."/".$C->router->fetch_method();}

function fdate($x,$F){
  if($x==NULL){return "-";}else{
    $mo=['/January/','/February/','/March/','/April/','/May/','/June/','/July/','/August/','/September/','/October/','/November/','/December/'];
    $m=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $do=['/Monday/','/Tuesday/','/Wednesday/','/Thursday/','/Friday/','/Saturday/','/Sunday/'];
    $d=['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
    $D=date_create($x); $R=startsWith($x,'0000-00-00 00:00:00')?'-':date_format($D,$F);
    if($R!='-' && strpos($F,'l')>-1){$R=preg_replace($do,$d,$R);}
    if($R!='-' && strpos($F,'F')>-1){$R=preg_replace($mo,$m,$R);}
    return $R;
  }
}
function spl_money($x){$word="";
  $num = array('','Satu','Dua','Tiga','Empat','Lima','Enam','Tujuh','Delapan','Sembilan');
  $lvl = array('','Ribu','Juta','Milyar','Triliun');
  $angka   = array('0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0');
  for($i = 1; $i <= strlen($x); $i++) { $angka[$i] = substr($x,-($i),1); }
  $i = 1; $j = 0;
  while($i <= strlen($x)){
      $conj = "";  $w1 = ""; $w2 = ""; $w3 = "";
      if($angka[$i+2] != "0"){
          if($angka[$i+2] == "1"){ $w1 = "Seratus"; }
          else{ $w1 = $num[$angka[$i+2]] . " Ratus"; }
      }
      if($angka[$i+1] != "0"){
          if($angka[$i+1] == "1"){
              if($angka[$i] == "0"){ $w2 = "Sepuluh"; }
              else if($angka[$i] == "1"){ $w2 = "Sebelas"; }
              else{ $w2 = $num[$angka[$i]] . " Belas"; }
          }else{ $w2 = $num[$angka[$i+1]] . " Puluh"; }
      }
      if ($angka[$i] != "0"){ if ($angka[$i+1] != "1"){ $w3 = $num[$angka[$i]]; } }
      if (($angka[$i] != "0") || ($angka[$i+1] != "0") || ($angka[$i+2] != "0")){
          $conj = $w1." ".$w2." ".$w3." ".$lvl[$j]." ";
      }
      $word = $conj . $word;
      $i = $i + 3;
      $j = $j + 1;
  }
  if (($angka[5] == "0") && ($angka[6] == "0")){ $word = str_replace("Satu Ribu","Seribu",$word); }
  return str_replace("  "," ",$word)." Rupiah";
}
function uuid(){
  $uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
  mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
  mt_rand( 0, 0x0fff ) | 0x4000,
  mt_rand( 0, 0x3fff ) | 0x8000,
  mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
  return $uuid;
}
function write_upload($filename,$file){ $err="";
  $b64=base64_decode($file);
  $msg="Upload";
  $path=explode('/',$filename);
  $fn = array_pop($path); $path=implode('/',$path); $new_tipe=explode("_",$fn);
  // var_dump(realpath($path));die();
  $rs=[];
  if (file_exists($path)) { $rs= array_diff(scandir($path), array('..', '.')); }
  foreach($rs as $x=>$r){ $old_tipe=explode('_',$r);
    if (!is_dir($path.$r) && count($old_tipe)==3 && count($new_tipe)==3){
      if($old_tipe[1]==$new_tipe[1]){ $msg="Replace"; unlink(realpath($path)."/".$r); }
    }
  }
  $w=file_put_contents($filename, $b64);
	if (! $w) { $err="can't upload to server..."; }
	return [ "done"=>$msg, "msg"=>$err ];
}
/* endof: GENERAL HELPER*/
if (!function_exists('none')) {
}

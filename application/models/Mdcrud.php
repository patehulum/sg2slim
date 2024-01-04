<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Mdcrud extends CI_Model{ // Model default operation
  private $TSCH=[];
  protected $TdbName="";
  private $title="";
  private $oTitle="";
  private $tbl_ai=false;
  private $ordby="";
  private $toStr=[];
  private $rel=[];
  protected $tbl_def=[];// table definition set at model load ==>constructors

  function __construct() {
    parent::__construct();
    $this->load->database();
    $this->load->model(["mddl"]);
    $this->load->helper(['defapp']);
    $this->config->load('appcfg',TRUE);
    $this->TSCH=$this->config->item("erd",'appcfg');
  }
  function dberr($r){ return ($r ? "" : "Error".$this->db->error()["message"]); }
  function init($x){
    $cc=$this->TSCH[$x];
    $this->TdbName=$x;
    $this->title =$cc["titl"];
    $this->oTitle =$cc["obj_titl"];
    $this->tbl_ai =$cc["tbl_ai"];
    $this->ordby =$cc["ordby"];
    $this->toStr =$cc["toStr"];
    $this->rel =$cc["rel"];
    $this->tbl_def=$this->mddl->dbDefinition($x);
    $this->read_def();
  }

  function tbl_dml($dat,$getdef=false){
    $exec=$this->mddl->alterfi($this->TdbName,$dat);
    if($getdef){
      return $this->read_def();
    }else{
      if($exec!=[]){
        $this->tbl_def=$this->mddl->dbDefinition($this->TdbName);
        $this->read_def();
      }return $exec;
    }
  }

  function limit_offset($c,$sql){$page=1;
    if( array_key_exists('offset', $c) && array_key_exists('limit', $c) ) {
      $c['limit']=$c['limit'] != "" ? $c['limit'] : 1;
      $page=$c['offset'];
      $c['offset']=$c['offset'] != "" ? ($page*$c['limit'])-$c['limit'] : 1;
      $sql.=$c['limit'] > 0 ? (" LIMIT ".($c['offset'] > 0 ? $c['offset'].", " : " ").$c['limit']) :"";
    } return ['page'=>$page,'sql'=>$sql];
  }

  function mk_filter($prm){
    $CFG=["def"=>$this->Schema(),"pk"=>[]];
    $whr=$this->def_getpk($prm);
    $by=$this->keyVal( "orby", $prm, "" );
		$CFG["cfg"]=[
      'filter' => $this->make_filter($prm),
      'search' => $this->make_search($prm),
      'orby' => array_key_exists($by,$CFG["def"]) ? $by : explode(' ',$this->Ordby())[0],
      'ordr' => $this->keyVal( "ordr", $prm, explode(' ',$this->Ordby())[1] ) ];
		if(array_key_exists("limit", $prm)){ $CFG["cfg"]['limit']=$prm["limit"];}
		if(array_key_exists("page", $prm) ){ $CFG["cfg"]['offset']=$prm["page"]; }
    if(!array_key_exists("page", $prm)){ $CFG["pk"]=$this->pk_verify($whr); }
    return $CFG;
  }
  function pk_verify($get,$mt=""){ $VLD=true; $filter=[];
    foreach($this->Schema() as $k=>$v){
      if( array_key_exists('pk', $v) && startsWith($k,$mt)){
        $g_k=$mt!="" ? str_replace("$mt.","",$k): $k;
        if(array_key_exists($g_k,$get)){
          $filter[$k] = $get[$g_k]; //echo $g_k;
          if($v["t"]=="int"){ $VLD=$VLD && $get[$g_k] > -1;}
          if($v["t"]=="string" ){ $VLD=$VLD && ($get[$g_k]!=null?trim($get[$g_k])!=="":true); }
          $VLD=$VLD && $get[$g_k]!==null && $get[$g_k]!=="";
        }else{$VLD=$VLD && false;}
      }
    } return $VLD ? $filter : [];
  }
  function def_getpk($arr){ $P=[];  // get pk only
    foreach($this->Schema() as $k=>$val){ $v=(array)$val;
      if( array_key_exists('pk', $v) ){
        if(array_key_exists($k, $arr)){ $P[$k] = $arr[$k]; }
        else{ $P[$k] = null;} // missing pk data
      }
    }
    return $P==[]? [] : $P;
  }
  function def_getall($arr,$incl_pk=true){$P=[];
    foreach($this->Schema() as $k=>$v){
      if(array_key_exists('pk', $v ) ){
        if($incl_pk){
          if(array_key_exists($k, $arr)){ $P[$k] = $arr[$k]; } else{} // missing pk data
        }
      }else{
        $wt=explode(".",$k);if(count($wt)>1){$k=$wt[1];}
        if(array_key_exists($k, $arr)){
          if(in_array($v['t'],['datetime','date']) && $arr[$k]==""){ }
          else{$P[$k] = $arr[$k]; }
        }else{} // missing data
      }
    } return $P;
  }

  function keyVal($needle,$hays, $alt=""){ return array_key_exists($needle,$hays)? $hays[$needle] : $alt; }

  function mklog($tbl,$obj,$id,$type=null){
    if($tbl!='z_log'){
      $data["eventdate"] = date("Y-m-d H:i:s");
      $data["username"] = $this->input->server('PHP_AUTH_USER');
      $data["buid"] = '';
      $data["name"] = $tbl.($type==null?" Update":" Insert");
      $data["objkey"] = reset($id);
      $data["descr"] = json_encode($obj);
      $this->db->insert('z_log',$data);
    }
  }

  function make_filter($t){ $filter=[]; $prim="";
    foreach(array_merge($this->Schema(),["*"=>["nm"=>"custom"]]) as $k=>$v){
      $prim = $prim=="" ? explode(".",$k)[0]."." : $prim; $k_g=str_replace($prim,'', $k);
      $k_g=str_replace('.','_',$k_g);

      if( array_key_exists($k_g, $t)){ $deget=$t[$k_g];
        if( array_key_exists('pk', $v) ){
          if(endsWith($deget,'null')){ $filter["$this->TdbName.$k".(startsWith($deget,'!')?' is not null':" is null")]=""; }
          else if($deget !== null && containsWith($deget,'*')==false){ $filter[$k]=$deget;  }
          if(startsWith($deget,'<<')){ $filter[$k.'<=']=str_replace('<<','',$deget); unset($filter[$k]);}
          if(startsWith($deget,'>>')){ $filter[$k.'>=']=str_replace('>>','',$deget); unset($filter[$k]);}
          if(startsWith($deget,'<')){ $filter[$k.'<']=str_replace('<','',$deget);  unset($filter[$k]);}
          if(startsWith($deget,'>')){ $filter[$k.'>']=str_replace('>','',$deget);  unset($filter[$k]);}
        }
        else{
          if($this->keyVal('def',$v)!==''){
            $filter[$k]=$t[$k_g] == null ? keyVal('def',$v) : $deget;
          }else{
            if(endsWith($deget,'null')){ $filter[$k.(startsWith($deget,'!')?' is not null':" is null")]=''; }
            elseif(startsWith($deget,'!')){ $filter[$k.'!=']=str_replace('!','',$deget); }
            elseif(substr($deget,0,3)=='IN('){
              $DD=str_replace('IN(','',$deget);
              $DD=substr($DD,0,-1);
              $filter[$k] = explode(',',$DD);
            }
            elseif(containsWith($deget,'%')){ }
            elseif(substr($deget,0,2)=='<<'){ $filter[$k.'<=']=str_replace('<<','',$deget); }
            elseif(substr($deget,0,2)=='>>'){ $filter[$k.'>=']=str_replace('>>','',$deget); }
            elseif(startsWith($deget,'<')){ $filter[$k.'<']=str_replace('<','',$deget); }
            elseif(startsWith($deget,'>')){ $filter[$k.'>']=str_replace('>','',$deget); }
            elseif(startsWith($deget,'between')){
              $start = str_replace('between','',explode('@',$deget)[0]);
              $end = date('Y-m-d',strtotime(explode('@',$deget)[1].'+1 day'));
              $filter[$k." BETWEEN '".$start."' AND '".$end."'"]='';
            }
            else{
              if($deget !== null && containsWith($deget,'*')==false){ $filter[$k]=$deget;}
            }
          }
        }
      }
      if(array_key_exists("$k<", $t)) { $filter["$k<"]=$t["$k<"]; }
      if(array_key_exists("$k>", $t)) { $filter["$k>"]=$t["$k>"]; }

    }return $filter;
  }
  function make_search($t,$mt=""){ $search=[];
    $filter_with_custom=$this->Schema();
    foreach ($t as $key => $value) {
      if(containsWith($key, "_")){ $filter_with_custom[$key]=["nm"=>$key];}
    }
    foreach($filter_with_custom as $k=>$v){
      $g_k=str_replace('.','_', ($mt!="" ? str_replace($mt.".","",$k):$k));
      if( array_key_exists($g_k, $t)){
      $g_v=str_replace('*','%',$t[$g_k]);

      if(strpos($k, $this->TdbName) !== false){
        $k = str_replace($this->TdbName.'_',$this->TdbName.'.',$k);
      }else{
        if( !array_key_exists($k, $this->Schema()) ){
          $ex=explode("_",$k);$fld=array_pop($ex);
          $k=implode("_",$ex).".$fld";
          if(substr($k,0,1)=='.'){
            $k = substr($k,1);
          }
        }
      }

      if( array_key_exists('pk', $v) && startsWith($k,$mt) ){
        if($g_v !== null && containsWith($g_v,'%') ){
           array_push($search, $k.' LIKE "'.$g_v.'"');
        }
      }else{
        if($g_v !== null && containsWith($g_v,'%') ){
          array_push($search, $k.' LIKE "'.$g_v.'"');
        }elseif( !array_key_exists($k, $this->Schema())){
          array_push($search, $k.' = "'.$g_v.'"');
        }
      }
    }} //print_r($search);die();
    return implode(" AND ",$search);
  }

  function read_def(){
    $this->sel_def=[];
    $mpfx=count($this->rel) > 0 ? "$this->TdbName." : "";
    // damn_var($this->tbl_def);die();
    foreach($this->tbl_def as $k => $v){
      array_push($this->sel_def, "$mpfx$k as '$mpfx$k'");
      if(count($this->rel) > 0){
        $this->tbl_def["$mpfx$k"]=$v; unset($this->tbl_def[$k]);
      }
    }
    // damn_var($this->TdbName,$this->sel_def);die();
    $j_tbl=[];
    foreach($this->rel as $j){ $nt=[];
      if(array_key_exists("select",$j)){
        $fields=explode(",", $j["select"]);
        foreach ($fields as $k => $f) {
          $nt["$f"]=[];
          array_push($this->sel_def, "$f");
        }
      }else{
        $alias=explode(" ", $j["tbl"]);
        $ftbl =count($alias)>=1?$alias[0]:$alias;
        $atbl =count($alias)>=2?$alias[1]:$ftbl;
        $rdef = $this->mddl->dbDefinition($ftbl);
        foreach($rdef as $k => $v){
          $nt["$atbl.$k"]=$v;
          array_push($this->sel_def, "$atbl.$k as '$ftbl.$k'");
        }
      }
      $j_tbl+=$nt;
    }
    $this->tbl_defjoin=$this->tbl_def + $j_tbl;
    // damn_var($this->sel_def,$this->tbl_defjoin);die();
    foreach ($this->tbl_def as $key => $value) {
      if(array_key_exists('ex',$value)){ if($value['ex']=="auto_increment"){ $this->tbl_ai=true; } }
    }
    return $this->tbl_def;
  }

  function toString(){ return $this->toStr; }
  function Ordby(){ return $this->ordby; }
  function Schema(){ $Schema=[];
    foreach($this->tbl_def as $k => $v) {
      $ky=explode("$this->TdbName.",$k);
      if(count($ky)>1){ $Schema[$ky[1]]=$v; unset($Schema[$k]); }
      else{$Schema[$ky[0]]=$v;}
		} return $Schema;
  }

  function get( $c=[] ){
    if(array_key_exists('filter', $c)){
      foreach($c['filter'] as $k=>$v){
        if(endsWith($k,'null')){
          foreach ($this->sel_def as $key=>$val) {
            $exploded_sel = explode(' ',$val);
            $afs = explode('.',end($exploded_sel));
            $as_field = str_replace("'",'',end($afs));
            if($as_field==$k){
              $c['filter'][$exploded_sel[0]] = $v;
              if($exploded_sel[0]!=$k){unset($c['filter'][$k]);}
            }
          }
          if($c['filter'] != ""){ $this->db->where($k); }
        }else{
          if($k=="*"){ $this->db->where($v); }
          else if( ! startsWith($k,$this->TdbName.".") ){
            $this->db->where([$this->TdbName.".".$k=>$v]);
          }
        }
      }
    }

    if(array_key_exists('search', $c)){
      if($c['search'] != ""){
        $exploded_search = explode(' ',$c['search']);
        $search_field = $exploded_search[0];
        foreach ($this->sel_def as $key=>$val) {
          $exploded_sel = explode(' ',$val);
          $afs = explode('.',end($exploded_sel));
          $as_field = str_replace("'",'',end($afs));

          if($search_field==$as_field){
            $exploded_search[0] = $exploded_sel[0];
            $c['search'] = implode(' ',$exploded_search);
          }

        }
        $this->db->where($c['search']);
      }
    }
    if(array_key_exists('orby', $c)){ if($c['orby'] != "" && $c['ordr'] != ""){
      $this->db->order_by($c['orby'], $c['ordr']);
    }}

    $mtbl=$this->TdbName;
    if(count($this->rel)>0){
      foreach($this->rel as $j){ $lnk=[];
        foreach ($j["r"] as $key => $rj) { $lnk[$key]=$rj['pk']."=".$rj['fk']; }
        $direction= array_key_exists("JOIN",$j)?"right":"left";
        $this->db->join($j["tbl"], implode(' and ',$lnk) ,$direction);
      }
    }

    $this->db->select($this->sel_def);
    $sql=$this->db->get_compiled_select($mtbl);//$this->db->last_query();
    $sql=str_replace('`','',$sql);
    $q=$this->db->query($sql);
    $LIMOFF=$this->limit_offset($c,$sql);
    return [
      // "sql" => $LIMOFF['sql'], "filt"=>$c,
      "page" => $LIMOFF['page'], "total" => $q->num_rows(),
      "rows" => $this->db->query($LIMOFF['sql'])->result(),
      "titl" => $this->title,
      "otitl"=> $this->oTitle,
      "def"  => $this->tbl_defjoin
    ];
  }
  function exist($id_r) {  return $this->db->get_where($this->TdbName, $id_r)->num_rows()==1;  }
  function line($id_r)  {
    // $q = $this->db->get_where($this->TdbName, $id_r);
    // $rd=count((array )$q->row())>0 && count($id_r)>0 ? $q->result()[0] : $this->make_empty();

    /* $id_r can be empty or set with zero/null value */
    if(empty($id_r)){
      $rd=$this->make_empty();
    }else{
      $q = $this->db->get_where($this->TdbName, $id_r);
      $rd=count((array )$q->row())>0 && count($id_r)>0 ? $q->result()[0] : $this->make_empty();
    }
    
    $defina=[];
    foreach ($this->tbl_defjoin as $key => $value) { $el=explode('.',$key);
      if(count($el)>1 && $el[0]==$this->TdbName){ $defina[$el[1]]=$value; }else{
        $kve=explode(" as ",$key);
        if(count($kve)>1){
          $defina[$kve[0]]=["nm"=>$kve[1]];
        }else{ $defina[$key]=$value; }
      }
    }
    if(!array_key_exists("DEFINE",(array)$rd)){$rd->DEFINE=$defina;}
    return $rd;
  }
  function make_empty(){
    $arr=[];
    $ldate=fsvr_tz(@$_SERVER["HTTP_SG2BU"]);
    foreach($this->tbl_defjoin as $k=>$v){
      if(count($v)>1){

        switch($v['t']){
          case 'int': $arr[$k]=0; break;
          case 'tinyint': $arr[$k]=0; break;
          case 'string': $arr[$k]=''; break;
          case 'date': $arr[$k]=date('Y-m-d', $ldate); break;
          case 'datetime': $arr[$k]=date('Y-m-d H:i:s', $ldate); break;
          default: $arr[$k]=0; break;
        }
        if(startsWith($k,$this->TdbName)){
            $arr[str_replace("$this->TdbName.","",$k)]=$arr[$k];unset($arr[$k]);
        }
      }
    }
    return (object)$arr;
  }

  function insorupd($obj){
    $dpk=$this->def_getpk($obj);
    if($this->exist($dpk) && $dpk!=[]){ return $this->upd($obj,$dpk); }
    else{ return $this->ins($obj); }
  }
  function insreplace($obj,$filt){
    $this->db->delete($this->TdbName, $filt);
    $this->ins($obj);
  }
  function ins($obj){
    $clean_obj=$this->def_getall($obj);
    $dpk=$this->def_getpk($clean_obj);
    $pk=[];
    foreach($dpk as $key => $value) {
      $k=startsWith($key,$this->TdbName) ? str_replace("$this->TdbName.","",$key) : $key;
      $key = array_key_exists($key,$this->tbl_def) ? $key : $this->TdbName.'.'.$key;
      if($this->tbl_ai){ unset($clean_obj[$key]);  }
      else if($this->tbl_def[$key]["pk"]=="PRI" && $this->tbl_def[$key]["t"]=="string" && $this->tbl_def[$key]["l"]==36){
        if( ! array_key_exists($k,$clean_obj)){
          $uid=uuid();
          $pk=$uid;
          $clean_obj[$k]=$uid;
        }
      }
      else {
        if(!array_key_exists($key,$this->tbl_def)){
          $pk=$this->tbl_def[$key]["t"]=="int"?0:"";
        }
        else{
          $pk=substr($clean_obj[$k],0,$this->tbl_def[$key]["l"]);
        }
      }
    }
    $tznow=fsvr_tz($_SERVER["HTTP_SG2BU"]);
    if(array_key_exists('create_by',$this->tbl_def) || array_key_exists($this->TdbName.'.create_by',$this->tbl_def)){
      if(array_key_exists('create_by',$clean_obj)){
        if($clean_obj['create_by']==""){
          $clean_obj['create_by']=$this->input->server('PHP_AUTH_USER');
        }
      }else{
        $clean_obj['create_by']=$this->input->server('PHP_AUTH_USER');
      }
    }
    if(array_key_exists('create_date',$this->tbl_def) || array_key_exists($this->TdbName.'.create_date',$this->tbl_def)){
      $clean_obj['create_date']=date('Y-m-d H:i:s',$tznow);
    }
    if(array_key_exists('modified_by',$this->tbl_def) || array_key_exists($this->TdbName.'.modified_by',$this->tbl_def)){
      if(array_key_exists('modified_by',$clean_obj)){
        if($clean_obj['modified_by']==""){
          $clean_obj['modified_by']=$this->input->server('PHP_AUTH_USER');
        }
      }else{
        $clean_obj['modified_by']=$this->input->server('PHP_AUTH_USER');
      }
    }
    if(array_key_exists('modified_date',$this->tbl_def) || array_key_exists($this->TdbName.'.modified_date',$this->tbl_def)){
      $clean_obj['modified_date']=date('Y-m-d H:i:s',$tznow);
    }
    $result = $this->db->insert($this->TdbName, $clean_obj);
    $res_id = [key($dpk)=>$this->tbl_ai ? $this->db->insert_id() : $pk];
    // $this->mklog($this->TdbName, $clean_obj, $res_id, "Insert");
    return (object)['id' => $res_id, 'errmsg' => $this->dberr($result)];
  }
  function upd($obj, $id_r, $allow=[], $modifupd=TRUE)  {
    $clean_obj=$this->def_getall( $obj, false); // pk will never update
    foreach ($allow as $k => $v) { $clean_obj[$k]=$v; }
    if($modifupd){/* modul selain transaksi utama harus set $modifupd=FALSE */
      if(array_key_exists('modified_by',$this->tbl_def) || array_key_exists($this->TdbName.'.modified_by',$this->tbl_def)){
        if(array_key_exists('modified_by',$clean_obj)){
          if($clean_obj['modified_by']==""){
            $clean_obj['modified_by']=$this->input->server('PHP_AUTH_USER');
          }
        }else{
          $clean_obj['modified_by']=$this->input->server('PHP_AUTH_USER');
        }
      }
      if(array_key_exists('modified_date',$this->tbl_def) || array_key_exists($this->TdbName.'.modified_date',$this->tbl_def)){
        $clean_obj['modified_date']=date('Y-m-d H:i:s',fsvr_tz($_SERVER["HTTP_SG2BU"]));
      }
    }
    $result = $this->db->update($this->TdbName, $clean_obj,  $id_r );
    foreach ($allow as $k => $v) { $id_r[$k]=$v; }
    // $this->mklog($this->TdbName, $clean_obj, $id_r, "Update");
    return (object)['id' => $id_r, 'errmsg' => $this->dberr($result)];
  }
  function remove($id_r) {
    $result = $this->db->delete($this->TdbName, $id_r);
    return (object)['id' => $id_r, 'errmsg' => $this->dberr($result)];
  }
  function query($q){
    $query = $this->db->query($q);
    return $query->result();
  }
}

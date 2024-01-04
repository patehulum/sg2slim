<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Mddl extends CI_Model{
  private $schema="";

  function __construct() {
    parent::__construct();
    $this->load->database();
    $this->schema=$this->db->database;
    $this->SC = $this->load->database("schema_ro",TRUE);
  }

  function dbDefinition($table_name,$nopk=false){
    $this->SC->order_by("ORDINAL_POSITION");
    $rs=$this->SC->get_where("COLUMNS", ["TABLE_SCHEMA"=>$this->schema, "TABLE_NAME" => $table_name] );
    $R=[];
    foreach ($rs->result() as $d) {
      $nm=$d->COLUMN_COMMENT;
      $nm=trim($nm)==""?$d->COLUMN_NAME:$nm;
      $nm=str_replace('_',' ',$nm);
      $R[$d->COLUMN_NAME]=[
        // "cat"=>$d->TABLE_CATALOG, "sce"=>$d->TABLE_SCHEMA, "tbl"=>$d->TABLE_NAME,
        "t"=>in_array($d->DATA_TYPE,["nvarchar","nchar","varchar","char","text"])?"string":$d->DATA_TYPE,
        "l"=>$d->CHARACTER_MAXIMUM_LENGTH,
        "nm"=>ucwords($nm)
      ];
      
      if($d->IS_NULLABLE=="NO"){ $R[$d->COLUMN_NAME]["rq"]=true; }
      if($d->COLUMN_KEY!=""){
        if($d->COLUMN_KEY=='PRI'){$R[$d->COLUMN_NAME]["pk"]=$d->COLUMN_KEY;}
        // if($d->COLUMN_KEY=='UNI'){$R[$d->COLUMN_NAME]["pk"]=$d->COLUMN_KEY;}
      }
      if($d->EXTRA!=""){ $R[$d->COLUMN_NAME]["ex"]=$d->EXTRA; }
      if($d->COLUMN_KEY!="" && $nopk){ unset($R[$d->COLUMN_NAME]); }
    }
    return $R;
  }
  function renupper($tbl_name){
    $def=$this->dbDefinition($tbl_name);
    foreach ($def as $key => $v) { $ku=mb_strtoupper($key);
      // damn_var($v);
      if($v["t"]=="string"){ $l=$v["l"];
          $this->db->query("ALTER TABLE `$tbl_name` CHANGE `$key` `$ku` VARCHAR($l)");
      }
      else if($v["t"]=="date"){
          $this->db->query("ALTER TABLE `$tbl_name` CHANGE `$key` `$ku` date null");
      }
    }
  }
  function alterfi($tbl_name, $mock){
    $def=$this->dbDefinition($tbl_name);
    $def_key=array_keys($def);
    $field=null;
    if(in_array('create_date',$def_key)){
      $idx=array_search('create_date',$def_key);
      $field=$idx==0 ? '' : $def_key[$idx-1];
    }else if(in_array('id',$def_key)){ $field='id'; }
    $changes=[];
    foreach ($mock as $key => $value) {
      $typedt=$this->predict_type($value);
      if(!in_array($key,$def_key)){
        $field=$field==''?'FIRST': "AFTER $field";
        $this->db->query("ALTER TABLE $tbl_name ADD $key $typedt NULL $field");
        array_push($changes,$this->db->last_query());
      }else{
        if($def[$key]["t"]=="string"){
          $len=strlen($value);
          if($def[$key]["l"] < $len ){ $len=$len>50?$len:50;
            $this->db->query("ALTER TABLE $tbl_name CHANGE $key $key VARCHAR($len) NULL");
            array_push($changes,$this->db->last_query());
          }
        }
      } $field=$key;
    } return $changes;
  }
  function predict_type($str){
    if(is_numeric($str) && substr($str,0,1)!='0'){ return "INT"; }else{
      try{
          $str=is_array($str)? implode(" ",$str) : $str;
          if((DateTime::createFromFormat("Y-m-d h:i:s", $str)) !== FALSE ||
             (DateTime::createFromFormat("Y-m-d", $str)) !== FALSE ){
               return "DATETIME";
          }else{
            $len=strlen($str);
            return "VARCHAR($len)";
          }
      }catch(Exception $e){ echo $e;die();damn_var($e); }
    }
  }
  // ALTER TABLE `test` ADD `message` TEXT NOT NULL AFTER `origin`;
  // ALTER TABLE `test` CHANGE `timestamp` `timestamp` DATE NOT NULL;
  // ALTER TABLE `test` CHANGE `dest` `destination` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
  // ALTER TABLE `test` CHANGE `id` `id` VARCHAR(10) NOT NULL;
  // ALTER TABLE `test` CHANGE `id` `id` INT NOT NULL;

}

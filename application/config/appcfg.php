<?php defined('BASEPATH') OR exit('No direct script access allowed');
$config["app_name"]="SITEK API";
$config["app_name_abbv"]="SG2 API";
$config["org_name"]="Angkasa Pura Logistik";

$config["trantipe"]=[
  0=>"-- Not Set --",
  1=>"Outgoing",
  2=>"Incoming",
  3=>"Transit",
  4=>"RA"
];
$config["domint"]=[
  0=>"-- Not Set --",
  1=>"Domestic",
  2=>"International",
];
$config["opt_lock"]=[
  1=>"Open",
  2=>"Released",
  3=>"Closed",
  4=>"Canceled"
];
$config["opt_pay"]=[
  1=>"Cash",
  2=>"Deposit",
];
$config["emp_opt_pay"]=[
  1=>"Cash",
  2=>"Debit",
  3=>"Invoice",
];
$config["active"]=[
  TRUE=>"True",
  FALSE=>"False",
];
$config["vat"]=[
  1=>"Include",
  2=>"Exclude",
  3=>"No Tax",
];
$config["value_factor"]=[
  1=>"Fixed",
  2=>"Qty",
  3=>"CHW",
  4=>"n-Transaction",
];
$config["chg_modul"]=[
  1=>"Cargo",
  2=>"Payment",
];
$config["deposit_tipe"]=[
  1=>"Top Up",
  2=>"Payment",
];
//untuk login
$config["authentication_enable"] = TRUE;
// untuk modul
$config["authorization_enable"] = FALSE;
/* Authentication */
$config["role_type"]=["Config", "Super", "Management",];

/* MENU CONF */
$config["ot_code"]=["N"=>"None","R"=>"Read Only","W"=>"Write owned","S"=>"Write All"];
/* level= 0: no access, 1: reading, 2: writeaccess, 3: super
L:POST    : Get Pagination / Query
V:GET     : Get One
I:PUT     : Insert/Create
U:PATCH   : Update/Edit
rules: validation will pass minimum access level from config bellow
*/
$config["endpoints"]=[
  "cargo/header"    => ["name" => "Cargo Header", "L"=>1, "V"=>1, "I"=>2, "U"=>2],
  "cargo/item"      => ["name" => "Cargo Item", "L"=>1, "V"=>1, "I"=>2, "U"=>2],
  "cargo/charge"      => ["name" => "Cargo Charge", "L"=>1, "V"=>1, "I"=>2, "U"=>2],

  // "manifest/"             => ["name" => "Planning"],
  // "manifest/edit"         => ["name" => "Planning"],

  // "deposit/"              => ["name" => "Deposit Transaction"],
  // "deposit/edit"          => ["name" => "Deposit Transaction"],

  "payment/header"  => ["name" => "Payment Header", "L"=>1, "V"=>1, "I"=>2, "U"=>2],
  "payment/detail"  => ["name" => "Payment Detail", "L"=>1, "V"=>1, "I"=>2, "U"=>2],
  "payment/charge"  => ["name" => "Payment Charge", "L"=>1, "V"=>1, "I"=>2, "U"=>2],
  "payment/additional"  => ["name" => "Payment Additional", "L"=>1, "V"=>1, "I"=>2, "U"=>2],
  "payment/additional_detail"  => ["name" => "Payment Additional Detail", "L"=>1, "V"=>1, "I"=>2, "U"=>2],

  // "reversal/"             => ["name" => "Reversal Transaction"],
  // "reversal/edit"         => ["name" => "Reversal Transaction"],

  "master/customer"       => ["name" => "Master Customer", "L"=>0, "V"=>0],
  "master/airline"        => ["name" => "Master Airline", "L"=>0, "V"=>0],
  "master/good"           => ["name" => "Master Goods", "L"=>0, "V"=>0],
  "master/area"           => ["name" => "Master Location", "L"=>0, "V"=>0],

  "master/country"        => ["name" => "Master Country", "L"=>0, "V"=>0],
  "master/city"           => ["name" => "Master City", "L"=>0, "V"=>0],
  "master/port"           => ["name" => "Master Port", "L"=>0, "V"=>0],
  "master/bu"             => ["name" => "Master Branch", "L"=>0, "V"=>0],

  "setting/user"          => ["name" => "User"],
  "setting/pass_change"   => ["name" => "Change Password", "U"=>2], /* [W,S] */
  "setting/pass_reset"    => ["name" => "Reset Password", "U"=>0], /* Auth not required */

  "setting/role"          => ["name" => "Role", "L"=>0, "V"=>0],
  "setting/roledet"       => ["name" => "Role Access", "L"=>0, "V"=>0],
  "setting/rolemn"        => ["name" => "Role Menu", "L"=>0, "V"=>0],

  "setting/org"           => ["name" => "Organization", "L"=>0, "V"=>0, "I"=>2, "U"=>2],
  "setting/rate"          => ["name" => "Rate", "L"=>0, "V"=>0, "I"=>2, "U"=>2],
  "setting/charge"        => ["name" => "Charge", "L"=>0, "V"=>0, "I"=>2, "U"=>2],
  "setting/tax"           => ["name" => "Tax", "L"=>0, "V"=>0, "I"=>2, "U"=>2]
];
/* endof: MENU CONF */
/* ERD */
$config["erd"]["z_user"]=[
  "titl"=>"Users", "obj_titl"=>"User", "tbl_ai" => false,
  "ordby" => "z_user.username asc",
  "toStr" => ["name"],
  "rel" => [
    // ["select"=>"org.name as orgName", "tbl"=>"z_org org","r"=>[
    //   ["fk"=>"z_user.orgid","pk"=>"org.orgid"] ] ],
    // ["select"=>"rl.name as roleName", "tbl"=>"z_role rl","r"=>[
    //   ["fk"=>"z_user.roleid","pk"=>"rl.roleid"] ] ],
    // ["select"=>"bu.portid as portid", "tbl"=>"m_bu bu","r"=>[
    //   ["fk"=>"z_user.buid","pk"=>"bu.buid"] ] ],
  ],
];
$config["erd"]["z_user_bu"]=[
  "titl"=>"User Accesses", "obj_titl"=>"User Access", "tbl_ai" => false,
  "ordby" => "usr.username asc",
  "toStr" => ["buid"],
  "rel" => [
    ["select"=>"usr.id, usr.name, usr.username, usr.last_login, usr.active as Active", "tbl"=>"z_user usr", "JOIN"=>"RIGHT", "r"=>[
      ["fk"=>"z_user_bu.userid","pk"=>"usr.id"] ] ],
    ["select"=>"rl.name as role_Name", "tbl"=>"z_role rl", "r"=>[
      ["fk"=>"z_user_bu.roleid","pk"=>"rl.roleid"] ] ],
    ["select"=>"bu.name as bu_Name", "tbl"=>"m_bu bu","r"=>[
      ["fk"=>"z_user_bu.buid","pk"=>"bu.buid"] ] ],
  ],
];
$config["erd"]["z_role"]=[
  "titl"=>"Roles", "obj_titl"=>"Role", "tbl_ai" => true,
  "ordby" => "roleid asc",
  "toStr" => ["name"],
  "rel" => [  ],
];
$config["erd"]["z_role_det"]=[
  "titl"=>"Api Modules", "obj_titl"=>"Api Module", "tbl_ai" => false,
  "ordby" => "roleid asc",
  "toStr" => ["roleid","modul"],
  "rel" => [  ],
];
$config["erd"]["z_role_menu"]=[
  "titl"=>"Modules", "obj_titl"=>"Module", "tbl_ai" => false,
  "ordby" => "roleid asc",
  "toStr" => ["roleid","menu"],
  "rel" => [  ],
];
$config["erd"]["z_log"]=[
  "titl"=>"Logs", "obj_titl"=>"Log", "tbl_ai" => false,
  "ordby" => "eventdate desc",
  "toStr" => ["descr"],
  "rel" => [  ],
];

$config["erd"]["z_org"]=[
  "titl"=>"Organizations", "obj_titl"=>"Organization", "tbl_ai" => false,
  "ordby" => "z_org.id asc",
  "toStr" => ["z_org.name"],
  "rel" => [
    // ["select"=>"prt.name as parentName", "tbl"=>"z_org prt", "r"=>[
    //   ["fk"=>"z_org.parentid","pk"=>"prt.id"], ] ],
   ],
];

$config["erd"]["z_rate"]=[
  "titl"=>"Rates", "obj_titl"=>"Rate", "tbl_ai" => false,
  "ordby" => "z_rate.rate_date desc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["z_charges"]=[
  "titl"=>"Charges", "obj_titl"=>"Charge", "tbl_ai" => true,
  "ordby" => "name asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["z_tax"]=[
  "titl"=>"Taxes", "obj_titl"=>"Tax", "tbl_ai" => true,
  "ordby" => "id asc",
  "toStr" => [],
  "rel" => [],
];

// Master
$config["erd"]["m_airline"]=[
  "titl"=>"Airlines", "obj_titl"=>"Master Airline", "tbl_ai" => true,
  "ordby" => "name asc",
  "toStr" => ["name"],
  "rel" => [  ],
];

$config["erd"]["m_bu"]=[
  "titl"=>"Branches", "obj_titl"=>"Branch", "tbl_ai" => false,
  "ordby" => "buid asc",
  "toStr" => ["name"],
  "rel" => [  ],
];

$config["erd"]["m_customer"]=[
  "titl"=>"Customers", "obj_titl"=>"Customer", "tbl_ai" => true,
  "ordby" => "name asc",
  "toStr" => ["name"],
  "rel" => [  ],
];

$config["erd"]["m_country"]=[
  "titl"=>"Countries", "obj_titl"=>"Country", "tbl_ai" => true,
  "ordby" => "name asc",
  "toStr" => ["name"],
  "rel" => [  ],
];

$config["erd"]["m_city"]=[
  "titl"=>"Cities", "obj_titl"=>"City", "tbl_ai" => true,
  "ordby" => "name asc",
  "toStr" => ["name"],
  "rel" => [  ],
];

$config["erd"]["m_good"]=[
  "titl"=>"Goods", "obj_titl"=>"Good", "tbl_ai" => true,
  "ordby" => "goodgroup asc",
  "toStr" => ["goodgroup"],
  "rel" => [  ],
];

$config["erd"]["m_port"]=[
  "titl"=>"Ports", "obj_titl"=>"Port", "tbl_ai" => false,
  "ordby" => "name asc",
  "toStr" => ["name"],
  "rel" => [  ],
];

$config["erd"]["m_area"]=[
  "titl"=>"Areas", "obj_titl"=>"Master Area", "tbl_ai" => true,
  "ordby" => "m_area_id asc",
  "toStr" => ["name"],
  "rel" => [  ],
];


$config["erd"]["m_tracking"]=[
  "titl"=>"Tracking Master", "obj_titl"=>"Tracking Master", "tbl_ai" => false,
  "ordby" => "steps asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["m_ra"]=[
  "titl"=>"Master RA", "obj_titl"=>"Master RA", "tbl_ai" => true,
  "ordby" => "id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["m_ipaddr"]=[
  "titl"=>"Master Accept IP Address", "obj_titl"=>"Accept IP Address", "tbl_ai" => false,
  "ordby" => "buid asc",
  "toStr" => [],
  "rel" => [],
];

// Transaction
$config["erd"]["api_tracking"]=[
  "titl"=>"Tracking Data", "obj_titl"=>"Tracking Data", "tbl_ai" => true,
  "ordby" => "api_tracking.create_date asc",
  "toStr" => [],
  "rel" => [
    ["select"=>"mt.tipe, mt.steps, mt.alias, mt.descr", "tbl"=>"m_tracking mt","r"=>[
      ["fk"=>"api_tracking.code","pk"=>"mt.code"] ] ],
  ],
];

$config["erd"]["t_csd"]=[
  "titl"=>"CSD", "obj_titl"=>"CSD", "tbl_ai" => false,
  "ordby" => "cargo_id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_area"]=[
  "titl"=>"Area", "obj_titl"=>"Area", "tbl_ai" => false,
  "ordby" => "t_area_id asc",
  "toStr" => [],
  "rel" => [
    ["select"=>"ma.descr as descr_area", "tbl"=>"m_area ma","r"=>[
      ["fk"=>"t_area.area_id","pk"=>"ma.m_area_id"] ] ],
    ["select"=>"mv.descr as descr_move", "tbl"=>"m_area mv","r"=>[
      ["fk"=>"t_area.move","pk"=>"mv.m_area_id"] ] ],
  ],
];

$config["erd"]["t_bast"]=[
  "titl"=>"BAST", "obj_titl"=>"BAST", "tbl_ai" => false,
  "ordby" => "bast_id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_bast_detail"]=[
  "titl"=>"BAST Detail", "obj_titl"=>"BAST Detail", "tbl_ai" => false,
  "ordby" => "bastdet_id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_cargo"]=[
  "titl"=>"Cargo", "obj_titl"=>"Cargo", "tbl_ai" => true,
  "ordby" => "trandate desc",
  "toStr" => ["name"],
  "rel" => [  ],
];

$config["erd"]["t_cargo_item"]=[
  "titl"=>"Items", "obj_titl"=>"item", "tbl_ai" => true,
  "ordby" => "item_id asc",
  "toStr" => ["name"],
  "rel" => [],
];

$config["erd"]["t_cargo_charges"]=[
  "titl"=>"Cargo Charges", "obj_titl"=>"Cargo Charge", "tbl_ai" => true,
  "ordby" => "t_cargo_charges.id asc",
  "toStr" => [],
  "rel" => [
    ["select"=>"chg.name as charge_name, chg.org_id, chg.vat", "tbl"=>"z_charges chg","r"=>[
      ["fk"=>"t_cargo_charges.charge_id","pk"=>"chg.id"] ] ],
    ["select"=>"org.name as org_name, org.img as img", "tbl"=>"z_org org","r"=>[
      ["fk"=>"chg.org_id","pk"=>"org.id"] ] ],
  ],
];

$config["erd"]["t_document"]=[
  "titl"=>"Documents", "obj_titl"=>"Document", "tbl_ai" => true,
  "ordby" => "doc_id asc",
  "toStr" => [],
  "rel" => [],
];


$config["erd"]["t_manifest"]=[
  "titl"=>"Handover", "obj_titl"=>"Handover", "tbl_ai" => true,
  "ordby" => "manifest_id desc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_manifest_detail"]=[
  "titl"=>"Handover Detail", "obj_titl"=>"Handover Detail", "tbl_ai" => true,
  "ordby" => "mandet_id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_manifest_offload"]=[
  "titl"=>"Offload", "obj_titl"=>"Offload", "tbl_ai" => true,
  "ordby" => "offload_id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["api_manifest_in"]=[
  "titl"=>"Manifest", "obj_titl"=>"Manifest", "tbl_ai" => true,
  "ordby" => "manifest_id desc",
  "toStr" => [],
  "rel" => [
    ["select"=>"ma.name as airline_name", "tbl"=>"m_airline ma","r"=>[
      ["fk"=>"api_manifest_in.airline_id","pk"=>"ma.iata"] ] ],
  ],
];

$config["erd"]["api_manifest_in_detail"]=[
  "titl"=>"Manifest", "obj_titl"=>"Manifest", "tbl_ai" => true,
  "ordby" => "mandet_id asc",
  "toStr" => [],
  "rel" => [],
];


// Money
$config["erd"]["t_payment"]=[
  "titl"=>"Payments", "obj_titl"=>"Payment", "tbl_ai" => true,
  "ordby" => "trandate desc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_payment_charges"]=[
  "titl"=>"Payment Charges", "obj_titl"=>"Payment Charge", "tbl_ai" => true,
  "ordby" => "t_payment_charges.id asc",
  "toStr" => [],
  "rel" => [
    ["select"=>"chg.name as charge_name, chg.org_id, chg.vat, chg.value_factor", "tbl"=>"z_charges chg","r"=>[
      ["fk"=>"t_payment_charges.charge_id","pk"=>"chg.id"] ] ],
  ],
];

$config["erd"]["t_payment_detail"]=[
  "titl"=>"Payments Detail", "obj_titl"=>"Payment Detail", "tbl_ai" => true,
  "ordby" => "paydet_id asc",
  "toStr" => [],
  "rel" => [
    ["select"=>"c.airline_id, c.cis2_id, c.trannbr, c.awb, c.trandate, c.flight, c.sender, c.receiver, c.ori, c.dst, c.trantipe, c.dom_int, c.good, c.goodsub, c.goodgroup, c.qty, c.gw, c.vol, c.chw", "tbl"=>"t_cargo c","r"=>[
      ["fk"=>"t_payment_detail.cargo_id","pk"=>"c.cargo_id"] ] ],
  ],
];

$config["erd"]["t_payment_additional"]=[
  "titl"=>"Additional", "obj_titl"=>"Additional", "tbl_ai" => true,
  "ordby" => "additional_id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_payment_additional_detail"]=[
  "titl"=>"Additional", "obj_titl"=>"Additional", "tbl_ai" => true,
  "ordby" => "additional_detail_id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_reversal"]=[
  "titl"=>"Reversals", "obj_titl"=>"Reversal", "tbl_ai" => true,
  "ordby" => "reversal_id desc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_reversal_detail"]=[
  "titl"=>"Reversal Details", "obj_titl"=>"Reversal Detail", "tbl_ai" => true,
  "ordby" => "revdet_id asc",
  "toStr" => [],
  "rel" => [
    ["select"=>"c.trannbr, c.awb, c.trandate, c.flight, c.sender, c.receiver, c.ori, c.dst, c.trantipe, c.dom_int, c.good, c.goodgroup, c.qty, c.gw, c.chw", "tbl"=>"t_cargo c","r"=>[
      ["fk"=>"t_reversal_detail.cargo_id","pk"=>"c.cargo_id"] ] ],
  ],
];

$config["erd"]["t_booking"]=[
  "titl"=>"Bookings", "obj_titl"=>"Booking", "tbl_ai" => true,
  "ordby" => "book_id desc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_booking_detail"]=[
  "titl"=>"Booking Details", "obj_titl"=>"Booking Detail", "tbl_ai" => true,
  "ordby" => "bookdet_id asc",
  "toStr" => [],
  "rel" => [
    ["select"=>"b.trantipe, b.agt_id", "tbl"=>"t_booking b","r"=>[
      ["fk"=>"t_booking_detail.book_id","pk"=>"b.book_id"] ] ],
    ["select"=>"cus.name as 'cus.name' ", "tbl"=>"emp_customer cus","r"=>[
      ["fk"=>"t_booking_detail.customer_id","pk"=>"cus.customer_id"] ] ],
    ["select"=>"pay.trannbr as pay_trannbr, pay.trandate as pay_trandate", "tbl"=>"emp_payment pay","r"=>[
      ["fk"=>"t_booking_detail.payment_id","pk"=>"pay.payment_id"] ] ],
  ],
];

$config["erd"]["t_deposit"]=[
  "titl"=>"Deposits", "obj_titl"=>"Deposit", "tbl_ai" => true,
  "ordby" => "trandate desc",
  "toStr" => [],
  "rel" => [
    ["select"=>"org.name", "tbl"=>"z_org org","r"=>[
      ["fk"=>"t_deposit.account_org","pk"=>"org.id"] ] ],
  ],
];

$config["erd"]["t_deposit_log"]=[
  "titl"=>"Deposit Logs", "obj_titl"=>"Deposit Log", "tbl_ai" => true,
  "ordby" => "deplog_id desc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_faktur"]=[
  "titl"=>"Faktur", "obj_titl"=>"Faktur", "tbl_ai" => true,
  "ordby" => "faktur_id desc",
  "toStr" => [],
  "rel" => [
    ["select"=>"cus.name as customername", "tbl"=>"m_customer cus","r"=>[
      ["fk"=>"t_faktur.customer_id","pk"=>"cus.customer_id"] ] ],
    ["select"=>"pay.trannbr", "tbl"=>"t_payment pay","r"=>[
      ["fk"=>"t_faktur.payment_id","pk"=>"pay.payment_id"] ] ],
  ],
];

$config["erd"]["t_queue"]=[
  "titl"=>"Queue", "obj_titl"=>"Queue", "tbl_ai" => true,
  "ordby" => "queue_id asc",
  "toStr" => [],
  "rel" => [
    ["select"=>"bk.booknumber, bk.agt_name, bk.truckno, bk.trschedule", "tbl"=>"t_booking bk","r"=>[
      ["fk"=>"t_queue.book_id","pk"=>"bk.book_id"] ] ],
  ],
];

$config["erd"]["BATALPLP"] = [
  "titl"=>"CANCEL PLP", "obj_titl"=>"CANCEL PLP", "tbl_ai" => true,
  "ordby" => "id desc", "toStr" => [],
  "rel"=>[]
];

$config["erd"]["BATALPLP_KMS"] = [
  "titl"=>"CANCEL PLPKMS", "obj_titl"=>"CANCEL PLPKMS", "tbl_ai" => true,
  "ordby" => "id desc", "toStr" => [],
  "rel"=>[]
];

$config["erd"]["PLP"] = [
  "titl"=>"PLP", "obj_titl"=>"PLP", "tbl_ai" => true,
  "ordby" => "id desc", "toStr" => [],
  "rel"=>[]
];

$config["erd"]["PLP_KMS"] = [
  "titl"=>"PLPKMS", "obj_titl"=>"PLPKMS", "tbl_ai" => true,
  "ordby" => "id desc", "toStr" => [],
  "rel"=>[]
];

$config["erd"]["SPPB"] = [
  "titl"=>"SPPB 20", "obj_titl"=>"SPPB 20", "tbl_ai" => false,
  "ordby" => "create_date desc", "toStr" => ["CAR"],
  "rel"=>[ ]
];
$config["erd"]["SPPB_KMS"] = [
  "titl"=>"SPPB 20 KMS", "obj_titl"=>"SPPB 20 KMS", "tbl_ai" => false,
  "ordby" => "CAR desc", "toStr" => ["CAR"],
  "rel"=>[ ]
];
$config["erd"]["SPJM"] = [
  "titl"=>"SPJM", "obj_titl"=>"SPJM Header", "tbl_ai" => false,
  "ordby" => "CAR desc", "toStr" => ["CAR"],
  "rel"=>[ ]
];
$config["erd"]["SPJM_KMS"] = [
  "titl"=>"SPJM KMS", "obj_titl"=>"SPJM KMS", "tbl_ai" => false,
  "ordby" => "CAR desc", "toStr" => ["CAR"],
  "rel"=>[ ]
];
$config["erd"]["SPJM_DOK"] = [
  "titl"=>"SPJM DOK", "obj_titl"=>"SPJM DOK", "tbl_ai" => false,
  "ordby" => "CAR desc", "toStr" => ["CAR"],
  "rel"=>[ ]
];
$config["erd"]["api_ffm"]= [
  "titl" => "FFM", "obj_titl"=>"FFM", "tbl_ai" => true,
  "ordby" => "flightdate desc", "toStr" => ["flight","create_date"],
  "rel" => [  ],
];
$config["erd"]["api_ffmd"]= [
  "titl" => "FFM", "obj_titl"=>"FFM", "tbl_ai" => true,
  "ordby" => "id desc", "toStr" => ["awb"],
  "rel" => [  ],
];
$config["erd"]["api_fwb"]= [
  "titl" => "FWB", "obj_titl"=>"FWB", "tbl_ai" => false,
  "ordby" => "create_date desc", "toStr" => ["awb"],
  "rel" => [  ],
];
$config["erd"]["api_fwb_rtd"]= [
  "titl" => "FWB RTD", "obj_titl"=>"RTD", "tbl_ai" => false,
  "ordby" => "awb asc",
  "toStr" => ["awb","rtd"],
  "rel" => [  ],
];
$config["erd"]["api_fhl"]= [
  "titl" => "FHL", "obj_titl"=>"FHL", "tbl_ai" => false,
  "ordby" => "create_date desc", "toStr" => [],
  "rel" => [  ],
];

/* payment_billing */
$config["erd"]["t_billing"]=[
  "titl"=>"Billings", "obj_titl"=>"Billing", "tbl_ai" => false,
  "ordby" => "id desc",
  "toStr" => ["billnbr"],
  "rel" => [],
];
$config["erd"]["t_bills"]=[
  "titl"=>"Billing Details", "obj_titl"=>"Billing Detail", "tbl_ai" => false,
  "ordby" => "id desc",
  "toStr" => [],
  "rel" => [],
];
$config["erd"]["t_bill_bni"]=[
  "titl"=>"BNI Billings", "obj_titl"=>"BNI Billing", "tbl_ai" => false,
  "ordby" => "id desc",
  "toStr" => [],
  "rel" => [],
];

/* AP1 */
$config["erd"]["ap1_bpp"]=[
  "titl"=>"BPP", "obj_titl"=>"BPP", "tbl_ai" => true,
  "ordby" => "bpp_id desc",
  "toStr" => [],
  "rel" => [],
];
$config["erd"]["ap1_bpp_detail"]=[
  "titl"=>"BPP Detail", "obj_titl"=>"BPP Detail", "tbl_ai" => true,
  "ordby" => "bppdet_id asc",
  "toStr" => [],
  "rel" => [],
];

/* Odisys */
$config["erd"]["api_odisys"]=[
  "titl"=>"Empu", "obj_titl"=>"Empu", "tbl_ai" => true,
  "ordby" => "id desc",
  "toStr" => [],
  "rel" => [],
];

/* SAP */
$config["erd"]["api_sap"]=[
  "titl"=>"SAP", "obj_titl"=>"SAP", "tbl_ai" => true,
  "ordby" => "id desc",
  "toStr" => [],
  "rel" => [],
];

/* Cargoreg POS EMPU */
$config["erd"]["emp_koli"]= [
  "titl" => "Koli", "obj_titl"=>"Koli", "tbl_ai" => true,
  "ordby" => "koli_id asc",
  "toStr" => [],
  "rel" => [  ],
];
$config["erd"]["emp_charges"]= [
  "titl" => "Charges", "obj_titl"=>"Charge", "tbl_ai" => true,
  "ordby" => "id asc",
  "toStr" => ["name","port"],
  "rel" => [  ],
];
$config["erd"]["emp_trcharges"]= [
  "titl" => "Pricing", "obj_titl"=>"Price", "tbl_ai" => false,
  "ordby" => "id asc",
  "toStr" => ["name"],
  "rel" => [  ],
];
$config["erd"]["emp_customer"]= [
  "titl" => "Customers", "obj_titl"=>"Customer", "tbl_ai" => false,
  "ordby" => "name asc",
  "toStr" => ["name"],
  "rel" => [  ],
];
$config["erd"]["emp_register"]=[
  "titl"=>"Register", "obj_titl"=>"Register", "tbl_ai" => false,
  "ordby" => "id desc",
  "toStr" => [],
  "rel" => [],
];
$config["erd"]["emp_cargo_charges"]=[
  "titl"=>"Charges", "obj_titl"=>"Charge", "tbl_ai" => true,
  "ordby" => "emp_cargo_charges.id asc",
  "toStr" => [],
  "rel" => [
    ["select"=>"chg.name as charge_name, chg.charge_group, chg.mandatory, chg.amount, chg.is_reference", "tbl"=>"emp_charges chg","r"=>[
      ["fk"=>"emp_cargo_charges.charge_id","pk"=>"chg.id"] ] ],
    ["select"=>"rhg.amount as ref_amount", "tbl"=>"emp_charges rhg","r"=>[
      ["fk"=>"chg.reference","pk"=>"rhg.id"] ] ],
  ],
];
$config["erd"]["emp_rate"]=[
  "titl"=>"Rates", "obj_titl"=>"Rate", "tbl_ai" => true,
  "ordby" => "rate_date desc",
  "toStr" => [],
  "rel" => [],
];
$config["erd"]["emp_payment"]=[
  "titl"=>"Payments", "obj_titl"=>"Payment", "tbl_ai" => true,
  "ordby" => "trandate desc",
  "toStr" => [],
  "rel" => [
    ["select"=>"cus.name as 'cus.name' ", "tbl"=>"emp_customer cus","r"=>[
      ["fk"=>"emp_payment.customer_id","pk"=>"cus.customer_id"] ] ],
  ],
];
$config["erd"]["emp_closing"]=[
  "titl"=>"Closing", "obj_titl"=>"Closing", "tbl_ai" => true,
  "ordby" => "closing_id desc",
  "toStr" => [],
  "rel" => [],
];
$config["erd"]["emp_reversal"]=[
  "titl"=>"Reversals", "obj_titl"=>"Reversal", "tbl_ai" => true,
  "ordby" => "trandate desc",
  "toStr" => [],
  "rel" => [
    ["select"=>"cus.name as 'cus.name' ", "tbl"=>"emp_customer cus","r"=>[
      ["fk"=>"emp_reversal.customer_id","pk"=>"cus.customer_id"] ] ],
  ],
];

/* Cargoreg PJT */
$config["erd"]["pjt_cargo"]= [
  "titl" => "Charges", "obj_titl"=>"Charge", "tbl_ai" => true,
  "ordby" => "id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_closing"]= [
  "titl" => "Closing", "obj_titl"=>"Closing", "tbl_ai" => true,
  "ordby" => "closing_id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_closing_dtl"]= [
  "titl" => "Closing Dtl ", "obj_titl"=>"Closing", "tbl_ai" => false,
  "ordby" => "closing_id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_closing_daily"]= [
  "titl" => "Closing Daily", "obj_titl"=>"Closing Daily", "tbl_ai" => true,
  "ordby" => "closing_daily_id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_closing_daily_dtl"]= [
  "titl" => "Closing Daily Dtl", "obj_titl"=>"Closing Daily Dtl", "tbl_ai" => true,
  "ordby" => "closing_daily_dtl_id asc",
  "toStr" => [],
  "rel" => [],
];

$config["erd"]["t_closing_daily_cust"]= [
  "titl" => "Closing Daily Cust", "obj_titl"=>"Closing Daily Cust", "tbl_ai" => true,
  "ordby" => "closing_daily_cust_id asc",
  "toStr" => [],
  "rel" => [],
];

?>

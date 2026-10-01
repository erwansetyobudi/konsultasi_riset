<?php
/*
 * File: helper.php
 * Created on Thu Oct 01 2026
 * Last Updated: Thu Oct 01 2026 6:19:32 PM
 * Author: Erwan Setyo Budi
 * Email: erwans818@gmail.com
 * License: The GNU General Public License, Version 3 (GPL-3.0) - Copyright (C) 2026 Erwan Setyo Budi. This program is free software.
 */

use SLiMS\DB;
function kr_h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function kr_db(){return DB::getInstance();}
function kr_id(){return $_GET['id']??$_POST['id']??'';}
function kr_mod(){return $_GET['mod']??$_POST['mod']??'system';}
function kr_url(array $x=[]){return AWB.'plugin_container.php?'.http_build_query(array_merge(['mod'=>kr_mod(),'id'=>kr_id()],$x));}
function kr_storage($kind){$d=__DIR__.'/storage/'.($kind==='photos'?'photos':'attachments');if(!is_dir($d))@mkdir($d,0755,true);return $d;}
function kr_new_id(){
 $db=kr_db(); $prefix='KR-'.date('Ymd').'-';
 $st=$db->prepare('SELECT consultation_id FROM research_consultations WHERE consultation_id LIKE ? ORDER BY consultation_id DESC LIMIT 1');
 $st->execute([$prefix.'%']); $last=$st->fetchColumn(); $n=$last?(int)substr($last,-3)+1:1;
 do{$id=$prefix.str_pad((string)$n,3,'0',STR_PAD_LEFT);$c=$db->prepare('SELECT COUNT(*) FROM research_consultations WHERE consultation_id=?');$c->execute([$id]);$n++;}while($c->fetchColumn());
 return $id;
}
function kr_get($id){$s=kr_db()->prepare('SELECT * FROM research_consultations WHERE id=?');$s->execute([(int)$id]);return $s->fetch(PDO::FETCH_ASSOC)?:null;}
function kr_types(){return ['Penelusuran Literatur','Database Jurnal','Referensi dan Sitasi','Bibliometrik','Reference Manager','Publikasi Ilmiah','Systematic Review / Literature Review','Pemilihan Jurnal','Pengecekan Similarity','Lainnya'];}
function kr_methods(){return ['Tatap Muka','Online','WhatsApp','Zoom','Lainnya'];}
function kr_upload($field,$kind,$allowed,$old=null){
 if(empty($_FILES[$field]['name'])) return $old;
 if(($_FILES[$field]['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('Upload '.$field.' gagal.');
 $ext=strtolower(pathinfo($_FILES[$field]['name'],PATHINFO_EXTENSION)); if(!in_array($ext,$allowed,true)) throw new RuntimeException('Format file '.$field.' tidak diizinkan.');
 if($_FILES[$field]['size']>15*1024*1024) throw new RuntimeException('Ukuran file maksimal 15 MB.');
 $name=date('YmdHis').'-'.bin2hex(random_bytes(4)).'.'.$ext; $dest=kr_storage($kind).'/'.$name;
 if(!move_uploaded_file($_FILES[$field]['tmp_name'],$dest)) throw new RuntimeException('Gagal menyimpan file.');
 if($old && is_file(kr_storage($kind).'/'.basename($old))) @unlink(kr_storage($kind).'/'.basename($old));
 return $name;
}
function kr_stream($doc,$kind){$photo=$kind==='photo';$field=$photo?'consultation_photo':'attachment_file';$file=$doc[$field]??'';if(!$file)exit('File tidak ditemukan');$path=kr_storage($photo?'photos':'attachments').'/'.basename($file);if(!is_file($path))exit('File tidak ditemukan');$name=$photo?basename($path):($doc['attachment_original_name']?:basename($path));while(ob_get_level())@ob_end_clean();$mime=function_exists('mime_content_type')?mime_content_type($path):'application/octet-stream';header('Content-Type: '.$mime);header('Content-Length: '.filesize($path));header('Content-Disposition: '.($photo?'inline':'attachment').'; filename="'.str_replace('"','',$name).'"');readfile($path);exit;}

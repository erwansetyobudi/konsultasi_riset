<?php

/*
 * File: index.inc.php
 * Created on Thu Oct 01 2026
 * Last Updated: Thu Oct 01 2026 6:20:08 PM
 * Author: Erwan Setyo Budi
 * Email: erwans818@gmail.com
 * License: The GNU General Public License, Version 3 (GPL-3.0) - Copyright (C) 2026 Erwan Setyo Budi. This program is free software.
 */

defined('INDEX_AUTH') OR die('Direct access not allowed!');
require_once LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
require_once SB . 'admin/default/session.inc.php';
require_once __DIR__ . '/../helper.php';
if (!utility::havePrivilege('system', 'r'))
    die('<div class="alert alert-danger">Tidak memiliki hak akses.</div>');
$db = kr_db();
$canWrite = utility::havePrivilege('system', 'w');
$view = $_GET['view'] ?? 'list';
$msg = '';
if (isset($_GET['file'], $_GET['doc'])) {
    $d = kr_get((int) $_GET['doc']);
    if ($d)
        kr_stream($d, $_GET['file']);
}
if (isset($_GET['member_search'])) {
    while (ob_get_level())
        @ob_end_clean();
    header('Content-Type: application/json');
    $q = trim($_GET['term'] ?? '');
    $out = [];
    if (strlen($q) >= 2) {
        $s = $db->prepare('SELECT member_id,member_name,inst_name FROM member WHERE member_id LIKE ? OR member_name LIKE ? ORDER BY member_name LIMIT 20');
        $s->execute(['%' . $q . '%', '%' . $q . '%']);
        $out = $s->fetchAll(PDO::FETCH_ASSOC);
    }
    echo json_encode($out);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['kr_action'] ?? '', ['save', 'update'], true) && $canWrite) {
    try {
        $isNew = $_POST['kr_action'] === 'save';
        $old = $isNew ? null : kr_get((int) $_POST['doc']);
        if (!$isNew && !$old)
            throw new RuntimeException('Data tidak ditemukan.');
        if (trim($_POST['member_id'] ?? '') === '')
            throw new RuntimeException('Pemustaka wajib dipilih dari data anggota.');
        $photo = kr_upload('consultation_photo', 'photos', ['jpg', 'jpeg', 'png'], $old['consultation_photo'] ?? null);
        $att = kr_upload('attachment_file', 'attachments', ['pdf', 'doc', 'docx', 'xls', 'xlsx'], $old['attachment_file'] ?? null);
        $attOrig = !empty($_FILES['attachment_file']['name']) ? basename($_FILES['attachment_file']['name']) : ($old['attachment_original_name'] ?? null);
        $vals = [trim($_POST['consultation_datetime']), (int) $_SESSION['uid'], $_SESSION['realname'], trim($_POST['service_method']), trim($_POST['service_method_other'] ?? ''), trim($_POST['member_id']), trim($_POST['member_name']), trim($_POST['member_institution'] ?? ''), trim($_POST['consultation_type']), trim($_POST['consultation_type_other'] ?? ''), trim($_POST['question'] ?? ''), trim($_POST['consultation_description'] ?? ''), trim($_POST['recommendation'] ?? ''), trim($_POST['librarian_notes'] ?? ''), $photo, $att, $attOrig, ($_POST['followup_status'] ?? 'completed') === 'followup' ? 'followup' : 'completed', trim($_POST['followup_plan'] ?? '')];
        if ($isNew) {
            $cid = kr_new_id();
            $sql = 'INSERT INTO research_consultations (consultation_id,consultation_datetime,librarian_uid,librarian_name,service_method,service_method_other,member_id,member_name,member_institution,consultation_type,consultation_type_other,question,consultation_description,recommendation,librarian_notes,consultation_photo,attachment_file,attachment_original_name,followup_status,followup_plan,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $st = $db->prepare($sql);
            $st->execute(array_merge([$cid], $vals, [date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]));
            $msg = 'Konsultasi ' . $cid . ' berhasil disimpan.';
        } else {
            $sql = 'UPDATE research_consultations SET consultation_datetime=?,librarian_uid=?,librarian_name=?,service_method=?,service_method_other=?,member_id=?,member_name=?,member_institution=?,consultation_type=?,consultation_type_other=?,question=?,consultation_description=?,recommendation=?,librarian_notes=?,consultation_photo=?,attachment_file=?,attachment_original_name=?,followup_status=?,followup_plan=?,updated_at=? WHERE id=?';
            $st = $db->prepare($sql);
            $st->execute(array_merge($vals, [date('Y-m-d H:i:s'), $old['id']]));
            $msg = 'Data konsultasi berhasil diperbarui.';
        }
        $view = 'list';
    } catch (Throwable $e) {
        $msg = 'ERROR: ' . $e->getMessage();
        $view = $_POST['kr_action'] === 'update' ? 'edit' : 'add';
        $_GET['doc'] = $_POST['doc'] ?? 0;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['kr_action'] ?? '') === 'delete' && $canWrite) {
    $d = kr_get((int) $_POST['doc']);
    if ($d) {
        foreach ([['consultation_photo', 'photos'], ['attachment_file', 'attachments']] as $f) {
            if ($d[$f[0]] && is_file(kr_storage($f[1]) . '/' . basename($d[$f[0]])))
                @unlink(kr_storage($f[1]) . '/' . basename($d[$f[0]]));
        }
        $db->prepare('DELETE FROM research_consultations WHERE id=?')->execute([$d['id']]);
        $msg = 'Data konsultasi berhasil dihapus.';
    }
    $view = 'list';
}
?>
<div class="menuBox"><div class="menuBoxInner systemIcon"><div class="per_title"><h2>Layanan Konsultasi Riset</h2></div><div class="infoBox">Pencatatan konsultasi riset, dokumentasi, rekomendasi, dan tindak lanjut layanan perpustakaan.</div></div></div>
<?php if ($msg): ?><div class="alert alert-info"><?= kr_h($msg) ?></div><?php endif; ?>
<?php if (in_array($view, ['add', 'edit'], true)):
    $d = $view === 'edit' ? kr_get((int) ($_GET['doc'] ?? 0)) : null;
    if ($view === 'edit' && !$d): ?><div class="alert alert-danger">Data tidak ditemukan.</div><?php else:
        $v = $d ?: []; ?>
<div class="sub_section"><h3><?= $view === 'add' ? 'Tambah Konsultasi' : 'Edit Konsultasi ' . kr_h($d['consultation_id']) ?></h3>
<form id="krForm" class="notAJAX" method="post" enctype="multipart/form-data" action="<?= kr_h(kr_url()) ?>">
<input type="hidden" name="mod" value="<?= kr_h(kr_mod()) ?>"><input type="hidden" name="id" value="<?= kr_h(kr_id()) ?>"><input type="hidden" name="kr_action" value="<?= $view === 'add' ? 'save' : 'update' ?>"><?php if ($d): ?><input type="hidden" name="doc" value="<?= $d['id'] ?>"><?php endif; ?>
<h4>Identitas Konsultasi</h4><div class="row"><div class="col-md-4 form-group"><label>ID Konsultasi</label><input class="form-control" value="<?= $d ? kr_h($d['consultation_id']) : 'Otomatis saat disimpan' ?>" readonly></div><div class="col-md-4 form-group"><label>Tanggal & Waktu</label><input type="datetime-local" name="consultation_datetime" class="form-control" value="<?= kr_h(isset($v['consultation_datetime']) ? date('Y-m-d\TH:i', strtotime($v['consultation_datetime'])) : date('Y-m-d\TH:i')) ?>" required></div><div class="col-md-4 form-group"><label>Nama Pustakawan</label><input class="form-control" value="<?= kr_h($_SESSION['realname']) ?>" readonly></div></div>
<div class="form-group"><label>Jenis Layanan</label><select name="service_method" id="krMethod" class="form-control" required><option value="">-- pilih --</option><?php foreach (kr_methods() as $x): ?><option <?= ($v['service_method'] ?? '') === $x ? 'selected' : '' ?>><?= kr_h($x) ?></option><?php endforeach; ?></select></div><div class="form-group" id="krMethodOther"><label>Jenis layanan lainnya</label><input name="service_method_other" class="form-control" value="<?= kr_h($v['service_method_other'] ?? '') ?>"></div>
<hr><h4>Data Pemustaka</h4><div class="form-group"><label>Cari Anggota SLiMS</label><div class="input-group"><input id="krMemberQuery" class="form-control" placeholder="Ketik ID anggota atau nama..."><span class="input-group-btn"><button type="button" id="krMemberSearch" class="btn btn-default"><i class="fa fa-search"></i> Cari</button></span></div><div id="krMemberResults" style="margin-top:5px"></div></div>
<div class="row"><div class="col-md-4 form-group"><label>ID Anggota</label><input name="member_id" id="krMemberId" class="form-control" value="<?= kr_h($v['member_id'] ?? '') ?>" readonly required></div><div class="col-md-4 form-group"><label>Nama Pemustaka</label><input name="member_name" id="krMemberName" class="form-control" value="<?= kr_h($v['member_name'] ?? '') ?>" readonly></div><div class="col-md-4 form-group"><label>Instansi/Prodi</label><input name="member_institution" id="krMemberInst" class="form-control" value="<?= kr_h($v['member_institution'] ?? '') ?>" readonly></div></div>
<hr><h4>Isi Konsultasi</h4><div class="form-group"><label>Jenis Konsultasi Riset</label><select name="consultation_type" id="krType" class="form-control" required><option value="">-- pilih --</option><?php foreach (kr_types() as $x): ?><option <?= ($v['consultation_type'] ?? '') === $x ? 'selected' : '' ?>><?= kr_h($x) ?></option><?php endforeach; ?></select></div><div class="form-group" id="krTypeOther"><label>Jenis konsultasi lainnya</label><input name="consultation_type_other" class="form-control" value="<?= kr_h($v['consultation_type_other'] ?? '') ?>"></div>
<?php foreach (['question' => 'Pertanyaan/Permasalahan', 'consultation_description' => 'Deskripsi Konsultasi', 'recommendation' => 'Rekomendasi/Solusi', 'librarian_notes' => 'Catatan Pustakawan'] as $n => $l): ?><div class="form-group"><label><?= $l ?></label><textarea name="<?= $n ?>" class="form-control" rows="4"><?= kr_h($v[$n] ?? '') ?></textarea></div><?php endforeach; ?>
<hr><h4>Dokumentasi</h4><div class="row"><div class="col-md-6 form-group"><label>Foto Konsultasi (JPG/PNG)</label><input type="file" name="consultation_photo" accept=".jpg,.jpeg,.png,image/jpeg,image/png"><?php if (!empty($v['consultation_photo'])): ?><small>File saat ini tersedia.</small><?php endif; ?></div><div class="col-md-6 form-group"><label>Lampiran (PDF/DOC/DOCX/XLS/XLSX)</label><input type="file" name="attachment_file" accept=".pdf,.doc,.docx,.xls,.xlsx"><?php if (!empty($v['attachment_file'])): ?><small><?= kr_h($v['attachment_original_name']) ?></small><?php endif; ?></div></div>
<hr><h4>Tindak Lanjut</h4><div class="form-group"><label>Status</label><select name="followup_status" id="krStatus" class="form-control"><option value="completed" <?= ($v['followup_status'] ?? 'completed') === 'completed' ? 'selected' : '' ?>>Selesai</option><option value="followup" <?= ($v['followup_status'] ?? '') === 'followup' ? 'selected' : '' ?>>Perlu Tindak Lanjut</option></select></div><div class="form-group" id="krFollow"><label>Rencana Tindak Lanjut</label><textarea name="followup_plan" class="form-control" rows="3"><?= kr_h($v['followup_plan'] ?? '') ?></textarea></div>
<button class="btn btn-primary" type="submit">Simpan</button> <a class="btn btn-default simbioAJAX" href="<?= kr_h(kr_url()) ?>">Batal</a></form></div>
<iframe name="krFrame" id="krFrame" style="display:none"></iframe>
<script>(function($){
 function toggle(){ $('#krMethodOther').toggle($('#krMethod').val()==='Lainnya');$('#krTypeOther').toggle($('#krType').val()==='Lainnya');$('#krFollow').toggle($('#krStatus').val()==='followup');} toggle();$('#krMethod,#krType,#krStatus').on('change',toggle);
 function search(){var q=$('#krMemberQuery').val();if(q.length<2)return;$('#krMemberResults').html('Mencari...');$.getJSON(<?= json_encode(kr_url(['member_search' => 1])) ?>+'&term='+encodeURIComponent(q),function(rows){var h='';if(!rows.length)h='<div class="alert alert-warning">Anggota tidak ditemukan.</div>';$.each(rows,function(i,r){h+='<button type="button" class="btn btn-default btn-sm krPick" style="margin:2px" data-id="'+$('<div>').text(r.member_id).html()+'" data-name="'+$('<div>').text(r.member_name).html()+'" data-inst="'+$('<div>').text(r.inst_name||'').html()+'">'+$('<div>').text(r.member_id+' — '+r.member_name).html()+'</button>';});$('#krMemberResults').html(h);});}
 $('#krMemberSearch').on('click',search);$('#krMemberQuery').on('keydown',function(e){if(e.keyCode===13){e.preventDefault();search();}});$(document).on('click','.krPick',function(){$('#krMemberId').val($(this).data('id'));$('#krMemberName').val($(this).data('name'));$('#krMemberInst').val($(this).data('inst'));$('#krMemberResults').empty();});
 $('#krForm').on('submit',function(){this.target='krFrame';var f=document.getElementById('krFrame');f.onload=function(){try{var t=f.contentDocument.body.innerText;if(t)alert(t);}catch(e){} parent.jQuery('#mainContent').simbioAJAX(<?= json_encode(kr_url()) ?>);};});
})(jQuery);</script>
<?php endif; ?>
<?php elseif ($view === 'detail'):
    $d = kr_get((int) ($_GET['doc'] ?? 0));
    if (!$d): ?><div class="alert alert-danger">Data tidak ditemukan.</div><?php else: ?><div class="sub_section"><h3>Detail Konsultasi <?= kr_h($d['consultation_id']) ?></h3><table class="table table-striped"><tr><th width="230">Tanggal & Waktu</th><td><?= kr_h($d['consultation_datetime']) ?></td></tr><tr><th>Pustakawan</th><td><?= kr_h($d['librarian_name']) ?></td></tr><tr><th>Jenis Layanan</th><td><?= kr_h($d['service_method'] . ($d['service_method_other'] ? ' — ' . $d['service_method_other'] : '')) ?></td></tr><tr><th>Pemustaka</th><td><?= kr_h($d['member_id'] . ' — ' . $d['member_name']) ?><br><?= kr_h($d['member_institution']) ?></td></tr><tr><th>Jenis Konsultasi</th><td><?= kr_h($d['consultation_type'] . ($d['consultation_type_other'] ? ' — ' . $d['consultation_type_other'] : '')) ?></td></tr><?php foreach (['question' => 'Pertanyaan/Permasalahan', 'consultation_description' => 'Deskripsi Konsultasi', 'recommendation' => 'Rekomendasi/Solusi', 'librarian_notes' => 'Catatan Pustakawan'] as $n => $l): ?><tr><th><?= $l ?></th><td><?= nl2br(kr_h($d[$n])) ?></td></tr><?php endforeach; ?><tr><th>Status</th><td><b><?= $d['followup_status'] === 'completed' ? 'SELESAI' : 'PERLU TINDAK LANJUT' ?></b></td></tr><tr><th>Rencana Tindak Lanjut</th><td><?= nl2br(kr_h($d['followup_plan'])) ?></td></tr></table><?php if ($d['consultation_photo']): ?><p><b>Foto Konsultasi</b><br><img src="<?= kr_h(kr_url(['file' => 'photo', 'doc' => $d['id']])) ?>" style="max-width:500px;max-height:350px"></p><?php endif; ?><?php if ($d['attachment_file']): ?><a class="btn btn-info notAJAX" href="<?= kr_h(kr_url(['file' => 'attachment', 'doc' => $d['id']])) ?>"><i class="fa fa-download"></i> Download Lampiran</a> <?php endif; ?><a class="btn btn-default simbioAJAX" href="<?= kr_h(kr_url()) ?>">Kembali</a></div><?php endif; ?>
<?php else:
    $q = trim($_GET['q'] ?? '');
    $status = $_GET['status'] ?? '';
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = 20;
    $off = ($page - 1) * $limit;
    $where = [];
    $p = [];
    if ($q !== '') {
        $where[] = '(consultation_id LIKE ? OR member_id LIKE ? OR member_name LIKE ? OR consultation_type LIKE ? OR librarian_name LIKE ?)';
        for ($i = 0; $i < 5; $i++)
            $p[] = '%' . $q . '%';
    }
    if (in_array($status, ['completed', 'followup'], true)) {
        $where[] = 'followup_status=?';
        $p[] = $status;
    }
    $wh = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $s = $db->prepare('SELECT COUNT(*) FROM research_consultations' . $wh);
    $s->execute($p);
    $total = (int) $s->fetchColumn();
    $s = $db->prepare('SELECT * FROM research_consultations' . $wh . ' ORDER BY consultation_datetime DESC,id DESC LIMIT ' . $limit . ' OFFSET ' . $off);
    $s->execute($p);
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    $pages = max(1, (int) ceil($total / $limit)); ?>
<div class="sub_section"><p><?php if ($canWrite): ?><a class="btn btn-primary simbioAJAX" href="<?= kr_h(kr_url(['view' => 'add'])) ?>"><i class="fa fa-plus"></i> Tambah Konsultasi</a><?php endif; ?></p><form class="form-inline notAJAX" onsubmit="parent.jQuery('#mainContent').simbioAJAX(jQuery(this).attr('action')+'&'+jQuery(this).serialize());return false" action="<?= kr_h(kr_url()) ?>"><input type="hidden" name="mod" value="<?= kr_h(kr_mod()) ?>"><input type="hidden" name="id" value="<?= kr_h(kr_id()) ?>"><input name="q" value="<?= kr_h($q) ?>" class="form-control" style="min-width:350px" placeholder="Cari ID, anggota, jenis, pustakawan"><select name="status" class="form-control"><option value="">Semua Status</option><option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Selesai</option><option value="followup" <?= $status === 'followup' ? 'selected' : '' ?>>Perlu Tindak Lanjut</option></select> <button class="btn btn-default">Cari</button> <a class="btn btn-default simbioAJAX" href="<?= kr_h(kr_url()) ?>">Reset</a></form><br><div class="table-responsive"><table class="table table-bordered table-striped table-hover"><thead><tr><th>No</th><th>ID Konsultasi</th><th>Tanggal</th><th>Pemustaka</th><th>Jenis Konsultasi</th><th>Pustakawan</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php $no = $off + 1;
    foreach ($rows as $r): ?><tr><td><?= $no++ ?></td><td><b><?= kr_h($r['consultation_id']) ?></b></td><td><?= kr_h($r['consultation_datetime']) ?></td><td><?= kr_h($r['member_name']) ?><br><small><?= kr_h($r['member_id']) ?></small></td><td><?= kr_h($r['consultation_type']) ?></td><td><?= kr_h($r['librarian_name']) ?></td><td><span class="label <?= $r['followup_status'] === 'completed' ? 'label-success' : 'label-warning' ?>"><?= $r['followup_status'] === 'completed' ? 'Selesai' : 'Tindak Lanjut' ?></span></td><td style="white-space:nowrap"><a class="btn btn-xs btn-default simbioAJAX" href="<?= kr_h(kr_url(['view' => 'detail', 'doc' => $r['id']])) ?>">Detail</a> <?php if ($canWrite): ?><a class="btn btn-xs btn-primary simbioAJAX" href="<?= kr_h(kr_url(['view' => 'edit', 'doc' => $r['id']])) ?>">Edit</a> <form method="post" class="notAJAX" action="<?= kr_h(kr_url()) ?>" style="display:inline" onsubmit="if(!confirm('Hapus data konsultasi ini?'))return false;parent.jQuery('#mainContent').simbioAJAX(jQuery(this).attr('action'),{method:'post',addData:jQuery(this).serialize()});return false"><input type="hidden" name="mod" value="<?= kr_h(kr_mod()) ?>"><input type="hidden" name="id" value="<?= kr_h(kr_id()) ?>"><input type="hidden" name="kr_action" value="delete"><input type="hidden" name="doc" value="<?= $r['id'] ?>"><button class="btn btn-xs btn-danger">Delete</button></form><?php endif; ?></td></tr><?php endforeach;
    if (!$rows): ?><tr><td colspan="8" class="text-center">Belum ada data konsultasi.</td></tr><?php endif; ?></tbody></table></div><?php if ($pages > 1):
        foreach (range(1, $pages) as $i): ?><a class="btn btn-xs <?= $i === $page ? 'btn-primary' : 'btn-default' ?> simbioAJAX" href="<?= kr_h(kr_url(['q' => $q, 'status' => $status, 'page' => $i])) ?>"><?= $i ?></a> <?php endforeach;
    endif; ?></div>
<?php endif; ?>

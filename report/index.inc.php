<?php

/*
 * File: index.inc.php
 * Created on Thu Oct 01 2026
 * Last Updated: Thu Oct 01 2026 6:19:46 PM
 * Author: Erwan Setyo Budi
 * Email: erwans818@gmail.com
 * License: The GNU General Public License, Version 3 (GPL-3.0) - Copyright (C) 2026 Erwan Setyo Budi. This program is free software.
 */

defined('INDEX_AUTH') OR die('Direct access not allowed!');
require_once LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
require_once SB . 'admin/default/session.inc.php';
require_once __DIR__ . '/../helper.php';
if (!utility::havePrivilege('reporting', 'r') && !utility::havePrivilege('system', 'r'))
    die('<div class="alert alert-danger">Tidak memiliki hak akses.</div>');
$db = kr_db();
$from = $_GET['date_from'] ?? date('Y-m-01');
$to = $_GET['date_to'] ?? date('Y-m-d');
$lib = trim($_GET['librarian'] ?? '');
$type = trim($_GET['type'] ?? '');
$status = trim($_GET['status'] ?? '');
$w = ['DATE(consultation_datetime) BETWEEN ? AND ?'];
$p = [$from, $to];
if ($lib !== '') {
    $w[] = 'librarian_name=?';
    $p[] = $lib;
}
if ($type !== '') {
    $w[] = 'consultation_type=?';
    $p[] = $type;
}
if ($status !== '') {
    $w[] = 'followup_status=?';
    $p[] = $status;
}
$wh = ' WHERE ' . implode(' AND ', $w);
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    while (ob_get_level())
        @ob_end_clean();
    header('Content-Type:text/csv;charset=UTF-8');
    header('Content-Disposition:attachment;filename="laporan-konsultasi-riset-' . $from . '-' . $to . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\u{FEFF}");
    fputcsv($o, ['ID Konsultasi', 'Tanggal', 'ID Anggota', 'Nama Pemustaka', 'Instansi', 'Jenis Layanan', 'Jenis Konsultasi', 'Pertanyaan', 'Deskripsi', 'Rekomendasi', 'Pustakawan', 'Status', 'Rencana Tindak Lanjut']);
    $s = $db->prepare('SELECT * FROM research_consultations' . $wh . ' ORDER BY consultation_datetime DESC');
    $s->execute($p);
    while ($r = $s->fetch(PDO::FETCH_ASSOC))
        fputcsv($o, [$r['consultation_id'], $r['consultation_datetime'], $r['member_id'], $r['member_name'], $r['member_institution'], $r['service_method'], $r['consultation_type'], $r['question'], $r['consultation_description'], $r['recommendation'], $r['librarian_name'], $r['followup_status'] === 'completed' ? 'Selesai' : 'Perlu Tindak Lanjut', $r['followup_plan']]);
    fclose($o);
    exit;
}
$s = $db->prepare('SELECT COUNT(*) total,COUNT(DISTINCT member_id) members,SUM(followup_status="completed") completed,SUM(followup_status="followup") followup FROM research_consultations' . $wh);
$s->execute($p);
$sum = $s->fetch(PDO::FETCH_ASSOC);
$s = $db->prepare('SELECT consultation_type,COUNT(*) total FROM research_consultations' . $wh . ' GROUP BY consultation_type ORDER BY total DESC');
$s->execute($p);
$byType = $s->fetchAll(PDO::FETCH_ASSOC);
$s = $db->prepare('SELECT librarian_name,COUNT(*) total FROM research_consultations' . $wh . ' GROUP BY librarian_name ORDER BY total DESC');
$s->execute($p);
$byLib = $s->fetchAll(PDO::FETCH_ASSOC);
$libs = $db->query('SELECT DISTINCT librarian_name FROM research_consultations ORDER BY librarian_name')->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="menuBox"><div class="menuBoxInner reportIcon"><div class="per_title"><h2>Laporan Konsultasi Riset</h2></div><div class="infoBox">Rekap layanan berdasarkan periode, pustakawan, jenis konsultasi, dan status tindak lanjut.</div></div></div>
<div class="sub_section"><form class="form-inline notAJAX" action="<?= kr_h(kr_url()) ?>" onsubmit="parent.jQuery('#mainContent').simbioAJAX(jQuery(this).attr('action')+'&'+jQuery(this).serialize());return false"><input type="hidden" name="mod" value="<?= kr_h(kr_mod()) ?>"><input type="hidden" name="id" value="<?= kr_h(kr_id()) ?>"><label>Dari</label> <input type="date" name="date_from" class="form-control" value="<?= kr_h($from) ?>"> <label>Sampai</label> <input type="date" name="date_to" class="form-control" value="<?= kr_h($to) ?>"> <select name="librarian" class="form-control"><option value="">Semua Pustakawan</option><?php foreach ($libs as $x): ?><option <?= ($lib === $x ? 'selected' : '') ?>><?= kr_h($x) ?></option><?php endforeach; ?></select><select name="type" class="form-control"><option value="">Semua Jenis</option><?php foreach (kr_types() as $x): ?><option <?= ($type === $x ? 'selected' : '') ?>><?= kr_h($x) ?></option><?php endforeach; ?></select><select name="status" class="form-control"><option value="">Semua Status</option><option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Selesai</option><option value="followup" <?= $status === 'followup' ? 'selected' : '' ?>>Perlu Tindak Lanjut</option></select> <button class="btn btn-primary">Tampilkan</button> <a class="btn btn-success notAJAX" href="<?= kr_h(kr_url(['date_from' => $from, 'date_to' => $to, 'librarian' => $lib, 'type' => $type, 'status' => $status, 'export' => 'csv'])) ?>"><i class="fa fa-download"></i> Export CSV</a></form><hr>
<div class="row"><div class="col-md-3"><div class="alert alert-info"><b>Total Konsultasi</b><br><span style="font-size:28px"><?= (int) $sum['total'] ?></span></div></div><div class="col-md-3"><div class="alert alert-info"><b>Pemustaka Unik</b><br><span style="font-size:28px"><?= (int) $sum['members'] ?></span></div></div><div class="col-md-3"><div class="alert alert-success"><b>Selesai</b><br><span style="font-size:28px"><?= (int) $sum['completed'] ?></span></div></div><div class="col-md-3"><div class="alert alert-warning"><b>Perlu Tindak Lanjut</b><br><span style="font-size:28px"><?= (int) $sum['followup'] ?></span></div></div></div>
<div class="row"><div class="col-md-6"><h4>Rekap Jenis Konsultasi</h4><table class="table table-bordered table-striped"><thead><tr><th>Jenis</th><th width="100">Jumlah</th></tr></thead><tbody><?php foreach ($byType as $r): ?><tr><td><?= kr_h($r['consultation_type']) ?></td><td><?= $r['total'] ?></td></tr><?php endforeach; ?></tbody></table></div><div class="col-md-6"><h4>Rekap Pustakawan</h4><table class="table table-bordered table-striped"><thead><tr><th>Pustakawan</th><th width="100">Jumlah</th></tr></thead><tbody><?php foreach ($byLib as $r): ?><tr><td><?= kr_h($r['librarian_name']) ?></td><td><?= $r['total'] ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>

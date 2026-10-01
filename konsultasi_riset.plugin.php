<?php
/**
 * Plugin Name: Layanan Konsultasi Riset
 * Description: Pencatatan layanan konsultasi riset perpustakaan, dokumentasi, tindak lanjut, dan pelaporan.
 * Version: 1.0.0
 * Author: OErwan Setyo Budi
 */
use SLiMS\Plugins;
$plugin=Plugins::getInstance();
$plugin->registerMenu('membership','Konsultasi Riset',__DIR__.'/admin/index.inc.php');
$plugin->registerMenu('reporting','Laporan Konsultasi Riset',__DIR__.'/report/index.inc.php');

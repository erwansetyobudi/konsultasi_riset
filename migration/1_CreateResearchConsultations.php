<?php
/*
 * File: 1_CreateResearchConsultations.php
 * Created on Thu Oct 01 2026
 * Last Updated: Thu Oct 01 2026 6:19:57 PM
 * Author: Erwan Setyo Budi
 * Email: erwans818@gmail.com
 * License: The GNU General Public License, Version 3 (GPL-3.0) - Copyright (C) 2026 Erwan Setyo Budi. This program is free software.
 */

use SLiMS\Migration\Migration;
class CreateResearchConsultations extends Migration {
 public function up(){
  \SLiMS\DB::getInstance()->query(<<<SQL
CREATE TABLE IF NOT EXISTS `research_consultations` (
 `id` bigint unsigned NOT NULL AUTO_INCREMENT,
 `consultation_id` varchar(30) NOT NULL,
 `consultation_datetime` datetime NOT NULL,
 `librarian_uid` int NOT NULL,
 `librarian_name` varchar(255) NOT NULL,
 `service_method` varchar(50) NOT NULL,
 `service_method_other` varchar(100) DEFAULT NULL,
 `member_id` varchar(50) NOT NULL,
 `member_name` varchar(255) NOT NULL,
 `member_institution` varchar(255) DEFAULT NULL,
 `consultation_type` varchar(100) NOT NULL,
 `consultation_type_other` varchar(150) DEFAULT NULL,
 `question` text DEFAULT NULL,
 `consultation_description` text DEFAULT NULL,
 `recommendation` text DEFAULT NULL,
 `librarian_notes` text DEFAULT NULL,
 `consultation_photo` varchar(255) DEFAULT NULL,
 `attachment_file` varchar(255) DEFAULT NULL,
 `attachment_original_name` varchar(255) DEFAULT NULL,
 `followup_status` enum('completed','followup') NOT NULL DEFAULT 'completed',
 `followup_plan` text DEFAULT NULL,
 `created_at` datetime NOT NULL,
 `updated_at` datetime DEFAULT NULL,
 PRIMARY KEY (`id`), UNIQUE KEY `consultation_id` (`consultation_id`),
 KEY `member_id` (`member_id`), KEY `librarian_uid` (`librarian_uid`),
 KEY `consultation_datetime` (`consultation_datetime`), KEY `followup_status` (`followup_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
 }
 public function down(){ \SLiMS\DB::getInstance()->query("DROP TABLE IF EXISTS `research_consultations`"); }
}

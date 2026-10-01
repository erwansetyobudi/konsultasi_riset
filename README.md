# Plugin Layanan Konsultasi Riset SLiMS

Plugin untuk mencatat layanan konsultasi riset perpustakaan dan membuat laporan layanan.

## Fitur
- ID konsultasi otomatis: `KR-YYYYMMDD-001` dan nomor urut harian.
- Pustakawan otomatis dari session user SLiMS.
- Pencarian pemustaka langsung dari tabel `member` berdasarkan ID/nama.
- Jenis layanan: Tatap Muka, Online, WhatsApp, Zoom, Lainnya.
- Kategori konsultasi riset: penelusuran literatur, database jurnal, referensi/sitasi, bibliometrik, reference manager, publikasi ilmiah, systematic/literature review, pemilihan jurnal, similarity, lainnya.
- Pertanyaan, deskripsi proses, rekomendasi/solusi, dan catatan pustakawan.
- Upload foto JPG/PNG dan lampiran PDF/DOC/DOCX/XLS/XLSX, maksimal 15 MB per file.
- Status Selesai / Perlu Tindak Lanjut beserta rencana tindak lanjut.
- Datagrid pencarian, filter status, paging 20 data, Detail, Edit, Delete.
- Menu Laporan Konsultasi Riset: filter periode, pustakawan, jenis konsultasi, status; ringkasan total, pemustaka unik, selesai, tindak lanjut; rekap jenis dan pustakawan; Export CSV.
- Semua form/filter/action mempertahankan routing `mod` dan `id` plugin SLiMS.

## Instalasi
1. Ekstrak folder `konsultasi_riset` ke folder `plugins` SLiMS.
2. Aktifkan plugin dari menu System > Plugins.
3. Jalankan migrasi plugin jika diminta SLiMS.
4. Menu **Konsultasi Riset** tersedia pada System dan **Laporan Konsultasi Riset** pada Reporting.

## Tampilan
<img width="1347" height="596" alt="image" src="https://github.com/user-attachments/assets/1ae690bc-506c-41e9-a6b4-e57146a5c6a8" />


## Database
Plugin membuat tabel `research_consultations`. Data anggota tetap mengambil tabel bawaan SLiMS `member`; plugin menyimpan snapshot ID, nama, dan instansi/prodi saat konsultasi dicatat.

## Catatan
Hak baca/tulis mengikuti privilege modul System. Menu laporan menerima privilege Reporting atau System.

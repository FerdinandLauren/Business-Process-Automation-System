# PROJECT 04 — BUSINESS PROCESS AUTOMATION SYSTEM

**Project Name:** Business Process Automation System  
**Project Type:** Enterprise Web Application  
**Category:** Business Process Management, Workflow Automation, Enterprise Systems  
**Project Stage:** Portfolio Project  
**Specification:** PROJECT.md  
**Document Version:** 1.0.0  
**Status:** Ready for Implementation

---

# 1. PROJECT OVERVIEW

## 1.1 Project Description

Business Process Automation System adalah aplikasi web untuk mengotomatisasi proses administrasi dan alur persetujuan dalam perusahaan.

Dalam operasional perusahaan, banyak aktivitas administratif masih dilakukan secara manual melalui email, WhatsApp, spreadsheet, dokumen fisik, dan komunikasi antarpegawai.

Kondisi tersebut dapat menyebabkan keterlambatan persetujuan, kesulitan memantau status pengajuan, kehilangan dokumen, kurangnya transparansi, serta tidak adanya riwayat aktivitas yang terpusat.

Sistem ini dirancang untuk mengubah proses tersebut menjadi workflow digital yang terstruktur, terotomatisasi, dapat dipantau, dan memiliki rekam jejak aktivitas.

Pengguna dapat mengajukan permintaan administrasi, mengunggah dokumen pendukung, mengikuti tahapan persetujuan, menerima notifikasi, dan memantau perkembangan pengajuan secara real-time atau melalui pembaruan berkala.

Administrator dapat membuat template workflow, menentukan urutan persetujuan, mengatur tanggung jawab setiap posisi, serta memantau keseluruhan proses operasional.

## 1.2 Business Scenario

Sistem dikembangkan menggunakan skenario perusahaan fiktif bernama PT Arunika Industrial Solutions.

Perusahaan ini memiliki beberapa departemen:

- Human Resources
- Finance
- General Affairs
- Procurement
- Information Technology
- Operations
- Management

Perusahaan menghadapi permasalahan dalam pengelolaan administrasi karena setiap departemen memiliki proses pengajuan dan persetujuan yang berbeda.

Contoh aktivitas administratif:

1. Pengajuan cuti karyawan.
2. Pengajuan reimbursement.
3. Permintaan pembelian barang.
4. Permintaan fasilitas kantor.
5. Permintaan perjalanan dinas.
6. Permintaan akses sistem IT.

Setiap pengajuan memiliki aturan, dokumen pendukung, pihak yang bertanggung jawab, dan tahapan persetujuan yang berbeda.

Business Process Automation System menjadi platform terpusat untuk mengelola seluruh aktivitas tersebut.

**Catatan:** PT Arunika Industrial Solutions merupakan perusahaan fiktif untuk keperluan demonstrasi dan portfolio.

## 1.3 Product Vision

Membangun platform workflow automation yang memungkinkan perusahaan mendigitalisasi proses administratif, mengurangi ketergantungan terhadap komunikasi manual, meningkatkan transparansi, dan memastikan setiap pekerjaan memiliki penanggung jawab serta riwayat aktivitas yang jelas.

## 1.4 Project Objectives

1. Mengubah proses administrasi manual menjadi workflow digital.
2. Mengotomatisasi proses persetujuan berjenjang.
3. Memusatkan seluruh pengajuan dan dokumen administratif.
4. Memberikan transparansi terhadap status setiap pengajuan.
5. Mengurangi keterlambatan melalui notifikasi dan SLA monitoring.
6. Menyediakan audit trail untuk seluruh aktivitas penting.
7. Memberikan fleksibilitas kepada perusahaan dalam mengatur workflow.
8. Menyediakan informasi operasional untuk evaluasi dan pengambilan keputusan.

---

# 2. BUSINESS ANALYSIS

## 2.1 Business Problems

Permasalahan utama yang ingin diselesaikan:

### A. Fragmented Administrative Processes

Pengajuan administrasi tersebar melalui berbagai media komunikasi.

Akibatnya:
- Data pengajuan tidak terpusat.
- Dokumen sulit ditemukan.
- Informasi antarbagian tidak konsisten.
- Riwayat komunikasi sulit ditelusuri.

### B. Manual Approval Process

Proses persetujuan dilakukan secara manual melalui komunikasi langsung atau email.

Akibatnya:
- Pengajuan dapat tertunda.
- Pemohon tidak mengetahui siapa yang sedang memproses pengajuan.
- Persetujuan sulit dipantau.
- Tidak terdapat mekanisme eskalasi yang konsisten.

### C. Lack of Process Visibility

Manajemen kesulitan mengetahui kondisi keseluruhan proses administrasi.

Akibatnya:
- Sulit mengidentifikasi bottleneck.
- Tidak diketahui departemen yang memiliki antrean pekerjaan tinggi.
- Sulit mengevaluasi ketepatan waktu penyelesaian.
- Tidak tersedia laporan operasional terpusat.

### D. Weak Accountability

Tanggung jawab setiap tahapan tidak selalu terdokumentasi.

Akibatnya:
- Sulit mengetahui siapa yang melakukan tindakan tertentu.
- Sulit mengevaluasi keterlambatan.
- Riwayat perubahan keputusan tidak terdokumentasi dengan baik.

## 2.2 Root Cause Analysis

| Problem | Root Cause | Business Impact |
|---|---|---|
| Pengajuan tersebar | Tidak ada platform terpusat | Data tidak konsisten |
| Persetujuan lambat | Proses manual | Waktu penyelesaian meningkat |
| Status tidak transparan | Tidak ada tracking | Pemohon harus melakukan follow-up |
| Dokumen hilang | Penyimpanan terpisah | Risiko administrasi |
| Tidak ada audit | Aktivitas tidak tercatat | Akuntabilitas rendah |
| Sulit mengevaluasi proses | Data operasional tidak terstruktur | Keputusan kurang optimal |

## 2.3 Stakeholder Analysis

### Employee

Bertanggung jawab mengajukan kebutuhan administratif.

Kebutuhan:
- Membuat pengajuan.
- Mengunggah dokumen.
- Melihat status pengajuan.
- Menerima notifikasi.
- Melihat riwayat pengajuan.

### Department Manager

Bertanggung jawab melakukan pemeriksaan dan persetujuan pengajuan dari departemennya.

Kebutuhan:
- Melihat antrean persetujuan.
- Memeriksa detail pengajuan.
- Menyetujui atau menolak permintaan.
- Meminta revisi.
- Memantau SLA.

### Process Approver

Bertanggung jawab menyetujui pengajuan sesuai tahapan workflow.

Kebutuhan:
- Mendapatkan tugas persetujuan.
- Melihat dokumen pendukung.
- Memberikan keputusan.
- Menambahkan catatan.

### Process Administrator

Bertanggung jawab mengelola konfigurasi workflow.

Kebutuhan:
- Membuat workflow.
- Menentukan tahapan persetujuan.
- Mengatur approver.
- Menentukan kondisi persetujuan.
- Mengaktifkan atau menonaktifkan workflow.

### System Administrator

Bertanggung jawab terhadap konfigurasi sistem dan pengelolaan akses.

Kebutuhan:
- Mengelola pengguna.
- Mengelola role dan permission.
- Mengelola departemen.
- Memantau aktivitas sistem.
- Mengakses audit log.

### Management

Bertanggung jawab memantau performa proses administrasi.

Kebutuhan:
- Melihat ringkasan operasional.
- Memantau jumlah pengajuan.
- Mengevaluasi waktu penyelesaian.
- Mengidentifikasi bottleneck.
- Mengakses laporan.

## 2.4 AS-IS Business Process

Kondisi proses sebelum sistem diterapkan.

Contoh pengajuan reimbursement:

1. Karyawan menghubungi bagian Finance.
2. Karyawan mengirimkan formulir melalui email atau dokumen.
3. Finance memeriksa kelengkapan dokumen.
4. Jika dokumen kurang, Finance menghubungi karyawan.
5. Karyawan mengirimkan revisi.
6. Finance meminta persetujuan atasan.
7. Atasan memberikan keputusan secara manual.
8. Finance memproses pengajuan.
9. Karyawan menghubungi Finance untuk menanyakan status.
10. Pengajuan dinyatakan selesai.

Permasalahan:
- Tidak ada tracking terpusat.
- Proses bergantung pada komunikasi manual.
- Tidak ada notifikasi otomatis.
- Tidak ada standar waktu penyelesaian.
- Riwayat keputusan sulit ditelusuri.

## 2.5 TO-BE Business Process

Kondisi setelah menggunakan Business Process Automation System.

1. Karyawan login ke aplikasi.
2. Karyawan memilih jenis pengajuan.
3. Sistem menampilkan formulir sesuai template.
4. Karyawan mengisi informasi dan mengunggah dokumen.
5. Sistem memvalidasi kelengkapan data.
6. Karyawan mengirimkan pengajuan.
7. Sistem membuat workflow instance.
8. Sistem menentukan approver berdasarkan konfigurasi.
9. Approver menerima notifikasi.
10. Approver memeriksa pengajuan.
11. Approver memberikan keputusan.
12. Sistem otomatis mengaktifkan tahapan berikutnya.
13. Jika diperlukan revisi, sistem mengembalikan pengajuan kepada pemohon.
14. Jika seluruh tahapan disetujui, pengajuan masuk ke status Approved.
15. Jika ditolak, pengajuan masuk ke status Rejected.
16. Sistem menyimpan seluruh riwayat aktivitas.
17. Pemohon menerima notifikasi perubahan status.

Hasil yang diharapkan:
- Proses lebih terstruktur.
- Status pengajuan transparan.
- Persetujuan berjalan otomatis.
- Riwayat aktivitas terdokumentasi.
- Manajemen dapat memantau performa workflow.

## 2.6 Business Value

| Area | Expected Value |
|---|---|
| Operational Efficiency | Mengurangi aktivitas administratif manual |
| Transparency | Status pengajuan dapat dipantau |
| Accountability | Setiap keputusan memiliki identitas pelaku |
| Process Standardization | Workflow mengikuti aturan perusahaan |
| Monitoring | Manajemen dapat memantau proses |
| Governance | Aktivitas penting memiliki audit trail |

Nilai bisnis tersebut merupakan tujuan desain, bukan klaim hasil implementasi.

---

# 3. PRODUCT SCOPE

## 3.1 MVP Scope

Fitur yang wajib tersedia pada versi pertama:

1. Authentication and Authorization.
2. User and Department Management.
3. Role-Based Access Control.
4. Request Management.
5. Dynamic Request Forms.
6. Workflow Template Management.
7. Multi-Level Approval.
8. Approval Task Management.
9. Document Attachment.
10. Request Status Tracking.
11. Notification Center.
12. SLA Monitoring.
13. Activity History.
14. Operational Dashboard.
15. Basic Reporting.

## 3.2 Version 2

Fitur pengembangan berikutnya:

1. Conditional Workflow.
2. Parallel Approval.
3. Delegated Approval.
4. Advanced SLA Escalation.
5. Workflow Analytics.
6. Advanced Reporting.
7. Email Integration.
8. Export Reports.
9. Workflow Versioning.
10. Process Performance Comparison.

## 3.3 Future Development

1. AI-Assisted Request Classification.
2. AI Document Information Extraction.
3. AI Workflow Recommendation.
4. Natural Language Workflow Configuration.
5. Process Bottleneck Detection.
6. Integration with ERP Systems.
7. External E-Signature Integration.

AI hanya bertindak sebagai pendukung. Keputusan persetujuan tetap dilakukan oleh pengguna yang memiliki otoritas.

## 3.4 Out of Scope

Untuk MVP, sistem tidak bertujuan menjadi:

- Full ERP System.
- Payroll Management System.
- Accounting System.
- Full HRIS.
- Electronic Signature Provider.
- Enterprise Integration Platform.
- AI Autonomous Approval System.

---

# 4. FUNCTIONAL REQUIREMENTS

## FR-01 Authentication

Sistem harus menyediakan mekanisme autentikasi pengguna.

Requirements:
- Login menggunakan email dan password.
- Logout.
- Password hashing.
- Session management.
- Password reset.
- Account activation/deactivation.
- Proteksi halaman berdasarkan autentikasi.

Acceptance Criteria:
- Pengguna dengan kredensial valid dapat login.
- Pengguna dengan kredensial tidak valid tidak dapat mengakses sistem.
- Pengguna nonaktif tidak dapat login.
- Halaman internal tidak dapat diakses tanpa autentikasi.

## FR-02 User Management

Administrator dapat:
- Membuat pengguna.
- Mengubah informasi pengguna.
- Mengaktifkan dan menonaktifkan pengguna.
- Menentukan departemen.
- Menentukan role.
- Melihat daftar pengguna.

Acceptance Criteria:
- Email pengguna harus unik.
- Setiap pengguna memiliki departemen dan role sesuai konfigurasi.
- Pengguna nonaktif tidak menerima tugas baru.

## FR-03 Request Management

Karyawan dapat membuat pengajuan administrasi.

Data pengajuan:
- Request Number.
- Request Type.
- Request Title.
- Description.
- Requester.
- Department.
- Submission Date.
- Priority.
- Attachments.
- Current Status.

Acceptance Criteria:
- Request Number dibuat otomatis.
- Pengajuan harus memenuhi validasi formulir.
- Pengajuan draft dapat diedit oleh pemiliknya.
- Pengajuan yang sudah dikirim tidak dapat diubah secara langsung.
- Perubahan setelah submission harus melalui mekanisme revision.

## FR-04 Dynamic Request Forms

Administrator dapat membuat template formulir berdasarkan jenis pengajuan.

Jenis field yang didukung:
- Text.
- Textarea.
- Number.
- Date.
- Dropdown.
- Radio.
- Checkbox.
- File Upload.

Business Rules:
- Setiap template memiliki nama dan identitas unik.
- Field dapat ditentukan sebagai required atau optional.
- Setiap field memiliki konfigurasi validasi.
- Template yang sudah digunakan tidak boleh mengubah struktur pengajuan historis secara retroaktif.

Acceptance Criteria:
- Pemohon melihat formulir sesuai jenis pengajuan.
- Field required harus diisi.
- Data disimpan dengan referensi template dan versinya.

## FR-05 Workflow Template Management

Administrator dapat mendesain alur persetujuan.

Setiap workflow memiliki:
- Workflow Name.
- Description.
- Request Type.
- Workflow Version.
- Status.
- Approval Stages.
- Assigned Roles.
- SLA Configuration.

Contoh workflow reimbursement:

Stage 1: Department Manager  
Stage 2: Finance Manager  
Stage 3: Finance Processing

Acceptance Criteria:
- Workflow harus memiliki minimal satu tahap persetujuan.
- Setiap tahap harus memiliki approver yang valid.
- Workflow aktif dapat digunakan untuk pengajuan baru.
- Perubahan konfigurasi tidak mengubah workflow instance yang sedang berjalan.
- Workflow yang sedang digunakan tidak dapat dihapus secara permanen.

## FR-06 Multi-Level Approval

Sistem harus mendukung persetujuan berjenjang.

Action:
- Approve.
- Reject.
- Request Revision.

Business Rules:
- Approver hanya dapat memproses tugas yang ditugaskan kepadanya.
- Approve mengaktifkan tahap berikutnya.
- Reject menghentikan workflow.
- Request Revision mengembalikan pengajuan kepada pemohon.
- Setiap keputusan wajib memiliki timestamp dan identitas pengguna.
- Keputusan tidak boleh diproses dua kali.
- Pengajuan yang telah selesai tidak dapat menerima keputusan baru.

Acceptance Criteria:
- Persetujuan pertama mengaktifkan tahapan berikutnya.
- Persetujuan terakhir menyelesaikan workflow.
- Penolakan mengubah status pengajuan menjadi Rejected.
- Permintaan revisi mengubah status menjadi Revision Required.
- Semua keputusan tersimpan dalam approval history.

## FR-07 Approval Task Management

Approver memiliki halaman khusus untuk mengelola tugas.

Informasi:
- Request Number.
- Requester.
- Request Type.
- Submission Date.
- Priority.
- Current Stage.
- SLA Deadline.

Fitur:
- Pending Approvals.
- Completed Approvals.
- Rejected Requests.
- Request Detail.
- Approval History.

Acceptance Criteria:
- Approver hanya melihat tugas sesuai hak akses.
- Tugas yang sudah selesai tidak muncul sebagai pending.
- Daftar tugas dapat difilter dan dicari.

## FR-08 Document Management

Sistem menyediakan fasilitas unggah dan pengelolaan dokumen.

Requirements:
- Upload attachment.
- Download attachment.
- File validation.
- File size limitation.
- Secure file storage.
- Document metadata.

Security:
- Validasi MIME type.
- Pembatasan ukuran file.
- Nama file disimpan secara aman.
- Dokumen tidak boleh diakses tanpa otorisasi.
- Hindari mengeksekusi file yang diunggah pengguna.

## FR-09 Request Tracking

Pemohon dapat memantau status pengajuan.

Status utama:

- Draft.
- Submitted.
- Under Review.
- Revision Required.
- Approved.
- Rejected.
- Cancelled.

Setiap pengajuan menampilkan:
- Current Status.
- Current Approver.
- Approval Timeline.
- Submission Date.
- Last Updated.
- Approval History.

Acceptance Criteria:
- Perubahan status tercermin pada halaman tracking.
- Pemohon hanya dapat melihat pengajuan miliknya, kecuali memiliki permission tambahan.
- Timeline mencerminkan urutan aktivitas sebenarnya.

## FR-10 Notification Center

Sistem harus memberikan notifikasi ketika terjadi aktivitas penting.

Trigger:
- Pengajuan berhasil dikirim.
- Tugas persetujuan baru dibuat.
- Pengajuan disetujui.
- Pengajuan ditolak.
- Pengajuan membutuhkan revisi.
- Revisi telah dikirim.
- SLA mendekati batas waktu.
- Pengajuan selesai.

Notification Data:
- Recipient.
- Title.
- Message.
- Related Request.
- Notification Type.
- Read Status.
- Created At.

MVP menggunakan in-app notification. Integrasi email dapat dikembangkan secara bertahap.

## FR-11 SLA Monitoring

Sistem memantau waktu penyelesaian setiap tahapan.

Contoh:
- Department Manager: 24 jam kerja.
- Finance Manager: 48 jam kerja.
- Finance Processing: 72 jam kerja.

Business Rules:
- SLA dikonfigurasi per tahap.
- Perhitungan menggunakan timezone perusahaan.
- Pengajuan yang menunggu pemohon melakukan revisi tidak dihitung sebagai waktu pemrosesan internal.
- SLA breach harus dicatat.
- Reminder dapat dikirim sebelum deadline.

Untuk MVP, gunakan kalender kerja sederhana yang dapat dikonfigurasi. Dukungan hari libur nasional dan kalender kerja kompleks masuk pengembangan lanjutan.

## FR-12 Audit Trail

Sistem harus mencatat aktivitas penting.

Aktivitas:
- Login.
- Request Creation.
- Request Submission.
- Request Approval.
- Request Rejection.
- Revision Request.
- Workflow Configuration.
- User Management.
- Role Changes.

Audit Record:
- Actor.
- Action.
- Entity Type.
- Entity ID.
- Timestamp.
- Relevant Metadata.

Audit log bersifat append-only dari sisi aplikasi. Pengguna biasa tidak dapat mengubah atau menghapus riwayat audit.

## FR-13 Operational Dashboard

Dashboard disesuaikan dengan role pengguna.

Employee:
- My Requests.
- Pending Requests.
- Approved Requests.
- Revision Required.

Approver:
- Pending Approvals.
- Completed Approvals.
- Overdue Tasks.

Administrator:
- Total Requests.
- Active Workflows.
- Pending Approvals.
- Overdue Requests.
- Department Activity.

Management:
- Request Volume.
- Approval Performance.
- Average Processing Time.
- SLA Compliance.
- Workflow Bottlenecks.

Semua angka harus dihitung berdasarkan data aktual aplikasi, bukan angka statis.

## FR-14 Reporting

Sistem menyediakan laporan operasional.

Laporan MVP:
- Request Summary.
- Approval Summary.
- SLA Performance.
- Department Activity.
- Request Status Distribution.

Fitur:
- Date Filter.
- Department Filter.
- Request Type Filter.
- Status Filter.
- Export CSV.

---

# 5. BUSINESS RULES

## BR-01 Request Ownership

Pemohon hanya dapat mengelola pengajuannya sendiri, kecuali diberikan permission khusus.

## BR-02 Approval Authorization

Hanya pengguna yang ditugaskan sebagai approver pada tahap aktif yang dapat memberikan keputusan.

## BR-03 Sequential Approval

Workflow MVP menggunakan sequential approval.

Tahap berikutnya hanya dapat berjalan setelah tahap sebelumnya disetujui.

## BR-04 Rejection

Pengajuan yang ditolak harus menghentikan workflow dan menyimpan alasan penolakan.

## BR-05 Revision

Pengajuan yang membutuhkan revisi dikembalikan kepada pemohon.

Setelah pemohon mengirimkan revisi, proses dilanjutkan sesuai aturan workflow.

## BR-06 Workflow Versioning

Setiap workflow instance harus menyimpan referensi versi workflow ketika pengajuan dibuat.

Perubahan workflow hanya berlaku untuk pengajuan baru.

## BR-07 Approval History

Setiap keputusan harus disimpan sebagai record baru.

## BR-08 Duplicate Approval Prevention

Sistem harus mencegah keputusan ganda akibat double-click, request bersamaan, atau pengiriman ulang.

Gunakan database transaction dan locking ketika memproses keputusan.

## BR-09 SLA Calculation

SLA dihitung berdasarkan waktu aktif suatu approval task dengan aturan kalender kerja yang berlaku.

## BR-10 Data Isolation

Setiap pengguna hanya dapat mengakses informasi sesuai role, department, ownership, dan permission.

## BR-11 Cancellation

Pemohon dapat membatalkan pengajuan sebelum workflow selesai, sesuai aturan bisnis.

Pengajuan yang telah selesai tidak dapat dibatalkan melalui mekanisme biasa.

## BR-12 Workflow Integrity

Workflow aktif yang memiliki instance berjalan tidak boleh dimodifikasi secara retroaktif.

---

# 6. NON-FUNCTIONAL REQUIREMENTS

## NFR-01 Performance

- Halaman utama ditargetkan memiliki waktu respons di bawah 3 detik pada lingkungan demo normal.
- Query database harus menggunakan indexing yang sesuai.
- Pagination digunakan untuk daftar data besar.
- Proses notifikasi tidak boleh memperlambat transaksi utama.

Target ini perlu diverifikasi melalui pengujian.

## NFR-02 Security

- Password menggunakan hashing yang aman.
- Role-Based Access Control.
- Server-side validation.
- CSRF protection.
- Protection against SQL Injection.
- Secure file uploads.
- Session security.
- Rate limiting pada endpoint sensitif.
- Secure environment variables.
- Authorization checks pada setiap resource.

## NFR-03 Reliability

- Operasi approval harus menggunakan database transaction.
- Sistem harus mencegah inkonsistensi status workflow.
- Proses background harus memiliki mekanisme retry.
- Error harus dicatat tanpa membocorkan informasi sensitif.

## NFR-04 Maintainability

- Menggunakan modular architecture.
- Business logic dipisahkan dari presentation layer.
- Database migration terdokumentasi.
- Automated testing.
- API dan dokumentasi teknis diperbarui.

## NFR-05 Usability

- Antarmuka mudah dipahami pengguna nonteknis.
- Informasi status harus jelas.
- Approval action mudah ditemukan.
- Form memiliki validasi yang informatif.
- Responsive pada desktop, tablet, dan mobile.

## NFR-06 Auditability

Setiap aktivitas bisnis yang mengubah status atau konfigurasi harus dapat ditelusuri.

---

# 7. SYSTEM ARCHITECTURE

## 7.1 Recommended Technology Stack

Gunakan modular monolith agar pengembangan dan pemeliharaan lebih sederhana.

Backend:
- Laravel 12.
- PHP 8.3+.
- Laravel Authentication.
- Laravel Policies and Gates.

Frontend:
- Livewire 3.
- Blade.
- Tailwind CSS.
- Alpine.js.

Database:
- PostgreSQL.

Infrastructure:
- Redis untuk queue dan cache apabila diperlukan.
- Laravel Queue.
- Local private storage untuk pengembangan.
- SMTP-compatible mail service untuk integrasi email.

Testing:
- Pest atau PHPUnit.
- Laravel Feature Tests.
- Laravel Unit Tests.
- Browser testing bila diperlukan.

Development Tools:
- Git.
- Composer.
- NPM.
- Docker Compose sebagai opsi lingkungan pengembangan.

Pilih versi dependency yang kompatibel dan masih didukung saat implementasi. Jangan mengasumsikan semua package kompatibel tanpa verifikasi.

## 7.2 Architecture Overview

Presentation Layer:
- Dashboard.
- Request Management.
- Approval Management.
- Workflow Designer.
- Reporting.

Application Layer:
- Request Service.
- Workflow Engine.
- Approval Service.
- Notification Service.
- SLA Service.
- Reporting Service.

Domain Layer:
- Request Rules.
- Approval Rules.
- Workflow Rules.
- Authorization Rules.
- SLA Rules.

Infrastructure Layer:
- PostgreSQL.
- File Storage.
- Queue.
- Notification Delivery.
- Logging.

## 7.3 Core Workflow Engine

Workflow Engine merupakan komponen utama sistem.

Tanggung jawab:
1. Membaca workflow template.
2. Membuat workflow instance.
3. Membuat approval tasks.
4. Menentukan approver.
5. Memvalidasi keputusan.
6. Mengaktifkan tahap berikutnya.
7. Mengelola revisi.
8. Menghentikan workflow jika ditolak.
9. Menyelesaikan workflow jika semua tahapan disetujui.
10. Mencatat seluruh aktivitas.

Pisahkan Workflow Engine dari controller agar business logic dapat diuji secara independen.

---

# 8. DATABASE DESIGN

## 8.1 Core Entities

### users

- id
- name
- email
- password
- department_id
- is_active
- created_at
- updated_at

### departments

- id
- name
- description
- created_at
- updated_at

### roles

- id
- name
- description

### permissions

- id
- name
- description

### role_user

- user_id
- role_id

### permission_role

- role_id
- permission_id

### request_types

- id
- name
- description
- is_active

### form_templates

- id
- request_type_id
- version
- name
- schema
- is_active
- created_at

### workflow_templates

- id
- request_type_id
- name
- description
- version
- is_active
- created_by
- created_at

### workflow_stages

- id
- workflow_template_id
- stage_order
- stage_name
- approver_role_id
- sla_hours
- is_active

### requests

- id
- request_number
- request_type_id
- form_template_id
- requester_id
- department_id
- title
- description
- form_data
- priority
- status
- submitted_at
- completed_at
- created_at
- updated_at

### workflow_instances

- id
- request_id
- workflow_template_id
- workflow_version
- current_stage_order
- status
- started_at
- completed_at

### approval_tasks

- id
- workflow_instance_id
- workflow_stage_id
- assigned_approver_id
- status
- due_at
- completed_at
- created_at

### approval_histories

- id
- approval_task_id
- actor_id
- action
- comment
- created_at

### request_attachments

- id
- request_id
- uploaded_by
- original_filename
- stored_filename
- mime_type
- file_size
- storage_path
- created_at

### notifications

- id
- user_id
- request_id
- title
- message
- type
- read_at
- created_at

### audit_logs

- id
- actor_id
- action
- entity_type
- entity_id
- metadata
- ip_address
- created_at

### workflow_events

- id
- workflow_instance_id
- event_type
- payload
- occurred_at

## 8.2 Database Design Principles

- Gunakan foreign key untuk menjaga integritas relasi.
- Gunakan unique constraint untuk request_number.
- Gunakan index pada status, department_id, requester_id, dan due_at sesuai kebutuhan query.
- Gunakan JSONB untuk form_data dengan validasi schema di application layer.
- Gunakan transaksi database untuk perubahan status workflow.
- Gunakan soft delete hanya untuk entitas yang memang memerlukannya.
- Jangan menghapus histori approval atau audit secara permanen melalui aplikasi.
- Simpan versi template dan workflow agar histori tetap konsisten.

## 8.3 Important Relationships

- Department memiliki banyak users.
- User dapat memiliki beberapa roles.
- Role memiliki banyak permissions.
- Request Type memiliki beberapa Form Templates.
- Request Type memiliki beberapa Workflow Templates.
- Workflow Template memiliki banyak Workflow Stages.
- Request memiliki satu Workflow Instance.
- Workflow Instance memiliki banyak Approval Tasks.
- Approval Task memiliki banyak Approval Histories.
- Request memiliki banyak Attachments.
- User memiliki banyak Notifications.

---

# 9. CORE APPLICATION WORKFLOWS

## 9.1 Employee Submission Workflow

1. Login.
2. Open Request Center.
3. Select Request Type.
4. Complete Dynamic Form.
5. Upload Supporting Documents.
6. Save Draft or Submit.
7. System Validates Request.
8. System Generates Request Number.
9. System Creates Workflow Instance.
10. System Creates First Approval Task.
11. Notification Is Sent.

Expected Result:

Pengajuan tersimpan dan masuk ke proses persetujuan.

## 9.2 Approval Workflow

1. Approver opens Approval Inbox.
2. Selects pending task.
3. Reviews request information.
4. Reviews attachments.
5. Selects an action.
6. System validates authorization and task status.
7. System processes the decision transactionally.
8. System records approval history.
9. System activates the next stage or completes the workflow.
10. System sends notifications.

## 9.3 Revision Workflow

1. Approver selects Request Revision.
2. Approver enters revision notes.
3. System records the decision.
4. Request status becomes Revision Required.
5. Requester receives notification.
6. Requester updates permitted fields and attachments.
7. Requester resubmits.
8. System reactivates the appropriate approval process.
9. New approval activity is recorded.

## 9.4 Rejection Workflow

1. Approver selects Reject.
2. Approver provides rejection reason.
3. System validates authorization.
4. System records rejection.
5. Workflow becomes Rejected.
6. Remaining pending tasks are cancelled.
7. Requester receives notification.

---

# 10. UI/UX DESIGN SPECIFICATION

## 10.1 Design Direction

Design concept: Modern Enterprise Workflow Workspace.

The interface should feel like a professional enterprise application, not a generic administration template.

Visual characteristics:
- Clean and structured.
- Professional typography.
- Clear information hierarchy.
- Consistent spacing.
- Subtle borders.
- Restrained shadows.
- Clear status indicators.
- Responsive layouts.

Use a neutral foundation with a restrained blue accent for primary actions.

## 10.2 Main Navigation

Sidebar:

- Overview
- My Requests
- Request Center
- Approval Inbox
- Workflow Management
- Reports
- Notifications
- Administration
- Settings

Navigation visibility depends on user permissions.

## 10.3 Main Pages

### A. Overview Dashboard

Purpose:
Memberikan ringkasan pekerjaan berdasarkan role.

Components:
- Summary Cards.
- Pending Tasks.
- Recent Activities.
- Request Status Chart.
- SLA Overview.
- Quick Actions.

### B. Request Center

Purpose:
Tempat membuat pengajuan administratif.

Components:
- Request Type Selection.
- Dynamic Form.
- Attachment Upload.
- Validation Messages.
- Save Draft.
- Submit Request.

### C. My Requests

Purpose:
Memantau seluruh pengajuan pengguna.

Components:
- Request Table.
- Search.
- Status Filter.
- Date Filter.
- Request Detail.
- Workflow Timeline.

### D. Approval Inbox

Purpose:
Menyediakan pusat pengelolaan tugas persetujuan.

Components:
- Pending Approval Table.
- Priority Indicator.
- SLA Deadline.
- Request Detail Panel.
- Document Preview.
- Approval Actions.

### E. Workflow Management

Purpose:
Mengatur alur persetujuan perusahaan.

Components:
- Workflow List.
- Workflow Details.
- Workflow Stages.
- Approver Configuration.
- SLA Configuration.
- Workflow Activation.

### F. Reporting

Purpose:
Memberikan informasi operasional kepada manajemen.

Components:
- Request Volume.
- Approval Performance.
- Average Processing Time.
- SLA Compliance.
- Department Performance.
- Date Filters.
- CSV Export.

### G. Administration

Purpose:
Mengelola konfigurasi organisasi.

Components:
- User Management.
- Role Management.
- Permission Management.
- Department Management.
- Request Type Management.
- Audit Logs.

## 10.4 UX Principles

- Setiap halaman harus memiliki tujuan yang jelas.
- Setiap tindakan penting harus memiliki feedback.
- Status pengajuan harus mudah dipahami.
- Approval action harus memerlukan konfirmasi.
- Rejection wajib memiliki alasan.
- Revision wajib memiliki instruksi yang jelas.
- Form harus menampilkan validasi dekat dengan field terkait.
- Empty states harus memberikan arahan tindakan.
- Loading states dan error states harus dirancang.
- Gunakan responsive design untuk mobile.

---

# 11. API AND APPLICATION CONTRACTS

MVP dapat menggunakan Laravel routes, controllers, Livewire actions, dan application services. Pisahkan kontrak bisnis dari implementasi UI.

Contoh endpoint jika REST API dibutuhkan:

Authentication:
- POST /api/login
- POST /api/logout
- GET /api/me

Requests:
- GET /api/requests
- POST /api/requests
- GET /api/requests/{id}
- PUT /api/requests/{id}
- POST /api/requests/{id}/submit
- POST /api/requests/{id}/cancel

Approvals:
- GET /api/approvals
- GET /api/approvals/{id}
- POST /api/approvals/{id}/approve
- POST /api/approvals/{id}/reject
- POST /api/approvals/{id}/request-revision

Workflows:
- GET /api/workflows
- POST /api/workflows
- GET /api/workflows/{id}
- PUT /api/workflows/{id}
- POST /api/workflows/{id}/activate

Notifications:
- GET /api/notifications
- POST /api/notifications/{id}/read

Reports:
- GET /api/reports/requests
- GET /api/reports/approvals
- GET /api/reports/sla

Endpoint di atas merupakan kontrak rancangan. Jika implementasi menggunakan Livewire tanpa REST API publik, pertahankan operasi bisnis dan authorization yang setara melalui application services.

---

# 12. IMPLEMENTATION ROADMAP

Implementasikan secara bertahap. Jangan membangun seluruh fitur sekaligus.

## Phase 0 — Repository Inspection and Planning

Tasks:
- Read PROJECT.md.
- Inspect existing repository.
- Identify existing dependencies.
- Identify existing architecture.
- Inspect database structure.
- Identify reusable components.
- Create implementation plan.

Deliverables:
- Repository Assessment.
- Technical Implementation Plan.
- Dependency Analysis.

## Phase 1 — Foundation

Tasks:
- Initialize Laravel project.
- Configure PostgreSQL.
- Configure environment variables.
- Configure frontend assets.
- Implement authentication.
- Implement role and permission system.
- Implement department management.
- Create initial database migrations.

Deliverables:
- Working application foundation.
- Authentication.
- User management.
- RBAC.

## Phase 2 — Request Management

Tasks:
- Implement request types.
- Implement form templates.
- Implement dynamic form rendering.
- Implement request submission.
- Implement draft management.
- Implement attachment handling.
- Implement request tracking.

Deliverables:
- Functional Request Center.
- Functional My Requests.
- Request detail and tracking.

## Phase 3 — Workflow Engine

Tasks:
- Implement workflow templates.
- Implement workflow stages.
- Implement workflow versioning.
- Implement workflow instances.
- Implement approval task generation.
- Implement sequential approval.
- Implement rejection.
- Implement revision.
- Implement transactional processing.

Deliverables:
- Functional Workflow Engine.
- Multi-level approval.
- Approval history.

This is the most important technical milestone.

## Phase 4 — Notifications and SLA

Tasks:
- Implement notification center.
- Implement notification triggers.
- Implement queue processing.
- Implement SLA calculation.
- Implement reminders.
- Implement overdue indicators.

Deliverables:
- Notification system.
- SLA monitoring.

## Phase 5 — Dashboard and Reporting

Tasks:
- Implement role-based dashboards.
- Implement request analytics.
- Implement approval analytics.
- Implement SLA reports.
- Implement filtering.
- Implement CSV export.

Deliverables:
- Management Dashboard.
- Operational Reports.

## Phase 6 — Security and Quality Assurance

Tasks:
- Review authorization.
- Review file upload security.
- Test workflow integrity.
- Test concurrent approvals.
- Test invalid state transitions.
- Test notification delivery.
- Test SLA calculations.
- Run automated tests.
- Fix discovered defects.

Deliverables:
- Test reports.
- Security review.
- Acceptance test results.

## Phase 7 — Documentation and Demonstration

Tasks:
- Update README.
- Document installation.
- Document database architecture.
- Document business workflows.
- Document user roles.
- Prepare demo data.
- Prepare demonstration scenarios.
- Document known limitations.

Deliverables:
- GitHub-ready repository.
- Technical documentation.
- Business documentation.
- Demo environment instructions.

---

# 13. TESTING STRATEGY

## 13.1 Unit Testing

Test individual business components:

- Request Number Generator.
- Workflow Stage Resolver.
- Approval Decision Handler.
- SLA Calculator.
- Notification Trigger.
- Permission Resolver.

## 13.2 Feature Testing

Test complete application features:

- Authentication.
- User Management.
- Request Submission.
- Dynamic Form Validation.
- Attachment Upload.
- Workflow Creation.
- Approval Processing.
- Revision Processing.
- Rejection Processing.
- Request Tracking.
- Notification Delivery.
- Reporting.

## 13.3 Integration Testing

Test interactions between:
- Request Service and Workflow Engine.
- Workflow Engine and Approval Service.
- Approval Service and Notification Service.
- SLA Service and Approval Tasks.
- Application and PostgreSQL.
- Application and Queue.

## 13.4 Critical Test Scenarios

| ID | Scenario | Expected Result |
|---|---|---|
| TC-01 | Employee submits valid request | Request created |
| TC-02 | Employee submits incomplete form | Validation error |
| TC-03 | Employee accesses another user's request | Access denied |
| TC-04 | Approver approves assigned task | Next stage activated |
| TC-05 | Unauthorized user approves task | Access denied |
| TC-06 | Approver rejects request | Workflow rejected |
| TC-07 | Approver requests revision | Request returned |
| TC-08 | Employee resubmits revision | Workflow resumes |
| TC-09 | Final approver approves | Workflow completed |
| TC-10 | Same task approved twice | Duplicate action prevented |
| TC-11 | Workflow configuration changes | Existing instance unaffected |
| TC-12 | SLA deadline exceeded | Overdue status recorded |
| TC-13 | Unauthorized attachment access | Access denied |
| TC-14 | Notification triggered | Correct recipient notified |
| TC-15 | User accesses restricted report | Access denied |
| TC-16 | User changes role | Permission changes applied |
| TC-17 | Workflow has no valid approver | Submission blocked or safely failed |
| TC-18 | Approval processing encounters an error | Transaction rolled back |
| TC-19 | Cancelled request receives approval attempt | Action rejected |
| TC-20 | Audit log inspected | Historical activity preserved |

## 13.5 Acceptance Criteria

MVP dapat dinyatakan selesai jika:

1. Pengguna dapat login sesuai role.
2. Administrator dapat mengelola pengguna.
3. Karyawan dapat membuat pengajuan.
4. Pengajuan dapat menggunakan dynamic form.
5. Administrator dapat membuat workflow.
6. Sistem dapat menjalankan persetujuan berjenjang.
7. Sistem dapat menangani rejection.
8. Sistem dapat menangani revision.
9. Sistem dapat mengirimkan notifikasi.
10. Pemohon dapat memantau status.
11. Approver dapat memantau tugas.
12. Sistem dapat mencatat audit trail.
13. SLA dapat dihitung dan dipantau.
14. Dashboard menampilkan data aktual.
15. Laporan dapat difilter dan diekspor.
16. Authorization dan security test berjalan.
17. Automated test suite lulus.
18. README dan dokumentasi teknis tersedia.

Jangan menyatakan acceptance criteria terpenuhi sebelum benar-benar diuji.

---

# 14. DEMONSTRATION SCENARIO

Gunakan skenario berikut untuk menunjukkan kemampuan sistem kepada recruiter.

## Scenario: Employee Reimbursement

Aktor:
- Ferdinand — Employee.
- Andi — Department Manager.
- Budi — Finance Manager.
- Sari — Finance Administrator.

Skenario:

1. Ferdinand login.
2. Ferdinand memilih Reimbursement Request.
3. Ferdinand mengisi formulir.
4. Ferdinand mengunggah dokumen pendukung.
5. Ferdinand mengirim pengajuan.
6. Sistem membuat Request Number.
7. Andi menerima tugas persetujuan.
8. Andi memeriksa dokumen dan menyetujui pengajuan.
9. Budi menerima tugas berikutnya.
10. Budi menyetujui pengajuan.
11. Sari menerima tugas pemrosesan administratif.
12. Sari menyelesaikan pemrosesan.
13. Sistem mengubah status menjadi Approved.
14. Ferdinand menerima notifikasi.
15. Manajemen dapat melihat aktivitas tersebut melalui dashboard.

Tambahkan skenario alternatif:

- Pengajuan membutuhkan revisi.
- Pengajuan ditolak.
- Approval melewati SLA.
- Approver tidak memiliki permission.
- Workflow mengalami perubahan versi.

Seluruh data demonstrasi harus menggunakan identitas dan transaksi fiktif.

---

# 15. DOCUMENTATION STRUCTURE

Struktur repository yang diharapkan:

```text
business-process-automation-system/
│
├── app/
│   ├── Models/
│   ├── Services/
│   │   ├── RequestService.php
│   │   ├── WorkflowEngine.php
│   │   ├── ApprovalService.php
│   │   ├── NotificationService.php
│   │   └── SLAService.php
│   ├── Policies/
│   ├── Jobs/
│   ├── Events/
│   └── Listeners/
│
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
│
├── resources/
│   ├── views/
│   ├── css/
│   └── js/
│
├── routes/
│
├── tests/
│   ├── Unit/
│   ├── Feature/
│   └── Integration/
│
├── docs/
│   ├── BUSINESS_ANALYSIS.md
│   ├── SYSTEM_ARCHITECTURE.md
│   ├── DATABASE_DESIGN.md
│   ├── WORKFLOW_SPECIFICATION.md
│   ├── TESTING_REPORT.md
│   ├── USER_GUIDE.md
│   └── CHANGELOG.md
│
├── public/
├── storage/
├── .env.example
├── README.md
└── PROJECT.md
```

Struktur tersebut merupakan target awal. Sesuaikan dengan struktur Laravel aktual setelah repository diperiksa. Jangan memaksakan nama atau lokasi file jika bertentangan dengan konvensi framework.

---

# 16. GITHUB README REQUIREMENTS

README.md harus memuat:

1. Project Introduction.
2. Business Problem.
3. Project Objectives.
4. Key Features.
5. Business Workflow.
6. User Roles.
7. Technology Stack.
8. System Architecture.
9. Database Overview.
10. Installation Guide.
11. Environment Configuration.
12. Database Migration.
13. Demo Accounts.
14. Testing Instructions.
15. Screenshots.
16. Known Limitations.
17. Future Development.

README harus menggunakan bahasa Inggris profesional.

Jangan mengklaim integrasi, performa, atau fitur yang belum benar-benar diimplementasikan.

---

# 17. OPENCode IMPLEMENTATION INSTRUCTIONS

OpenCode harus menggunakan PROJECT.md ini sebagai Single Source of Truth.

## 17.1 Initial Instructions

Sebelum mengubah repository:

1. Baca seluruh PROJECT.md.
2. Inspeksi seluruh struktur repository.
3. Identifikasi framework dan dependency yang sudah tersedia.
4. Identifikasi file yang dapat digunakan kembali.
5. Evaluasi apakah implementasi sesuai dengan spesifikasi.
6. Buat ringkasan kondisi repository.
7. Buat staged implementation plan.
8. Identifikasi risiko teknis.
9. Identifikasi kebutuhan konfigurasi.
10. Tunggu tidak perlu meminta konfirmasi untuk setiap tahap yang sudah jelas dan aman dikerjakan.

Jangan langsung membuat file sebelum memahami repository.

## 17.2 Implementation Principles

- Prioritaskan workflow yang benar-benar berfungsi.
- Jangan membuat dashboard statis dengan angka palsu.
- Jangan membuat tombol yang tidak memiliki fungsi.
- Jangan menyimpan business logic utama di UI.
- Jangan membuat approval flow hanya berupa perubahan status manual.
- Gunakan database transaction untuk operasi penting.
- Terapkan authorization pada server-side.
- Gunakan validasi backend.
- Pisahkan workflow engine dari controller.
- Implementasikan automated tests.
- Gunakan fictional demo data.
- Jangan memasukkan credentials ke repository.

## 17.3 Design Quality

Gunakan skill berikut apabila tersedia:

- Taste: menjaga kualitas visual dan konsistensi produk.
- Impeccable: meningkatkan kualitas UI/UX dan design system.
- Superpowers: membantu proses implementasi secara bertahap, pengujian, dan verifikasi.

Jangan mengorbankan fungsionalitas demi visual.

## 17.4 Development Execution

Implementasikan berdasarkan urutan roadmap.

Pada setiap phase:

1. Jelaskan tujuan.
2. Jelaskan perubahan yang akan dilakukan.
3. Implementasikan fitur.
4. Jalankan test.
5. Perbaiki kegagalan.
6. Verifikasi acceptance criteria.
7. Perbarui dokumentasi.
8. Laporkan hasil aktual.

Jangan berhenti setelah membuat implementation plan.

Jika terjadi blocker, jelaskan:
- Penyebab.
- Dampak.
- Alternatif.
- Keputusan teknis yang diambil.

## 17.5 Final Reporting

Setelah implementasi, berikan laporan:

**Project Status**
- Completed.
- Partially Completed.
- Not Implemented.

**Implemented Features**
- Daftar fitur yang benar-benar berfungsi.

**Architecture**
- Framework.
- Database.
- Backend.
- Frontend.
- Integrations.

**Testing**
- Test command.
- Actual test results.
- Failed tests.
- Remaining issues.

**Documentation**
- Dokumentasi yang dibuat atau diperbarui.

**Limitations**
- Fitur yang belum tersedia.
- Integrasi yang masih disimulasikan.
- Technical debt.

**Next Steps**
- Prioritas pengembangan selanjutnya.

Jangan pernah mengarang hasil pengujian atau mengklaim deployment berhasil tanpa bukti.

---

# 18. PROJECT SUCCESS DEFINITION

Business Process Automation System dianggap berhasil sebagai portfolio project apabila mampu menunjukkan kemampuan dalam:

### Business Analysis
- Mengidentifikasi permasalahan bisnis.
- Melakukan root cause analysis.
- Memetakan AS-IS dan TO-BE.
- Mengidentifikasi stakeholder.
- Menyusun business requirements.
- Mendefinisikan business rules.

### System Analysis
- Mengubah kebutuhan bisnis menjadi functional requirements.
- Mendesain workflow.
- Mendesain database.
- Menentukan system architecture.
- Menyusun acceptance criteria.
- Melakukan requirements traceability.

### Product Thinking
- Mendesain pengalaman pengguna.
- Menentukan MVP.
- Mengelola prioritas fitur.
- Mempertimbangkan kebutuhan stakeholder.
- Mengevaluasi efektivitas proses.

### Technical Understanding
- Memahami backend dan frontend.
- Memahami database relationships.
- Memahami authorization.
- Memahami workflow automation.
- Memahami API dan integrations.
- Memahami testing dan software quality.

### AI and Automation Readiness
- Memahami peluang penerapan AI dalam proses administrasi.
- Membedakan deterministic workflow dengan AI-assisted decisions.
- Memahami document extraction dan request classification.
- Memastikan keputusan penting tetap memiliki human oversight.

---

# 19. FINAL PROJECT STATEMENT

Business Process Automation System merupakan platform enterprise workflow automation yang dirancang untuk mendigitalisasi proses administrasi dan persetujuan perusahaan.

Melalui sistem ini, perusahaan dapat mengelola pengajuan administratif, mengatur workflow persetujuan berjenjang, memantau SLA, mengirimkan notifikasi, dan mengevaluasi performa operasional melalui satu platform terpusat.

Project ini bertujuan menunjukkan kemampuan dalam Business Analysis, System Analysis, Business Process Management, Workflow Design, Product Thinking, dan implementasi aplikasi enterprise.

**Primary Focus:** Business Process Automation.

**Core Value:** Transforming manual administrative processes into structured, transparent, and automated digital workflows.

**End of Specification.**
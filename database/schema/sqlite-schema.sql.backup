CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "jenis_tindakan"(
  "id" integer primary key autoincrement not null,
  "kode" varchar not null,
  "nama" varchar not null,
  "deskripsi" text,
  "tarif" numeric not null,
  "jasa_dokter" numeric not null default '0',
  "jasa_paramedis" numeric not null default '0',
  "jasa_non_paramedis" numeric not null default '0',
  "kategori" varchar check("kategori" in('konsultasi', 'pemeriksaan', 'tindakan', 'obat', 'lainnya')) not null,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "jenis_tindakan_kode_index" on "jenis_tindakan"("kode");
CREATE INDEX "jenis_tindakan_nama_index" on "jenis_tindakan"("nama");
CREATE INDEX "jenis_tindakan_kategori_index" on "jenis_tindakan"("kategori");
CREATE UNIQUE INDEX "jenis_tindakan_kode_unique" on "jenis_tindakan"("kode");
CREATE TABLE IF NOT EXISTS "pasien"(
  "id" integer primary key autoincrement not null,
  "no_rekam_medis" varchar not null,
  "nama" varchar not null,
  "tanggal_lahir" date not null,
  "jenis_kelamin" varchar check("jenis_kelamin" in('L', 'P')) not null,
  "alamat" text,
  "no_telepon" varchar,
  "email" varchar,
  "pekerjaan" varchar,
  "status_pernikahan" varchar check("status_pernikahan" in('belum_menikah', 'menikah', 'janda', 'duda')),
  "kontak_darurat_nama" varchar,
  "kontak_darurat_telepon" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime
);
CREATE INDEX "pasien_no_rekam_medis_index" on "pasien"("no_rekam_medis");
CREATE INDEX "pasien_nama_index" on "pasien"("nama");
CREATE INDEX "pasien_tanggal_lahir_index" on "pasien"("tanggal_lahir");
CREATE UNIQUE INDEX "pasien_no_rekam_medis_unique" on "pasien"(
  "no_rekam_medis"
);
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "display_name" varchar not null,
  "description" text,
  "permissions" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "guard_name" varchar not null default 'web'
);
CREATE INDEX "roles_name_index" on "roles"("name");
CREATE UNIQUE INDEX "roles_name_unique" on "roles"("name");
CREATE TABLE IF NOT EXISTS "shifts"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "start_time" time not null,
  "end_time" time not null,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "shifts_name_index" on "shifts"("name");
CREATE TABLE IF NOT EXISTS "uang_duduk"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "tanggal" date not null,
  "shift_id" integer not null,
  "nominal" numeric not null,
  "keterangan" text,
  "input_by" integer not null,
  "status_validasi" varchar check("status_validasi" in('pending', 'disetujui', 'ditolak')) not null default 'pending',
  "validasi_by" integer,
  "validasi_at" datetime,
  "catatan_validasi" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("shift_id") references "shifts"("id") on delete cascade,
  foreign key("input_by") references "users"("id") on delete cascade,
  foreign key("validasi_by") references "users"("id") on delete set null
);
CREATE INDEX "uang_duduk_tanggal_index" on "uang_duduk"("tanggal");
CREATE INDEX "uang_duduk_status_validasi_index" on "uang_duduk"(
  "status_validasi"
);
CREATE INDEX "uang_duduk_user_id_tanggal_index" on "uang_duduk"(
  "user_id",
  "tanggal"
);
CREATE INDEX "uang_duduk_shift_id_tanggal_index" on "uang_duduk"(
  "shift_id",
  "tanggal"
);
CREATE TABLE IF NOT EXISTS "permissions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "permissions_name_guard_name_unique" on "permissions"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "model_has_permissions"(
  "permission_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  primary key("permission_id", "model_id", "model_type")
);
CREATE INDEX "model_has_permissions_model_id_model_type_index" on "model_has_permissions"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "model_has_roles"(
  "role_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("role_id", "model_id", "model_type")
);
CREATE INDEX "model_has_roles_model_id_model_type_index" on "model_has_roles"(
  "model_id",
  "model_type"
);
CREATE TABLE IF NOT EXISTS "role_has_permissions"(
  "permission_id" integer not null,
  "role_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("permission_id", "role_id")
);
CREATE TABLE IF NOT EXISTS "audit_logs"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "action" varchar not null,
  "model_type" varchar,
  "model_id" integer,
  "old_values" text,
  "new_values" text,
  "ip_address" varchar,
  "user_agent" text,
  "url" varchar,
  "method" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "audit_logs_user_id_created_at_index" on "audit_logs"(
  "user_id",
  "created_at"
);
CREATE INDEX "audit_logs_action_created_at_index" on "audit_logs"(
  "action",
  "created_at"
);
CREATE INDEX "audit_logs_model_type_model_id_index" on "audit_logs"(
  "model_type",
  "model_id"
);
CREATE TABLE IF NOT EXISTS "notifications"(
  "id" varchar not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "data" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "notifications_notifiable_type_notifiable_id_index" on "notifications"(
  "notifiable_type",
  "notifiable_id"
);
CREATE TABLE IF NOT EXISTS "pendapatan"(
  "id" integer primary key autoincrement not null,
  "tanggal" date not null,
  "keterangan" text,
  "nominal" numeric,
  "kategori" varchar,
  "tindakan_id" integer,
  "input_by" integer not null,
  "status_validasi" varchar not null default('pending'),
  "validasi_by" integer,
  "validasi_at" datetime,
  "catatan_validasi" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "kode_pendapatan" varchar,
  "nama_pendapatan" varchar,
  "sumber_pendapatan" varchar,
  "is_aktif" tinyint(1) not null default '1',
  foreign key("validasi_by") references users("id") on delete set null on update no action,
  foreign key("input_by") references users("id") on delete cascade on update no action,
  foreign key("tindakan_id") references tindakan("id") on delete set null on update no action
);
CREATE INDEX "pendapatan_kategori_index" on "pendapatan"("kategori");
CREATE INDEX "pendapatan_status_validasi_index" on "pendapatan"(
  "status_validasi"
);
CREATE INDEX "pendapatan_tanggal_index" on "pendapatan"("tanggal");
CREATE INDEX "pendapatan_tanggal_kategori_index" on "pendapatan"(
  "tanggal",
  "kategori"
);
CREATE TABLE IF NOT EXISTS "pengeluaran"(
  "id" integer primary key autoincrement not null,
  "tanggal" date not null,
  "keterangan" text,
  "nominal" numeric,
  "kategori" varchar,
  "bukti_transaksi" varchar,
  "input_by" integer not null,
  "status_validasi" varchar not null default('pending'),
  "validasi_by" integer,
  "validasi_at" datetime,
  "catatan_validasi" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "kode_pengeluaran" varchar,
  "nama_pengeluaran" varchar,
  foreign key("validasi_by") references users("id") on delete set null on update no action,
  foreign key("input_by") references users("id") on delete cascade on update no action
);
CREATE INDEX "pengeluaran_kategori_index" on "pengeluaran"("kategori");
CREATE INDEX "pengeluaran_status_validasi_index" on "pengeluaran"(
  "status_validasi"
);
CREATE INDEX "pengeluaran_tanggal_index" on "pengeluaran"("tanggal");
CREATE INDEX "pengeluaran_tanggal_kategori_index" on "pengeluaran"(
  "tanggal",
  "kategori"
);
CREATE TABLE IF NOT EXISTS "jenis_transaksis"(
  "id" integer primary key autoincrement not null,
  "nama" varchar not null,
  "kategori" varchar check("kategori" in('Pendapatan', 'Pengeluaran')) not null,
  "is_aktif" tinyint(1) not null default '1',
  "deskripsi" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "jenis_transaksis_kategori_is_aktif_index" on "jenis_transaksis"(
  "kategori",
  "is_aktif"
);
CREATE TABLE IF NOT EXISTS "personal_access_tokens"(
  "id" integer primary key autoincrement not null,
  "tokenable_type" varchar not null,
  "tokenable_id" integer not null,
  "name" text not null,
  "token" varchar not null,
  "abilities" text,
  "last_used_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens"(
  "tokenable_type",
  "tokenable_id"
);
CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens"(
  "token"
);
CREATE TABLE IF NOT EXISTS "attendances"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "date" date not null,
  "time_in" time not null,
  "time_out" time,
  "latlon_in" varchar not null,
  "latlon_out" varchar,
  "location_name_in" varchar,
  "location_name_out" varchar,
  "device_info" varchar,
  "photo_in" varchar,
  "photo_out" varchar,
  "notes" text,
  "status" varchar check("status" in('present', 'late', 'incomplete')) not null default 'present',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "device_id" varchar,
  "device_fingerprint" varchar,
  "latitude" numeric,
  "longitude" numeric,
  "accuracy" float,
  "checkout_latitude" numeric,
  "checkout_longitude" numeric,
  "checkout_accuracy" float,
  "location_validated" tinyint(1) not null default '0',
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "attendances_user_id_date_index" on "attendances"(
  "user_id",
  "date"
);
CREATE INDEX "attendances_date_index" on "attendances"("date");
CREATE INDEX "attendances_status_index" on "attendances"("status");
CREATE INDEX "attendances_user_id_device_id_index" on "attendances"(
  "user_id",
  "device_id"
);
CREATE INDEX "attendances_device_fingerprint_index" on "attendances"(
  "device_fingerprint"
);
CREATE TABLE IF NOT EXISTS "face_recognitions"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "face_encoding" varchar,
  "face_landmarks" text,
  "face_image_path" varchar,
  "confidence_score" numeric not null default '0',
  "encoding_algorithm" varchar not null default 'dlib',
  "is_active" tinyint(1) not null default '1',
  "is_verified" tinyint(1) not null default '0',
  "verified_at" datetime,
  "verified_by" integer,
  "metadata" text,
  "deleted_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("verified_by") references "users"("id")
);
CREATE INDEX "face_recognitions_user_id_index" on "face_recognitions"(
  "user_id"
);
CREATE INDEX "face_recognitions_user_id_is_active_index" on "face_recognitions"(
  "user_id",
  "is_active"
);
CREATE INDEX "face_recognitions_confidence_score_index" on "face_recognitions"(
  "confidence_score"
);
CREATE TABLE IF NOT EXISTS "absence_requests"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "absence_date" date not null,
  "absence_type" varchar check("absence_type" in('sick', 'personal', 'vacation', 'emergency', 'medical', 'family', 'other')) not null,
  "reason" text not null,
  "evidence_file" varchar,
  "evidence_metadata" text,
  "status" varchar check("status" in('pending', 'approved', 'rejected', 'cancelled')) not null default 'pending',
  "admin_notes" text,
  "submitted_at" datetime not null default CURRENT_TIMESTAMP,
  "reviewed_at" datetime,
  "reviewed_by" integer,
  "requires_medical_cert" tinyint(1) not null default '0',
  "is_half_day" tinyint(1) not null default '0',
  "half_day_start" time,
  "half_day_end" time,
  "deduction_amount" numeric not null default '0',
  "replacement_staff" text,
  "deleted_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id")
);
CREATE INDEX "absence_requests_user_id_index" on "absence_requests"("user_id");
CREATE INDEX "absence_requests_absence_date_index" on "absence_requests"(
  "absence_date"
);
CREATE INDEX "absence_requests_status_index" on "absence_requests"("status");
CREATE INDEX "absence_requests_user_id_absence_date_index" on "absence_requests"(
  "user_id",
  "absence_date"
);
CREATE TABLE IF NOT EXISTS "work_locations"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "address" varchar not null,
  "latitude" numeric not null,
  "longitude" numeric not null,
  "radius_meters" integer not null default '100',
  "is_active" tinyint(1) not null default '1',
  "location_type" varchar check("location_type" in('main_office', 'branch_office', 'project_site', 'mobile_location', 'client_office')) not null default 'main_office',
  "allowed_shifts" text,
  "working_hours" text,
  "tolerance_settings" text,
  "contact_person" varchar,
  "contact_phone" varchar,
  "require_photo" tinyint(1) not null default '1',
  "strict_geofence" tinyint(1) not null default '1',
  "gps_accuracy_required" integer not null default '20',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "work_locations_is_active_index" on "work_locations"("is_active");
CREATE INDEX "work_locations_location_type_index" on "work_locations"(
  "location_type"
);
CREATE INDEX "work_locations_latitude_longitude_index" on "work_locations"(
  "latitude",
  "longitude"
);
CREATE TABLE IF NOT EXISTS "gps_spoofing_detections"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "device_id" varchar,
  "ip_address" varchar not null,
  "user_agent" varchar,
  "latitude" numeric not null,
  "longitude" numeric not null,
  "accuracy" numeric,
  "altitude" numeric,
  "speed" numeric,
  "heading" numeric,
  "detection_results" text not null,
  "risk_level" varchar check("risk_level" in('low', 'medium', 'high', 'critical')) not null default 'low',
  "risk_score" integer not null default '0',
  "is_spoofed" tinyint(1) not null default '0',
  "is_blocked" tinyint(1) not null default '0',
  "mock_location_detected" tinyint(1) not null default '0',
  "fake_gps_app_detected" tinyint(1) not null default '0',
  "developer_mode_detected" tinyint(1) not null default '0',
  "impossible_travel_detected" tinyint(1) not null default '0',
  "coordinate_anomaly_detected" tinyint(1) not null default '0',
  "device_integrity_failed" tinyint(1) not null default '0',
  "spoofing_indicators" text,
  "detected_fake_apps" varchar,
  "travel_speed_kmh" numeric,
  "time_diff_seconds" integer,
  "distance_from_last_km" numeric,
  "action_taken" varchar check("action_taken" in('none', 'warning', 'blocked', 'flagged')) not null default 'none',
  "admin_notes" text,
  "reviewed_at" datetime,
  "reviewed_by" integer,
  "attendance_type" varchar,
  "attempted_at" datetime not null,
  "location_source" varchar,
  "device_fingerprint" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id")
);
CREATE INDEX "gps_spoofing_detections_user_id_attempted_at_index" on "gps_spoofing_detections"(
  "user_id",
  "attempted_at"
);
CREATE INDEX "gps_spoofing_detections_is_spoofed_risk_level_index" on "gps_spoofing_detections"(
  "is_spoofed",
  "risk_level"
);
CREATE INDEX "gps_spoofing_detections_ip_address_device_id_index" on "gps_spoofing_detections"(
  "ip_address",
  "device_id"
);
CREATE INDEX "gps_spoofing_detections_attempted_at_index" on "gps_spoofing_detections"(
  "attempted_at"
);
CREATE TABLE IF NOT EXISTS "gps_spoofing_settings"(
  "id" integer primary key autoincrement not null,
  "is_enabled" tinyint(1) not null default '1',
  "name" varchar not null default 'GPS Anti-Spoofing Configuration',
  "description" text,
  "mock_location_score" integer not null default '25',
  "fake_gps_app_score" integer not null default '30',
  "developer_mode_score" integer not null default '20',
  "impossible_travel_score" integer not null default '35',
  "coordinate_anomaly_score" integer not null default '15',
  "device_integrity_score" integer not null default '25',
  "low_risk_threshold" integer not null default '30',
  "medium_risk_threshold" integer not null default '60',
  "high_risk_threshold" integer not null default '80',
  "warning_threshold" integer not null default '50',
  "flagged_threshold" integer not null default '60',
  "blocked_threshold" integer not null default '80',
  "detect_mock_location" tinyint(1) not null default '1',
  "detect_fake_gps_apps" tinyint(1) not null default '1',
  "detect_developer_mode" tinyint(1) not null default '1',
  "detect_impossible_travel" tinyint(1) not null default '1',
  "detect_coordinate_anomaly" tinyint(1) not null default '1',
  "detect_device_integrity" tinyint(1) not null default '1',
  "max_travel_speed_kmh" numeric not null default '120',
  "min_time_between_locations" integer not null default '30',
  "accuracy_threshold" numeric not null default '1',
  "send_email_alerts" tinyint(1) not null default '1',
  "send_realtime_alerts" tinyint(1) not null default '1',
  "send_critical_only" tinyint(1) not null default '0',
  "notification_recipients" text,
  "auto_block_enabled" tinyint(1) not null default '1',
  "block_duration_hours" integer not null default '24',
  "require_admin_unblock" tinyint(1) not null default '1',
  "whitelisted_ips" text,
  "whitelisted_devices" text,
  "trusted_locations" text,
  "fake_gps_apps_database" text,
  "log_all_attempts" tinyint(1) not null default '1',
  "log_low_risk_only" tinyint(1) not null default '0',
  "retention_days" integer not null default '90',
  "created_by" integer,
  "updated_by" integer,
  "last_updated_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("created_by") references "users"("id"),
  foreign key("updated_by") references "users"("id")
);
CREATE UNIQUE INDEX "gps_spoofing_settings_id_unique" on "gps_spoofing_settings"(
  "id"
);
CREATE TABLE IF NOT EXISTS "employee_cards"(
  "id" integer primary key autoincrement not null,
  "pegawai_id" integer not null,
  "user_id" integer,
  "card_number" varchar not null,
  "card_type" varchar not null default 'standard',
  "design_template" varchar not null default 'default',
  "employee_name" varchar not null,
  "employee_id" varchar not null,
  "position" varchar not null,
  "department" varchar not null,
  "role_name" varchar,
  "join_date" date,
  "photo_path" varchar,
  "issued_date" date not null,
  "valid_until" date,
  "is_active" tinyint(1) not null default '1',
  "pdf_path" varchar,
  "image_path" varchar,
  "card_data" text,
  "created_by" integer not null,
  "updated_by" integer,
  "generated_at" datetime,
  "printed_at" datetime,
  "print_count" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("pegawai_id") references "pegawais"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null,
  foreign key("created_by") references "users"("id"),
  foreign key("updated_by") references "users"("id")
);
CREATE INDEX "employee_cards_pegawai_id_is_active_index" on "employee_cards"(
  "pegawai_id",
  "is_active"
);
CREATE INDEX "employee_cards_card_type_is_active_index" on "employee_cards"(
  "card_type",
  "is_active"
);
CREATE INDEX "employee_cards_issued_date_index" on "employee_cards"(
  "issued_date"
);
CREATE UNIQUE INDEX "employee_cards_card_number_unique" on "employee_cards"(
  "card_number"
);
CREATE TABLE IF NOT EXISTS "location_validations"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "latitude" numeric not null,
  "longitude" numeric not null,
  "accuracy" numeric,
  "work_zone_radius" integer not null,
  "is_within_zone" tinyint(1) not null default '0',
  "distance_from_zone" numeric,
  "validation_time" datetime not null,
  "attendance_type" varchar check("attendance_type" in('check_in', 'check_out')) not null,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "location_validations_user_id_validation_time_index" on "location_validations"(
  "user_id",
  "validation_time"
);
CREATE INDEX "location_validations_attendance_type_is_within_zone_index" on "location_validations"(
  "attendance_type",
  "is_within_zone"
);
CREATE INDEX "location_validations_validation_time_index" on "location_validations"(
  "validation_time"
);
CREATE INDEX "location_validations_is_within_zone_index" on "location_validations"(
  "is_within_zone"
);
CREATE TABLE IF NOT EXISTS "gps_spoofing_configs"(
  "id" integer primary key autoincrement not null,
  "config_name" varchar not null default 'default',
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "max_travel_speed_kmh" numeric not null default '120',
  "min_time_diff_seconds" integer not null default '300',
  "max_distance_km" numeric not null default '50',
  "min_gps_accuracy_meters" numeric not null default '50',
  "max_gps_accuracy_meters" numeric not null default '1000',
  "mock_location_weight" integer not null default '40',
  "fake_gps_app_weight" integer not null default '35',
  "developer_mode_weight" integer not null default '15',
  "impossible_travel_weight" integer not null default '50',
  "coordinate_anomaly_weight" integer not null default '25',
  "device_integrity_weight" integer not null default '30',
  "low_risk_threshold" integer not null default '20',
  "medium_risk_threshold" integer not null default '40',
  "high_risk_threshold" integer not null default '70',
  "critical_risk_threshold" integer not null default '85',
  "auto_block_critical" tinyint(1) not null default '1',
  "auto_block_high_risk" tinyint(1) not null default '0',
  "auto_flag_medium_risk" tinyint(1) not null default '1',
  "auto_warning_low_risk" tinyint(1) not null default '0',
  "enable_mock_location_detection" tinyint(1) not null default '1',
  "enable_fake_gps_detection" tinyint(1) not null default '1',
  "enable_developer_mode_detection" tinyint(1) not null default '1',
  "enable_impossible_travel_detection" tinyint(1) not null default '1',
  "enable_coordinate_anomaly_detection" tinyint(1) not null default '1',
  "enable_device_integrity_check" tinyint(1) not null default '1',
  "data_retention_days" integer not null default '90',
  "polling_interval_seconds" integer not null default '15',
  "enable_real_time_alerts" tinyint(1) not null default '1',
  "enable_email_notifications" tinyint(1) not null default '0',
  "notification_email" varchar,
  "whitelisted_ips" text,
  "whitelisted_devices" text,
  "trusted_locations" text,
  "max_failed_attempts_per_hour" integer not null default '5',
  "temporary_block_duration_minutes" integer not null default '30',
  "require_admin_review_for_unblock" tinyint(1) not null default '1',
  "created_by" integer,
  "updated_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "auto_register_devices" tinyint(1) not null default '1',
  "max_devices_per_user" integer not null default '1',
  "require_admin_approval_for_new_devices" tinyint(1) not null default '0',
  "device_limit_policy" varchar check("device_limit_policy" in('strict', 'warn', 'flexible')) not null default 'strict',
  "device_auto_cleanup_days" integer not null default '30',
  "auto_revoke_excess_devices" tinyint(1) not null default '1',
  foreign key("created_by") references "users"("id"),
  foreign key("updated_by") references "users"("id")
);
CREATE INDEX "gps_spoofing_configs_is_active_config_name_index" on "gps_spoofing_configs"(
  "is_active",
  "config_name"
);
CREATE INDEX "gps_spoofing_configs_created_by_index" on "gps_spoofing_configs"(
  "created_by"
);
CREATE INDEX "gps_spoofing_configs_updated_by_index" on "gps_spoofing_configs"(
  "updated_by"
);
CREATE TABLE IF NOT EXISTS "pendapatan_harians"(
  "id" integer primary key autoincrement not null,
  "tanggal_input" date not null,
  "shift" varchar not null,
  "pendapatan_id" integer not null,
  "nominal" numeric not null,
  "deskripsi" text,
  "user_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "status_validasi" varchar not null default 'pending',
  "validasi_by" integer,
  "validasi_at" datetime,
  "catatan_validasi" text,
  foreign key("pendapatan_id") references pendapatan("id") on delete cascade on update no action,
  foreign key("user_id") references users("id") on delete cascade on update no action,
  foreign key("validasi_by") references "users"("id") on delete set null
);
CREATE INDEX "pendapatan_harians_tanggal_input_shift_index" on "pendapatan_harians"(
  "tanggal_input",
  "shift"
);
CREATE INDEX "pendapatan_harians_user_id_tanggal_input_index" on "pendapatan_harians"(
  "user_id",
  "tanggal_input"
);
CREATE INDEX "pendapatan_harians_status_validasi_tanggal_input_index" on "pendapatan_harians"(
  "status_validasi",
  "tanggal_input"
);
CREATE TABLE IF NOT EXISTS "pengeluaran_harians"(
  "id" integer primary key autoincrement not null,
  "tanggal_input" date not null,
  "shift" varchar check("shift" in('Pagi', 'Sore')) not null,
  "pengeluaran_id" integer not null,
  "nominal" numeric not null,
  "deskripsi" text,
  "user_id" integer not null,
  "status_validasi" varchar not null default 'pending',
  "validasi_by" integer,
  "validasi_at" datetime,
  "catatan_validasi" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("pengeluaran_id") references "pengeluaran"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("validasi_by") references "users"("id") on delete set null
);
CREATE INDEX "pengeluaran_harians_status_validasi_tanggal_input_index" on "pengeluaran_harians"(
  "status_validasi",
  "tanggal_input"
);
CREATE INDEX "pengeluaran_harians_user_id_tanggal_input_index" on "pengeluaran_harians"(
  "user_id",
  "tanggal_input"
);
CREATE TABLE IF NOT EXISTS "kalender_kerjas"(
  "id" integer primary key autoincrement not null,
  "pegawai_id" integer not null,
  "tanggal" date not null,
  "shift" varchar check("shift" in('Pagi', 'Sore', 'Malam', 'Off')) not null,
  "unit" varchar,
  "keterangan" text,
  "created_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("pegawai_id") references "users"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete cascade
);
CREATE INDEX "kalender_kerjas_tanggal_pegawai_id_index" on "kalender_kerjas"(
  "tanggal",
  "pegawai_id"
);
CREATE INDEX "kalender_kerjas_tanggal_shift_index" on "kalender_kerjas"(
  "tanggal",
  "shift"
);
CREATE UNIQUE INDEX "kalender_kerjas_pegawai_id_tanggal_unique" on "kalender_kerjas"(
  "pegawai_id",
  "tanggal"
);
CREATE TABLE IF NOT EXISTS "cuti_pegawais"(
  "id" integer primary key autoincrement not null,
  "pegawai_id" integer not null,
  "tanggal_awal" date not null,
  "tanggal_akhir" date not null,
  "jumlah_hari" integer not null,
  "alasan" text not null,
  "status" varchar check("status" in('menunggu', 'disetujui', 'ditolak')) not null default 'menunggu',
  "komentar_admin" text,
  "approved_by" integer,
  "approved_at" datetime,
  "created_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("pegawai_id") references "users"("id") on delete cascade,
  foreign key("approved_by") references "users"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete cascade
);
CREATE INDEX "cuti_pegawais_pegawai_id_status_index" on "cuti_pegawais"(
  "pegawai_id",
  "status"
);
CREATE INDEX "cuti_pegawais_tanggal_awal_tanggal_akhir_index" on "cuti_pegawais"(
  "tanggal_awal",
  "tanggal_akhir"
);
CREATE INDEX "cuti_pegawais_status_index" on "cuti_pegawais"("status");
CREATE TABLE IF NOT EXISTS "dokter_presensis"(
  "id" integer primary key autoincrement not null,
  "dokter_id" integer not null,
  "tanggal" date not null,
  "jam_masuk" time,
  "jam_pulang" time,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("dokter_id") references "dokters"("id") on delete cascade
);
CREATE UNIQUE INDEX "dokter_presensis_dokter_id_tanggal_unique" on "dokter_presensis"(
  "dokter_id",
  "tanggal"
);
CREATE INDEX "dokter_presensis_dokter_id_tanggal_index" on "dokter_presensis"(
  "dokter_id",
  "tanggal"
);
CREATE TABLE IF NOT EXISTS "jaspel_rekaps"(
  "id" integer primary key autoincrement not null,
  "dokter_id" integer not null,
  "bulan" integer not null,
  "tahun" integer not null,
  "total_umum" numeric not null default '0',
  "total_bpjs" numeric not null default '0',
  "total_tindakan" integer not null default '0',
  "status_pembayaran" varchar check("status_pembayaran" in('pending', 'dibayar', 'ditolak')) not null default 'pending',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("dokter_id") references "dokters"("id") on delete cascade
);
CREATE UNIQUE INDEX "jaspel_rekaps_dokter_id_bulan_tahun_unique" on "jaspel_rekaps"(
  "dokter_id",
  "bulan",
  "tahun"
);
CREATE INDEX "jaspel_rekaps_dokter_id_tahun_bulan_index" on "jaspel_rekaps"(
  "dokter_id",
  "tahun",
  "bulan"
);
CREATE INDEX "jaspel_rekaps_status_pembayaran_index" on "jaspel_rekaps"(
  "status_pembayaran"
);
CREATE TABLE IF NOT EXISTS "dokters"(
  "id" integer primary key autoincrement not null,
  "nik" varchar,
  "nama_lengkap" varchar not null,
  "tanggal_lahir" date,
  "jenis_kelamin" varchar,
  "jabatan" varchar not null,
  "nomor_sip" varchar,
  "email" varchar,
  "aktif" tinyint(1) not null default('1'),
  "spesialisasi" varchar,
  "alamat" text,
  "no_telepon" varchar,
  "tanggal_bergabung" date,
  "foto" varchar,
  "keterangan" text,
  "input_by" integer,
  "username" varchar,
  "password" varchar,
  "status_akun" varchar not null default('aktif'),
  "password_changed_at" datetime,
  "last_login_at" datetime,
  "password_reset_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "user_id" integer,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "dokters_aktif_index" on "dokters"("aktif");
CREATE INDEX "dokters_nama_lengkap_index" on "dokters"("nama_lengkap");
CREATE INDEX "dokters_nik_index" on "dokters"("nik");
CREATE UNIQUE INDEX "dokters_nik_unique" on "dokters"("nik");
CREATE UNIQUE INDEX "dokters_username_unique" on "dokters"("username");
CREATE INDEX "dokters_user_id_index" on "dokters"("user_id");
CREATE TABLE IF NOT EXISTS "shift_templates"(
  "id" integer primary key autoincrement not null,
  "nama_shift" varchar not null,
  "jam_masuk" time not null,
  "jam_pulang" time not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "shift_templates_nama_shift_index" on "shift_templates"(
  "nama_shift"
);
CREATE TABLE IF NOT EXISTS "jadwal_jagas"(
  "id" integer primary key autoincrement not null,
  "tanggal_jaga" date not null,
  "shift_template_id" integer not null,
  "pegawai_id" integer not null,
  "unit_instalasi" varchar,
  "peran" varchar check("peran" in('Paramedis', 'NonParamedis', 'Dokter')) not null,
  "status_jaga" varchar check("status_jaga" in('Aktif', 'Cuti', 'Izin', 'OnCall')) not null default 'Aktif',
  "keterangan" text,
  "created_at" datetime,
  "updated_at" datetime,
  "unit_kerja" varchar check("unit_kerja" in('Pendaftaran', 'Pelayanan', 'Dokter Jaga')) not null,
  foreign key("shift_template_id") references "shift_templates"("id") on delete cascade,
  foreign key("pegawai_id") references "users"("id") on delete cascade
);
CREATE INDEX "jadwal_jagas_tanggal_jaga_pegawai_id_index" on "jadwal_jagas"(
  "tanggal_jaga",
  "pegawai_id"
);
CREATE INDEX "jadwal_jagas_tanggal_jaga_status_jaga_index" on "jadwal_jagas"(
  "tanggal_jaga",
  "status_jaga"
);
CREATE INDEX "jadwal_jagas_pegawai_id_status_jaga_index" on "jadwal_jagas"(
  "pegawai_id",
  "status_jaga"
);
CREATE TABLE IF NOT EXISTS "permohonan_cutis"(
  "id" integer primary key autoincrement not null,
  "pegawai_id" integer not null,
  "tanggal_mulai" date not null,
  "tanggal_selesai" date not null,
  "jenis_cuti" varchar check("jenis_cuti" in('Cuti Tahunan', 'Sakit', 'Izin', 'Dinas Luar')) not null,
  "keterangan" text,
  "status" varchar check("status" in('Menunggu', 'Disetujui', 'Ditolak')) not null default 'Menunggu',
  "disetujui_oleh" integer,
  "tanggal_pengajuan" datetime not null default CURRENT_TIMESTAMP,
  "tanggal_keputusan" datetime,
  "catatan_approval" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("pegawai_id") references "users"("id") on delete cascade,
  foreign key("disetujui_oleh") references "users"("id") on delete set null
);
CREATE INDEX "permohonan_cutis_pegawai_id_status_index" on "permohonan_cutis"(
  "pegawai_id",
  "status"
);
CREATE INDEX "permohonan_cutis_tanggal_mulai_tanggal_selesai_index" on "permohonan_cutis"(
  "tanggal_mulai",
  "tanggal_selesai"
);
CREATE INDEX "permohonan_cutis_status_tanggal_pengajuan_index" on "permohonan_cutis"(
  "status",
  "tanggal_pengajuan"
);
CREATE UNIQUE INDEX "unique_staff_shift_per_day" on "jadwal_jagas"(
  "tanggal_jaga",
  "pegawai_id",
  "shift_template_id"
);
CREATE INDEX "jadwal_jagas_tanggal_jaga_unit_kerja_index" on "jadwal_jagas"(
  "tanggal_jaga",
  "unit_kerja"
);
CREATE TABLE IF NOT EXISTS "leave_types"(
  "id" integer primary key autoincrement not null,
  "nama" varchar not null,
  "alokasi_hari" integer,
  "active" tinyint(1) not null default '1',
  "description" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "leave_types_active_nama_index" on "leave_types"(
  "active",
  "nama"
);
CREATE TABLE IF NOT EXISTS "system_configs"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "value" text,
  "description" varchar,
  "category" varchar not null default 'general',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "system_configs_key_unique" on "system_configs"("key");
CREATE TABLE IF NOT EXISTS "jaspel"(
  "id" integer primary key autoincrement not null,
  "tindakan_id" integer,
  "user_id" integer not null,
  "jenis_jaspel" varchar not null default 'tindakan',
  "nominal" numeric not null default '0',
  "total_jaspel" numeric not null default '0',
  "tanggal" date not null,
  "shift_id" integer,
  "input_by" integer not null,
  "status_validasi" varchar check("status_validasi" in('pending', 'disetujui', 'ditolak')) not null default 'pending',
  "validasi_by" integer,
  "validasi_at" datetime,
  "catatan_validasi" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("tindakan_id") references "tindakan"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("shift_id") references "shifts"("id") on delete set null,
  foreign key("input_by") references "users"("id") on delete cascade,
  foreign key("validasi_by") references "users"("id") on delete set null
);
CREATE INDEX "jaspel_user_id_tanggal_index" on "jaspel"("user_id", "tanggal");
CREATE INDEX "jaspel_status_validasi_index" on "jaspel"("status_validasi");
CREATE INDEX "jaspel_created_at_index" on "jaspel"("created_at");
CREATE TABLE IF NOT EXISTS "pegawais"(
  "id" integer primary key autoincrement not null,
  "nik" varchar not null,
  "nama_lengkap" varchar not null,
  "tanggal_lahir" date,
  "jenis_kelamin" varchar,
  "jabatan" varchar not null,
  "jenis_pegawai" varchar not null default('Non-Paramedis'),
  "aktif" tinyint(1) not null default('1'),
  "foto" varchar,
  "input_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "user_id" integer,
  "username" varchar,
  "password" varchar,
  "status_akun" varchar check("status_akun" in('Aktif', 'Suspend')) not null default 'Aktif',
  "password_changed_at" datetime,
  "password_reset_by" integer,
  "email" varchar,
  foreign key("user_id") references users("id") on delete set null on update no action,
  foreign key("input_by") references users("id") on delete set null on update no action,
  foreign key("password_reset_by") references "users"("id") on delete set null
);
CREATE INDEX "pegawais_jabatan_index" on "pegawais"("jabatan");
CREATE INDEX "pegawais_jenis_pegawai_aktif_index" on "pegawais"(
  "jenis_pegawai",
  "aktif"
);
CREATE INDEX "pegawais_nama_lengkap_index" on "pegawais"("nama_lengkap");
CREATE UNIQUE INDEX "pegawais_nik_unique" on "pegawais"("nik");
CREATE INDEX "pegawais_user_id_index" on "pegawais"("user_id");
CREATE INDEX "pegawais_username_index" on "pegawais"("username");
CREATE INDEX "pegawais_status_akun_index" on "pegawais"("status_akun");
CREATE UNIQUE INDEX "pegawais_username_unique" on "pegawais"("username");
CREATE TABLE IF NOT EXISTS "telegram_settings"(
  "id" integer primary key autoincrement not null,
  "role" varchar not null,
  "chat_id" varchar,
  "notification_types" text,
  "is_active" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  "user_id" integer,
  "user_name" varchar,
  "role_type" varchar,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "telegram_settings_role_user_id_index" on "telegram_settings"(
  "role",
  "user_id"
);
CREATE UNIQUE INDEX "telegram_settings_role_user_type_unique" on "telegram_settings"(
  "role",
  "user_id",
  "role_type"
);
CREATE TABLE IF NOT EXISTS "tindakan"(
  "id" integer primary key autoincrement not null,
  "pasien_id" integer not null,
  "jenis_tindakan_id" integer not null,
  "dokter_id" integer,
  "paramedis_id" integer,
  "non_paramedis_id" integer,
  "shift_id" integer not null,
  "tanggal_tindakan" datetime not null,
  "tarif" numeric not null,
  "jasa_dokter" numeric not null default('0'),
  "jasa_paramedis" numeric not null default('0'),
  "jasa_non_paramedis" numeric not null default('0'),
  "catatan" text,
  "status" varchar not null default('pending'),
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "input_by" integer not null,
  "status_validasi" varchar not null default('pending'),
  "validated_by" integer,
  "validated_at" datetime,
  "komentar_validasi" text,
  foreign key("non_paramedis_id") references pegawais("id") on delete set null on update no action,
  foreign key("paramedis_id") references pegawais("id") on delete set null on update no action,
  foreign key("dokter_id") references dokters("id") on delete cascade on update no action,
  foreign key("input_by") references users("id") on delete cascade on update no action,
  foreign key("pasien_id") references pasien("id") on delete cascade on update no action,
  foreign key("jenis_tindakan_id") references jenis_tindakan("id") on delete cascade on update no action,
  foreign key("shift_id") references shifts("id") on delete cascade on update no action,
  foreign key("validated_by") references users("id") on delete set null on update no action
);
CREATE INDEX "tindakan_dokter_id_tanggal_tindakan_index" on "tindakan"(
  "dokter_id",
  "tanggal_tindakan"
);
CREATE INDEX "tindakan_pasien_id_tanggal_tindakan_index" on "tindakan"(
  "pasien_id",
  "tanggal_tindakan"
);
CREATE INDEX "tindakan_status_index" on "tindakan"("status");
CREATE INDEX "tindakan_status_validasi_index" on "tindakan"("status_validasi");
CREATE INDEX "tindakan_tanggal_tindakan_index" on "tindakan"(
  "tanggal_tindakan"
);
CREATE TABLE IF NOT EXISTS "jumlah_pasien_harians"(
  "id" integer primary key autoincrement not null,
  "tanggal" date not null,
  "poli" varchar not null default('umum'),
  "jumlah_pasien_umum" integer not null default('0'),
  "jumlah_pasien_bpjs" integer not null default('0'),
  "dokter_id" integer not null,
  "input_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "status_validasi" varchar check("status_validasi" in('pending', 'approved', 'rejected')) not null default 'pending',
  "validasi_by" integer,
  "validasi_at" datetime,
  "catatan_validasi" text,
  foreign key("input_by") references users("id") on delete cascade on update no action,
  foreign key("dokter_id") references dokters("id") on delete cascade on update no action,
  foreign key("validasi_by") references "users"("id") on delete set null
);
CREATE INDEX "jumlah_pasien_harians_dokter_id_index" on "jumlah_pasien_harians"(
  "dokter_id"
);
CREATE INDEX "jumlah_pasien_harians_poli_index" on "jumlah_pasien_harians"(
  "poli"
);
CREATE INDEX "jumlah_pasien_harians_tanggal_index" on "jumlah_pasien_harians"(
  "tanggal"
);
CREATE UNIQUE INDEX "unique_daily_record" on "jumlah_pasien_harians"(
  "tanggal",
  "poli",
  "dokter_id"
);
CREATE INDEX "jumlah_pasien_harians_status_validasi_index" on "jumlah_pasien_harians"(
  "status_validasi"
);
CREATE INDEX "jumlah_pasien_harians_validasi_by_index" on "jumlah_pasien_harians"(
  "validasi_by"
);
CREATE INDEX "idx_checkin_location" on "attendances"("latitude", "longitude");
CREATE INDEX "idx_checkout_location" on "attendances"(
  "checkout_latitude",
  "checkout_longitude"
);
CREATE INDEX "idx_location_validated" on "attendances"("location_validated");
CREATE TABLE IF NOT EXISTS "non_paramedis_attendances"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "work_location_id" integer,
  "check_in_time" datetime,
  "check_in_latitude" numeric,
  "check_in_longitude" numeric,
  "check_in_accuracy" numeric,
  "check_in_address" varchar,
  "check_in_distance" numeric,
  "check_in_valid_location" tinyint(1) not null default '0',
  "check_out_time" datetime,
  "check_out_latitude" numeric,
  "check_out_longitude" numeric,
  "check_out_accuracy" numeric,
  "check_out_address" varchar,
  "check_out_distance" numeric,
  "check_out_valid_location" tinyint(1) not null default '0',
  "total_work_minutes" integer,
  "attendance_date" date not null,
  "status" varchar check("status" in('checked_in', 'checked_out', 'incomplete')) not null default 'incomplete',
  "notes" text,
  "device_info" varchar,
  "browser_info" varchar,
  "ip_address" varchar,
  "gps_metadata" text,
  "suspected_spoofing" tinyint(1) not null default '0',
  "approval_status" varchar check("approval_status" in('pending', 'approved', 'rejected')) not null default 'pending',
  "approved_by" integer,
  "approved_at" datetime,
  "approval_notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("work_location_id") references "work_locations"("id") on delete set null,
  foreign key("approved_by") references "users"("id") on delete set null
);
CREATE INDEX "non_paramedis_attendances_user_id_attendance_date_index" on "non_paramedis_attendances"(
  "user_id",
  "attendance_date"
);
CREATE INDEX "non_paramedis_attendances_work_location_id_attendance_date_index" on "non_paramedis_attendances"(
  "work_location_id",
  "attendance_date"
);
CREATE INDEX "non_paramedis_attendances_status_attendance_date_index" on "non_paramedis_attendances"(
  "status",
  "attendance_date"
);
CREATE INDEX "non_paramedis_attendances_approval_status_index" on "non_paramedis_attendances"(
  "approval_status"
);
CREATE TABLE IF NOT EXISTS "refresh_tokens"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "user_device_id" integer,
  "token_hash" varchar not null,
  "client_type" varchar not null default 'mobile_app',
  "scopes" text,
  "expires_at" datetime not null,
  "last_used_at" datetime,
  "last_used_ip" varchar,
  "user_agent" varchar,
  "is_revoked" tinyint(1) not null default '0',
  "revoked_at" datetime,
  "revoked_reason" varchar,
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("user_device_id") references "user_devices"("id") on delete cascade
);
CREATE INDEX "refresh_tokens_user_id_client_type_index" on "refresh_tokens"(
  "user_id",
  "client_type"
);
CREATE INDEX "refresh_tokens_expires_at_index" on "refresh_tokens"(
  "expires_at"
);
CREATE INDEX "refresh_tokens_is_revoked_index" on "refresh_tokens"(
  "is_revoked"
);
CREATE INDEX "refresh_tokens_token_hash_index" on "refresh_tokens"(
  "token_hash"
);
CREATE UNIQUE INDEX "refresh_tokens_token_hash_unique" on "refresh_tokens"(
  "token_hash"
);
CREATE TABLE IF NOT EXISTS "user_sessions"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "user_device_id" integer,
  "session_id" varchar not null,
  "access_token_id" varchar,
  "client_type" varchar not null default 'mobile_app',
  "session_data" text,
  "started_at" datetime not null,
  "last_activity_at" datetime not null,
  "expires_at" datetime,
  "ip_address" varchar not null,
  "user_agent" varchar,
  "location_country" varchar,
  "location_city" varchar,
  "location_latitude" numeric,
  "location_longitude" numeric,
  "is_active" tinyint(1) not null default '1',
  "force_logout" tinyint(1) not null default '0',
  "ended_at" datetime,
  "ended_reason" varchar,
  "security_flags" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("user_device_id") references "user_devices"("id") on delete cascade
);
CREATE INDEX "user_sessions_user_id_is_active_index" on "user_sessions"(
  "user_id",
  "is_active"
);
CREATE INDEX "user_sessions_session_id_index" on "user_sessions"("session_id");
CREATE INDEX "user_sessions_last_activity_at_index" on "user_sessions"(
  "last_activity_at"
);
CREATE INDEX "user_sessions_expires_at_index" on "user_sessions"("expires_at");
CREATE INDEX "user_sessions_force_logout_index" on "user_sessions"(
  "force_logout"
);
CREATE UNIQUE INDEX "user_sessions_session_id_unique" on "user_sessions"(
  "session_id"
);
CREATE TABLE IF NOT EXISTS "biometric_templates"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "user_device_id" integer,
  "template_id" varchar not null,
  "biometric_type" varchar not null,
  "template_data" text not null,
  "template_hash" varchar not null,
  "template_metadata" text,
  "algorithm_version" varchar,
  "is_primary" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "enrolled_at" datetime not null,
  "last_verified_at" datetime,
  "verification_count" integer not null default '0',
  "failed_attempts" integer not null default '0',
  "last_failed_at" datetime,
  "is_compromised" tinyint(1) not null default '0',
  "compromised_at" datetime,
  "compromised_reason" varchar,
  "security_metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("user_device_id") references "user_devices"("id") on delete cascade
);
CREATE INDEX "biometric_templates_user_id_biometric_type_index" on "biometric_templates"(
  "user_id",
  "biometric_type"
);
CREATE INDEX "biometric_templates_template_hash_index" on "biometric_templates"(
  "template_hash"
);
CREATE INDEX "biometric_templates_is_active_index" on "biometric_templates"(
  "is_active"
);
CREATE INDEX "biometric_templates_is_compromised_index" on "biometric_templates"(
  "is_compromised"
);
CREATE UNIQUE INDEX "biometric_templates_user_id_biometric_type_template_id_unique" on "biometric_templates"(
  "user_id",
  "biometric_type",
  "template_id"
);
CREATE UNIQUE INDEX "biometric_templates_template_id_unique" on "biometric_templates"(
  "template_id"
);
CREATE TABLE IF NOT EXISTS "user_devices"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "device_id" varchar not null,
  "device_name" varchar,
  "device_type" varchar not null,
  "platform" varchar not null,
  "os_version" varchar,
  "browser_name" varchar,
  "browser_version" varchar,
  "user_agent" varchar,
  "ip_address" varchar,
  "mac_address" varchar,
  "device_specs" text,
  "device_fingerprint" varchar not null,
  "push_token" varchar,
  "is_active" tinyint(1) not null default('1'),
  "is_primary" tinyint(1) not null default('0'),
  "status" varchar not null default('active'),
  "first_login_at" datetime not null,
  "last_login_at" datetime,
  "last_activity_at" datetime,
  "verified_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "biometric_capabilities" text,
  "biometric_enabled" tinyint(1) not null default '0',
  "biometric_types" text,
  "biometric_enrolled_at" datetime,
  "biometric_verification_count" integer not null default '0',
  "last_biometric_verification_at" datetime,
  "refresh_token_hash" varchar,
  "refresh_token_expires_at" datetime,
  "session_metadata" text,
  "security_score" integer not null default '100',
  "security_violations" text,
  "requires_admin_approval" tinyint(1) not null default '0',
  "admin_approved_at" datetime,
  "approved_by" integer,
  foreign key("user_id") references users("id") on delete cascade on update no action,
  foreign key("approved_by") references "users"("id") on delete set null
);
CREATE INDEX "user_devices_device_fingerprint_index" on "user_devices"(
  "device_fingerprint"
);
CREATE UNIQUE INDEX "user_devices_device_fingerprint_unique" on "user_devices"(
  "device_fingerprint"
);
CREATE UNIQUE INDEX "user_devices_device_id_unique" on "user_devices"(
  "device_id"
);
CREATE INDEX "user_devices_last_activity_at_index" on "user_devices"(
  "last_activity_at"
);
CREATE INDEX "user_devices_status_is_active_index" on "user_devices"(
  "status",
  "is_active"
);
CREATE INDEX "user_devices_user_id_device_id_index" on "user_devices"(
  "user_id",
  "device_id"
);
CREATE UNIQUE INDEX "user_devices_user_id_device_id_unique" on "user_devices"(
  "user_id",
  "device_id"
);
CREATE INDEX "user_devices_biometric_enabled_index" on "user_devices"(
  "biometric_enabled"
);
CREATE INDEX "user_devices_security_score_index" on "user_devices"(
  "security_score"
);
CREATE INDEX "user_devices_requires_admin_approval_index" on "user_devices"(
  "requires_admin_approval"
);
CREATE TABLE IF NOT EXISTS "user_notifications"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "type" varchar not null,
  "title" varchar not null,
  "message" text not null,
  "data" text,
  "priority" varchar check("priority" in('low', 'medium', 'high', 'urgent')) not null default 'medium',
  "channel" varchar check("channel" in('in_app', 'email', 'push', 'sms')) not null default 'in_app',
  "is_read" tinyint(1) not null default '0',
  "read_at" datetime,
  "scheduled_for" datetime,
  "is_sent" tinyint(1) not null default '0',
  "sent_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "user_notifications_user_id_is_read_index" on "user_notifications"(
  "user_id",
  "is_read"
);
CREATE INDEX "user_notifications_user_id_created_at_index" on "user_notifications"(
  "user_id",
  "created_at"
);
CREATE INDEX "user_notifications_type_priority_index" on "user_notifications"(
  "type",
  "priority"
);
CREATE INDEX "user_notifications_scheduled_for_index" on "user_notifications"(
  "scheduled_for"
);
CREATE INDEX "user_notifications_expires_at_index" on "user_notifications"(
  "expires_at"
);
CREATE TABLE IF NOT EXISTS "feature_flags"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "name" varchar not null,
  "description" text,
  "is_enabled" tinyint(1) not null default '0',
  "conditions" text,
  "environment" varchar,
  "starts_at" datetime,
  "ends_at" datetime,
  "meta" text,
  "is_permanent" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "feature_flags_key_is_enabled_index" on "feature_flags"(
  "key",
  "is_enabled"
);
CREATE INDEX "feature_flags_environment_index" on "feature_flags"(
  "environment"
);
CREATE INDEX "feature_flags_is_permanent_index" on "feature_flags"(
  "is_permanent"
);
CREATE UNIQUE INDEX "feature_flags_key_unique" on "feature_flags"("key");
CREATE TABLE IF NOT EXISTS "system_settings"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "value" text,
  "type" varchar not null default 'string',
  "group" varchar not null default 'general',
  "label" varchar not null,
  "description" text,
  "is_public" tinyint(1) not null default '0',
  "is_readonly" tinyint(1) not null default '0',
  "validation_rules" text,
  "meta" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "system_settings_group_key_index" on "system_settings"(
  "group",
  "key"
);
CREATE INDEX "system_settings_is_public_index" on "system_settings"(
  "is_public"
);
CREATE UNIQUE INDEX "system_settings_key_unique" on "system_settings"("key");
CREATE TABLE IF NOT EXISTS "system_metrics"(
  "id" integer primary key autoincrement not null,
  "metric_type" varchar not null,
  "metric_name" varchar not null,
  "metric_value" numeric not null,
  "metric_data" text,
  "alert_threshold" numeric,
  "status" varchar check("status" in('healthy', 'warning', 'critical', 'unknown')) not null default 'healthy',
  "recorded_at" datetime not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "system_metrics_metric_type_metric_name_index" on "system_metrics"(
  "metric_type",
  "metric_name"
);
CREATE INDEX "system_metrics_status_recorded_at_index" on "system_metrics"(
  "status",
  "recorded_at"
);
CREATE INDEX "system_metrics_recorded_at_metric_type_index" on "system_metrics"(
  "recorded_at",
  "metric_type"
);
CREATE INDEX "system_metrics_metric_type_index" on "system_metrics"(
  "metric_type"
);
CREATE INDEX "system_metrics_metric_name_index" on "system_metrics"(
  "metric_name"
);
CREATE INDEX "system_metrics_recorded_at_index" on "system_metrics"(
  "recorded_at"
);
CREATE TABLE IF NOT EXISTS "two_factor_auth"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "secret_key" text not null,
  "recovery_codes" text,
  "enabled" tinyint(1) not null default '0',
  "enabled_at" datetime,
  "last_used_at" datetime,
  "backup_codes_used" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "two_factor_auth_user_id_unique" on "two_factor_auth"(
  "user_id"
);
CREATE INDEX "two_factor_auth_user_id_enabled_index" on "two_factor_auth"(
  "user_id",
  "enabled"
);
CREATE TABLE IF NOT EXISTS "bulk_operations"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "operation_type" varchar not null,
  "model_type" varchar not null,
  "operation_data" text not null,
  "filters" text,
  "status" varchar check("status" in('pending', 'processing', 'completed', 'failed', 'cancelled', 'paused')) not null default 'pending',
  "total_records" integer not null default '0',
  "processed_records" integer not null default '0',
  "successful_records" integer not null default '0',
  "failed_records" integer not null default '0',
  "error_details" text,
  "started_at" datetime,
  "completed_at" datetime,
  "estimated_duration" integer,
  "progress_percentage" numeric not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "bulk_operations_user_id_status_index" on "bulk_operations"(
  "user_id",
  "status"
);
CREATE INDEX "bulk_operations_operation_type_status_index" on "bulk_operations"(
  "operation_type",
  "status"
);
CREATE INDEX "bulk_operations_created_at_status_index" on "bulk_operations"(
  "created_at",
  "status"
);
CREATE INDEX "bulk_operations_operation_type_index" on "bulk_operations"(
  "operation_type"
);
CREATE INDEX "bulk_operations_model_type_index" on "bulk_operations"(
  "model_type"
);
CREATE TABLE IF NOT EXISTS "reports"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "name" varchar not null,
  "description" text,
  "report_type" varchar check("report_type" in('table', 'chart', 'dashboard', 'export', 'kpi')) not null default 'table',
  "category" varchar check("category" in('financial', 'operational', 'medical', 'administrative', 'security', 'performance', 'custom')) not null default 'custom',
  "query_config" text not null,
  "chart_config" text,
  "filters" text,
  "columns" text,
  "is_public" tinyint(1) not null default '0',
  "is_scheduled" tinyint(1) not null default '0',
  "schedule_config" text,
  "last_generated_at" datetime,
  "cache_duration" integer not null default '300',
  "status" varchar check("status" in('active', 'inactive', 'draft', 'archived')) not null default 'active',
  "tags" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "reports_user_id_status_index" on "reports"("user_id", "status");
CREATE INDEX "reports_report_type_category_index" on "reports"(
  "report_type",
  "category"
);
CREATE INDEX "reports_is_public_status_index" on "reports"(
  "is_public",
  "status"
);
CREATE INDEX "reports_is_scheduled_status_index" on "reports"(
  "is_scheduled",
  "status"
);
CREATE TABLE IF NOT EXISTS "report_executions"(
  "id" integer primary key autoincrement not null,
  "report_id" integer not null,
  "user_id" integer not null,
  "parameters" text,
  "status" varchar check("status" in('pending', 'running', 'completed', 'failed', 'cancelled')) not null default 'pending',
  "result_data" text,
  "result_count" integer,
  "execution_time" integer,
  "memory_usage" integer,
  "started_at" datetime,
  "completed_at" datetime,
  "error_message" text,
  "cache_key" varchar,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("report_id") references "reports"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "report_executions_report_id_status_index" on "report_executions"(
  "report_id",
  "status"
);
CREATE INDEX "report_executions_user_id_status_index" on "report_executions"(
  "user_id",
  "status"
);
CREATE INDEX "report_executions_created_at_status_index" on "report_executions"(
  "created_at",
  "status"
);
CREATE INDEX "report_executions_cache_key_index" on "report_executions"(
  "cache_key"
);
CREATE TABLE IF NOT EXISTS "report_shares"(
  "id" integer primary key autoincrement not null,
  "report_id" integer not null,
  "user_id" integer not null,
  "shared_by" integer not null,
  "permissions" text,
  "expires_at" datetime,
  "access_count" integer not null default '0',
  "last_accessed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("report_id") references "reports"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("shared_by") references "users"("id") on delete cascade
);
CREATE INDEX "report_shares_report_id_user_id_index" on "report_shares"(
  "report_id",
  "user_id"
);
CREATE INDEX "report_shares_user_id_expires_at_index" on "report_shares"(
  "user_id",
  "expires_at"
);
CREATE INDEX "report_shares_shared_by_created_at_index" on "report_shares"(
  "shared_by",
  "created_at"
);
CREATE UNIQUE INDEX "report_shares_report_id_user_id_unique" on "report_shares"(
  "report_id",
  "user_id"
);
CREATE TABLE IF NOT EXISTS "data_imports"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "user_id" integer not null,
  "source_type" varchar not null,
  "target_model" varchar not null,
  "file_path" varchar,
  "file_name" varchar,
  "file_size" varchar,
  "mime_type" varchar,
  "source_config" text,
  "mapping_config" text not null,
  "validation_rules" text,
  "status" varchar check("status" in('pending', 'processing', 'completed', 'failed', 'cancelled')) not null default 'pending',
  "total_rows" integer,
  "processed_rows" integer not null default '0',
  "successful_rows" integer not null default '0',
  "failed_rows" integer not null default '0',
  "skipped_rows" integer not null default '0',
  "error_details" text,
  "validation_errors" text,
  "progress_percentage" integer not null default '0',
  "started_at" datetime,
  "completed_at" datetime,
  "execution_time" integer,
  "memory_usage" integer,
  "is_scheduled" tinyint(1) not null default '0',
  "schedule_frequency" varchar,
  "next_run_at" datetime,
  "notification_settings" text,
  "backup_before_import" tinyint(1) not null default '1',
  "backup_file_path" varchar,
  "preview_data" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "data_imports_user_id_status_index" on "data_imports"(
  "user_id",
  "status"
);
CREATE INDEX "data_imports_status_created_at_index" on "data_imports"(
  "status",
  "created_at"
);
CREATE INDEX "data_imports_target_model_status_index" on "data_imports"(
  "target_model",
  "status"
);
CREATE INDEX "data_imports_is_scheduled_next_run_at_index" on "data_imports"(
  "is_scheduled",
  "next_run_at"
);
CREATE TABLE IF NOT EXISTS "data_exports"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "user_id" integer not null,
  "source_model" varchar not null,
  "export_format" varchar not null,
  "file_path" varchar,
  "file_name" varchar,
  "file_size" varchar,
  "query_config" text,
  "column_config" text not null,
  "format_config" text,
  "status" varchar check("status" in('pending', 'processing', 'completed', 'failed', 'cancelled')) not null default 'pending',
  "total_rows" integer,
  "exported_rows" integer not null default '0',
  "progress_percentage" integer not null default '0',
  "started_at" datetime,
  "completed_at" datetime,
  "execution_time" integer,
  "memory_usage" integer,
  "error_details" text,
  "is_scheduled" tinyint(1) not null default '0',
  "schedule_frequency" varchar,
  "next_run_at" datetime,
  "notification_settings" text,
  "compress_output" tinyint(1) not null default '0',
  "compression_format" varchar,
  "encrypt_output" tinyint(1) not null default '0',
  "encryption_key" varchar,
  "expires_at" datetime,
  "download_count" integer not null default '0',
  "last_downloaded_at" datetime,
  "access_permissions" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "data_exports_user_id_status_index" on "data_exports"(
  "user_id",
  "status"
);
CREATE INDEX "data_exports_status_created_at_index" on "data_exports"(
  "status",
  "created_at"
);
CREATE INDEX "data_exports_source_model_status_index" on "data_exports"(
  "source_model",
  "status"
);
CREATE INDEX "data_exports_is_scheduled_next_run_at_index" on "data_exports"(
  "is_scheduled",
  "next_run_at"
);
CREATE INDEX "data_exports_expires_at_index" on "data_exports"("expires_at");
CREATE TABLE IF NOT EXISTS "data_transformations"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "user_id" integer not null,
  "transformation_type" varchar not null,
  "source_model" varchar not null,
  "target_model" varchar,
  "transformation_rules" text not null,
  "field_mappings" text,
  "validation_rules" text,
  "cleanup_rules" text,
  "status" varchar check("status" in('pending', 'processing', 'completed', 'failed', 'cancelled')) not null default 'pending',
  "total_records" integer,
  "processed_records" integer not null default '0',
  "transformed_records" integer not null default '0',
  "failed_records" integer not null default '0',
  "skipped_records" integer not null default '0',
  "transformation_stats" text,
  "error_log" text,
  "progress_percentage" integer not null default '0',
  "started_at" datetime,
  "completed_at" datetime,
  "execution_time" integer,
  "memory_usage" integer,
  "is_scheduled" tinyint(1) not null default '0',
  "schedule_frequency" varchar,
  "next_run_at" datetime,
  "notification_settings" text,
  "backup_before_transform" tinyint(1) not null default '1',
  "backup_file_path" varchar,
  "dry_run" tinyint(1) not null default '0',
  "dry_run_results" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "data_transformations_user_id_status_index" on "data_transformations"(
  "user_id",
  "status"
);
CREATE INDEX "data_transformations_status_created_at_index" on "data_transformations"(
  "status",
  "created_at"
);
CREATE INDEX "data_transformations_source_model_status_index" on "data_transformations"(
  "source_model",
  "status"
);
CREATE INDEX "data_transformations_transformation_type_status_index" on "data_transformations"(
  "transformation_type",
  "status"
);
CREATE INDEX "data_transformations_is_scheduled_next_run_at_index" on "data_transformations"(
  "is_scheduled",
  "next_run_at"
);
CREATE TABLE IF NOT EXISTS "workflows"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "user_id" integer not null,
  "trigger_type" varchar not null,
  "trigger_config" text,
  "steps" text not null,
  "conditions" text,
  "status" varchar check("status" in('draft', 'active', 'paused', 'archived')) not null default 'draft',
  "category" varchar not null default 'general',
  "tags" text,
  "priority" integer not null default '1',
  "timeout" integer not null default '300',
  "is_public" tinyint(1) not null default '0',
  "is_template" tinyint(1) not null default '0',
  "max_retries" integer not null default '3',
  "retry_config" text,
  "notification_config" text,
  "error_handling" text,
  "last_executed_at" datetime,
  "execution_count" integer not null default '0',
  "success_count" integer not null default '0',
  "failure_count" integer not null default '0',
  "avg_execution_time" numeric,
  "performance_stats" text,
  "next_run_at" datetime,
  "schedule_frequency" varchar,
  "is_enabled" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "workflows_user_id_status_index" on "workflows"(
  "user_id",
  "status"
);
CREATE INDEX "workflows_trigger_type_status_index" on "workflows"(
  "trigger_type",
  "status"
);
CREATE INDEX "workflows_category_status_index" on "workflows"(
  "category",
  "status"
);
CREATE INDEX "workflows_is_enabled_status_index" on "workflows"(
  "is_enabled",
  "status"
);
CREATE INDEX "workflows_next_run_at_status_index" on "workflows"(
  "next_run_at",
  "status"
);
CREATE INDEX "workflows_is_public_is_template_index" on "workflows"(
  "is_public",
  "is_template"
);
CREATE TABLE IF NOT EXISTS "workflow_executions"(
  "id" integer primary key autoincrement not null,
  "workflow_id" integer not null,
  "user_id" integer,
  "execution_id" varchar not null,
  "trigger_source" varchar,
  "trigger_data" text,
  "status" varchar check("status" in('pending', 'running', 'completed', 'failed', 'cancelled', 'timeout')) not null default 'pending',
  "current_step" text,
  "step_results" text,
  "context_data" text,
  "execution_log" text,
  "error_message" text,
  "error_details" text,
  "started_at" datetime,
  "completed_at" datetime,
  "execution_time" integer,
  "memory_usage" integer,
  "steps_completed" integer not null default '0',
  "total_steps" integer not null default '0',
  "retry_count" integer not null default '0',
  "warnings_count" integer not null default '0',
  "performance_metrics" text,
  "output_data" text,
  "priority" varchar not null default 'normal',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("workflow_id") references "workflows"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "workflow_executions_workflow_id_status_index" on "workflow_executions"(
  "workflow_id",
  "status"
);
CREATE INDEX "workflow_executions_user_id_status_index" on "workflow_executions"(
  "user_id",
  "status"
);
CREATE INDEX "workflow_executions_status_created_at_index" on "workflow_executions"(
  "status",
  "created_at"
);
CREATE INDEX "workflow_executions_trigger_source_status_index" on "workflow_executions"(
  "trigger_source",
  "status"
);
CREATE INDEX "workflow_executions_execution_id_index" on "workflow_executions"(
  "execution_id"
);
CREATE INDEX "workflow_executions_started_at_status_index" on "workflow_executions"(
  "started_at",
  "status"
);
CREATE UNIQUE INDEX "workflow_executions_execution_id_unique" on "workflow_executions"(
  "execution_id"
);
CREATE TABLE IF NOT EXISTS "quick_actions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "user_id" integer not null,
  "action_type" varchar not null,
  "action_config" text not null,
  "icon" varchar,
  "color" varchar not null default 'primary',
  "keyboard_shortcut" varchar,
  "category" varchar not null default 'general',
  "permissions" text,
  "context_filters" text,
  "sort_order" integer not null default '0',
  "is_enabled" tinyint(1) not null default '1',
  "is_public" tinyint(1) not null default '0',
  "show_in_toolbar" tinyint(1) not null default '1',
  "show_in_menu" tinyint(1) not null default '1',
  "confirm_before_execute" tinyint(1) not null default '0',
  "confirmation_message" varchar,
  "usage_count" integer not null default '0',
  "last_used_at" datetime,
  "usage_stats" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "quick_actions_user_id_is_enabled_index" on "quick_actions"(
  "user_id",
  "is_enabled"
);
CREATE INDEX "quick_actions_action_type_is_enabled_index" on "quick_actions"(
  "action_type",
  "is_enabled"
);
CREATE INDEX "quick_actions_category_is_enabled_index" on "quick_actions"(
  "category",
  "is_enabled"
);
CREATE INDEX "quick_actions_is_public_is_enabled_index" on "quick_actions"(
  "is_public",
  "is_enabled"
);
CREATE INDEX "quick_actions_show_in_toolbar_is_enabled_index" on "quick_actions"(
  "show_in_toolbar",
  "is_enabled"
);
CREATE INDEX "quick_actions_keyboard_shortcut_index" on "quick_actions"(
  "keyboard_shortcut"
);
CREATE INDEX "quick_actions_sort_order_index" on "quick_actions"("sort_order");
CREATE TABLE IF NOT EXISTS "saved_searches"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "model_class" varchar not null,
  "name" varchar not null,
  "search_params" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "saved_searches_user_id_model_class_index" on "saved_searches"(
  "user_id",
  "model_class"
);
CREATE UNIQUE INDEX "saved_searches_user_id_model_class_name_unique" on "saved_searches"(
  "user_id",
  "model_class",
  "name"
);
CREATE INDEX "idx_pasien_created_at" on "pasien"("created_at");
CREATE INDEX "idx_pasien_jenis_kelamin" on "pasien"("jenis_kelamin");
CREATE INDEX "idx_pasien_tanggal_lahir" on "pasien"("tanggal_lahir");
CREATE INDEX "idx_pasien_gender_created" on "pasien"(
  "jenis_kelamin",
  "created_at"
);
CREATE INDEX "idx_pasien_no_rekam_medis" on "pasien"("no_rekam_medis");
CREATE INDEX "idx_pasien_nama" on "pasien"("nama");
CREATE INDEX "idx_pasien_deleted_at" on "pasien"("deleted_at");
CREATE INDEX "idx_tindakan_pasien_id" on "tindakan"("pasien_id");
CREATE INDEX "idx_tindakan_dokter_id" on "tindakan"("dokter_id");
CREATE INDEX "idx_tindakan_jenis_tindakan_id" on "tindakan"(
  "jenis_tindakan_id"
);
CREATE INDEX "idx_tindakan_tanggal" on "tindakan"("tanggal_tindakan");
CREATE INDEX "idx_tindakan_status" on "tindakan"("status");
CREATE INDEX "idx_tindakan_status_validasi" on "tindakan"("status_validasi");
CREATE INDEX "idx_tindakan_created_at" on "tindakan"("created_at");
CREATE INDEX "idx_tindakan_validated_at" on "tindakan"("validated_at");
CREATE INDEX "idx_tindakan_input_by" on "tindakan"("input_by");
CREATE INDEX "idx_tindakan_validated_by" on "tindakan"("validated_by");
CREATE INDEX "idx_tindakan_deleted_at" on "tindakan"("deleted_at");
CREATE INDEX "idx_tindakan_pasien_tanggal" on "tindakan"(
  "pasien_id",
  "tanggal_tindakan"
);
CREATE INDEX "idx_tindakan_dokter_tanggal" on "tindakan"(
  "dokter_id",
  "tanggal_tindakan"
);
CREATE INDEX "idx_tindakan_status_tanggal" on "tindakan"(
  "status",
  "tanggal_tindakan"
);
CREATE INDEX "idx_tindakan_validasi_created" on "tindakan"(
  "status_validasi",
  "created_at"
);
CREATE INDEX "idx_tindakan_jenis_status" on "tindakan"(
  "jenis_tindakan_id",
  "status"
);
CREATE INDEX "idx_pendapatan_tindakan_id" on "pendapatan"("tindakan_id");
CREATE INDEX "idx_pendapatan_kategori" on "pendapatan"("kategori");
CREATE INDEX "idx_pendapatan_status" on "pendapatan"("status");
CREATE INDEX "idx_pendapatan_created_at" on "pendapatan"("created_at");
CREATE INDEX "idx_pendapatan_input_by" on "pendapatan"("input_by");
CREATE INDEX "idx_pendapatan_validasi_by" on "pendapatan"("validasi_by");
CREATE INDEX "idx_pendapatan_validated_at" on "pendapatan"("validated_at");
CREATE INDEX "idx_pendapatan_deleted_at" on "pendapatan"("deleted_at");
CREATE INDEX "idx_pendapatan_status_created" on "pendapatan"(
  "status",
  "created_at"
);
CREATE INDEX "idx_pendapatan_kategori_status" on "pendapatan"(
  "kategori",
  "status"
);
CREATE INDEX "idx_pendapatan_created_jumlah" on "pendapatan"(
  "created_at",
  "jumlah"
);
CREATE INDEX "idx_pendapatan_tindakan_status" on "pendapatan"(
  "tindakan_id",
  "status"
);
CREATE INDEX "idx_pengeluaran_kategori" on "pengeluaran"("kategori");
CREATE INDEX "idx_pengeluaran_status" on "pengeluaran"("status");
CREATE INDEX "idx_pengeluaran_created_at" on "pengeluaran"("created_at");
CREATE INDEX "idx_pengeluaran_input_by" on "pengeluaran"("input_by");
CREATE INDEX "idx_pengeluaran_validasi_by" on "pengeluaran"("validasi_by");
CREATE INDEX "idx_pengeluaran_validated_at" on "pengeluaran"("validated_at");
CREATE INDEX "idx_pengeluaran_deleted_at" on "pengeluaran"("deleted_at");
CREATE INDEX "idx_pengeluaran_status_created" on "pengeluaran"(
  "status",
  "created_at"
);
CREATE INDEX "idx_pengeluaran_kategori_status" on "pengeluaran"(
  "kategori",
  "status"
);
CREATE INDEX "idx_pengeluaran_created_jumlah" on "pengeluaran"(
  "created_at",
  "jumlah"
);
CREATE INDEX "idx_dokters_user_id" on "dokters"("user_id");
CREATE INDEX "idx_dokters_spesialisasi" on "dokters"("spesialisasi");
CREATE INDEX "idx_dokters_aktif" on "dokters"("aktif");
CREATE INDEX "idx_dokters_status_akun" on "dokters"("status_akun");
CREATE INDEX "idx_dokters_created_at" on "dokters"("created_at");
CREATE INDEX "idx_dokters_input_by" on "dokters"("input_by");
CREATE INDEX "idx_dokters_deleted_at" on "dokters"("deleted_at");
CREATE INDEX "idx_dokters_nomor_sip" on "dokters"("nomor_sip");
CREATE INDEX "idx_dokters_nik" on "dokters"("nik");
CREATE INDEX "idx_dokters_nama_lengkap" on "dokters"("nama_lengkap");
CREATE INDEX "idx_dokters_aktif_spesialisasi" on "dokters"(
  "aktif",
  "spesialisasi"
);
CREATE INDEX "idx_dokters_user_aktif" on "dokters"("user_id", "aktif");
CREATE INDEX "idx_jenis_tindakan_nama" on "jenis_tindakan"("nama");
CREATE INDEX "idx_jenis_tindakan_kategori" on "jenis_tindakan"("kategori");
CREATE INDEX "idx_jenis_tindakan_is_active" on "jenis_tindakan"("is_active");
CREATE INDEX "idx_jenis_tindakan_created_at" on "jenis_tindakan"("created_at");
CREATE INDEX "idx_jenis_tindakan_deleted_at" on "jenis_tindakan"("deleted_at");
CREATE INDEX "idx_jenis_tindakan_kategori_active" on "jenis_tindakan"(
  "kategori",
  "is_active"
);
CREATE INDEX "idx_jenis_tindakan_active_tarif" on "jenis_tindakan"(
  "is_active",
  "tarif"
);
CREATE INDEX "idx_jaspel_tindakan_id" on "jaspel"("tindakan_id");
CREATE INDEX "idx_jaspel_user_id" on "jaspel"("user_id");
CREATE INDEX "idx_jaspel_jenis_jaspel" on "jaspel"("jenis_jaspel");
CREATE INDEX "idx_jaspel_periode" on "jaspel"("periode");
CREATE INDEX "idx_jaspel_status" on "jaspel"("status");
CREATE INDEX "idx_jaspel_created_at" on "jaspel"("created_at");
CREATE INDEX "idx_jaspel_deleted_at" on "jaspel"("deleted_at");
CREATE INDEX "idx_jaspel_user_periode" on "jaspel"("user_id", "periode");
CREATE INDEX "idx_jaspel_tindakan_jenis" on "jaspel"(
  "tindakan_id",
  "jenis_jaspel"
);
CREATE INDEX "idx_jaspel_status_periode" on "jaspel"("status", "periode");
CREATE INDEX "idx_jaspel_user_status" on "jaspel"("user_id", "status");
CREATE INDEX "idx_audit_logs_user_id" on "audit_logs"("user_id");
CREATE INDEX "idx_audit_logs_action" on "audit_logs"("action");
CREATE INDEX "idx_audit_logs_model_type" on "audit_logs"("model_type");
CREATE INDEX "idx_audit_logs_model_id" on "audit_logs"("model_id");
CREATE INDEX "idx_audit_logs_created_at" on "audit_logs"("created_at");
CREATE INDEX "idx_audit_logs_user_role" on "audit_logs"("user_role");
CREATE INDEX "idx_audit_logs_ip_address" on "audit_logs"("ip_address");
CREATE INDEX "idx_audit_logs_model" on "audit_logs"("model_type", "model_id");
CREATE INDEX "idx_audit_logs_user_action" on "audit_logs"("user_id", "action");
CREATE INDEX "idx_audit_logs_action_created" on "audit_logs"(
  "action",
  "created_at"
);
CREATE INDEX "idx_audit_logs_role_created" on "audit_logs"(
  "user_role",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "error_logs"(
  "id" integer primary key autoincrement not null,
  "level" varchar not null,
  "message" text not null,
  "context" text,
  "exception" text,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "url" text,
  "method" varchar,
  "session_id" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "error_logs_level_created_at_index" on "error_logs"(
  "level",
  "created_at"
);
CREATE INDEX "error_logs_user_id_created_at_index" on "error_logs"(
  "user_id",
  "created_at"
);
CREATE INDEX "error_logs_created_at_index" on "error_logs"("created_at");
CREATE INDEX "error_logs_level_index" on "error_logs"("level");
CREATE TABLE IF NOT EXISTS "performance_logs"(
  "id" integer primary key autoincrement not null,
  "operation" varchar not null,
  "duration" numeric not null,
  "memory_usage" integer not null,
  "memory_peak" integer not null,
  "metrics" text,
  "user_id" integer,
  "url" text,
  "method" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "performance_logs_operation_created_at_index" on "performance_logs"(
  "operation",
  "created_at"
);
CREATE INDEX "performance_logs_duration_created_at_index" on "performance_logs"(
  "duration",
  "created_at"
);
CREATE INDEX "performance_logs_user_id_created_at_index" on "performance_logs"(
  "user_id",
  "created_at"
);
CREATE INDEX "performance_logs_created_at_index" on "performance_logs"(
  "created_at"
);
CREATE INDEX "performance_logs_operation_index" on "performance_logs"(
  "operation"
);
CREATE UNIQUE INDEX "pegawais_email_unique" on "pegawais"("email");
CREATE TABLE IF NOT EXISTS "dokter_umum_jaspels"(
  "id" integer primary key autoincrement not null,
  "jenis_shift" varchar not null,
  "ambang_pasien" integer not null default '0',
  "fee_pasien_umum" numeric not null default '0',
  "fee_pasien_bpjs" numeric not null default '0',
  "status_aktif" tinyint(1) not null default '1',
  "keterangan" text,
  "created_by" integer,
  "updated_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("created_by") references "users"("id") on delete set null,
  foreign key("updated_by") references "users"("id") on delete set null
);
CREATE INDEX "dokter_umum_jaspels_jenis_shift_status_aktif_index" on "dokter_umum_jaspels"(
  "jenis_shift",
  "status_aktif"
);
CREATE INDEX "dokter_umum_jaspels_jenis_shift_index" on "dokter_umum_jaspels"(
  "jenis_shift"
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "role_id" integer,
  "nip" varchar,
  "no_telepon" varchar,
  "tanggal_bergabung" date,
  "is_active" tinyint(1) not null default('1'),
  "deleted_at" datetime,
  "username" varchar,
  "phone" varchar,
  "address" text,
  "bio" text,
  "date_of_birth" date,
  "gender" varchar,
  "emergency_contact_name" varchar,
  "emergency_contact_phone" varchar,
  "profile_photo_path" varchar,
  "default_work_location_id" integer,
  "auto_check_out" tinyint(1) not null default('0'),
  "overtime_alerts" tinyint(1) not null default('1'),
  "email_notifications" tinyint(1) not null default('1'),
  "push_notifications" tinyint(1) not null default('1'),
  "attendance_reminders" tinyint(1) not null default('1'),
  "schedule_updates" tinyint(1) not null default('1'),
  "profile_visibility" varchar not null default('colleagues'),
  "location_sharing" tinyint(1) not null default('1'),
  "activity_status" tinyint(1) not null default('1'),
  "language" varchar not null default('id'),
  "timezone" varchar not null default('Asia/Jakarta'),
  "theme" varchar not null default('light'),
  "pegawai_id" integer,
  foreign key("pegawai_id") references pegawais("id") on delete cascade on update no action,
  foreign key("default_work_location_id") references work_locations("id") on delete set null on update no action,
  foreign key("role_id") references roles("id") on delete cascade on update no action
);
CREATE INDEX "idx_users_active_created" on "users"("is_active", "created_at");
CREATE INDEX "idx_users_created_at" on "users"("created_at");
CREATE INDEX "idx_users_deleted_at" on "users"("deleted_at");
CREATE INDEX "idx_users_is_active" on "users"("is_active");
CREATE TABLE IF NOT EXISTS "schedules"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "shift_id" integer,
  "date" date not null,
  "is_day_off" tinyint(1) not null default('0'),
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references users("id") on delete cascade on update no action
);
CREATE INDEX "schedules_date_index" on "schedules"("date");
CREATE INDEX "schedules_user_id_date_index" on "schedules"("user_id", "date");
CREATE UNIQUE INDEX "schedules_user_id_date_unique" on "schedules"(
  "user_id",
  "date"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_01_12_create_schedules_table',1);
INSERT INTO migrations VALUES(5,'2025_07_11_092652_create_jenis_tindakan_table',1);
INSERT INTO migrations VALUES(6,'2025_07_11_092652_create_pasien_table',1);
INSERT INTO migrations VALUES(7,'2025_07_11_092652_create_roles_table',1);
INSERT INTO migrations VALUES(8,'2025_07_11_092652_create_shifts_table',1);
INSERT INTO migrations VALUES(9,'2025_07_11_092652_create_tindakan_table',1);
INSERT INTO migrations VALUES(10,'2025_07_11_092700_add_role_id_to_users_table',1);
INSERT INTO migrations VALUES(11,'2025_07_11_092700_create_pendapatan_table',1);
INSERT INTO migrations VALUES(12,'2025_07_11_092700_create_pengeluaran_table',1);
INSERT INTO migrations VALUES(13,'2025_07_11_092700_create_uang_duduk_table',1);
INSERT INTO migrations VALUES(14,'2025_07_11_101747_add_spatie_permission_support',1);
INSERT INTO migrations VALUES(15,'2025_07_11_102432_create_audit_logs_table',1);
INSERT INTO migrations VALUES(16,'2025_07_11_121310_create_notifications_table',1);
INSERT INTO migrations VALUES(17,'2025_07_11_123000_add_input_by_to_tindakan_table',1);
INSERT INTO migrations VALUES(18,'2025_07_11_125444_add_new_fields_to_pendapatan_table',1);
INSERT INTO migrations VALUES(19,'2025_07_11_125722_update_pendapatan_table_nullable_fields',1);
INSERT INTO migrations VALUES(20,'2025_07_11_131023_add_new_fields_to_pengeluaran_table',1);
INSERT INTO migrations VALUES(21,'2025_07_11_155338_create_jenis_transaksis_table',1);
INSERT INTO migrations VALUES(22,'2025_07_11_155338_create_pendapatan_harians_table',1);
INSERT INTO migrations VALUES(23,'2025_07_11_160519_add_is_aktif_to_pendapatan_table',1);
INSERT INTO migrations VALUES(24,'2025_07_11_162113_change_pendapatan_harians_relation_to_pendapatan',1);
INSERT INTO migrations VALUES(25,'2025_07_11_163844_create_personal_access_tokens_table',1);
INSERT INTO migrations VALUES(26,'2025_07_11_163901_create_attendances_table',1);
INSERT INTO migrations VALUES(27,'2025_07_11_165219_create_user_devices_table',1);
INSERT INTO migrations VALUES(28,'2025_07_11_165455_add_device_fields_to_attendances_table',1);
INSERT INTO migrations VALUES(29,'2025_07_11_170845_create_face_recognitions_table',1);
INSERT INTO migrations VALUES(30,'2025_07_11_170902_create_absence_requests_table',1);
INSERT INTO migrations VALUES(31,'2025_07_11_171316_create_work_locations_table',1);
INSERT INTO migrations VALUES(32,'2025_07_11_225513_create_gps_spoofing_detections_table',1);
INSERT INTO migrations VALUES(33,'2025_07_11_230305_create_pegawais_table',1);
INSERT INTO migrations VALUES(34,'2025_07_11_230950_create_gps_spoofing_settings_table',1);
INSERT INTO migrations VALUES(35,'2025_07_11_233203_update_pegawais_table_make_nik_required',1);
INSERT INTO migrations VALUES(36,'2025_07_11_235240_create_employee_cards_table',1);
INSERT INTO migrations VALUES(37,'2025_07_12_001635_create_location_validations_table',1);
INSERT INTO migrations VALUES(38,'2025_07_12_005224_create_gps_spoofing_configs_table',1);
INSERT INTO migrations VALUES(39,'2025_07_12_013248_add_device_limit_settings_to_gps_spoofing_configs_table',1);
INSERT INTO migrations VALUES(40,'2025_07_12_021528_add_validation_fields_to_pendapatan_harians_table',1);
INSERT INTO migrations VALUES(41,'2025_07_12_023721_create_pengeluaran_harians_table',1);
INSERT INTO migrations VALUES(42,'2025_07_12_063012_create_kalender_kerjas_table',1);
INSERT INTO migrations VALUES(43,'2025_07_12_063016_create_cuti_pegawais_table',1);
INSERT INTO migrations VALUES(44,'2025_07_12_072713_create_dokters_table',1);
INSERT INTO migrations VALUES(45,'2025_07_12_072714_create_dokter_presensis_table',1);
INSERT INTO migrations VALUES(46,'2025_07_12_072730_create_jaspel_rekaps_table',1);
INSERT INTO migrations VALUES(47,'2025_07_12_073448_add_user_id_to_dokters_table',1);
INSERT INTO migrations VALUES(48,'2025_07_12_105719_create_shift_templates_table',1);
INSERT INTO migrations VALUES(49,'2025_07_12_105801_create_jadwal_jagas_table',1);
INSERT INTO migrations VALUES(50,'2025_07_12_105900_create_permohonan_cutis_table',1);
INSERT INTO migrations VALUES(51,'2025_07_12_113242_update_shift_templates_table',1);
INSERT INTO migrations VALUES(52,'2025_07_12_113300_update_jadwal_jagas_table_units_and_constraints',1);
INSERT INTO migrations VALUES(53,'2025_07_12_131056_add_auth_management_to_dokters_table',1);
INSERT INTO migrations VALUES(54,'2025_07_12_225550_add_username_to_users_table',1);
INSERT INTO migrations VALUES(55,'2025_07_13_000205_add_user_id_to_pegawais_table',1);
INSERT INTO migrations VALUES(56,'2025_07_13_002306_create_leave_types_table',1);
INSERT INTO migrations VALUES(57,'2025_07_13_002448_create_system_configs_table',1);
INSERT INTO migrations VALUES(58,'2025_07_13_010832_create_telegram_settings_table',1);
INSERT INTO migrations VALUES(59,'2025_07_13_012935_create_jaspel_table',1);
INSERT INTO migrations VALUES(60,'2025_07_13_075245_add_login_fields_to_pegawais_table',1);
INSERT INTO migrations VALUES(61,'2025_07_13_081253_add_user_fields_to_telegram_settings_table',1);
INSERT INTO migrations VALUES(62,'2025_07_13_082807_remove_unique_constraint_from_role_in_telegram_settings',1);
INSERT INTO migrations VALUES(63,'2025_07_13_100339_add_validation_fields_to_tindakan_table',1);
INSERT INTO migrations VALUES(64,'2025_07_13_100412_fix_foreign_keys_in_tindakan_table',1);
INSERT INTO migrations VALUES(65,'2025_07_13_100434_make_dokter_id_nullable_in_tindakan_table',1);
INSERT INTO migrations VALUES(66,'2025_07_13_140942_create_jumlah_pasien_harians_table',1);
INSERT INTO migrations VALUES(67,'2025_07_13_150636_add_status_validasi_to_jumlah_pasien_harians_table',1);
INSERT INTO migrations VALUES(68,'2025_07_14_010934_add_gps_fields_to_attendances_table',1);
INSERT INTO migrations VALUES(69,'2025_07_14_230032_create_non_paramedis_attendances_table',1);
INSERT INTO migrations VALUES(70,'2025_07_15_034855_create_refresh_tokens_table',1);
INSERT INTO migrations VALUES(71,'2025_07_15_035031_create_user_sessions_table',1);
INSERT INTO migrations VALUES(72,'2025_07_15_035100_create_biometric_templates_table',1);
INSERT INTO migrations VALUES(73,'2025_07_15_035128_add_biometric_support_to_user_devices_table',1);
INSERT INTO migrations VALUES(74,'2025_07_15_070054_add_profile_settings_to_users_table',1);
INSERT INTO migrations VALUES(75,'2025_07_15_073052_create_user_notifications_table',1);
INSERT INTO migrations VALUES(76,'2025_07_15_094706_create_feature_flags_table',1);
INSERT INTO migrations VALUES(77,'2025_07_15_094706_create_system_settings_table',1);
INSERT INTO migrations VALUES(78,'2025_07_15_094715_bridge_custom_roles_to_spatie_permission',1);
INSERT INTO migrations VALUES(79,'2025_07_15_095251_make_role_id_nullable_in_users_table',1);
INSERT INTO migrations VALUES(80,'2025_07_15_100959_create_system_metrics_table',1);
INSERT INTO migrations VALUES(81,'2025_07_15_101742_create_two_factor_auth_table',1);
INSERT INTO migrations VALUES(82,'2025_07_15_102442_create_bulk_operations_table',1);
INSERT INTO migrations VALUES(83,'2025_07_15_103339_create_reports_table',1);
INSERT INTO migrations VALUES(84,'2025_07_15_103358_create_report_executions_table',1);
INSERT INTO migrations VALUES(85,'2025_07_15_103411_create_report_shares_table',1);
INSERT INTO migrations VALUES(86,'2025_07_15_104326_create_data_imports_table',1);
INSERT INTO migrations VALUES(87,'2025_07_15_104329_create_data_exports_table',1);
INSERT INTO migrations VALUES(88,'2025_07_15_104333_create_data_transformations_table',1);
INSERT INTO migrations VALUES(89,'2025_07_15_112146_create_workflows_table',1);
INSERT INTO migrations VALUES(90,'2025_07_15_112150_create_workflow_executions_table',1);
INSERT INTO migrations VALUES(91,'2025_07_15_112153_create_quick_actions_table',1);
INSERT INTO migrations VALUES(92,'2025_07_15_154627_create_saved_searches_table',1);
INSERT INTO migrations VALUES(93,'2025_07_15_165850_add_database_indexes_for_performance',1);
INSERT INTO migrations VALUES(94,'2025_07_15_create_error_logs_table',1);
INSERT INTO migrations VALUES(95,'2025_07_15_create_performance_logs_table',1);
INSERT INTO migrations VALUES(97,'2025_07_15_231720_add_pegawai_id_to_users_table',2);
INSERT INTO migrations VALUES(98,'2025_07_16_000000_add_email_to_pegawais_table',3);
INSERT INTO migrations VALUES(99,'2025_07_11_092653_create_shifts_table',1);
INSERT INTO migrations VALUES(100,'2025_07_16_074949_create_dokter_umum_jaspels_table',4);
INSERT INTO migrations VALUES(101,'2025_07_11_092654_create_jenis_tindakan_table',1);
INSERT INTO migrations VALUES(102,'2025_07_11_092655_create_pasien_table',1);
INSERT INTO migrations VALUES(103,'2025_07_11_092656_enhance_tindakan_table_complete',1);
INSERT INTO migrations VALUES(104,'2025_07_11_092700_enhance_pendapatan_table_complete',1);
INSERT INTO migrations VALUES(105,'2025_07_11_092700_enhance_users_table_complete',1);
INSERT INTO migrations VALUES(106,'2025_07_11_163901_enhance_attendances_table_complete',1);
INSERT INTO migrations VALUES(107,'2025_07_11_230305_enhance_pegawais_table_complete',1);
INSERT INTO migrations VALUES(108,'2025_07_15_create_security_logs_table',1);
INSERT INTO migrations VALUES(109,'2025_07_16_140000_create_performance_alerts_table',1);
INSERT INTO migrations VALUES(110,'2025_07_17_111607_make_email_nullable_in_users_table',5);
INSERT INTO migrations VALUES(111,'2025_01_18_cleanup_dummy_data',6);
INSERT INTO migrations VALUES(112,'2025_07_11_092657_create_schedules_table',6);
INSERT INTO migrations VALUES(113,'2025_07_11_092659_add_shift_foreign_key_to_schedules_table',7);

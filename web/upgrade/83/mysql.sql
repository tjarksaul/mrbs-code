-- Create table for registration requests
CREATE TABLE mrbs_registration_requests
(
  id                      int NOT NULL auto_increment,
  username                varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  display_name            varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  email                   varchar(75) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  organization            varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Verein',
  role                    varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Funktion im Verein',
  password_hash           varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  email_verification_token varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  email_verified          tinyint DEFAULT 0 NOT NULL,
  email_verified_at       int DEFAULT NULL,
  approval_token          varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  approved                tinyint DEFAULT 0 NOT NULL,
  approved_at             int DEFAULT NULL,
  approved_by             varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  rejected                tinyint DEFAULT 0 NOT NULL,
  rejected_at             int DEFAULT NULL,
  rejected_by             varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  rejection_reason        text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  created_at              int NOT NULL,
  updated_at              int DEFAULT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_username (username),
  UNIQUE KEY uq_email (email),
  INDEX idx_email_verification_token (email_verification_token),
  INDEX idx_approval_token (approval_token),
  INDEX idx_email_verified (email_verified),
  INDEX idx_approved (approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

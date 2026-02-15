-- Create table for registration requests
CREATE TABLE mrbs_registration_requests
(
  id                      SERIAL PRIMARY KEY,
  username                VARCHAR(30) NOT NULL,
  display_name            VARCHAR(191) NOT NULL,
  email                   VARCHAR(75) NOT NULL,
  organization            VARCHAR(255) NOT NULL,
  role                    VARCHAR(255) NOT NULL,
  password_hash           VARCHAR(255) NOT NULL,
  email_verification_token VARCHAR(64) DEFAULT NULL,
  email_verified          SMALLINT DEFAULT 0 NOT NULL,
  email_verified_at       INTEGER DEFAULT NULL,
  approval_token          VARCHAR(64) DEFAULT NULL,
  approved                SMALLINT DEFAULT 0 NOT NULL,
  approved_at             INTEGER DEFAULT NULL,
  approved_by             VARCHAR(30) DEFAULT NULL,
  rejected                SMALLINT DEFAULT 0 NOT NULL,
  rejected_at             INTEGER DEFAULT NULL,
  rejected_by             VARCHAR(30) DEFAULT NULL,
  rejection_reason        TEXT DEFAULT NULL,
  created_at              INTEGER NOT NULL,
  updated_at              INTEGER DEFAULT NULL,

  UNIQUE(username),
  UNIQUE(email)
);

CREATE INDEX idx_email_verification_token ON mrbs_registration_requests(email_verification_token);
CREATE INDEX idx_approval_token ON mrbs_registration_requests(approval_token);
CREATE INDEX idx_email_verified ON mrbs_registration_requests(email_verified);
CREATE INDEX idx_approved ON mrbs_registration_requests(approved);

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id`       INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `user_login`    VARCHAR(30)      NOT NULL,
  `user_password` VARCHAR(255)     NOT NULL COMMENT 'pass hash (MD5/bcrypt/Argon2/scrypt)',
  `user_algo`     VARCHAR(16)      NOT NULL DEFAULT 'md5' COMMENT 'hashing algorithm',
  `user_hash`     VARCHAR(64)      NOT NULL DEFAULT '' COMMENT 'current session hash',
  `user_ip`       INT UNSIGNED     NOT NULL DEFAULT 0 COMMENT 'user IP (int)',
  `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_login` (`user_login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

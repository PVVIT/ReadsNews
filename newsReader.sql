-- =====================================================
-- DATABASE RÚT GỌN (9 bảng): web đọc tin tức thành tiếng
-- MySQL 8+ | utf8mb4 | Laravel + Vue
-- Các bảng jobs, failed_jobs, personal_access_tokens,
-- password_reset_tokens, sessions... do Laravel tự tạo
-- khi chạy `php artisan migrate`.
-- =====================================================

CREATE DATABASE IF NOT EXISTS news_reader
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE news_reader;

-- ---------- 1. users ----------
CREATE TABLE users (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name              VARCHAR(100) NOT NULL,
  email             VARCHAR(191) NOT NULL UNIQUE,
  email_verified_at TIMESTAMP NULL,
  password          VARCHAR(255) NOT NULL,
  avatar            VARCHAR(255) NULL,
  role              ENUM('user','editor','admin') NOT NULL DEFAULT 'user',
  is_active         TINYINT(1) NOT NULL DEFAULT 1,
  remember_token    VARCHAR(100) NULL,
  created_at        TIMESTAMP NULL,
  updated_at        TIMESTAMP NULL,
  deleted_at        TIMESTAMP NULL
) ENGINE=InnoDB;

-- ---------- 2. categories ----------
CREATE TABLE categories (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id   BIGINT UNSIGNED NULL,       -- danh mục cha (Thể thao -> Bóng đá)
  name        VARCHAR(100) NOT NULL,
  slug        VARCHAR(120) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NULL,
  updated_at  TIMESTAMP NULL,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- 3. sources (nguồn báo, lấy tin qua RSS) ----------
CREATE TABLE sources (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(100) NOT NULL,
  slug            VARCHAR(120) NOT NULL UNIQUE,
  website_url     VARCHAR(255) NOT NULL,
  rss_url         VARCHAR(500) NULL,
  category_id     BIGINT UNSIGNED NULL,   -- RSS này thuộc chuyên mục nào
  last_crawled_at TIMESTAMP NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      TIMESTAMP NULL,
  updated_at      TIMESTAMP NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- 4. articles ----------
CREATE TABLE articles (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_id    BIGINT UNSIGNED NULL,
  category_id  BIGINT UNSIGNED NULL,
  title        VARCHAR(500) NOT NULL,
  slug         VARCHAR(520) NOT NULL UNIQUE,
  summary      TEXT NULL,                -- mô tả ngắn từ RSS
  ai_summary   TEXT NULL,                -- tóm tắt do AI tạo (làm sau)
  content      LONGTEXT NULL,            -- nội dung hiển thị
  content_tts  LONGTEXT NULL,            -- văn bản sạch đưa vào TTS
  thumbnail    VARCHAR(500) NULL,
  original_url VARCHAR(700) NOT NULL,
  url_hash     CHAR(64) NOT NULL UNIQUE, -- sha256(original_url) chống trùng
  author       VARCHAR(150) NULL,
  word_count   INT UNSIGNED NOT NULL DEFAULT 0,
  view_count   INT UNSIGNED NOT NULL DEFAULT 0,
  listen_count INT UNSIGNED NOT NULL DEFAULT 0,
  published_at TIMESTAMP NULL,
  created_at   TIMESTAMP NULL,
  updated_at   TIMESTAMP NULL,
  FOREIGN KEY (source_id)   REFERENCES sources(id)    ON DELETE SET NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_category_time (category_id, published_at),
  INDEX idx_published (published_at),
  FULLTEXT INDEX ft_search (title, summary)
) ENGINE=InnoDB;

-- ---------- 5. voices (giọng đọc) ----------
CREATE TABLE voices (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider   VARCHAR(50) NOT NULL,        -- web_speech, google...
  code       VARCHAR(100) NOT NULL,       -- mã giọng của nhà cung cấp
  name       VARCHAR(100) NOT NULL,       -- tên hiển thị
  language   VARCHAR(10) NOT NULL DEFAULT 'vi-VN',
  gender     ENUM('male','female','neutral') NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_provider_code (provider, code)
) ENGINE=InnoDB;

-- ---------- 6. audio_files (cache audio + hàng đợi TTS) ----------
CREATE TABLE audio_files (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id       BIGINT UNSIGNED NOT NULL,
  voice_id         BIGINT UNSIGNED NOT NULL,
  speed            DECIMAL(3,2) NOT NULL DEFAULT 1.00,
  file_path        VARCHAR(500) NULL,
  duration_seconds INT UNSIGNED NULL,
  characters_count INT UNSIGNED NULL,     -- theo dõi mức dùng API
  status           ENUM('pending','processing','ready','failed') NOT NULL DEFAULT 'pending',
  error_message    TEXT NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  UNIQUE KEY uq_article_voice_speed (article_id, voice_id, speed),
  INDEX idx_status (status),
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
  FOREIGN KEY (voice_id)   REFERENCES voices(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- 7. user_settings ----------
CREATE TABLE user_settings (
  user_id          BIGINT UNSIGNED PRIMARY KEY,
  default_voice_id BIGINT UNSIGNED NULL,
  default_speed    DECIMAL(3,2) NOT NULL DEFAULT 1.00,
  autoplay_next    TINYINT(1) NOT NULL DEFAULT 1,
  voice_control_on TINYINT(1) NOT NULL DEFAULT 0,
  theme            ENUM('light','dark','system') NOT NULL DEFAULT 'system',
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  FOREIGN KEY (user_id)          REFERENCES users(id)  ON DELETE CASCADE,
  FOREIGN KEY (default_voice_id) REFERENCES voices(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- 8. bookmarks ----------
CREATE TABLE bookmarks (
  user_id    BIGINT UNSIGNED NOT NULL,
  article_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  PRIMARY KEY (user_id, article_id),
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- 9. listening_history ----------
CREATE TABLE listening_history (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          BIGINT UNSIGNED NOT NULL,
  article_id       BIGINT UNSIGNED NOT NULL,
  audio_file_id    BIGINT UNSIGNED NULL,
  progress_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  is_completed     TINYINT(1) NOT NULL DEFAULT 0,
  last_listened_at TIMESTAMP NULL,
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  UNIQUE KEY uq_user_article (user_id, article_id),
  INDEX idx_recent (user_id, last_listened_at),
  FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
  FOREIGN KEY (article_id)    REFERENCES articles(id)    ON DELETE CASCADE,
  FOREIGN KEY (audio_file_id) REFERENCES audio_files(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- DỮ LIỆU MẪU (tùy chọn, có thể bỏ qua)
-- =====================================================
INSERT INTO categories (name, slug, sort_order, created_at, updated_at) VALUES
('Tin mới nhất', 'tin-moi-nhat', 1, NOW(), NOW()),
('Thế giới',     'the-gioi',     2, NOW(), NOW()),
('Kinh doanh',   'kinh-doanh',   3, NOW(), NOW()),
('Thể thao',     'the-thao',     4, NOW(), NOW()),
('Công nghệ',    'cong-nghe',    5, NOW(), NOW());

-- Kiểm tra lại link RSS trên trang của từng báo trước khi dùng
INSERT INTO sources (name, slug, website_url, rss_url, category_id, created_at, updated_at) VALUES
('VnExpress', 'vnexpress-tin-moi', 'https://vnexpress.net', 'https://vnexpress.net/rss/tin-moi-nhat.rss', 1, NOW(), NOW()),
('Tuổi Trẻ',  'tuoitre-tin-moi',   'https://tuoitre.vn',    'https://tuoitre.vn/rss/tin-moi-nhat.rss',   1, NOW(), NOW());

INSERT INTO voices (provider, code, name, language, gender, created_at, updated_at) VALUES
('web_speech', 'default',          'Giọng trình duyệt',   'vi-VN', 'neutral', NOW(), NOW()),
('google',     'vi-VN-Wavenet-A',  'Google Nữ (WaveNet)', 'vi-VN', 'female',  NOW(), NOW()),
('google',     'vi-VN-Wavenet-B',  'Google Nam (WaveNet)','vi-VN', 'male',    NOW(), NOW()),
('google',     'vi-VN-Standard-A', 'Google Nữ (Standard)','vi-VN', 'female',  NOW(), NOW()),
('google',     'vi-VN-Standard-B', 'Google Nam (Standard)','vi-VN','male',    NOW(), NOW());

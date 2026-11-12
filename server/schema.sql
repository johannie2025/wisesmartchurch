-- Wise Design Smart Church System — panel d'abonnements (MySQL 5.7+/MariaDB 10.3+)
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plans (
  code VARCHAR(20) PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  months TINYINT NOT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  pay_url VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO plans (code,name,months,price) VALUES
 ('monthly','Mensuel',1,10),('quarterly','Trimestriel',3,27),('semiannual','Semestriel',6,50),('annual','Annuel',12,90);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NULL,
  phone VARCHAR(40) NULL,
  church VARCHAR(160) NULL,
  country VARCHAR(60) NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  license_key VARCHAR(30) NOT NULL UNIQUE,
  plan VARCHAR(20) NOT NULL,
  starts_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  status ENUM('active','suspended','cancelled') NOT NULL DEFAULT 'active',
  max_devices TINYINT NOT NULL DEFAULT 3,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sub_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(40) NULL,
  ref VARCHAR(80) NULL,
  paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sub_id) REFERENCES subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS devices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sub_id INT NOT NULL,
  device_id VARCHAR(60) NOT NULL,
  platform VARCHAR(20) NULL,
  version VARCHAR(20) NULL,
  first_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq (sub_id,device_id),
  FOREIGN KEY (sub_id) REFERENCES subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usage_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sub_id INT NOT NULL,
  device_id VARCHAR(60) NOT NULL,
  takes INT NOT NULL DEFAULT 0,
  mins INT NOT NULL DEFAULT 0,
  sessions INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sub_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
  INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== v2 : paramètres, IA, témoignages, demandes, téléchargements =====
CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(60) PRIMARY KEY,
  v TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO settings (k,v) VALUES ('ai_enabled','1'),('ai_daily_limit','300'),('whatsapp',''),('contact_email',''),('download_requires','register');

CREATE TABLE IF NOT EXISTS ai_providers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  type ENUM('gemini','openai') NOT NULL DEFAULT 'openai',
  endpoint VARCHAR(255) NOT NULL,
  api_key TEXT NULL,
  free_only TINYINT NOT NULL DEFAULT 0,
  enabled TINYINT NOT NULL DEFAULT 1,
  priority INT NOT NULL DEFAULT 10,
  last_error VARCHAR(255) NULL,
  models_fetched_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_models (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider_id INT NOT NULL,
  model VARCHAR(160) NOT NULL,
  enabled TINYINT NOT NULL DEFAULT 1,
  audio TINYINT NOT NULL DEFAULT 0,
  priority INT NOT NULL DEFAULT 10,
  cooldown_until DATETIME NULL,
  fails INT NOT NULL DEFAULT 0,
  ok_count INT NOT NULL DEFAULT 0,
  last_used DATETIME NULL,
  UNIQUE KEY uq (provider_id,model),
  FOREIGN KEY (provider_id) REFERENCES ai_providers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sub_id INT NOT NULL,
  model VARCHAR(160) NULL,
  ok TINYINT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (sub_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS testimonials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  role VARCHAR(160) NULL,
  church VARCHAR(160) NULL,
  body TEXT NOT NULL,
  approved TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NULL,
  phone VARCHAR(40) NULL,
  plan VARCHAR(20) NULL,
  message TEXT NULL,
  status ENUM('new','contacted','done') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS downloads (
  platform VARCHAR(20) PRIMARY KEY,
  label VARCHAR(80) NOT NULL,
  filename VARCHAR(160) NULL,
  version VARCHAR(20) NULL,
  external_url VARCHAR(255) NULL,
  need_license TINYINT NOT NULL DEFAULT 0,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO downloads (platform,label) VALUES ('exe','Régie Windows (.exe)'),('apk','Android téléphone / TV (.apk)'),('mac','Régie macOS (.dmg)'),('ios','iPhone / iPad (web app)');

CREATE TABLE IF NOT EXISTS download_tokens (
  token CHAR(40) PRIMARY KEY,
  platform VARCHAR(20) NOT NULL,
  email VARCHAR(160) NULL,
  uses TINYINT NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS downloads_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  platform VARCHAR(20) NOT NULL,
  email VARCHAR(160) NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO ai_providers (id,name,type,endpoint,free_only,priority) VALUES
 (1,'Google Gemini','gemini','https://generativelanguage.googleapis.com/v1beta',1,1),
 (2,'OpenRouter (modèles :free)','openai','https://openrouter.ai/api/v1',1,2),
 (3,'Groq','openai','https://api.groq.com/openai/v1',0,3),
 (4,'Cerebras','openai','https://api.cerebras.ai/v1',0,4),
 (5,'Mistral','openai','https://api.mistral.ai/v1',0,5);

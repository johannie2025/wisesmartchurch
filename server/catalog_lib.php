<?php
/* Crée les tables catalogue / mises à jour si elles n'existent pas (installations existantes : pas besoin de rejouer schema.sql). */
function ensure_catalog_tables(): void {
    q("CREATE TABLE IF NOT EXISTS catalog (id INT AUTO_INCREMENT PRIMARY KEY, kind ENUM('bible','background','studio','hymns','hyperframe') NOT NULL, title VARCHAR(160) NOT NULL, lang VARCHAR(5) NULL, url VARCHAR(500) NOT NULL, info VARCHAR(255) NULL, active TINYINT NOT NULL DEFAULT 1, sort INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (kind, active)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try { q("ALTER TABLE catalog MODIFY kind ENUM('bible','background','studio','hymns','hyperframe') NOT NULL"); } catch (Throwable $e) {}
    q("CREATE TABLE IF NOT EXISTS releases (id INT AUTO_INCREMENT PRIMARY KEY, platform VARCHAR(20) NOT NULL, version VARCHAR(20) NOT NULL, notes_fr TEXT NULL, notes_es TEXT NULL, notes_en TEXT NULL, mandatory TINYINT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (platform, id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

-- =========================================================
-- VITEGOURMAND - FULL SCHEMA + SEED (MySQL 8.0)
-- =========================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- (Opcional) cria DB
CREATE DATABASE IF NOT EXISTS vitegourmand
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE vitegourmand;

-- Evitar problemas com FK durante criação
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
-- 1) ROLES (optional table, even if Symfony uses json roles)
-- =========================================================
DROP TABLE IF EXISTS roles;
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  label VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT INTO roles (code, label) VALUES
('ROLE_USER', 'Utilisateur'),
('ROLE_EMPLOYEE', 'Employé'),
('ROLE_ADMIN', 'Administrateur');

-- =========================================================
-- 2) USERS
-- =========================================================
DROP TABLE IF EXISTS user;
CREATE TABLE user (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(180) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  roles JSON NOT NULL,
  google_id VARCHAR(255) NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  rgpd_consent TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL DEFAULT NULL,
  INDEX idx_user_active (is_active),
  INDEX idx_user_created (created_at)
) ENGINE=InnoDB;

-- ✅ seed contas (passwords: mete hash real depois)
INSERT INTO user (email, first_name, last_name, roles, google_id, password, is_active, rgpd_consent)
VALUES
('admin@restaurant.test', 'Admin', 'Restaurant', JSON_ARRAY('ROLE_ADMIN'), NULL, 'CHANGE_ME_HASH_ADMIN', 1, 1),
('employee@restaurant.test', 'Employee', 'Restaurant', JSON_ARRAY('ROLE_EMPLOYEE'), NULL, 'CHANGE_ME_HASH_EMP', 1, 1),
('user@restaurant.test', 'User', 'Restaurant', JSON_ARRAY('ROLE_USER'), NULL, 'CHANGE_ME_HASH_USER', 1, 1);

-- =========================================================
-- 3) ALLERGENES
-- =========================================================
DROP TABLE IF EXISTS allergene;
CREATE TABLE allergene (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO allergene (name) VALUES
('Gluten'), ('Crustacés'), ('Œufs'), ('Poisson'), ('Arachides'),
('Soja'), ('Lait'), ('Fruits à coque'), ('Céleri'), ('Moutarde'),
('Sésame'), ('Sulfites'), ('Lupin'), ('Mollusques');

-- =========================================================
-- 4) MENUS
-- =========================================================
DROP TABLE IF EXISTS menu;
CREATE TABLE menu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  INDEX idx_menu_active (is_active),
  INDEX idx_menu_created (created_at)
) ENGINE=InnoDB;

-- =========================================================
-- 5) PLATS
-- =========================================================
DROP TABLE IF EXISTS plat;
CREATE TABLE plat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  menu_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  CONSTRAINT fk_plat_menu FOREIGN KEY (menu_id) REFERENCES menu(id) ON DELETE CASCADE,
  INDEX idx_plat_menu (menu_id),
  INDEX idx_plat_active (is_active)
) ENGINE=InnoDB;

-- =========================================================
-- 6) PLAT_ALLERGENES (many-to-many)
-- =========================================================
DROP TABLE IF EXISTS plat_allergene;
CREATE TABLE plat_allergene (
  plat_id INT NOT NULL,
  allergene_id INT NOT NULL,
  PRIMARY KEY (plat_id, allergene_id),
  CONSTRAINT fk_pa_plat FOREIGN KEY (plat_id) REFERENCES plat(id) ON DELETE CASCADE,
  CONSTRAINT fk_pa_allergene FOREIGN KEY (allergene_id) REFERENCES allergene(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
-- 7) HORAIRES (opening hours)
-- =========================================================
DROP TABLE IF EXISTS horaire;
CREATE TABLE horaire (
  id INT AUTO_INCREMENT PRIMARY KEY,
  day_of_week TINYINT NOT NULL, -- 1=Mon ... 7=Sun
  open_time TIME NULL,
  close_time TIME NULL,
  is_closed TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uniq_day (day_of_week)
) ENGINE=InnoDB;

INSERT INTO horaire (day_of_week, open_time, close_time, is_closed) VALUES
(1, '11:30:00', '22:30:00', 0),
(2, '11:30:00', '22:30:00', 0),
(3, '11:30:00', '22:30:00', 0),
(4, '11:30:00', '22:30:00', 0),
(5, '11:30:00', '23:00:00', 0),
(6, '11:30:00', '23:00:00', 0),
(7, '12:00:00', '22:00:00', 0);

-- =========================================================
-- 8) ETATS DE COMMANDE
-- =========================================================
DROP TABLE IF EXISTS commande_status;
CREATE TABLE commande_status (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  label VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT INTO commande_status (code, label) VALUES
('PENDING', 'En attente'),
('ACCEPTED', 'Acceptée'),
('REFUSED', 'Refusée'),
('PREPARING', 'En préparation'),
('READY', 'Prête'),
('DELIVERING', 'En livraison'),
('DELIVERED', 'Livrée'),
('CANCELLED', 'Annulée');

-- =========================================================
-- 9) COMMANDES
-- =========================================================
DROP TABLE IF EXISTS commande;
CREATE TABLE commande (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  status_id INT NOT NULL,
  delivery_address VARCHAR(255) NULL,
  delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00, -- ex: 10.00
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  cancel_reason VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL,
  CONSTRAINT fk_commande_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE RESTRICT,
  CONSTRAINT fk_commande_status FOREIGN KEY (status_id) REFERENCES commande_status(id) ON DELETE RESTRICT,
  INDEX idx_commande_user (user_id),
  INDEX idx_commande_status (status_id),
  INDEX idx_commande_created (created_at)
) ENGINE=InnoDB;

-- =========================================================
-- 10) COMMANDE_ITEMS (plats in a commande)
-- =========================================================
DROP TABLE IF EXISTS commande_item;
CREATE TABLE commande_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  commande_id INT NOT NULL,
  plat_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT fk_ci_commande FOREIGN KEY (commande_id) REFERENCES commande(id) ON DELETE CASCADE,
  CONSTRAINT fk_ci_plat FOREIGN KEY (plat_id) REFERENCES plat(id) ON DELETE RESTRICT,
  INDEX idx_ci_commande (commande_id),
  INDEX idx_ci_plat (plat_id)
) ENGINE=InnoDB;

-- =========================================================
-- 11) AVIS (reviews)
-- =========================================================
DROP TABLE IF EXISTS avis;
CREATE TABLE avis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  note TINYINT NOT NULL,
  comment TEXT NULL,
  is_validated TINYINT(1) NOT NULL DEFAULT 0,
  validated_by INT NULL,
  validated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_avis_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
  CONSTRAINT fk_avis_validated_by FOREIGN KEY (validated_by) REFERENCES user(id) ON DELETE SET NULL,
  CONSTRAINT chk_avis_note CHECK (note BETWEEN 1 AND 5),
  INDEX idx_avis_valid (is_validated),
  INDEX idx_avis_created (created_at)
) ENGINE=InnoDB;

-- =========================================================
-- 12) CONTACT MESSAGES (optional but useful)
-- =========================================================
DROP TABLE IF EXISTS contact_message;
CREATE TABLE contact_message (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(180) NOT NULL,
  subject VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_contact_created (created_at)
) ENGINE=InnoDB;

-- =========================================================
-- SEED MENUS / PLATS EXEMPLO
-- =========================================================
INSERT INTO menu (name, description, base_price, is_active) VALUES
('Menu Déjeuner', 'Entrée + Plat + Dessert', 19.90, 1),
('Menu Vegan', '100% végétal', 21.90, 1),
('Menu Gourmet', 'Expérience premium', 29.90, 1);

INSERT INTO plat (menu_id, name, description, price, is_active) VALUES
(1, 'Salade fraîche', 'Salade de saison', 6.50, 1),
(1, 'Poulet rôti', 'Poulet et pommes', 12.00, 1),
(1, 'Mousse chocolat', 'Chocolat noir', 5.50, 1),
(2, 'Bowl quinoa', 'Quinoa, légumes, sauce', 11.50, 1),
(2, 'Soupe du jour', 'Légumes mixés', 6.00, 1),
(3, 'Saumon snacké', 'Sauce citron', 16.90, 1),
(3, 'Tarte maison', 'Dessert du chef', 7.50, 1);

-- ligar alguns alergénios (exemplo)
-- mousse chocolat -> lait
INSERT INTO plat_allergene (plat_id, allergene_id)
SELECT p.id, a.id FROM plat p, allergene a
WHERE p.name='Mousse chocolat' AND a.name='Lait';

SET FOREIGN_KEY_CHECKS = 1;

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203201550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE password_reset_request (id INT AUTO_INCREMENT NOT NULL, token_hash VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_C5D0A95AB3BC57DA (token_hash), INDEX IDX_C5D0A95AA76ED395 (user_id), INDEX idx_password_reset_expires_at (expires_at), INDEX idx_password_reset_created_at (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) NOT NULL, date DATETIME NOT NULL, people INT NOT NULL, message LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE restaurant (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, address VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE password_reset_request ADD CONSTRAINT FK_C5D0A95AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY `fk_avis_user`');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY `fk_avis_validated_by`');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY `fk_commande_status`');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY `fk_commande_user`');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY `fk_ci_commande`');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY `fk_ci_plat`');
        $this->addSql('ALTER TABLE plat DROP FOREIGN KEY `fk_plat_menu`');
        $this->addSql('ALTER TABLE plat_allergene DROP FOREIGN KEY `fk_pa_allergene`');
        $this->addSql('ALTER TABLE plat_allergene DROP FOREIGN KEY `fk_pa_plat`');
        $this->addSql('DROP TABLE allergene');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_item');
        $this->addSql('DROP TABLE commande_status');
        $this->addSql('DROP TABLE contact_message');
        $this->addSql('DROP TABLE horaire');
        $this->addSql('DROP TABLE menu');
        $this->addSql('DROP TABLE plat');
        $this->addSql('DROP TABLE plat_allergene');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP INDEX idx_user_created ON user');
        $this->addSql('DROP INDEX idx_user_active ON user');
        $this->addSql('ALTER TABLE user DROP is_active, DROP rgpd_consent, DROP created_at, DROP deleted_at');
        $this->addSql('ALTER TABLE user RENAME INDEX google_id TO UNIQ_8D93D64976F5C865');
        $this->addSql('ALTER TABLE user RENAME INDEX email TO uniq_user_email');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE allergene (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, UNIQUE INDEX name (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, note TINYINT NOT NULL, comment TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, is_validated TINYINT DEFAULT 0 NOT NULL, validated_by INT DEFAULT NULL, validated_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_avis_created (created_at), INDEX idx_avis_valid (is_validated), INDEX fk_avis_validated_by (validated_by), INDEX fk_avis_user (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, status_id INT NOT NULL, delivery_address VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, delivery_fee NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, discount_rate NUMERIC(5, 2) DEFAULT \'0.00\' NOT NULL, total_amount NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, cancel_reason VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_commande_created (created_at), INDEX idx_commande_status (status_id), INDEX idx_commande_user (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commande_item (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, plat_id INT NOT NULL, quantity INT DEFAULT 1 NOT NULL, unit_price NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, line_total NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, INDEX idx_ci_plat (plat_id), INDEX idx_ci_commande (commande_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commande_status (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, label VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, UNIQUE INDEX code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE contact_message (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, subject VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, message TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_contact_created (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE horaire (id INT AUTO_INCREMENT NOT NULL, day_of_week TINYINT NOT NULL, open_time TIME DEFAULT NULL, close_time TIME DEFAULT NULL, is_closed TINYINT DEFAULT 0 NOT NULL, UNIQUE INDEX uniq_day (day_of_week), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE menu (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, base_price NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_menu_created (created_at), INDEX idx_menu_active (is_active), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE plat (id INT AUTO_INCREMENT NOT NULL, menu_id INT NOT NULL, name VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, price NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_plat_active (is_active), INDEX idx_plat_menu (menu_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE plat_allergene (plat_id INT NOT NULL, allergene_id INT NOT NULL, INDEX fk_pa_allergene (allergene_id), INDEX IDX_6FA44BBFD73DB560 (plat_id), PRIMARY KEY (plat_id, allergene_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE roles (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, label VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, UNIQUE INDEX code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT `fk_avis_user` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT `fk_avis_validated_by` FOREIGN KEY (validated_by) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT `fk_commande_status` FOREIGN KEY (status_id) REFERENCES commande_status (id) ON UPDATE NO ACTION');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT `fk_commande_user` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT `fk_ci_commande` FOREIGN KEY (commande_id) REFERENCES commande (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT `fk_ci_plat` FOREIGN KEY (plat_id) REFERENCES plat (id) ON UPDATE NO ACTION');
        $this->addSql('ALTER TABLE plat ADD CONSTRAINT `fk_plat_menu` FOREIGN KEY (menu_id) REFERENCES menu (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plat_allergene ADD CONSTRAINT `fk_pa_allergene` FOREIGN KEY (allergene_id) REFERENCES allergene (id) ON UPDATE NO ACTION');
        $this->addSql('ALTER TABLE plat_allergene ADD CONSTRAINT `fk_pa_plat` FOREIGN KEY (plat_id) REFERENCES plat (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE password_reset_request DROP FOREIGN KEY FK_C5D0A95AA76ED395');
        $this->addSql('DROP TABLE password_reset_request');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE restaurant');
        $this->addSql('ALTER TABLE `user` ADD is_active TINYINT DEFAULT 1 NOT NULL, ADD rgpd_consent TINYINT DEFAULT 0 NOT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_user_created ON `user` (created_at)');
        $this->addSql('CREATE INDEX idx_user_active ON `user` (is_active)');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_8d93d64976f5c865 TO google_id');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_user_email TO email');
    }
}

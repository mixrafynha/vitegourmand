<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217194906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE allergen (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_25BF08CE5E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, note INT NOT NULL, comment LONGTEXT DEFAULT NULL, is_validated TINYINT NOT NULL, validated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, validated_by_id INT DEFAULT NULL, INDEX IDX_8F91ABF0A76ED395 (user_id), INDEX IDX_8F91ABF0C69DE5E5 (validated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE cart_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, user_id INT NOT NULL, menu_id INT NOT NULL, INDEX IDX_F0FE2527A76ED395 (user_id), INDEX IDX_F0FE2527CCD7E912 (menu_id), UNIQUE INDEX uniq_user_menu (user_id, menu_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, delivery_address VARCHAR(255) DEFAULT NULL, delivery_fee NUMERIC(10, 2) NOT NULL, discount_rate NUMERIC(5, 2) NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, cancel_reason VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, payment_provider VARCHAR(30) DEFAULT NULL, payment_session_id VARCHAR(255) DEFAULT NULL, payment_intent_id VARCHAR(255) DEFAULT NULL, idempotency_key VARCHAR(255) DEFAULT NULL, payment_status VARCHAR(30) DEFAULT NULL, paid_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_6EEAA67D54C96D0A (payment_session_id), UNIQUE INDEX UNIQ_6EEAA67D8BC036E4 (payment_intent_id), UNIQUE INDEX UNIQ_6EEAA67D7FD1C147 (idempotency_key), INDEX IDX_6EEAA67DA76ED395 (user_id), INDEX idx_commande_status (status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE commande_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, line_total NUMERIC(10, 2) NOT NULL, commande_id INT NOT NULL, plat_id INT NOT NULL, INDEX IDX_747724FD82EA2E54 (commande_id), INDEX IDX_747724FDD73DB560 (plat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE menu (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, base_price NUMERIC(10, 2) NOT NULL, stock INT DEFAULT 0 NOT NULL, reserved INT DEFAULT 0 NOT NULL, image_url VARCHAR(255) DEFAULT NULL, extra_images JSON DEFAULT NULL, ingredients LONGTEXT DEFAULT NULL, allergens LONGTEXT DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE password_reset_request (id INT AUTO_INCREMENT NOT NULL, token_hash VARCHAR(64) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_C5D0A95AA76ED395 (user_id), INDEX idx_prr_expires (expires_at), INDEX idx_prr_token_hash (token_hash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE plat (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, stock INT DEFAULT 0 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, menu_id INT NOT NULL, INDEX IDX_2038A207CCD7E912 (menu_id), INDEX idx_plat_active (is_active), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE plat_allergen (plat_id INT NOT NULL, allergen_id INT NOT NULL, INDEX IDX_1B562E95D73DB560 (plat_id), INDEX IDX_1B562E956E775A4A (allergen_id), PRIMARY KEY (plat_id, allergen_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) NOT NULL, date DATETIME NOT NULL, people INT NOT NULL, message LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE restaurant (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, address VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, label VARCHAR(100) DEFAULT NULL, UNIQUE INDEX uniq_role_code (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, roles JSON NOT NULL, google_id VARCHAR(255) DEFAULT NULL, is_verified TINYINT NOT NULL, email_verification_token VARCHAR(64) DEFAULT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D64976F5C865 (google_id), UNIQUE INDEX UNIQ_8D93D649C4995C67 (email_verification_token), UNIQUE INDEX uniq_user_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user_role (user_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_2DE8C6A3A76ED395 (user_id), INDEX IDX_2DE8C6A3D60322AC (role_id), PRIMARY KEY (user_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE2527A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE2527CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FD82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FDD73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id)');
        $this->addSql('ALTER TABLE password_reset_request ADD CONSTRAINT FK_C5D0A95AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plat ADD CONSTRAINT FK_2038A207CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('ALTER TABLE plat_allergen ADD CONSTRAINT FK_1B562E95D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plat_allergen ADD CONSTRAINT FK_1B562E956E775A4A FOREIGN KEY (allergen_id) REFERENCES allergen (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0A76ED395');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0C69DE5E5');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE2527A76ED395');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE2527CCD7E912');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FD82EA2E54');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FDD73DB560');
        $this->addSql('ALTER TABLE password_reset_request DROP FOREIGN KEY FK_C5D0A95AA76ED395');
        $this->addSql('ALTER TABLE plat DROP FOREIGN KEY FK_2038A207CCD7E912');
        $this->addSql('ALTER TABLE plat_allergen DROP FOREIGN KEY FK_1B562E95D73DB560');
        $this->addSql('ALTER TABLE plat_allergen DROP FOREIGN KEY FK_1B562E956E775A4A');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3A76ED395');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3D60322AC');
        $this->addSql('DROP TABLE allergen');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_item');
        $this->addSql('DROP TABLE menu');
        $this->addSql('DROP TABLE password_reset_request');
        $this->addSql('DROP TABLE plat');
        $this->addSql('DROP TABLE plat_allergen');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE restaurant');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_role');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206140806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu ADD image_url VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME DEFAULT NULL, CHANGE name name VARCHAR(120) NOT NULL, CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL, CHANGE base_price price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE role RENAME INDEX uniq_57698a6a77153098 TO uniq_role_code');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu DROP image_url, DROP created_at, DROP updated_at, CHANGE name name VARCHAR(150) NOT NULL, CHANGE is_active is_active TINYINT NOT NULL, CHANGE price base_price NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE role RENAME INDEX uniq_role_code TO UNIQ_57698A6A77153098');
    }
}

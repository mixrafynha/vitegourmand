<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204205518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_password_reset_created_at ON password_reset_request');
        $this->addSql('ALTER TABLE password_reset_request DROP ip, DROP user_agent');
        $this->addSql('CREATE INDEX idx_prr_token_hash ON password_reset_request (token_hash)');
        $this->addSql('ALTER TABLE password_reset_request RENAME INDEX idx_password_reset_expires_at TO idx_prr_expires_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_prr_token_hash ON password_reset_request');
        $this->addSql('ALTER TABLE password_reset_request ADD ip VARCHAR(45) DEFAULT NULL, ADD user_agent VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_password_reset_created_at ON password_reset_request (created_at)');
        $this->addSql('ALTER TABLE password_reset_request RENAME INDEX idx_prr_expires_at TO idx_password_reset_expires_at');
    }
}

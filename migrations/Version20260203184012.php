<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203184012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD is_active TINYINT DEFAULT 1 NOT NULL, ADD rgpd_consent TINYINT DEFAULT 0 NOT NULL, ADD created_at DATETIME NOT NULL, ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d64976f5c865 TO uniq_user_google_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP is_active, DROP rgpd_consent, DROP created_at, DROP deleted_at');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_user_google_id TO UNIQ_8D93D64976F5C865');
    }
}

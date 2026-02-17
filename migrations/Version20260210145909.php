<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210145909 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande ADD payment_provider VARCHAR(30) DEFAULT NULL, ADD payment_session_id VARCHAR(255) DEFAULT NULL, ADD payment_intent_id VARCHAR(255) DEFAULT NULL, ADD idempotency_key VARCHAR(255) DEFAULT NULL, ADD payment_status VARCHAR(30) DEFAULT NULL, ADD paid_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6EEAA67D54C96D0A ON commande (payment_session_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6EEAA67D8BC036E4 ON commande (payment_intent_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6EEAA67D7FD1C147 ON commande (idempotency_key)');
        $this->addSql('CREATE INDEX idx_commande_status ON commande (status)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_6EEAA67D54C96D0A ON commande');
        $this->addSql('DROP INDEX UNIQ_6EEAA67D8BC036E4 ON commande');
        $this->addSql('DROP INDEX UNIQ_6EEAA67D7FD1C147 ON commande');
        $this->addSql('DROP INDEX idx_commande_status ON commande');
        $this->addSql('ALTER TABLE commande DROP payment_provider, DROP payment_session_id, DROP payment_intent_id, DROP idempotency_key, DROP payment_status, DROP paid_at');
    }
}

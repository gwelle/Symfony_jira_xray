<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250926204123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE last_activation_token_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE last_activation_token (id INT NOT NULL, account_id INT NOT NULL, hashed_token VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expired_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_978709879B6B5FBA ON last_activation_token (account_id)');
        $this->addSql('COMMENT ON COLUMN last_activation_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN last_activation_token.expired_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE last_activation_token ADD CONSTRAINT FK_978709879B6B5FBA FOREIGN KEY (account_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE last_activation_token_id_seq CASCADE');
        $this->addSql('ALTER TABLE last_activation_token DROP CONSTRAINT FK_978709879B6B5FBA');
        $this->addSql('DROP TABLE last_activation_token');
    }
}

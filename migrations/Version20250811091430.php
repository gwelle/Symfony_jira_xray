<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250811091430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE activation_token_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE activation_token (id INT NOT NULL, account_id INT DEFAULT NULL, token VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expired_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B1B4826B5F37A13B ON activation_token (token)');
        $this->addSql('CREATE INDEX IDX_B1B4826B9B6B5FBA ON activation_token (account_id)');
        $this->addSql('COMMENT ON COLUMN activation_token.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN activation_token.expired_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE activation_token ADD CONSTRAINT FK_B1B4826B9B6B5FBA FOREIGN KEY (account_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE activation_token_id_seq CASCADE');
        $this->addSql('ALTER TABLE activation_token DROP CONSTRAINT FK_B1B4826B9B6B5FBA');
        $this->addSql('DROP TABLE activation_token');
    }
}

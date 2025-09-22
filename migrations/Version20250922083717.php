<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250922083717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ALTER is_resend SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER resend_count SET DEFAULT 0');
        $this->addSql('ALTER TABLE "user" ALTER resend_count SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" ALTER is_resend DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER resend_count DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER resend_count DROP NOT NULL');
    }
}

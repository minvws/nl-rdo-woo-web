<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522132622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('COMMENT ON COLUMN "user".mfa_token IS \'(DC2Type:encrypted_string)\'');
        $this->addSql('COMMENT ON COLUMN "user".mfa_recovery IS \'(DC2Type:encrypted_array)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('COMMENT ON COLUMN "user".mfa_token IS NULL');
        $this->addSql('COMMENT ON COLUMN "user".mfa_recovery IS NULL');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230524133919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE woo_request RENAME TO inquiry');
        $this->addSql('ALTER TABLE woo_request_document RENAME TO inquiry_document');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inquiry RENAME TO woo_request');
        $this->addSql('ALTER TABLE inquiry_document RENAME TO woo_request_document');
    }
}

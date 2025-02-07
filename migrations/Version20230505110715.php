<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230505110715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document ALTER dossier_id SET NOT NULL');
        $this->addSql('ALTER TABLE document ALTER summary DROP NOT NULL');
        $this->addSql('ALTER TABLE document ALTER title DROP NOT NULL');
        $this->addSql('ALTER TABLE dossier DROP hash');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document ALTER dossier_id DROP NOT NULL');
        $this->addSql('ALTER TABLE document ALTER summary SET NOT NULL');
        $this->addSql('ALTER TABLE document ALTER title SET NOT NULL');
        $this->addSql('ALTER TABLE dossier ADD hash VARCHAR(100) NOT NULL');
    }
}

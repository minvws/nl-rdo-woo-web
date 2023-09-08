<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230814144216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE decision_document DROP CONSTRAINT FK_55B54548611C0C56');
        $this->addSql('ALTER TABLE decision_document ADD CONSTRAINT FK_55B54548611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document ALTER file_name DROP NOT NULL');
        $this->addSql('ALTER TABLE document ALTER file_source_type DROP NOT NULL');
        $this->addSql('ALTER TABLE inventory DROP CONSTRAINT FK_B12D4A36611C0C56');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A36611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE decision_document DROP CONSTRAINT fk_55b54548611c0c56');
        $this->addSql('ALTER TABLE decision_document ADD CONSTRAINT fk_55b54548611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document ALTER file_name SET NOT NULL');
        $this->addSql('ALTER TABLE document ALTER file_source_type SET NOT NULL');
        $this->addSql('ALTER TABLE inventory DROP CONSTRAINT fk_b12d4a36611c0c56');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT fk_b12d4a36611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}

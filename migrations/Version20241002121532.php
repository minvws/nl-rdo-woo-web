<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241002121532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_dossier DROP CONSTRAINT FK_992746EBC33F7837');
        $this->addSql('ALTER TABLE document_dossier DROP CONSTRAINT FK_992746EB611C0C56');
        $this->addSql('ALTER TABLE document_dossier ADD CONSTRAINT FK_992746EBC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document_dossier ADD CONSTRAINT FK_992746EB611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE inquiry_dossier DROP CONSTRAINT FK_D6558E92A7AD6D71');
        $this->addSql('ALTER TABLE inquiry_dossier DROP CONSTRAINT FK_D6558E92611C0C56');
        $this->addSql('ALTER TABLE inquiry_dossier ADD CONSTRAINT FK_D6558E92A7AD6D71 FOREIGN KEY (inquiry_id) REFERENCES inquiry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE inquiry_dossier ADD CONSTRAINT FK_D6558E92611C0C56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_dossier DROP CONSTRAINT fk_992746ebc33f7837');
        $this->addSql('ALTER TABLE document_dossier DROP CONSTRAINT fk_992746eb611c0c56');
        $this->addSql('ALTER TABLE document_dossier ADD CONSTRAINT fk_992746ebc33f7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document_dossier ADD CONSTRAINT fk_992746eb611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE inquiry_dossier DROP CONSTRAINT fk_d6558e92a7ad6d71');
        $this->addSql('ALTER TABLE inquiry_dossier DROP CONSTRAINT fk_d6558e92611c0c56');
        $this->addSql('ALTER TABLE inquiry_dossier ADD CONSTRAINT fk_d6558e92a7ad6d71 FOREIGN KEY (inquiry_id) REFERENCES inquiry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE inquiry_dossier ADD CONSTRAINT fk_d6558e92611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}

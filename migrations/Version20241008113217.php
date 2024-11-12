<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241008113217 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_dossier DROP CONSTRAINT fk_992746eb611c0c56');
        $this->addSql('DROP INDEX idx_992746eb611c0c56');
        $this->addSql('ALTER TABLE document_dossier DROP CONSTRAINT document_dossier_pkey');
        $this->addSql('ALTER TABLE document_dossier RENAME COLUMN dossier_id TO woo_decision_id');
        $this->addSql('ALTER TABLE document_dossier ADD CONSTRAINT FK_992746EB21C2E34D FOREIGN KEY (woo_decision_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_992746EB21C2E34D ON document_dossier (woo_decision_id)');
        $this->addSql('ALTER TABLE document_dossier ADD PRIMARY KEY (document_id, woo_decision_id)');
        $this->addSql('ALTER TABLE inquiry_dossier DROP CONSTRAINT fk_d6558e92611c0c56');
        $this->addSql('DROP INDEX idx_d6558e92611c0c56');
        $this->addSql('ALTER TABLE inquiry_dossier DROP CONSTRAINT inquiry_dossier_pkey');
        $this->addSql('ALTER TABLE inquiry_dossier RENAME COLUMN dossier_id TO woo_decision_id');
        $this->addSql('ALTER TABLE inquiry_dossier ADD CONSTRAINT FK_D6558E9221C2E34D FOREIGN KEY (woo_decision_id) REFERENCES dossier (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D6558E9221C2E34D ON inquiry_dossier (woo_decision_id)');
        $this->addSql('ALTER TABLE inquiry_dossier ADD PRIMARY KEY (inquiry_id, woo_decision_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inquiry_dossier DROP CONSTRAINT FK_D6558E9221C2E34D');
        $this->addSql('DROP INDEX IDX_D6558E9221C2E34D');
        $this->addSql('DROP INDEX inquiry_dossier_pkey');
        $this->addSql('ALTER TABLE inquiry_dossier RENAME COLUMN woo_decision_id TO dossier_id');
        $this->addSql('ALTER TABLE inquiry_dossier ADD CONSTRAINT fk_d6558e92611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_d6558e92611c0c56 ON inquiry_dossier (dossier_id)');
        $this->addSql('ALTER TABLE inquiry_dossier ADD PRIMARY KEY (inquiry_id, dossier_id)');
        $this->addSql('ALTER TABLE document_dossier DROP CONSTRAINT FK_992746EB21C2E34D');
        $this->addSql('DROP INDEX IDX_992746EB21C2E34D');
        $this->addSql('DROP INDEX document_dossier_pkey');
        $this->addSql('ALTER TABLE document_dossier RENAME COLUMN woo_decision_id TO dossier_id');
        $this->addSql('ALTER TABLE document_dossier ADD CONSTRAINT fk_992746eb611c0c56 FOREIGN KEY (dossier_id) REFERENCES dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_992746eb611c0c56 ON document_dossier (dossier_id)');
        $this->addSql('ALTER TABLE document_dossier ADD PRIMARY KEY (document_id, dossier_id)');
    }
}

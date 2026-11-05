<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261013140302 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_795FD9BB611C0C5643625D9FBF396750 ON attachment (dossier_id, updated_at, id)');
        $this->addSql('CREATE INDEX IDX_F3F4EC1024CD504D ON batch_download (expiration)');
        $this->addSql('CREATE INDEX IDX_D8698A76C35E566A ON document (family_id)');
        $this->addSql('CREATE INDEX IDX_D8698A76C33F7837 ON document (document_id)');
        $this->addSql('CREATE INDEX IDX_D8698A76E2904019 ON document (thread_id)');
        $this->addSql('CREATE INDEX IDX_3D48E0379E6B15857B00651C8B8E8428 ON dossier (organisation_id, status, created_at)');
        $this->addSql('CREATE INDEX IDX_3D48E0379E6B1585ED18940B ON dossier (organisation_id, decision_date)');
        $this->addSql('CREATE INDEX IDX_3D48E0379E6B15853AF85C6E ON dossier (organisation_id, completed)');
        $this->addSql('CREATE INDEX IDX_3D48E0377B00651CA166B6B7 ON dossier (status, publication_date)');
        $this->addSql('CREATE INDEX IDX_3D48E0377B00651C43625D9FBF396750 ON dossier (status, updated_at, id)');
        $this->addSql('DROP INDEX idx_27ba704b8cde5729772e836a');
        $this->addSql('CREATE INDEX IDX_27BA704B8CDE5729772E836AF6F9706DBF396750 ON history (type, identifier, created_dt, id)');
        $this->addSql('CREATE INDEX IDX_5A3903F09E6B158585C88591 ON inquiry (organisation_id, inquiry_number)');
        $this->addSql('CREATE INDEX IDX_5A3903F09E6B158543625D9F ON inquiry (organisation_id, updated_at)');
        $this->addSql('CREATE INDEX IDX_D8900FF77B00651CD46F4E3 ON production_report_process_run (status, started_at)');
        $this->addSql('CREATE INDEX IDX_FBCE3E7AB751E0AD5E237E06 ON subject (landing_page_status, name)');
        $this->addSql('CREATE INDEX IDX_FBCE3E7A9E6B15855E237E06 ON subject (organisation_id, name)');
        $this->addSql('CREATE INDEX IDX_17BDE61FCCCFBA31 ON upload (upload_id)');
        $this->addSql('CREATE INDEX IDX_17BDE61F7B00651C ON upload (status)');
        $this->addSql('CREATE INDEX IDX_17BDE61F43625D9F ON upload (updated_at)');
        $this->addSql('CREATE INDEX IDX_9BCD33177B00651C8B8E8428 ON woo_index_sitemap (status, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_795FD9BB611C0C5643625D9FBF396750');
        $this->addSql('DROP INDEX IDX_F3F4EC1024CD504D');
        $this->addSql('DROP INDEX IDX_D8698A76C35E566A');
        $this->addSql('DROP INDEX IDX_D8698A76C33F7837');
        $this->addSql('DROP INDEX IDX_D8698A76E2904019');
        $this->addSql('DROP INDEX IDX_3D48E0379E6B15857B00651C8B8E8428');
        $this->addSql('DROP INDEX IDX_3D48E0379E6B1585ED18940B');
        $this->addSql('DROP INDEX IDX_3D48E0379E6B15853AF85C6E');
        $this->addSql('DROP INDEX IDX_3D48E0377B00651CA166B6B7');
        $this->addSql('DROP INDEX IDX_3D48E0377B00651C43625D9FBF396750');
        $this->addSql('DROP INDEX IDX_27BA704B8CDE5729772E836AF6F9706DBF396750');
        $this->addSql('CREATE INDEX idx_27ba704b8cde5729772e836a ON history (type, identifier)');
        $this->addSql('DROP INDEX IDX_5A3903F09E6B158585C88591');
        $this->addSql('DROP INDEX IDX_5A3903F09E6B158543625D9F');
        $this->addSql('DROP INDEX IDX_D8900FF77B00651CD46F4E3');
        $this->addSql('DROP INDEX IDX_FBCE3E7AB751E0AD5E237E06');
        $this->addSql('DROP INDEX IDX_FBCE3E7A9E6B15855E237E06');
        $this->addSql('DROP INDEX IDX_17BDE61FCCCFBA31');
        $this->addSql('DROP INDEX IDX_17BDE61F7B00651C');
        $this->addSql('DROP INDEX IDX_17BDE61F43625D9F');
        $this->addSql('DROP INDEX IDX_9BCD33177B00651C8B8E8428');
    }
}

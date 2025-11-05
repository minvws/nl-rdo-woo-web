<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250918152827 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX CONCURRENTLY IDX_795FD9BBD7DF1668 ON attachment (file_name)');
        $this->addSql('CREATE INDEX CONCURRENTLY IDX_CD1DE18AD7DF1668 ON department (file_name)');
        $this->addSql('CREATE INDEX CONCURRENTLY IDX_D8698A7678AA5BA1 ON document (document_nr)');
        $this->addSql('CREATE INDEX CONCURRENTLY IDX_D8698A76D2ABBE9 ON document (document_date)');
        $this->addSql('CREATE INDEX CONCURRENTLY IDX_D8698A76D7DF1668 ON document (file_name)');
        $this->addSql('CREATE INDEX CONCURRENTLY IDX_A29B24ADD7DF1668 ON document_file_update (file_name)');
        $this->addSql('CREATE INDEX CONCURRENTLY IDX_2D03F7CAD7DF1668 ON document_file_upload (file_name)');
        $this->addSql('CREATE INDEX CONCURRENTLY IDX_C857A5D8D7DF1668 ON inquiry_inventory (file_name)');
        $this->addSql('CREATE INDEX CONCURRENTLY IDX_B12D4A36D7DF1668 ON inventory (file_name)');
        $this->addSql('CREATE INDEX CONCURRENTLY IDX_14B9B03D7DF1668 ON main_document (file_name)');
        $this->addSql('CREATE INDEX CONCURRENTLY IDX_B8FE3D9D7DF1668 ON production_report (file_name)');
        $this->addSql('CREATE INDEX CONCURRENTLY IDX_D8900FF7D7DF1668 ON production_report_process_run (file_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_795FD9BBD7DF1668');
        $this->addSql('DROP INDEX IDX_CD1DE18AD7DF1668');
        $this->addSql('DROP INDEX IDX_D8698A7678AA5BA1');
        $this->addSql('DROP INDEX IDX_D8698A76D2ABBE9');
        $this->addSql('DROP INDEX IDX_D8698A76D7DF1668');
        $this->addSql('DROP INDEX IDX_A29B24ADD7DF1668');
        $this->addSql('DROP INDEX IDX_2D03F7CAD7DF1668');
        $this->addSql('DROP INDEX IDX_C857A5D8D7DF1668');
        $this->addSql('DROP INDEX IDX_B12D4A36D7DF1668');
        $this->addSql('DROP INDEX IDX_14B9B03D7DF1668');
        $this->addSql('DROP INDEX IDX_B8FE3D9D7DF1668');
        $this->addSql('DROP INDEX IDX_D8900FF7D7DF1668');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250102095807 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE batch_download ADD file_count INT NOT NULL DEFAULT 0');
        $this->addSql('UPDATE batch_download SET file_count = json_array_length(documents)');
        $this->addSql('ALTER TABLE batch_download DROP documents');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE batch_download ADD documents JSON NOT NULL');
        $this->addSql('ALTER TABLE batch_download DROP file_count');
    }
}

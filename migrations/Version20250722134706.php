<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250722134706 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document DROP summary');
        $this->addSql('ALTER TABLE document DROP title');
        $this->addSql('ALTER TABLE document ALTER remark TYPE VARCHAR(1000)');
        $this->addSql('ALTER TABLE document ALTER withdraw_explanation TYPE VARCHAR(1000)');
        $this->addSql('ALTER TABLE document ALTER document_id TYPE VARCHAR(170)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document ADD summary TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD title TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE document ALTER withdraw_explanation TYPE TEXT');
        $this->addSql('ALTER TABLE document ALTER remark TYPE TEXT');
        $this->addSql('ALTER TABLE document ALTER document_id TYPE VARCHAR(255)');
    }
}

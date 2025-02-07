<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231219101111 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE document_referrals (document_id UUID NOT NULL, referred_document_id UUID NOT NULL, PRIMARY KEY(document_id, referred_document_id))');
        $this->addSql('CREATE INDEX IDX_945E037C33F7837 ON document_referrals (document_id)');
        $this->addSql('CREATE INDEX IDX_945E037E75CCE85 ON document_referrals (referred_document_id)');
        $this->addSql('COMMENT ON COLUMN document_referrals.document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN document_referrals.referred_document_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE document_referrals ADD CONSTRAINT FK_945E037C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document_referrals ADD CONSTRAINT FK_945E037E75CCE85 FOREIGN KEY (referred_document_id) REFERENCES document (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_referrals DROP CONSTRAINT FK_945E037C33F7837');
        $this->addSql('ALTER TABLE document_referrals DROP CONSTRAINT FK_945E037E75CCE85');
        $this->addSql('DROP TABLE document_referrals');
    }
}

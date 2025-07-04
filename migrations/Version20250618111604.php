<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250618111604 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO public.content_page (slug, title, content, created_at, updated_at) VALUES ('balie-contact', 'Contact', e'**Vraag over hoe iets werkt?**

            Neem dan even contact op met je coördinator.

            **Vraag of opmerking voor de ontwikkelaars?**

            Loop je tijdens het gebruiken van deze website tegen technische problemen aan? Of heb je ideeën die het systeem makkelijker in gebruik zouden maken? Laat het ons dan weten via [woo-platform@irealisatie.nl](mailto:woo-platform@irealisatie.nl).', '2025-06-06 13:17:24', '2025-06-18 11:13:18');
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DELETE FROM content_page WHERE slug = 'balie-contact'
        SQL);
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250606131114 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO public.content_page (slug, title, content, created_at, updated_at) VALUES ('copyright', 'Copyright', e'Iedereen mag op grond van de Wet Hergebruik van Overheidsinformatie de informatie op deze website hergebruiken, tenzij anders is aangegeven.

            Het recht op hergebruik geldt voor alle soorten informatie. Bijvoorbeeld teksten, rapporten, foto’s, grafieken en afbeeldingen. Het hergebruik geldt zowel voor commerciële als niet-commerciële doelen.

            Hergebruik is toegestaan tenzij:
            *   via het copyrightteken (©) is aangegeven dat er op een foto wél copyright zit;
            *   het gaat om content van derden. Controleer daarom altijd de afzender van documenten.', '2025-06-06 13:17:24', '2025-06-06 11:52:58');
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO public.content_page (slug, title, content, created_at, updated_at) VALUES ('privacy', 'Privacyverklaring Wet open overheid (Woo)', e'**Welke persoonsgegevens verwerken wij?**

            Het ministerie van Volksgezondheid, Welzijn en Sport (VWS) verwerkt persoonsgegevens die u met ons deelt via uw Woo-verzoek. Dit zijn bijvoorbeeld uw NAW-gegevens (naam, adres, woonplaats).

            **Wie is verantwoordelijk voor de verwerking van uw persoonsgegevens?**

            De minister van VWS is de zogenaamde verwerkingsverantwoordelijke in de zin van de Algemene verordening gegevensbescherming (AVG). De [Privacyverklaring van AZ](https://www.rijksoverheid.nl/ministeries/ministerie-van-algemene-zaken/privacy/privacyverklaring-inkoop-en-aanbesteding) (AZ) vindt u elders op Rijksoverheid.nl. VWS heeft een functionaris voor gegevensbescherming (FG) als interne toezichthouder, die toezicht houdt op het zorgvuldig omgaan met persoonsgegevens door VWS. De FG is bereikbaar per e-mail: [FG-VWS@minvws.nl](mailto:FG-VWS@minvws.nl).

            **Met welk doel en op basis van welke grondslag verwerken wij persoonsgegevens?**

            Uw gegevens worden alleen gebruikt om uw verzoek te kunnen behandelen. De rechtsgrondslag voor verwerkingen in het kader van Woo-verzoeken is om te voldoen aan een wettelijke verplichting (de Woo).

            **Hoe lang bewaren wij persoonsgegevens?**

            VWS bewaart uw persoonsgegevens 5 jaar in het kader van de Archiefwet.

            **Met wie delen wij persoonsgegevens?**

            VWS deelt of verkoopt uw gegevens niet.

            **Kunnen persoonsgegevens langer bewaard worden?**

            Persoonsgegevens worden niet in alle situaties automatisch verwijderd. In een beperkt aantal gevallen mogen we deze gegevens langer bewaren . Bijvoorbeeld als iemand strafbare feiten pleegt en de bewaarde gegevens noodzakelijk zijn voor opsporing. Zo’n geval kan leiden tot een verlenging van de bewaartermijn.

            **Hoe beveiligen wij persoonsgegevens?**

            VWS neemt de bescherming van uw gegevens serieus en neemt passende maatregelen om misbruik, verlies, onbevoegde toegang, ongewenste openbaarmaking en ongeoorloofde wijziging tegen te gaan.

            **Kunt u gegevens inzien, wijzigen of verwijderen?**

            U heeft het recht om uw persoonsgegevens in te zien, te wijzigen of te verwijderen. Daarnaast heeft u het recht om uw eventuele toestemming voor de gegevensverwerking in te trekken, of bezwaar te maken tegen de verwerking van uw persoonsgegevens door VWS.

            U kunt ook [een melding doen bij de Autoriteit Persoonsgegevens.](https://autoriteitpersoonsgegevens.nl/nl/zelf-doen/privacyrechten/klacht-indienen-bij-de-ap)', '2025-06-06 13:17:24', '2025-06-06 11:53:11');
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO public.content_page (slug, title, content, created_at, updated_at) VALUES ('cookies', 'Cookies', e'Wij gebruiken het statistiekenprogramma Piwik om te analyseren welke pagina\'s het meest bezocht worden, hoe bezoekers op deze website zijn gekomen en welke zoektermen ze gebruiken in onze zoekmachine.

            Wij verzamelen, net als elke website, IP-adressen van onze bezoekers. Deze worden opgeslagen in zogeheten logfiles. De logfiles worden 5 dagen op de webserver bewaard, zodat ze beschikbaar zijn voor Piwik. Daarna blijven de logfiles 90 dagen bewaard voor uitsluitend beveiligingsredenen en worden ze ook alleen daarvoor bekeken.

            De Autoriteit Persoonsgegevens heeft maatregelen genomen om de herleidbaarheid van bezoekers naar onze website zo veel mogelijk te beperken. Dit doen we door onmiddellijk na het importeren van de logfiles in Piwik de laatste 2 octetten (cijfergroepen) van elk IP-adres weg te gooien. Dit gebeurt in een tijdelijk geheugen, voordat de IP-adressen in Piwik worden opgeslagen.

            Wij verzamelen de volgende gegevens met de logfiles:

            *   Cookies

            *   IP-adres

            *   user agents (browsers, operating system)

            *   gebruikte zoektermen om via externe zoekmachines op onze website te komen, gebruikte zoektermen in de zoekmachine op de website zelf

            *   gebruikte links binnen de website

            *   gebruikte links om op onze website te komen.


            Deze gegevens haalt Piwik uit de logfiles van de webserver. Deze logfiles blijven 31 dagen in de database van Piwik staan. Daarna worden ze verwijderd. Er blijft dan alleen een samengevoegde logfile over. Die geeft ons een jaarrapportage over het websitebezoek.

            Wij delen geen persoonsgegevens met derden, tenzij dat noodzakelijk is om aangifte te doen van strafbare feiten. Lees meer over het [cookiegebruik door Rijksoverheid.nl](https://www.rijksoverheid.nl/cookies).', '2025-06-06 13:17:24', '2025-06-06 11:52:53');
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO public.content_page (slug, title, content, created_at, updated_at) VALUES ('toegankelijkheid', 'Toegankelijkheid', e'**Toegankelijkheid**

            Wij willen graag dat iedereen deze website kan gebruiken. Komt u toch een pagina tegen die niet toegankelijk is? Of heeft u een andere vraag? [Neem dan contact met ons op](https://www.toegankelijkheidsverklaring.nl/register/20338/).

            [Toegankelijkheidsverklaring open.minvws.nl](https://www.toegankelijkheidsverklaring.nl/register/10602).[Download het toegankelijkheidsonderzoek (PDF 304 KB)](http://localhost:8000/documenten/2024-02-13.Toegankelijkheidsonderzoek.OpenVWS.open.minvws.nl.versie.2.1.pdf).

            Wat is een toegankelijke website?
            ---------------------------------

            Een toegankelijke website is voor alle bezoekers beter te gebruiken. Daarom gelden er functioneel-technische en redactionele [toegankelijkheidseisen](https://www.digitoegankelijk.nl/) of (voorheen: webrichtlijnen) voor websites van de overheid. Deze zijn beschreven in de [toegankelijkheidsstandaard Digitoegankelijk EN 301 549](https://www.forumstandaardisatie.nl/standaard/digitoegankelijk-en-301-549-met-wcag-21).

            **Borging van toegankelijkheid**

            Wij waarborgen goede toegankelijkheid op verschillende manieren binnen onze (dagelijkse) processen:

            *   Toegankelijkheid ‘by design’: toegankelijkheid is vanaf de start onderdeel van alle stappen in het ontwerp-, bouw en redactionele proces van deze website.

            *   Onderzoek: wij controleren regelmatig (onderdelen van) onze website op toegankelijkheid. Zowel voor de functioneel-technische onderdelen als de redactionele aspecten. Gevonden knelpunten lossen wij duurzaam op.

            *   Kennis medewerkers: onze medewerkers houden hun kennis over toegankelijkheid op peil en passen dit waar nodig toe.', '2025-06-06 13:17:24', '2025-06-06 11:22:03');
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO public.content_page (slug, title, content, created_at, updated_at) VALUES ('kwetsbaarheid-melden', 'Kwetsbaarheid melden', e'Ontdek je een zwakke plek of kwetsbaarheid op één van de websites, applicaties, of diensten van iRealisatie?

            Dat willen we graag weten. Meld de kwetsbaarheid via [security@irealisatie.nl](mailto:security@irealisatie.nl), dan bekijken wij het probleem en lossen we dit zo snel mogelijk op.', '2025-06-06 13:17:24', '2025-06-06 12:05:17');
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO public.content_page (slug, title, content, created_at, updated_at) VALUES ('over-dit-platform', 'Over dit platform', e'Iedereen heeft recht op informatie over wat de overheid doet, hoe ze dat doet en waarom. Bestuursorganen moeten die informatie uit zichzelf geven, of als iemand daarom vraagt. De informatie wordt dan openbaar.

            Op dit platform vindt u informatie die openbaar is gemaakt op grond van de [Wet open overheid (Woo)](https://www.rijksoverheid.nl/onderwerpen/wet-open-overheid-woo) door het Ministerie van Volksgezondheid, Welzijn en Sport en aan haar verwante (zelfstandig) bestuursorganen. Het platform zal de komende periode aangevuld worden met meer informatie.

            De openbaargemaakte Woo-besluiten van het ministerie van VWS zijn momenteel nog op verschillende websites te vinden:

            *   Algemene besluiten (niet COVID-19 gerelateerd) van het ministerie van Volksgezondheid, Welzijn en Sport kunt u vinden op [rijksoverheid.nl](https://www.rijksoverheid.nl/)

            *   COVID-19 gerelateerde besluiten kunt u vinden op deze website.


            **Andere publicaties van VWS**

            *   [Verwerkingsregister](https://open.minvws.nl/verwerkingsregister)

            *   [Algoritmeregister](https://algoritmes.overheid.nl/nl/organisatie/ministerie-vws)

            *   [Overzicht broncode van het ministerie van VWS op GitHub](https://github.com/minvws)


            **Een Woo-verzoek indienen**

            U mag informatie opvragen over wat de overheid doet. Dat is vastgelegd in de [Wet open overheid (Woo)](https://www.rijksoverheid.nl/onderwerpen/wet-open-overheid-woo). U vraagt informatie op via een Woo-verzoek. Voordat u een Woo-verzoek indient:

            *   Controleert u of de informatie die u zoekt al openbaar is. Kijk bijvoorbeeld op [rijksoverheid.nl](https://www.rijksoverheid.nl/documenten), [het archief van de Eerste Kamer en de Tweede Kamer](https://zoek.officielebekendmakingen.nl/uitgebreidzoeken/parlementair) of [open.overheid.nl](https://open.overheid.nl/) of de website van het bestuursorgaan.

            *   Gaat u na bij welk bestuursorgaan u het verzoek moet indienen.

            *   Geeft u zo precies mogelijk aan welke informatie u zoekt: over welk onderwerp, met welke betrokken personen en uit welke periode.

            *   Deelt u uw telefoonnummer in het verzoek. Mogelijk belt het bestuursorgaan u met extra vragen om uw verzoek te duiden.


            Heeft u een verzoek voor het ministerie van Volksgezondheid, Welzijn en Sport (VWS) en gaat het over de COVID-19 periode? Bekijk de pagina [Aanpak VWS Woo-verzoeken corona](https://www.rijksoverheid.nl/ministeries/ministerie-van-volksgezondheid-welzijn-en-sport/contact/aanpak-vws-woo-verzoeken-corona). Voor overige verzoeken verwijzen wij u naar [Woo-verzoek indienen bij het ministerie van VWS](https://www.rijksoverheid.nl/ministeries/ministerie-van-volksgezondheid-welzijn-en-sport/contact/woo-verzoek-indienen).', '2025-06-06 13:17:24', '2025-06-06 12:32:20');
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO public.content_page (slug, title, content, created_at, updated_at) VALUES ('contact', 'Contact', e'Wij zetten ons dagelijks in om dit platform te verbeteren, uit te breiden met nieuwe functionaliteit en te onderhouden. Voor feedback, tips of suggesties over hoe we dit platform kunnen verbeteren, kunt u ons bereiken via [woo-platform@irealisatie.nl](mailto:woo-platform@irealisatie.nl).

            De broncode van dit platform is open source beschikbaar op Github: [https://github.com/minvws/nl-rdo-woo-web](https://github.com/minvws/nl-rdo-woo-web).

            Heeft u een vraag of opmerking over een publicatie of document op dit platform, neem dan contact op met het bestuursorgaan dat hiervoor verantwoordelijk is. De contactgegevens zijn te vinden op [organisaties.overheid.nl](https://organisaties.overheid.nl/).', '2025-06-06 13:17:24', '2025-06-06 13:17:24');
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DELETE FROM content_page
        SQL);
    }
}

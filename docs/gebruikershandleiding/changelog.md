<!-- markdownlint-disable MD024 MD036 -->

# Changelog

## Release 3.9.0

_November 2026_

### Balie

- **Excel-bestanden bij beschikkingen en klachtoordelen.** Bij het uploaden van een Excel-bestand als hoofddocument van een beschikking of klachtoordeel wordt uitgelegd hoe het bestand wordt verwerkt.
Bevat het bestand meerdere werkbladen of geen header-rij, dan krijgt de gebruiker een melding om het bestand aan te passen.

- **Aparte rol voor contentbeheer.** Content pagina's kunnen voortaan beheerd worden door gebruikers met de nieuwe rol Contentbeheerder,
 zonder dat zij superbeheerder hoeven te zijn. Contentbeheer heeft een eigen menu-item gekregen en de namen van de rollen zijn consistenter gemaakt.

- **Publieke links zichtbaar op de documentpagina.** Op de documentpagina van een document in een Woo-besluit worden nu de publieke links getoond die via het productierapport zijn aangeleverd.
Dit is vooral van belang bij documenten die al openbaar zijn.

- **Duidelijkere foutmeldingen bij het productierapport.** Als een rij in een productierapport niet verwerkt kan worden, toont de foutmelding nu wat er mis is, zoals de ongeldige waarde.
Onverwachte fouten worden per rij gemeld in plaats van de hele verwerking te laten mislukken.

- **Optie om alle documenten in te trekken alleen bij documenten.** De optie om alle documenten in te trekken wordt niet meer getoond bij een Woo-besluit zonder documenten.

- **Elasticsearch-index altijd met de nieuwste mapping.** Bij het aanmaken van een nieuwe index of een rollover hoeft geen mappingversie meer gekozen te worden. Er wordt altijd de actuele mapping gebruikt.

### Publication API

- **Publication API altijd beschikbaar.** De Publication API, inclusief het bijwerken van gepubliceerde publicaties en het uploaden van documenten daarvoor, is voortaan voor alle organisaties beschikbaar. De featureflags hiervoor zijn verwijderd.

- **Excel-bestanden bij beschikkingen en klachtoordelen.** Excel-bestanden die via de API worden aangeleverd bij beschikkingen en klachtoordelen worden net als in de Balie als tabel verwerkt.
De API-documentatie beschrijft aan welke eisen het bestand moet voldoen.

- **Periode van een Woo-besluit optioneel.** De periode van een Woo-besluit is in de API niet langer verplicht, net als in de Balie.

- **Veldbeschrijvingen compleet in de API-documentatie.** Beschrijvingen van velden met een specifiek formaat of een vaste lijst waarden, zoals datums, bestandsnamen en `grounds`, worden nu ook getoond in de OpenAPI-specificatie.

### Publieke website

- **Excel-bestanden direct als tabel bekijken.** De inhoud van een Excel-bestand bij een beschikking of klachtoordeel wordt op de documentpagina als tabel getoond, met sorteren per kolom en een zoekveld.
Bij grote bestanden worden alleen de eerste 100 rijen en kolommen getoond. Het bestand blijft downloadbaar.

- **Onderwerpen en verhaallijnen vindbaar via de zoekfunctie.** Gepubliceerde onderwerpen verschijnen in de zoekresultaten met het label Onderwerp. Er wordt gezocht in de titel en beschrijving van de landingspagina en in de onderdelen van een zichtbare verhaallijn.

- **Eén downloadlink per document.** Een document heeft voortaan één vaste download-URL, onafhankelijk van het besluit waaraan het gekoppeld is. Bestaande downloadlinks worden doorgestuurd naar deze nieuwe URL.

### Backend

- **Extra kolommen in de inventaris.** De inventaris bevat nu ook de kolommen EmailThreadID, FamilyID, Source Type en Document Date. Hierdoor zijn gerelateerde documenten ook in de inventaris terug te vinden.

- **Publieke links beschikbaar in de zoekindex.** De publieke links van documenten in een Woo-besluit staan nu in Elasticsearch, zodat ook externe toepassingen naar de vindplaats van al openbare documenten kunnen verwijzen.

### Fixes

- **Uploadstatus in de Publication API.** Als een geüpload bestand niet aan een document gekoppeld kon worden, bleef de uploadstatus op `processing` staan. De status wordt nu `processing_failed`, zodat de API-client weet dat de upload mislukt is.

- **Paginanummer na het verwijderen van filters.** Na het verwijderen van alle zoekfilters kwam de gebruiker op een willekeurige pagina terecht. Nu begint de gebruiker weer op pagina 1, ook na het verwijderen van een enkel filter.

- **Lange filternamen breken de layout niet meer.** Lange labels in de zoekfilters lopen nu door op een volgende regel in plaats van buiten de layout te vallen.

- **Paginavoorvertoning bij onverwerkte PDF-pagina's.** Pagina's van een PDF die nog niet verwerkt zijn, gaven een rommelige layout in de paginavoorvertoning. Nu wordt een melding getoond dat de pagina nog verwerkt wordt.

- **Naam in bestandsnaam van login-instructies.** Bij het opslaan van de login-instructies als PDF ontbrak de naam van de gebruiker in de bestandsnaam en de tabtitel. Deze wordt nu wel getoond.

- **Herberekenen van bestandshashes stopt niet meer bij een fout.** Het commando om bestandshashes te herberekenen stopte als een bestand niet gelezen kon worden. Het gaat nu door met de overige bestanden en meldt welke bestanden niet verwerkt konden worden.

- **Opslagcontrole zonder bestanden.** De opslagcontrole crashte voor een organisatie zonder bestanden. Nu wordt een overzicht met 0 bestanden getoond.

## Release 3.8.0

_Oktober 2026_

### Balie

- **Verbeterde navigatie met broodkruimels.** De broodkruimelnavigatie is vereenvoudigd. Dossiernamen zijn vervangen door publicatietypen en tijdens de conceptfase worden geen broodkruimels meer getoond.

- **Verbeterd hoofdmenu.** Het hoofdmenu gebruikt nu de volledige schermbreedte. Organisaties heeft een eigen menu-item gekregen en Toegangsbeheer is hernoemd naar Gebruikers.

- **Previewtokens voor onderwerpen.** Alle onderwerpen, inclusief bestaande onderwerpen, hebben voortaan een previewtoken.

### Publication API

- **Consistente verwerking van documentnummers.** Documentnummers worden voortaan in kleine letters opgeslagen, ook wanneer ze met hoofdletters worden aangeleverd. Hierdoor kunnen documenten ongeacht het gebruik van hoofdletters worden gevonden.

### Publieke website

- **Correcte weergave van tekstbestanden.** Bestanden met de extensie `.txt` worden voortaan als Tekstbestand weergegeven in plaats van als Word-document. Dit geldt ook voor de filtering op bestandstype.
- **Consistente documentnummers in URL's.** Documentnummers worden in URL's voortaan in kleine letters weergegeven. Bestaande links met hoofdletters blijven werken.
- **Directe links naar documenten vanuit zoekresultaten.** Zoekresultaten verwijzen voortaan naar de canonieke URL van een document, in plaats van naar een URL via een willekeurig gekoppeld dossier.

### Fixes

- **Ontbrekende bestandshash bij uploads via de Balie verholpen.** Bij het uploaden van hoofddocumenten en bijlagen via de Balie werd geen bestandshash opgeslagen.
Hierdoor konden documenten onjuiste metadata en pagina-inhoud van andere documenten krijgen.
Dit is opgelost door bij uploads een bestandshash toe te voegen. Daarnaast is een commando toegevoegd om ontbrekende hashes bij bestaande documenten alsnog te genereren.

## Release 3.7.1

_Oktober 2026_

- **Fixed:** Een Document waarvan het documentnummer een streepje (-) of punt (.) bevat, kon bij een Woo besluit nooit een bestand gekoppeld krijgen.
Dit is opgelost: documentnummers met deze tekens worden nu correct herkend, zodat het bestand wel aan het document gekoppeld wordt.

## Release 3.7.0

_Oktober 2026_

### Balie

- **Concept-landingspagina’s direct bewerken.** In het onderwerpenoverzicht wordt nu ook een bewerklink getoond voor landingspagina’s met de status **Concept** zodra er inhoud is ingevuld.

- **Waarschuwing bij gebruik van Matter.** Bij het uploaden van een productierapport of koppelbestand met Matter-waarden wordt een melding getoond dat deze waarden worden vervangen door publicatiecontext.
  Het Excel-voorbeeld voor het koppelen van zaaknummers gebruikt voortaan `publicationContext` in plaats van `matter`.

- **Verhaallijnen beheren als concept of gepubliceerd.** De status van een verhaallijn wordt nu ingesteld met **Concept** of **Gepubliceerd** in plaats van met een checkbox.
  Concept-landingspagina’s kunnen vanuit het onderwerpenoverzicht via een previewlink worden bekeken.

- **Organisatieprefix wijzigen vóór het eerste dossier.** Een superuser kan de prefix van een organisatie aanpassen zolang er nog geen dossiers aan de organisatie zijn gekoppeld.

- **Publicatiebevestiging toont de juiste beschikbaarheidsdatum.** Na het publiceren van een Woo-besluit wordt nu duidelijk aangegeven vanaf welke publicatiedatum de documenten voor iedereen online zijn.
  Bij feitelijke verstrekking worden ook de gekoppelde zaken met datum en link getoond.

- **Focus en skiplink beter zichtbaar.** Elementen die met het toetsenbord focus krijgen zijn in de Balie nu duidelijker herkenbaar. De link **Ga naar inhoud** staat los van de hoofdnavigatie en werkt op dezelfde manier als op de publieke website.

### Backend

- **Wijzigingen aan gedeelde documenten worden verwerkt in alle gekoppelde besluiten.**
  Wanneer een document aan meerdere Woo-besluiten is gekoppeld, worden wijzigingen zoals intrekken, opschorten, aanpassen van gronden of vervangen van een bestand verwerkt voor alle gekoppelde besluiten.

- **Gedeelde documenten blijven consistent in gekoppelde publicaties.** Wanneer een gedeeld document wordt gewijzigd, worden ook de inventarislijst, batchdownload en zoekinformatie van de andere gekoppelde besluiten bijgewerkt.
  Een ingetrokken document blijft daardoor niet beschikbaar in een verouderde batchdownload.

- **Inventarislijst verwijst naar het juiste besluit.** Bij documenten die aan meerdere besluiten zijn gekoppeld toont de inventarislijst de besluitnaam van het besluit waarvoor de export is gemaakt.
  De documentlinks verwijzen daarbij naar de juiste context van dat besluit.

### Publication API

- **Gebruik van `publicationContext` verduidelijkt.** De API-documentatie beschrijft nu duidelijker aan welke eisen `publicationContext` moet voldoen. De verwijzing naar `matter` is uit het voorbeeld verwijderd.

- **Wijzigingen aan gedeelde documenten worden geweigerd.** De Publication API weigert wijzigingen aan documenten die al aan meerdere besluiten of aan een ander besluit zijn gekoppeld en retourneert hiervoor een HTTP 422-validatiefout.
  Verwerking van documenten binnen één besluit blijft ongewijzigd.

- **Mogelijke waarden voor `grounds` zichtbaar in de API-documentatie.** Bij requests toont de API-documentatie nu de toegestane waarden en toelichtingen voor `grounds`. Ook is validatie voor dit veld toegevoegd bij bijlagen.

- **Public-link verwijst naar de juiste documentpagina.** De `public`-link van een document in een gepubliceerd Woo-besluit verwijst nu naar de detailpagina van dat document in plaats van naar de dossierpagina.

- **Status van verhaallijn aangepast.** De verhaallijnstatus gebruikt in de Publication API nu de waarden `concept` en `published` in plaats van een boolean. Het bijbehorende veld is gewijzigd naar `landingPageContentTreeStatus`.

- **Padparameters duidelijker gedocumenteerd.** Padparameters voor onder andere organisaties, dossiers, documenten en bijlagen hebben nu consistente omschrijvingen en tonen het verwachte formaat van de identifier.

### Publieke website

- **Weergave van verhaallijnen verbeterd.** Op publieke landingspagina’s is de inhoud van een verhaallijn standaard uitgeklapt, zijn onderdelen beter uitgelijnd en zijn de grijze kaders rond afzonderlijke onderdelen verwijderd.

- **Piwik-scripts worden niet meer geblokkeerd.** Scripts die via Piwik Tag Manager op de publieke website worden geplaatst kunnen nu worden uitgevoerd zonder de Content-Security-Policy te versoepelen.

- **Ongeldige vervanging van een hoofddocument geeft een validatiefout.** Het hoofddocument van een gepubliceerd dossier kan niet meer worden vervangen door een melding dat het document niet openbaar is.
  De API retourneert hiervoor nu een 422-validatiefout in plaats van een 500-fout.

## Release 3.6.0

_September 2026_

### Backend

- **Besluit zichtbaar in documenthistorie.** De documenthistorie toont voortaan in een aparte kolom welk besluit de wijziging heeft veroorzaakt. De besluitnaam wordt als link weergegeven en blijft actueel wanneer de naam van het besluit wordt gewijzigd.
- **Downloaden van documenten met meerdere besluiten.** Documenten die aan meerdere besluiten zijn gekoppeld, kunnen ook vanuit de Balie worden gedownload. Voor documenten met één besluit blijft het bestaande gedrag ongewijzigd.
- **Besluit zichtbaar in documenthistorie.** De publieke documenthistorie toont voortaan in een aparte kolom welk besluit een wijziging heeft veroorzaakt. De besluitnaam wordt als link weergegeven en blijft actueel wanneer de naam van het besluit wordt gewijzigd.
- **Documenten onder meerdere besluiten blijven toegankelijk.** Een document dat aan meerdere besluiten is gekoppeld, blijft publiek beschikbaar wanneer minimaal één van deze besluiten toegang geeft.
  Documenten die uitsluitend aan conceptbesluiten zijn gekoppeld blijven niet publiek toegankelijk.
- **Documenthistorie bij meerdere besluiten verbeterd.** De publieke historie wordt voortaan bepaald vanaf de vroegste publicatiedatum van de gepubliceerde besluiten waaraan het document is gekoppeld.
  Hierdoor is de zichtbare historie niet meer afhankelijk van de volgorde van de gekoppelde besluiten.
- **Canonieke URL voor documenten.** Documenten hebben voortaan één canonieke URL die niet afhankelijk is van een specifiek besluit.
  Bestaande besluitgebonden URL's blijven werken en verwijzen naar deze canonieke URL. In de sitemap wordt ieder document nog maar één keer opgenomen.

### Publication API

- **Identiteitsvelden van gepubliceerde documenten blijven vergrendeld.** Wanneer een document aan meerdere besluiten is gekoppeld, worden `publicationContext` en `documentId` vergrendeld zodra minimaal één gekoppeld besluit niet meer de status concept heeft.
  Hierdoor kunnen deze velden niet via een conceptbesluit worden gewijzigd wanneer het document elders al is gepubliceerd.
- **Publicatiecontext vervangt Matter.** `publicationContext` is voortaan verplicht in de Publication API en `matter` is uit het requestcontract verwijderd. Requests die toch `matter` meesturen, worden met een validatiefout afgewezen.
- **Landingpage niet meer opgenomen in metadataresponse.** De volledige `landingPage` van een onderwerp wordt niet meer opgenomen in de response van een metadata-PUT. Hierdoor bevat de response alleen de relevante onderwerpgegevens.
- **Beschrijvingen toegevoegd aan API-specificatie.** Attributen, parameters en endpoints in de API-specificatie zijn voorzien van toelichtingen. Hierdoor is de betekenis en het gebruik ervan direct vanuit de API-documentatie beter te begrijpen.

### Fixes

- **Vaste volgorde van documenten in inzagedossiers.** Documenten op een publieke inzagedossierpagina worden standaard oplopend op documentnummer gesorteerd. Bij gelijke documentnummers blijft de volgorde eveneens voorspelbaar.
- **Meerdere verbeteringen in ContentTrees.** Lege ContentTree onderdelen worden nu niet meer weergegeven op de publieke website en zijn nu gelimiteerd op 5 niveaus.

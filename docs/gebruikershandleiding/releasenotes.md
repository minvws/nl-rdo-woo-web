<!-- markdownlint-disable MD024 MD036 -->

# Release notes

## v1.17.0

*18 augustus 2025*

### Balie

**Vernieuwd design voor badges**

De visuele stijl van badges (labels) in de balie is vernieuwd en in lijn gebracht met ons design system. Dit zorgt voor een consistentere en modernere gebruikerservaring.

**Vernieuwd design voor notificaties**

Ook de notificaties in de balie zijn geüpdatet volgens het nieuwe design. Hierdoor zijn meldingen duidelijker zichtbaar en beter leesbaar voor gebruikers.

### Website

**Aantallen opgeschorte documenten zichtbaar bij besluitinformatie**

Bij elk Woo-besluit wordt nu expliciet weergegeven hoeveel (deels) openbare documenten opgeschort zijn. Dit geeft bezoekers beter inzicht in het verschil tussen het aantal openbaar gemaakte documenten en wat daadwerkelijk gedownload kan worden.

**Aantallen ingetrokken en opgeschorte documenten op zaakpagina's**

Op de individuele zaakpagina's worden nu afzonderlijk weergegeven hoeveel documenten zijn ingetrokken en hoeveel zijn opgeschort.
Deze aantallen verschijnen als aparte regels in het grijze informatieblok, mits er sprake is van minimaal één ingetrokken of opgeschort document.

## v1.16.1

*22 augustus 2025*

### Balie

Bij het intrekken van een bijlage is het prullenbak-icoon vervangen door een duidelijkere knop met de tekst "Intrekken", zodat gebruikers beter zien welke actie wordt uitgevoerd.

De melding “Dit besluit valt niet onder de verantwoordelijkheid van het Ministerie van VWS” is nu configureerbaar per bestuursorgaan.
Hierdoor kan per organisatie een eigen verantwoordelijkheidstekst worden ingesteld.

De helptekst boven de Markdown editor is compacter gemaakt zodat er meer ruimte is voor het invoerveld.

Er zijn verbeterde invoercontroles en maximale lengtes ingesteld op diverse velden (bestuursorganen, documenten, dossiers, bijlagen, zaaknummers, onderwerpen).
Daarnaast is een technisch limiet ingesteld van maximaal 50.000 documenten per dossier.
Dit zorgt voor betere datakwaliteit en platformstabiliteit.

### Website

Op publicatiedetailpagina’s wordt nu altijd minimaal één tab getoond met de documentstatus.
Ook wordt per tab het aantal documenten weergegeven.
Dit maakt de status van documenten duidelijker, ook als er slechts één status aanwezig is.

Bij het klikken op een informatiecategorie, zoals “Woo-besluiten”, worden voortaan alleen de besluiten zelf getoond (zonder bijlagen of onderliggende documenten).
Hierdoor klopt het weergegeven aantal besluiten en zijn zoekresultaten relevanter.

De actieve filterknoppen op de website hebben een vernieuwde stijl gekregen, in lijn met het design system waarvan het platform gebruik maakt.

De mogelijkheid om documenten per dossier te downloaden via de zaakpagina is hersteld.
Dit was tijdelijk uitgeschakeld in verband met eerdere aanpassingen.

In de inventarislijst op de zaakpagina wordt nu per document weergegeven bij welk besluit het hoort.
Dit maakt het groeperen, filteren en analyseren van documenten eenvoudiger.
De besluitnaam hebben we ook toegevoegd aan de inventarislijst bij een besluit en zal daar logischerwijs maar één waarde hebben.

## v1.15.0

*10 juli 2025*

### Balie

Er zijn in deze release geen wijzigingen voor de balie doorgevoerd.

### Website

Er is een knop 'Alle filters wissen' toegevoegd op de zoekpagina’s (inclusief COVID-19 thema). Ook is er nu de optie 'Start een nieuwe zoekopdracht' bij nul resultaten. Beide brengen je terug naar een lege zoekopdracht.

Op documentpagina’s wordt nu bij elke bijlage het type getoond in plaats van de datum. Dit helpt bezoekers de aard van een bijlage sneller te begrijpen.

## v1.14.0

*3 juli 2025*

### Balie

Het is nu mogelijk om los van een advies, een adviesaanvraag te publiceren.
Reden hiervoor is dat het advies en de adviesaanvraag door andere bestuursorganen gepubliceerd kunnen worden.
Aan de adviesaanvraag kan een link toe worden gevoegd die verwijst naar het advies bijbehorend aan de adviesaanvraag.

### Website

Op de website kan nu gezocht worden op adviesaanvragen. Dit is mogelijk via zowel de zoekfunctionaliteit als de filters.

## v1.13.0

*25 juni 2025*

### Balie

In het overzicht en op de detailpagina van publicaties worden nu visuele meldingen getoond voor documenten met de status 'incompleet', 'ingetrokken' of 'opgeschort'. Dit helpt om sneller actie te ondernemen.

De knoppen in de balie zijn aangepast aan het vernieuwde design van ons design systeem, wat zorgt voor een consistentere en modernere uitstraling.

De balie is aangepast zodat instellingen zoals standaardteksten, logo’s en metadata per ministerie configureerbaar zijn. Dit maakt het platform gereed voor breder gebruik buiten VWS.

### Website

Gebruikers kunnen nu zoeken binnen documenten die naar het huidige document verwijzen, binnen e-mails uit dezelfde conversatie, en in bijlagen bij e-mails. Dit geeft meer context en samenhang in de zoekresultaten.

Organisatiebeheerders kunnen nu de landingspagina van de website opmaken met vet, cursief, opsommingen en hyperlinks. Dit maakt het eenvoudiger om heldere en aantrekkelijke teksten te publiceren.

De pagina open.minvws.nl/documentatie is voorzien van het iRealisatie Sphinx theme en sluit nu beter aan op de Rijkshuisstijl.

Net als de balie is ook de publieke website aangepast om VWS-specifieke elementen configureerbaar te maken. Hierdoor is de site eenvoudig aan te passen voor andere ministeries.

## v1.12.0

*28 mei 2025*

### Balie

De formele datum van een besluitbrief is nu gebaseerd op de datum waarop het besluit is genomen, in plaats van de publicatiedatum. Dit zorgt voor correctere weergave van de besluitdata op de website.

Bij het uploaden van een advies zijn ‘Adviesaanvraag’ en ‘Bijlagen’ voortaan gescheiden secties. Dit zorgt voor duidelijkere ordening en presentatie van documenten binnen een publicatie.

Er is gewerkt aan een nieuwe setup van de gebruiksdocumentatie. Deze is hier te zien.

De knoppen in de balie zijn geüpdatet naar het nieuwe design, waardoor de interface consistenter en gebruiksvriendelijker oogt.

Configuratie van landingspagina's:

- Gebruikers met de rol ‘Organisatie-beheer’ hebben nu toegang tot een extra menu-item ‘Bestuursorgaan’, waarmee zij zelfstandig landingspagina’s van gekoppelde bestuursorganen kunnen beheren.
- Bij het bewerken van teksten op de landingspagina zijn nu eenvoudige opmaakopties beschikbaar, zoals vet, cursief, opsommingen en hyperlinks. De wijzigingen zijn direct zichtbaar op de publieke site.
- Organisaties kunnen nu hun eigen logo uploaden en vervangen via het beheerscherm, mits in .svg-formaat. Dit zorgt voor betere herkenbaarheid op de landingspagina.

### Website

Wanneer een bestuursorgaan geen afkorting heeft, wordt nu de volledige naam getoond in de filters. Dit voorkomt lege filteropties en verhoogt de bruikbaarheid.

Bij besluiten die een volledig jaar beslaan, wordt de periode nu weergegeven als “januari t/m december [jaar]” in plaats van “Heel [jaar]”, voor meer duidelijkheid.

Op zaakpagina’s is de knop “zoeken in deze documenten >” nu ook bovenin zichtbaar, direct in het grijze informatieve vak. Hierdoor is deze functie sneller vindbaar voor gebruikers.

Een probleem met de weergave van informatiecategorieën bij een oneven aantal items is opgelost. De layout blijft nu correct in alle browsers.

## v1.11.0

*1 mei 2025*

### Balie

Bij het uploaden van een Woo-besluit is nu een duidelijke link toegevoegd naar documentatie over het productierapport, inclusief een downloadbare Excel-template. Dit helpt organisaties zonder Zylab om het juiste formaat te hanteren.

De kolom ‘Prefix & referentie’ in het publicatieoverzicht toont lange bestandsnamen nu ingekort, met de volledige naam zichtbaar via een tooltip. Hierdoor blijft de tabel overzichtelijk zonder horizontaal scrollen.

Het is nu mogelijk om meer dan 1000 documenten in één keer via een Excelbestand te koppelen aan zaaknummers zonder dat er een time-out optreedt.

Toegankelijkheidsverbeteringen:

- Foutmeldingen bij formulieren zijn nu duidelijker en beter leesbaar voor hulpsoftware.
- Statusberichten worden correct gepresenteerd aan schermlezers.

### Website

Toegankelijkheidsverbeteringen:

- Verbetering van de focusvolgorde bij toetsenbordnavigatie
- Focus wordt niet meer bedekt door mobiel menu
- De structuur en semantiek van de website zijn verbeterd zodat hulpsoftware onderdelen correct herkent.
- Statusberichten worden nu correct voorgelezen door schermlezers, wat navigatie en foutdetectie verbetert.

## v1.10.0

*22 april 2025*

### Balie

Het hoofdmenu en de contentbreedte van de pagina zijn visueel op elkaar afgestemd. Het hoofdmenu is verbreed naar 1280px en de content sluit hier netjes op aan, wat zorgt voor een rustiger en consistenter uiterlijk van de balie.

Op de homepage en landingspagina’s worden nu maximaal 6 items per categorie weergegeven in drie kolommen. Indien er meer resultaten zijn, verschijnt er een link naar de volledige lijst. Ook is de padding links en rechts in lijn gebracht met de rest van de content.

### Website

Toegankelijkheidsverbeteringen:

- Interactieve componenten hebben verbeterde tekstalternatieven.
- Content is beter navigeerbaar in twee dimensies.
- Linkdoelen zijn duidelijker omschreven voor schermlezers.

## v1.9.0

*3 april 2025*

### Balie

Het is nu mogelijk om videobestanden te uploaden in de balie, los of in een zip-bestand. Deze worden automatisch herkend als ‘Video’ en zijn ook vindbaar via de bijbehorende filter op de website.
Het uploadcomponent is aangepast en foutmeldingen over formaat en bestandsgrootte zijn geüpdatet.

Bij het uploaden of vervangen van een productierapport worden document ID's voortaan case-insensitive gematcht. Hierdoor worden documenten met variaties in hoofdlettergebruik correct herkend.
Indien meerdere matches ontstaan door hoofdlettervariaties, wordt het proces afgebroken met een duidelijke foutmelding per rij.

Verbeterde zoekfunctie in 'Alle publicaties'
De zoekfunctie in het overzicht ‘Alle publicaties’ is verbeterd. Je kunt nu makkelijker besluiten terugvinden, ook als je zoekt op slechts één woord in de titel of omschrijving. Dit voorkomt dat relevante publicaties gemist worden door te specifieke zoektermen.

### Website

Bezoekers kunnen nu zoeken op documentnummers (ID) en besluitnummers (referentienummer). Deze functionaliteit maakt het gemakkelijker om gerichte zoekopdrachten uit te voeren en sneller bij het juiste document of besluit uit te komen.

Naast zoeken binnen Woo-besluiten is het nu ook mogelijk om te zoeken in documenten die als gerelateerd zijn gekoppeld aan een ander document. Dit ondersteunt met name de ontsluiting van documentenreeksen zoals geanonimiseerde chatgesprekken.

Het ontwerp van de breadcrumbs is aangepast zodat lange titels netjes worden weergegeven. Op kleinere schermen worden breadcrumbs en het zoekveld nu beter getoond, waarbij het zoekveld niet langer over de navigatie heen valt.

## v1.8.1

*24 maart 2025*

### Balie

- Veilig updaten van download archieven
- Beschikbaar maken van de WooIndex
- Balie | Verbeteren zoekfunctie 'Alle publicaties'
- Één FE-component voor het opgeven van document/bijlage bij een publicatie
- PEN | Uploaden invalide bestandsformaten
- PEN | Verwijderen X-Frame-Options HTTP header + Server header
- PEN | Toevoegen mechanisme voor account vergrendeling
- Uploads van >2GB passen niet in de db
- Meerdere tab-componenten per pagina ondersteunen
- Opruimen oude document upload flow
- Balie | Matters niet hoofdlettergevoelig

### Website

- Website | Aangevinkte filteroptie altijd tonen op zoekpagina
- PRD | Aantal besluiten voor zaaknummer komt niet overeen

## v1.7.1

*12 maart 2025*

### Achtergrondtaken

Het process voor het genereren van een download archief van een Woo-besluit heeft betere foutafhandeling, waardoor er nooit een incompleet download archief aangeboden zal worden.
Een ingeplande publicatie zal altijd gepubliceerd worden, ook als deze de status incompleet heeft. Dit is een tijdelijke oplossing zodat publicaties met opgeschorte documenten nog steeds gepubliceerd kunnen worden.
In een latere release zullen we hier verder op gaan, zodat een dossier met onvoldoende documenten (49/50) niet gepubliceerd zal worden.

## v1.7.0

*20 februari 2025*

### Balie

#### Vervangen van meerdere documenten tegelijkertijd

Het vervangen van documenten is verbeterd. Het is vanaf nu mogelijk om meerdere documenten tegelijkertijd te vervangen. Hiervoor is het blok om documenten te uploaden altijd beschikbaar in het overzicht 'Documenten'.
Voorheen was het enkel mogelijk om één document per keer te vervangen door vanuit het overzicht 'Documenten' een document te selecteren en te kiezen voor 'Vervang document'. Hieronder een beschrijving van wat er is gewijzigd.

Vanuit het overzicht 'Alle publicaties' open je een Woo-besluit. Je scrolt naar het blok 'Documenten' en kiest voor 'Bewerken'. Onderstaand scherm verschijnt. Je ziet hier het blok om documenten te uploaden.

Er zijn diverse redenen waarom je hier een of meerdere documenten upload:

    Je upload een document om het huidige documenten te vervangen, omdat bijvoorbeeld de inhoud is gewijzigd.
    Je upload een document welke niet langer ingetrokken is.
    Je upload een document welke niet langer opgeschort is. Voordat je dit kunt doen dien je eerst het productierapport te vervangen.
    Je upload een document welke nieuw is toegevoegd aan een besluit. Voordat je dit kunt doen dien je eerst het productierapport te vervangen.

Upload een zip-bestand of een aantal losse bestanden. Wanneer de bestanden zijn geüpload, te zien aan de groene vink achter de bestandsnaam, kies je voor 'Bestanden controleren'. Deze knop is nieuw toegevoegd.

Vervolgens zie je welke aanpassingen er doorgevoerd gaan worden nadat de documenten daadwerkelijk verwerkt zijn. Wanneer de getoonde wijzingen kloppen kies je voor 'Ja, verwerk documenten'. Wanneer je de acties niet uit wilt voeren kies je voor 'Annuleren'.

#### Toevoeging van de informatiecategorie 'Adviezen'

Adviezen kunnen nu actief openbaar worden gemaakt op open.minvws.nl. Dit omvat:

- Uploaden van een advies (hoofddocument) met optionele bijlagen, inclusief adviesaanvragen.
- Invoeren en bewerken van metadata voor de publicatie, het advies en de bijlagen.
- Publiceren en verwijderen/vervangen van documenten in verschillende statussen.
- De categorie 'Adviezen' is beschikbaar als filteroptie in de zoekfunctionaliteit op de website.

#### Toevoeging van de informatiecategorie 'Overig'

Het is nu mogelijk om informatie openbaar te maken die niet onder een van de gedefinieerde informatiecategorieën valt. Hieraan zijn de volgende functionaliteiten toegevoegd:

- Uploaden van een informatiestuk met optionele bijlagen.
- Invoeren en bewerken van metadata voor de publicatie, het informatiestuk en bijlagen.
- Publiceren en verwijderen/vervangen van documenten in verschillende statussen.
- De informatiecategorie 'Overig' is beschikbaar als filteroptie in de zoekfunctionaliteit op de website.

#### Link naar documentatie

In de footer is een link naar de gebruikersdocumentatie toegevoegd, zodat gebruikers snel naar open.minvws.nl/documentatie kunnen navigeren.

#### Inzicht in ingetrokken/opgeschorte documenten

Een nieuwe kolom ‘Actie vereist’ toont een icoon als documenten ingetrokken, opgeschort of nog te uploaden zijn.
In het Woo-besluitoverzicht wordt deze status nu ook weergegeven. Daarnaast is de kolom ‘Bijzonderheden’ sorteerbaar gemaakt.

#### Onterechte melding bij vervangen productierapport

Wanneer er zaaknummers uit een productierapport van een reeds openbaar besluit worden verwijderd en het productierapport opnieuw wordt geüpload, verschijnt er geen melding meer dat er documenten aangepast worden naar aanleiding van de wijzigingen.
Voorheen verscheen deze melding wel. Dit is onterecht, omdat het niet meer mogelijk is om zaaknummers te ontkoppelen van Woo-documenten via het productierapport.

#### Intrekken van een bijlage

Het is mogelijk om een bijlage bij een Woo-besluit in te trekken. Hier kan je gebruik van maken wanneer een bijlage bijvoorbeeld onterecht is geüpload bij een besluit.
Anders dan bij de Woo-documenten is het niet mogelijk om na het intrekken van een bijlage een nieuwe versie van het document te uploaden.
Hiervoor zal je opnieuw een bijlage toe moeten voegen aan het besluit. Na het intrekken is de bijlage niet meer zichtbaar en vindbaar op de website.
Enkel de specifieke URL is nog te bereiken. Een melding op deze pagina geeft aan dat het document is ingetrokken met daarbij de reden.

## v1.6.4

*6 februari 2025*

### Balie

#### Wijzigingen in het uploaden van Woo-documenten

Er zijn een aantal wijzigingen aangebracht in het uploaden van Woo-documenten. In de nieuwe situatie wordt op ieder moment in het proces van uploaden en verwerken van documenten getoond dat het systeem bezig of dat er iets fout is gegaan.
Dit om te voorkomen dat er onduidelijkheid bestaat of het uploaden wel of niet goed gaat. Hieronder een beschrijving van wat er is gewijzigd.

Upload het zip-bestand of een aantal losse bestanden zoals je gewend bent. Wanneer de bestanden zijn geüpload, te zien aan de groene vink achter de bestandsnaam, kies je voor 'Bestanden verwerken'. Deze knop is nieuw toegevoegd.

Nadat je hebt gekozen voor 'Bestanden verwerken' verschijnt een melding in beeld dat het systeem bezig is met het verwerken van de geüploade bestanden en zie je een zogenaamde 'spinner' in beeld.
Het is niet nodig om in dit scherm te blijven en het is mogelijk om te werken aan een ander besluit. De verwerking van de bestanden vindt plaats op de achtergrond.

Het is niet mogelijk om op dit moment nieuwe bestanden te uploaden bij het besluit. Dit is wel weer mogelijk nadat de verwerking is afgerond.

Indien alle bestanden voor dit besluit zijn geüpload en verwerkt verschijnt onderstaande melding en kan je door naar de publicatie-stap:

### Website

#### Informatie toegevoegd aan de inventarislijst

Aan de inventarislijst, die te downloaden is vanaf de website, zijn twee kolommen toegevoegd. Indien van toepassing wordt in de ene kolom het 'Gerelateerd ID' getoond en in de andere kolom de URL waarop het document te vinden is.
Wanneer er meerdere gerelateerde ID's zijn gekoppeld aan een document, worden er meerdere ID's en URL's getoond in de kolommen.

#### Openen PDF in browser

Wanneer er geklikt wordt op de preview van een document dan opent dit document weer in de browser. Voorheen werd het document onterecht gedownload.

#### Capaciteit ziekenhuizen toegevoegd aan themapagina COVID-19

Het onderwerp 'Capaciteit ziekenhuizen' is toegevoegd aan de themapagina COVID-19.

## v1.6.3

*10 december 2024*

### Balie

#### Uploaden van meerdere bestandsformaten

Het is mogelijk om bestanden van een ander bestandsformaat dan PDF te uploaden als Woo-document. Voorheen was dit enkel mogelijk als bijlage bij een besluit. We ondersteunen PDF, Word, Excel, Powerpoint en Zip.
Specifiek betreft dit de volgende bestandsformaten: CSV, XLS, XLSX, ODF, ODP, ODS, ODT, TXT, PPSX, PPT, PPTX, PPS, RTF, DOC, DOCX, Zip en 7z.

Om een ander bestandsformaat dan PDF te uploaden is het niet nodig om aanpassingen te doen in het productierapport (publicatierapport). Ook kan het zip-bestand bestaan uit bestanden van verschillende bestandsformaten. Deze kunnen in één keer geüpload worden.

Daarnaast is het mogelijk om een bestand te vervangen door een bestand van een ander bestandsformaat, bijvoorbeeld een PDF vervangen door een Excel.

#### Wijziging in het vervangen van een productierapport

Wanneer een Woo-besluit in concept is, is het niet meer mogelijk om een productierapport (publicatierapport) te verwijderen. Het is enkel mogelijk om het rapport te vervangen.
Dit gaat op dezelfde manier als het vervangen van een rapport bij een Woo-besluit dat al openbaar is.

#### Verbeterde melding bij het koppelen van zaaknummers aan documenten

Bij het koppelen van zaaknummers aan documenten kan het voorkomen dat er in het Excel-bestand documentnummers staan die (nog) niet op het platform bekend zijn.
Voorheen zag je enkel de foutmelding: 'Regel [x]: Documentnummer [x] bestaat niet' en was het niet duidelijk of de overige documenten wel succesvol waren gekoppeld aan zaaknummers.

### Website

De pictogrammen in de kolom 'Type' worden weer juist getoond.

Wanneer in de balie werd gekozen voor een periode waarin 'januari 2021', 'januari 2022' of 'januari 2023' voorkwam, werd dit onjuist getoond op de website. Dit is opgelost.

Er is een duidelijker onderscheid gemaakt op de website tussen Woo- en Wob-besluiten.
Ook zijn er tekstuele wijzigingen doorgevoerd, zodat de termen 'Woo-besluit' en 'Wob-besluit' consistent gebruikt worden.

## v1.6.2

*12 november 2024*

### Balie

#### Uploaden besluitbrief aangepast

Het uploaden van een besluitbrief gaat op dezelfde manier als het uploaden van een bijlage bij een besluit. Je klikt op de knop 'Besluitbrief toevoegen', je upload de besluitbrief en vult de betreffende informatie in.
Als 'type document' wordt standaard 'Beslissing op wob-/woo-verzoek' gebruikt. Het is niet nodig om deze handmatig te kiezen zoals bij het uploaden van een bijlage.

### Website

#### Tekstuele aanpassingen

Binnenkort gaat er meer informatie gepubliceerd worden op open.minvws.nl dan de COVID-19 gerelateerde Woo-besluiten die de Programmadirectie Openbaarheid (PDO) om dit moment publiceert.
Hiervoor zijn er tekstuele aanpassingen doorgevoerd op een aantal algemene pagina's de website.

#### Zoekresultaten sorteren op 'Publicatiedatum' en 'Relevantie'

Op de website is het mogelijk om de zoekresultaten te sorteren op 'Publicatiedatum' en 'Relevantie'. Omdat de optie 'Datum besluit' straks enkel nog van toepassing is op Woo-besluiten en niet op andere publicaties, is deze optie weggehaald.

#### Themapagina COVID-19

De pagina waarop gezocht kon worden in alle gepubliceerde Wob-/Woo-besluiten is gewijzigd naar de nieuwe themapagina COVID-19.
Op deze pagina zijn alle Wob-/Woo-besluiten inclusief bijbehorende documenten te vinden die zijn gekoppeld aan de onderwerpen:
Opstart Corona, Overleg VWS, Overleg overig, RIVM, Digitale middelen, Besmettelijkheid kinderen, Scenario's en maatregelen, Medische hulpmiddelen, Capaciteit ziekenhuis, Testen, Vaccinaties en medicatie en Chats.

#### Besluitbrief als apart zoekresultaat

De besluitbrief wordt getoond als apart zoekresultaat op de algemene zoekpagina. Voorheen was het al mogelijk om te zoeken op de inhoud van een besluitbrief, maar werd het besluit getoond als zoekresultaat. Ook is het mogelijk om hierop te filteren.

#### Preview van bijlagen

Er wordt een preview (ook wel thumbnail) getoond van een bijlage bij een besluit wanneer deze het bestandsformaat PDF heeft. Dit werd al gedaan voor de documenten.

Inhoud van de zaakpagina sorteren op basis van 'Datum besluit'
De besluiten en documenten die op een zaakpagina staan worden getoond op basis van de datum van het besluit, van nieuw naar oud. Voorheen werden de documenten en besluiten niet in een logische volgorde getoond.

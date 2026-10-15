<!-- markdownlint-disable MD024 MD036 -->

# Changelog

## Release 3.7.0

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

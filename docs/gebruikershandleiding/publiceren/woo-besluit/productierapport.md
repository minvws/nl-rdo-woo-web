# Productierapport uitgelegd

Vaak zijn er bij een Woo-besluit veel documenten die openbaar gemaakt moeten worden, en om deze makkelijk snel in één keer te uploaden,
wordt er gewerkt met een productierapport die metadata over de documenten bevat. De gegevens worden opgeslagen in een Excel-bestand,
waarbij de kolommen de categorieën aangeven en de rijen de documenten die bij het Woo-besluit horen.

## Voorbeeld template

Om direct aan de slag te kunnen, is [hier](productierapport_template.xlsx) een template van een productierapport te vinden.

## Documentnummer en publicatiecontext

Het documentnummer wordt samengesteld uit de publicatiecontext en het document-ID:

`{publicatiecontext}-{id}`

Gebruik in een nieuw productierapport de kolom `Publicatiecontext`. De waarde moet 1 tot en met 255 tekens bevatten en mag
alleen letters, cijfers, `-`, `.`, `_` en `~` bevatten. Spaties zijn niet toegestaan.

Tot en met **31 december 2026** kan een bestaand productierapport zonder `Publicatiecontext` terugvallen op de kolom `Matter`.
Het platform combineert dan de vaste organisatieprefix met de waarde uit `Matter` tot de publicatiecontext. Vanaf **1 januari 2027**
is `Publicatiecontext` verplicht. Een rapport mag niet tegelijk de kolommen `Publicatiecontext` en `Matter` bevatten.

## Kolom specificaties

Een productierapport kan automatisch gegenereerd worden vanuit Zylab, maar kan ook handmatig samengesteld worden. In de onderstaande
tabel wordt uitgelegd welke kolommen erin moeten staan en wat hun functie is.

| No. | Kolomnaam | Verplicht | Voorbeeld waarde(s) | Beschrijving |
| --- | --- | --- | --- | --- |
| 1. | ID | Ja | 981451, 123g23x | Ieder document heeft een ID. Het ID moet ook gebruikt worden als daadwerkelijke bestandsnaam van het document, zodat de metadata gelinkt kan worden aan het bestand. De combinatie van ID en de publicatiecontext moet uniek zijn. |
| 2. | Publicatiecontext | Ja* | 2025-01, VWS-WOO-Spc | De context waarbinnen het document wordt gepubliceerd. De waarde mag 1 tot en met 255 tekens bevatten en alleen letters, cijfers, `-`, `.`, `_` en `~` bevatten. Samen met het ID vormt de publicatiecontext het documentnummer. |
| 3. | Family ID | Nee | 12345 | Een numeriek kenmerk waarmee e-mails en bijlagen aan elkaar gekoppeld kunnen worden. Deze relatie wordt getoond op de website. De term is afkomstig uit Zylab. |
| 4. | Email Thread ID | Nee | 12345 | Een numeriek kenmerk waarmee e-mails binnen dezelfde conversatie aan elkaar gekoppeld kunnen worden. Deze relatie wordt getoond op de website. De term is afkomstig uit Zylab. |
| 5. | Document | Ja | Technische briefing 25 maart 2020.docx | De documentnaam zoals deze op de website wordt getoond. In het geval van e-mails kan hier het onderwerp van de e-mails worden ingevuld. |
| 6. | File type | Nee | pdf | Het type bronbestand. Mogelijke types zijn: pdf, doc, image, presentation, spreadsheet, email, html, note, database, xml, video, audio, vcard, chat. Dit wordt getoond op de website als: PDF, Word-document, Afbeelding, Presentatie, Spreadsheet, E-mailbericht, Webpagina, Notitie, Database, XML, Video, Audio, Visitekaartje, Chatbericht. Wanneer het veld leeg is of ingevuld met een invalide waarde, is het type Onbekend. |
| 7. | Datum | Ja | 2020-03-26 | Datum van het oorspronkelijke document in format JJJJ-MM-DD of MM/DD/JJJJ. 'Sent at' voor e-mails, 'last modified' voor andere type bestanden. |
| 8. | Beoordeling | Ja | Gedeeltelijk openbaar | De beoordeling of de informatie in het bestand openbaar wordt gemaakt of niet. Er zijn 4 mogelijke beoordelingen*: Deels openbaar, Openbaar, Reeds openbaar, Niet openbaar. [wetten.overheid.nl/BWBR0045754/#Hoofdstuk5](https://wetten.overheid.nl/BWBR0045754/#Hoofdstuk5) |
| 9. | Opgeschort | Nee | Yes | Een document is opgeschort als er bezwaar is tegen de openbaarmaking. Dit wordt aangegeven met ‘yes’ in deze kolom. Als er geen opschorting is, blijft het veld leeg. |
| 10. | Beoordelingsgrond | Nee | 5.1.1c; 5.1.2e; | De wet aanduiding voor de beoordelingsgrond die is gebruikt in het document. Indien er meer dan één is gebruikt, scheiden door ;. [wetten.overheid.nl/BWBR0045754/#Hoofdstuk5](https://wetten.overheid.nl/BWBR0045754/#Hoofdstuk5) |
| 11. | Toelichting | Nee | “dit is de toelichting op de weigeringsgrond” | De toelichting is een kleine uitleg over de inhoud van het document. |
| 12. | Publieke link | Nee | [https://voorbeeldlink.org/](https://voorbeeldlink.org/) | Wanneer een document de beoordeling ‘reeds openbaar’ heeft, kan hier de link worden geplaatst naar waar dit document te vinden is. |
| 13. | Gerelateerd ID | Nee | 2025-01-123g23x | Een relatie leggen tussen documenten door in deze kolom het volledige documentnummer (`publicatiecontext-ID`) in te vullen van het document waar dit document een relatie mee heeft. Gebruik één volledige lookupwaarde en splits deze niet op. De relatie wordt getoond op de website. |
| 14. | Zaaknummer | Nee | 1234567;987654 | Op deze manier koppel je een of meerdere zaaknummer(s) aan het Woo-document. Zaaknummers scheiden met ';' of ','. Dit document wordt toegevoegd aan de zaakpagina van het zaaknummer. |

\* Tot en met **31 december 2026** kan een productierapport zonder kolom `Publicatiecontext` de kolom `Matter` gebruiken als
tijdelijke terugval. Vanaf **1 januari 2027** is de kolom `Publicatiecontext` verplicht.

## Betekenis van de beoordelingen

### Reeds openbaar

Dit document is al openbaar en voor iedereen toegankelijk. In het productierapport wordt verwezen naar de openbare bron van het document.

### Openbaar

Het gehele document wordt openbaar gemaakt met dit besluit.

### Deels openbaar

Het document wordt deels openbaar gemaakt met dit besluit. Een of meerdere beoordelingsgrond(en) van de Woo zijn van toepassing bij dit document.

### Niet openbaar

Het document wordt niet openbaar gemaakt omdat een of meerdere beoordelingsgrond(en) van toepassing zijn. De metadata van het document, zoals bestandsnaam, datum, beoordelingsgrond is opgenomen in het productierapport bij het besluit.

Opgeschort

Een of meerdere belanghebbenden hebben bezwaar gemaakt tegen de openbaarmaking van dit document. Dit bezwaar is nog in behandeling.

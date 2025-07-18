# Productierapport uitgelegd

Vaak zijn er bij een Woo-besluit veel documenten die openbaar gemaakt moeten worden, en om deze makkelijk snel in één keer te uploaden,
wordt er gewerkt met een productierapport die metadata over de documenten bevat. De gegevens worden opgeslagen in een Excel-bestand,
waarbij de kolommen de categorieën aangeven en de rijen de documenten die bij het Woo-besluit horen.

## Voorbeeld template

Om direct aan de slag te kunnen, is [hier](productierapport_template.xlsx) een template van een productierapport te vinden.

## Kolom specificaties

Een productierapport kan automatisch gegenereerd worden vanuit Zylab, maar kan ook handmatig samengesteld worden. In de onderstaande
tabel wordt uitgelegd welke kolommen erin moeten staan en wat hun functie is.

| No. | Kolomnaam         | Verplicht | Voorbeeld waarde(s)                                      | Beschrijving                                                                                                                                                                                                                                                                                                                                                                                                                        |
| --- | ----------------- | --------- | -------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1.  | ID                | Ja        | 981451, 123g23x                                          | Ieder document heeft een ID. Het ID moet ook gebruikt worden als daadwerkelijke bestandsnaam van het document, zodat de metadata gelinkt kan worden aan het bestand. De combinatie van ID, matter en prefix moet uniek zijn binnen de organisatie.                                                                                                                                                                                  |
| 2.  | Matter            | Ja        | 17, 134-123xyz                                           | Het kenmerk van een verzameling van documenten. De term is afkomstig uit Zylab. Wanneer niet wordt gewerkt met Zylab kan bijvoorbeeld een kenmerk worden gebruikt ter aanduiding van het Woo-verzoek. De combinatie van ID, matter en prefix moet uniek zijn binnen de organisatie. Een matter bestaat uit minimaal 2 karakters.                                                                                                    |
| 3.  | Family ID         | Nee       | 12345                                                    | Een numeriek kenmerk waarmee e-mails en bijlagen aan elkaar gekoppeld kunnen worden. Deze relatie wordt getoond op de website. De term is afkomstig uit Zylab.                                                                                                                                                                                                                                                                      |
| 4.  | Email Thread ID   | Nee       | 12345                                                    | Een numeriek kenmerk waarmee e-mails binnen dezelfde conversatie aan elkaar gekoppeld kunnen worden. Deze relatie wordt getoond op de website. De term is afkomstig uit Zylab.                                                                                                                                                                                                                                                      |
| 5.  | Document          | Ja        | Technische briefing 25 maart 2020.docx                   | De documentnaam zoals deze op de website wordt getoond. In het geval van e-mails kan hier het onderwerp van de e-mails worden ingevuld.                                                                                                                                                                                                                                                                                             |
| 6.  | File type         | Nee       | pdf                                                      | Het type bronbestand. Mogelijke types zijn: pdf, doc, image, presentation, spreadsheet, email, html, note, database, xml, video, audio, vcard, chat. Dit wordt getoond op de website als: PDF, Word-document, Afbeelding, Presentatie, Spreadsheet, E-mailbericht, Webpagina, Notitie, Database, XML, Video, Audio, Visitekaartje, Chatbericht. Wanneer het veld leeg is of ingevuld met een invalide waarde, is het type Onbekend. |
| 7.  | Datum             | Ja        | 2020-03-26                                               | Datum van het oorspronkelijke document in format JJJJ-MM-DD + eventueel tijdstip of MM/DD/JJJJ + eventueel tijdstip. ‘Sent at’ voor e-mails, ‘last modified’ voor andere type bestanden.                                                                                                                                                                                                                                            |
| 8.  | Beoordeling       | Ja        | Gedeeltelijk openbaar                                    | De beoordeling of de informatie in het bestand openbaar wordt gemaakt of niet. Er zijn 5 mogelijke beoordelingen*: Gedeeltelijk openbaar, Reeds openbaar, Openbaarmaking, Geen openbaarmaking en Niets aangetroffen. [https://protect-eu.mimecast.com/s/08_uCvgmXcNVyj8Szz4w7?domain=wetten.overheid.nl](https://protect-eu.mimecast.com/s/08_uCvgmXcNVyj8Szz4w7?domain=wetten.overheid.nl)                                         |
| 9.  | Opgeschort        | Nee       | Yes                                                      | Een document is opgeschort als er bezwaar is tegen de openbaarmaking. Dit wordt aangegeven met ‘yes’ in deze kolom. Als er geen opschorting is, blijft het veld leeg.                                                                                                                                                                                                                                                               |
| 10. | Beoordelingsgrond | Ja        | 5.1.1c; 5.1.2e;                                          | De wet aanduiding voor de beoordelingsgrond die is gebruikt in het document. Indien er meer dan één is gebruikt, scheiden door ;. [www.wetten.overheid.nl/BWBR0045754/2023-04-01#Hoofdstuk5](https://wetten.overheid.nl/BWBR0045754/2023-04-01#Hoofdstuk5)                                                                                                                                                                          |
| 11. | Toelichting       | Nee       | “dit is de toelichting op de weigeringsgrond”            | De toelichting is een kleine uitleg over de inhoud van het document.                                                                                                                                                                                                                                                                                                                                                                |
| 12. | Publieke link     | Nee       | [https://voorbeeldlink.org/](https://voorbeeldlink.org/) | Wanneer een document de beoordeling ‘reeds openbaar’ heeft, kan hier de link worden geplaatst naar waar dit document te vinden is.                                                                                                                                                                                                                                                                                                  |
| 13. | Gerelateerd ID    | Nee       | 134-123-wjz-5037                                         | Een relatie leggen tussen documenten door in deze kolom het ID in te vullen van het document waar dit document een relatie mee heeft. Heeft het document een relatie met een document uit een andere matter, gebruik dan [matter]-[ID]. De relatie wordt getoond op de website.                                                                                                                                                     |
| 14. | Zaaknummer        | Nee       | 1234567;987654                                           | Op deze manier koppel je een of meerdere zaaknummer(s) aan het Woo-document. Zaaknummers scheiden met ';' of ','. Dit document wordt toegevoegd aan de zaakpagina van het zaaknummer.                                                                                                                                                                                                                                               |

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

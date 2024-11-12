# Handleiding Administrator

Versie 0.9

## Inhoudsopgave

[1. Algemeen](#1-algemeen)  
[2. Publicatiebeheer](#2-publicatiebeheer)  
[3. Besluitdata](#3-besluitdata)  
[4. Verzoeken-beheer](#4-verzoeken-beheer)  
[5. Statistieken & Monitoring van het takenbeheer (RabbitMQ)](#5-statistieken--monitoring-van-het-takenbeheer-rabbitmq)  
[6. Elasticsearch management](#6-elasticsearch-management)  

---

## 1. Algemeen

Dit document is bedoeld voor administrators/beheerders die in staat moeten zijn om de het open.minvws.nl platform te kunnen beheren.
Voor onderwerpen omtrent toegangsbeheer en onderwerpen-beheer zie de organisatiebeheer handleiding.

In de onderstaande tabel staat de hiërarchie het platform weergegeven. Dit document is bedoeld voor gebruikers met de toegangsrollen
Beheerder en Superbeheerder.

| Rol                | Bevoegdheden                                                                                                                                                                                                                                                                                                                        |
|--------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Superbeheerder     | Een super beheerder heeft toegang tot alle functionaliteiten en heeft toegang tot alle organisaties                                                                                                                                                                                                                                 |
| Beheerder          | Een beheerder heeft toegang tot alle functionaliteit binnen de organisatie.                                                                                                                                                                                                                                                         |
| Organisatie-beheer | Met organisatiebeheer is het mogelijk om gebruikers te beheren door ze aan te maken, te bewerken en te verwijderen. Het is ook mogelijk om zaken en verzoeken aan te maken, echter heeft het geen mogelijkheid om ze te verwijderen. Verder heeft organisatie beher geen toegang tot het lezen van besluiten of andere publicaties. |
| Publicatie-beheer  | Een gebruiker met deze rechten kan zowel publicaties inzien als updaten. Daarbij kan deze wel niet-gepubliceerde publicaties verwijderen                                                                                                                                                                                            |
| Alleen lezen       | Een gebruiker met deze rechten is alleen bevoegd om dossiers en zaken in te zien. De gebruiker is niet bevoegd om deze op enige manier aan te passen.                                                                                                                                                                               |

Naast het organisatiebeheer van de balie is er ook een admin-ingang die meer inzicht geeft in de data op het open.minvws.nl platform,
deze is onderverdeeld in 4 categorieën: Besluit-beheer, Verzoeken-beheer, Statistics & Monitoring en Elasticsearch management.

<img src=images/admin_1.png  alt="In de figuur zie je de lijst met de 4 onderwerpen: publicatie beheer, verzoekenbeheer, statsitieken & monitoring
en elastic search managment"/>

In dit hoofdstuk worden alle functionaliteiten en betekenis van de data uitgelegd.

---

## 2. Publicatiebeheer

Bij publicatiebeheer is het mogelijk om alle publicatie van alle organisaties in te zien. Daarnaast is het mogelijk om per
publicatie de individuele data te zien.

<img src=images/admin_2.png  alt="In de figuur zie je een lisjt met alle publicaties"/>

*Dossier action*
Wanneer je op een dossier druk krijg het volgende menu te zien waar de status van het dossier terugte vinden is. Daarnaast
zijn er enkele dossier acties die uitgevoerd kunnen worden.

Dossier actie bestaat uit vijf verschillende acties die uitgevoerd kunnen worden. Per besluit is het mogelijk om de volgende acties te doen:

<img src=images/admin_3.png  alt="In de figuur zie je een overzicht van de data van 1 publicatie"/>

| Dossier actie                                          | Toelichting                                                                                                                                                                                                                                             |
|--------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Metadata opnieuw indexeren (exclusief document inhoud) | Met behulp van deze knop kan de website worden aangepast wanneer er wijzigingen worden aangebracht in de Excel. Als er nieuwe URL's of namen worden toegevoegd aan de Excel, kan met deze knop de informatie worden bijgewerkt op de website.           |
| Volledig opnieuw indexeren (inclusief document inhoud) | Wanneer documenten in een dossier worden gewijzigd, wordt de index van het dossier automatisch opnieuw bijgewerkt. Als er iets fout gaat is het met deze actie mogelijk om de validatie van de volledigheid van een dossier nog een keer uit te voeren. |
| Compleet status opnieuw bepalen                        | In uitzonderingssituaties wordt de 'compleet' status openieuw bepaald.                                                                                                                                                                                  |
| Downloads opnieuw genereren                            | Omdat de dossierdata losstaat van de documenten, worden deze afzonderlijk geïndexeerd. Het re-indexeren van een dossier is alleen in uitzonderlijke situaties nodig.                                                                                    |
| Inventarislijst opnieuw genereren                      | De inventarislijst voor het gekozen dossier wordt opnieuw samengesteld op basis van de huidige status van het dossier.                                                                                                                                  |

---

## 3. Besluitdata

Over elk besluit kan de volgende data worden ingezien.

<img src=images/admin_4.png  alt="In de figuur zie je een overzicht van de besluit data."/>

| Data                       | Toelichting                                                                                                                                                                       |
|----------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Status                     | Houdt de status van de publicatie bij. Mogelijke statussen: concept, published, scheduled en preview                                                                              |
| Organisatie                | De organisatie die de publicatie heeft uitgevoerd                                                                                                                                 |
| Documenten compleet        | Is de publicatie afgerond of niet. Boolean variabel.                                                                                                                              |
| Verwacht aantal uploads    | De verwachte hoeveelheid document aan de hand van het productierapport                                                                                                            |
| Werkelijke aantal uploads  | het aantal documenten dat is geüpload. Dit aantal kan minder zijn dan de Expected upload count wanneer nog niet alle documenten die op het productie rapport staan zijn geüpload. |
| Upload compleet            | Zijn alle uploads voltooid. Boolean variabel                                                                                                                                      |
| Stap “details” compleet    | Zijn alle details voltooid. Boolean variabel                                                                                                                                      |
| Stap “decision” compleet   | Zijn alle decisions voltooid. Boolean variabel                                                                                                                                    |
| Stap “documents” compleet  | Zijn alle  decisions documenten compleet. Boolean variabele                                                                                                                       |
| Stap “publicatie” compleet | Zijn is de publicatie voltooid. Boolean variabel                                                                                                                                  |

---

## 4. Verzoeken-beheer

<img src=images/admin_5.png  alt="In de figuur zie je het overzicht van alle zaaknummers"/>

Bij het verzoeken beheer is het mogelijk om alle verzoeken die gepubliceerd zijn op het platform in te zien.

Per verzoek is het mogelijk om twee acties uit te voeren. Deze zijn vergelijkbaar met de acties die bij besluiten ook mogelijk zijn.
Omdat een verzoek of ‘inquiry’ speciaal voor de verzoeker is, is deze anders dan een dossier waardoor deze los van elkaar staan en
op verschillende manieren zijn geïndexeerd.

| Actie                | Toelichting                                                                                                                                                                                                                                                           |
|----------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Regenerate inventory | Wanneer er aanpassingen worden gedaan aan de data van het verzoek dan wordt deze met behulp van deze knop ook geüpdatet naar de voorkant (inventarislijst).  Dit gebeurd in principe automatisch maar in uitzonderlijke gevallen kan dit hier handmatig worden gedaan |
| Regenerate archives  | Wanneer documenten worden veranderd in een wob-verzoek, kunnen veranderingen met behulp van deze functionaliteit doorgevoerd worden.  Dit gebeurd in principe automatisch maar in uitzonderlijke gevallen kan dit hier handmatig worden gedaan                        |

---

## 5. Statistieken & Monitoring van het takenbeheer (RabbitMQ)

<img src=images/admin_6.png  alt="In de figuur zie je een overzicht van de status van de verschillende taken van het platform."/>

| Naam           | Toelichting                                                                                                                             |
|----------------|-----------------------------------------------------------------------------------------------------------------------------------------|
| Aliveness-test | De aliveness test is een ping met een bevestiging van open.minvws.nl, die controleert of er nog een verbinding is met RabbitMQ.         |
| es_updates     | Elastic search updates.                                                                                                                 |
| global         | Omdat je bij het uploaden van documenten niet alle documenten compatibel zijn wil je snel die feedback te krijgen of er iets fout gaat. |
| high           | De voorrang van functies, bijvoorbeeld bij het kijken of er documenten missen bij het uploaden.                                         |
| ingestor       | Het daadwerkelijk verwerken van documenten, indexeren in elasticsearch etc.                                                             |

---

## 6. Elasticsearch management

<img src=images/admin_7.png  alt="In de figuur zie je een overzicht van de elasticsearch status"/>

Het concept achter ons systeem omvat twee indexen: één die continu live staat en gebruikt kan worden en een andere waar gegevens
naar geschreven kunnen worden. Dit ontwerp is bedoeld om te voorkomen dat informatie onvindbaar wordt tijdens de bevordering naar de 'live'-status.

Onder normale omstandigheden gaat het lezen en schrijven naar dezelfde index, maar tijdens de rollover worden het lezen en schrijven gescheiden.

Het gebruik van een 'rollover' is alleen zinvol wanneer er sprake is van specifieke updates aan de software van het publicatie platform,
als dit nodig is dan wordt dit angegeven bij de release. Daarnaast kan het nodig zijn bij issues, aanpassingen of updates aan de Elastic Search.
Een nieuwe 'rollover' houdt in dat de volledige Elasticsearch-zoekdatabase opnieuw wordt gevuld met documenten en gegevens.

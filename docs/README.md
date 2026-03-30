# Documentatie

## Bruno

De `bruno-collection` map bevat een Collection die je in de open-source API tool [Bruno](https://www.usebruno.com/) kan importeren. Om je Bruno omgeving voor te bereiden, doe het volgende:

1. [Download](https://www.usebruno.com/downloads), installeer en open Bruno.
2. Klik op het plusje linksboven en kies **Open collection**. Kies de bruno-collection map in de repo.

### Lokaal

Om lokaal te werken met Bruno, volg je de volgende stappen:

1. In een terminal binnen de repo, voer het volgende uit: `task docs:bruno:init`

   Dit zal een `Local.bru` environment file maken in *bruno-collection/environments* met de variabelen `baseUrl` en `organisationId`. Aangezien het organisationId per instance anders is, staat deze file niet in git.
2. In Bruno, selecteer rechtsboven de **Local** environment.
3. In Bruno, voer het volgende request uit: Organisation > **Retrieves the collection of OrganisationDto resources**

   Dit zal de `organisationId` variabele vullen met de waarde van *E2E Test Organisation* van je lokale omgeving.

Je bent nu klaar om Bruno requests uit te voeren.
Let erop dat niet alle requests een default body of de juiste id's hebben.

### Test en Acceptatie

Om met de Test en Acceptatie te communiceren, doe dan het volgende:

1. Maak de file `docs/bruno-collection/.env` aan met de volgende inhoud:

   ```text
   DOMAIN_TEST=
   DOMAIN_ACC=
   ```

   Neem voor de waardes contact op met het team.
2. Plaats in `certs/test` en/of `certs/acc` de `.pem` en `.key` files van een instantie (bijvoorbeeld VWS of MinFin) van de omgeving. Neem voor deze files ook contact op met het team.
3. In Bruno, kies rechtsboven voor **Test** of **Acc** als environment.
4. In Bruno, voer het volgende request uit: Organisation > **Retrieves the collection of OrganisationDto resources**

   Dit zal de `organisationId` variabele vullen met de waarde van *E2E Test Organisation* van de betreffende omgeving.

Nu zullen alle queries naar het betreffende organisation en environment gaan.

## Gebruikershandleiding

Deze map bevat de gebruikershandleiding. Opgezet in Markdown zodat we bij elke build een nieuwe versie kunnen genereren van deze handleiding.

De documentatie is publiekelijk beschikbaar op [open.minvws.nl/documentatie/](https://open.minvws.nl/documentatie).

Om deze lokaal te genereren, gebruik je het volgende commando:

```bash
task docs:build
```

## Technische documentatie

Deze map bevat technische documentatie om de broncode beter mee te kunnen begrijpen binnen deze repository.

- [access-roles.md](technische-documentatie/access-roles.md)
- [commands.md](technische-documentatie/commands.md)
- [definition-of-done.md](technische-documentatie/definition-of-done.md)
- [doctrine.md](technische-documentatie/doctrine.md)
- [dossier-types.md](technische-documentatie/dossier-types.md)
- [elastic_index.md](technische-documentatie/elastic_index.md)
- [environment-settings.md](technische-documentatie/environment-settings.md)
- [development_install.md](technische-documentatie/development_install.md)
- [logging.md](technische-documentatie/logging.md)
- [technical.md](technische-documentatie/technical.md)
- [terminology.md](technische-documentatie/terminology.md)
- [test.md](technische-documentatie/test.md)
- [translations.md](technische-documentatie/translations.md)
- [update.md](technische-documentatie/update.md)
- [usage.md](technische-documentatie/usage.md)

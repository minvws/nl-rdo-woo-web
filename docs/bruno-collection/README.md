# Bruno

Deze map bevat een Bruno collection die je in de gelijknamige open-source API testing tool [Bruno](https://www.usebruno.com/) kan importeren.

Om je Bruno omgeving voor te bereiden, doe het volgende:

1. [Download](https://www.usebruno.com/downloads), installeer en open Bruno.
2. Klik op het plusje linksboven en kies **Open collection**. Kies de bruno-collection map in de repo.
3. Als er rechtsboven een groen schildje met vinkje te zien is dan zit je in safe mode. Klik hierop en schakel de `developer mode` in waarna icoontje verandert naar `</>`. Dit is nodig voor de uitvoer van pre-request scripts.

## Lokaal

Om lokaal te werken met Bruno, volg je de volgende stappen:

1. In Bruno, selecteer rechtsboven de **Local - minvws** environment.
2. In Bruno, rechtermuisklik op de map Init en kies Run.
3. In de dialog, klik nogmaals op Run.

   Dit zal drie requests doen, die de volgende drie variabelen vullen met de uuid's van je lokale omgeving:
    - organisationId, object 'E2E Test Organisation'
    - departmentId, object 'E2E Test Department'
    - subjectId, object 'E2E Test Subject'

Je bent nu klaar om Bruno requests uit te voeren.

## Test en Acceptatie

Om met de Test en Acceptatie te communiceren, doe dan het volgende:

1. Maak de file `docs/bruno-collection/.env` aan met de volgende inhoud:

   ```text
   DOMAIN_TEST_MINVWS=
   DOMAIN_TEST_MINFIN=
   DOMAIN_ACC_MINVWS=
   DOMAIN_ACC_MINFIN=
   ```

   Neem voor de waardes contact op met het team.
2. Plaats in `/certs/test` en/of `/certs/acc` de `.pem` en `.key` files van een instantie (bijvoorbeeld VWS of MinFin) van de omgeving. Neem voor deze files ook contact op met het team.
3. In Bruno, kies rechtsboven voor **Test - minvws** of **Acc - minvws** als environment.
4. In Bruno, voer het volgende request uit: Organisation > **Retrieves the collection of OrganisationDto resources**

   Dit zal de `organisationId` variabele vullen met de waarde van *E2E Test Organisation* van de betreffende omgeving.

Nu zullen alle queries naar het betreffende organisation en environment gaan.

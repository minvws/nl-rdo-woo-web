# Pull request decoration

In PR [#5619](https://github.com/minvws/nl-rdo-woo-web-private/pull/5619) hebben we een PR linter geintroduceerd die forceert dat de PR titel conformeert aan de [Conventional Commits spec](https://www.conventionalcommits.org/).
We gebruiken grotendeels de default configuratie van de [GitHub action](https://github.com/amannn/action-semantic-pull-request), met wat kleine aanpassingen, wat resulteert in de volgende setup:

## Format

De volgende format wordt afgedwongen:

```text
feat(ui): Add `Button` component
^    ^    ^
|    |    |__ Subject
|    |_______ Scope
|____________ Type
```

### Type (vereist)

- feat:
  - Voor de implementatie van een User Story
- build:
  - Wijzigingen aan het build systeem of externe dependencies
- ci:
  - Voor wijzigingen aan de GitHub workflows
- docs:
  - Voor wijzigingen aan de documentatie (in de /docs map)
- style:
  - Voor wijzigingen die de werking van de code niet aanpassen (white-space, formatting, ontbrekende semi-colons, etc)
- refactor:
  - Een code wijziging die geen bug fixed of feature toevoegt
- perf:
  - Een code wijziging die de performance verbetert
- test:
  - Voor wijzigingen aan de testen
- fix:
  - Voor overige fixes

### Scope (optioneel)

Als er een GitHub issue is, zet het nummer hiervan in de scope. Als er geen issue is, kan je dit leeg laten of een andere waarde in zetten.

Voorbeelden:

- feat(1234): Lorem ipsum dolor sit amet, consectetur adipiscing elit
- ci(1234): Lorem ipsum dolor sit amet, consectetur adipiscing elit
- fix(1234): Lorem ipsum dolor sit amet, consectetur adipiscing elit
- fix: Lorem ipsum dolor sit amet, consectetur adipiscing elit
- test(e2e): Lorem ipsum dolor sit amet, consectetur adipiscing elit
- test(1234): Lorem ipsum dolor sit amet, consectetur adipiscing elit

### Subject (vereist)

Beschrijving van de change, of de titel van het issue in het Engels.

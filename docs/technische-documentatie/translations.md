# Translations

<!-- TOC -->
- [Translations](#translations)
  - [Key structure](#key-structure)
  - [Syntax](#syntax)
  - [Tools](#tools)
  - [Considerations](#considerations)
<!-- TOC -->

Currently, both the public website and the admin (Balie) are only available in Dutch (`default_locale: nl` in
`config/packages/translation.yaml`) but to enable future internationalisation most content in UI comes from a
translation file where text is linked to a key that can be used in the Twig templates.

The translation files live in `translations/`. `messages+intl-icu.nl.yaml` holds the bulk of the UI, with
`attachment+intl-icu.nl.yaml`, `validators.nl.yaml` and `security.nl.yaml` alongside it. There are English files too
(`messages+intl-icu.en.yaml` and `validators.en.yaml`) but they are only partially filled, so do not treat English as a
supported locale.

These keys are structured in a specific manner which will be explained below.

## Key structure

The translation keys are set up in the following way: `domain.feature.semantic.term`

**domain**: refers to either `public` (public website) or `admin` (balie). If the same term is used on both you can omit
this part, which is what the `global.*` keys do. A handful of other top-level segments predate this convention
(`history`, `publication`, `dossier`, `categories`, `elastic`); follow the convention for new keys rather than those.

**feature**: refers to where the term occurs, ie `global`, throughout the entire domain or `footer` when it's only occurrence is there.

**semantic**: if the term is used as a navigational item (`label`) or a title (`title`) or has any other semantical meaning.

**term**: if a key only refers to a single word or combination that could be considered a single word (ie: published on)
than the term can be added to the translation key (ie `global.published_on`).

## Syntax

- all parts of the key are separated by `.`
- any part of a key can be omitted
- parts that consist of more than one word are connected by `_` (ie `published_by`)

## Tools

Symfony offers some tooling to check whether a translation is missing or not being used. It can be useful to run this check once in a while to keep the translation file(s) as lean as possible and prevent clutter. Take a look at [these commands](https://symfony.com/doc/current/translation.html#how-to-find-missing-or-unused-translation-messages).

## Considerations

Always check when you add a new translation key whether a term (or something very similar) already exists. Or move a key to the global namespace when it's used in multiple domains or features.

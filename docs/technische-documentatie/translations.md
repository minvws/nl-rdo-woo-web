# Translations

<!-- TOC -->
- [Translations](#translations)
  - [Key structure](#key-structure)
  - [Syntax](#syntax)
  - [Tools](#tools)
  - [Considerations](#considerations)
<!-- TOC -->

Currently, both the public website and the admin (Balie) are only available in Dutch but to enable future internationalisation most content in UI comes from a translation file where text is linked to a key that can be used in the Twig templates.

These keys are structured in a specific manner which will be explained below.

## Key structure

The translation keys are set up in the following way: `domain.feature.semantic.term`

**domain**: refers to either `public` (public website) or `admin` (balie). If the same term is used on both you can omit this part.

**feature**: refers to where the term occurs, ie `global`, throughout the entire domain or `footer` when it's only occurrence is there.

**semantic**: if the term is used as a navigational item (`label`) or a title (`title`) or has any other semantical meaning.

**term**: if a key only refers to a single word or combination that could be considered a single word (ie: published by) than the term can be added to the translation key (ie `public.publications.published_by`).

## Syntax

- all parts of the key are separated by `.`
- any part of a key can be omitted
- parts that consist of more than one word are connected by `_` (ie `published_by`)

## Tools

Symfony offers some tooling to check whether a translation is missing or not being used. It can be useful to run this check once in a while to keep the translation file(s) as lean as possible and prevent clutter. Take a look at [these commands](https://symfony.com/doc/current/translation.html#how-to-find-missing-or-unused-translation-messages).

## Considerations

Always check when you add a new translation key whether a term (or something very similar) already exists. Or move a key to the global namespace when it's used in multiple domains or features.

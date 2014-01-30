
# Opdracht
Maak een applicatie die met behulp van het Silex microframework de inbox van een mail account uitleest.

* Gebruik het Silex microframework
* Voorzie enkele settings om de mailaccount gegevens in te vullen
* Gebruik IMAP
* Output alle emails uit de inbox met behulp van de Twig Template Engine
* Enkel de inbox is genoeg, andere mappen mag je negeren

# Getting started:

    composer install
    vagrant up

go to http://localhost:8080

# NOTES:

Notable bad/missing/insecure parts from the top of my head:

- blobstore (essentially a cache for mail part contents) never cleans up old parts.
- hardcoded path (/tmp), should be easy to fix using the app config service
- in the blobstore, message id and part content id are used as file paths as-is.
- there's a bunch of TODO's and FIXME's
- html emails are displayed as-is, against all privacy and security considerations.
- there's a bunch of unused modules in composer.json



# Opdracht
Maak een applicatie die met behulp van het Silex microframework de inbox van een mail account uitleest.

* Gebruik het Silex microframework
* Voorzie enkele settings om de mailaccount gegevens in te vullen
* Gebruik IMAP
* Output alle emails uit de inbox met behulp van de Twig Template Engine
* Enkel de inbox is genoeg, andere mappen mag je negeren

# Gebruik

Op uw machine:

    vagrant up
    composer install
    vagrant ssh

Op de VM:

    # Dit is een beetje ongelukkig - op de /vagrant mount kan je geen ownership wijzigen
    cp /vagrant/mailbox.db /tmp
    chown www-data:www-data /tmp/mailbox.db

-> http://localhost:8080


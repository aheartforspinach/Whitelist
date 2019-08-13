# Whitelist
Das Plugin erstellt am 01. eines jeden Monats eine Liste von allen Accounts, wo sich jeder User einzeln für jeden Charakter entscheiden kann, ob er sie behalten möchte oder nicht. Zudem werden abwesende Charaktere herausgefiltert wie auch welche, die auf Eis sind, falls man das möchte. Außerdem wird die Liste zurückgesetzt, so dass jeder User sich jeden Monat neu entscheiden muss.


## Funktionen
* alle Charaktere zu einem User werden aufgelistet und man kann sie über einen Account verwalten
* werden einsortiert in "Bleibt" und "Geht"
* automatischen Zurücksetzten am 01.MM. um 0 Uhr
* abwesende Charaktere werden gesondert gelistet und müssen sich nicht zurückmelden
* Charaktere, die auf Eis liegen, werden gesondert gelistet und müssen sich nicht zurückmelden (falls aktiviert)
* Bewerber können von der Liste ausgeschlossen werden
* es ist einstellbar, dass man nur seine eigenen Charaktere auf der Liste sieht
* nach einem gewissen Datum kann man sich nicht mehr zurückmelden
* man kann seinen Charakter nur auf "Bleibt" setzten, wenn er in den letzten x Monaten/Wochen einen Post geschrieben hat (optional)
* Hinweisbanner erscheint (wegklickbar)


## Voraussetzungen
* [Enhanced Account Switcher](http://doylecc.altervista.org/bb/downloads.php?dlid=26&cat=2) muss installiert sein 
* FontAwesome muss eingebunden sein, andernfalls muss man die Icons in den PHP-Datein ersetzen


## Template-Änderungen
__Neue globale Templates:__
* whitelist
* whitelistCharacters
* whitelistHeader
* whitelistIce
* whitelistUser

__Veränderte Templates:__
* header (wird um die Variable {$header_whitelist} erweitert)


## Auf Eis Profilfeld
Solltet ihr von der "Auf Eis"-Funktion Gebrauch machen wollen, müsst ihr händisch im Admin-CP ein neues Profilfeld anlegen, welches über Radiobuttons und den auswählbaren Funktionen "Ja" und "Nein" verfügt


## Vorschaubilder
__Einstellungen des Whitelist-Plugin__
![Whitelist Einstellungen](https://i.imgur.com/9D2jdPn.png)

__Whitelistseite ohne "Auf Eis"__
![Whitelistseite ohne "Auf Eis"](https://i.imgur.com/PVS1JVG.png)

__Whitelistseite mit "Auf Eis"__
![Whitelistseite mit "Auf Eis"](https://i.imgur.com/4PeenZy.png)

__Whitelistbanner__
![Whitelistbanner](https://i.imgur.com/uItM8rI.png)

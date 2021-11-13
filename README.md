# Whitelist 2.0
Das Plugin erstellt an einem spezifisschen Tag eines jeden Monats eine Liste von allen Accounts, wo sich jeder User einzeln für jeden Charakter entscheiden kann, ob er sie behalten möchte oder nicht. Zudem werden abwesende Charaktere herausgefiltert wie auch welche, die auf Eis sind, falls man das möchte. Außerdem wird die Liste zurückgesetzt, so dass jeder User sich jeden Monat neu entscheiden muss.

## Update
Dieser Branch unterstützt den Inplaytracker 3.0 von Jule. Falls ihr den Tracker in der Version 2.0 verwendet, müsst ihr [diesen Code](https://github.com/aheartforspinach/Whitelist/tree/version1) herunterladen

__Änderungen zu Version 1.0__
* Nutzung des Inplaytracker 3.0
* Verschiebung der Templates vom globalen in den stylespezifischen
* Unterstützung von Sprachdateien
* statt einem Profilfeld wird es nun alleinig über die Datenbanktabelle `users` abgebildet
* Datenbankabfragen wurden deutlich reduziert
* Man kann alle Charaktere auf einmal zurückmelden
* Es wird eine css-Datei namens `whitelist.css` angelegt

Wenn ihr das Whitelistplugin 1.0 verwendet, ladet den Quellcode herunter und bei euch wieder hoch **ohne** das Plugin zu deaktivieren oder zu deinstallieren. Anschließend müsst ihr in eurem Forum die URL `/misc.php?action=whitelist-update`. Die neuen Templates werden erstellt, aber die alten, die im globalen liegen, bleiben bestehen, damit ihr noch Dinge sichern könnt. Ich würde euch aber empfehlen diese zu löschen anschließend - ebenso wie das Profilfeld. Das Update nicht unbedingt durchführen, wenn eine aktive Whitelist läuft, weil diese Felder zurück gesetzt werden

Die CSS-Datei muss einmal manuell nach dem Update angelegt werden. Einfach einen neuen Stylesheet mit dem Namen `whitelist` anlegen und folgenden Inhalt einfügen:

```
.whitelist-form-heading-container {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.whitelist-form-heading-container .button {
    height: min-content;
    align-self: center;
    margin-left: 15px;	
}

.whitelist-form-heading-container form {
    align-self: center;
    margin-left: 20px;
}

.whitelist-form-characters-container {
    display: grid;
    grid-gap: 15px;
    grid-template-columns: repeat(5, 1fr);
}

.whitelist-banner-close {
    font-size: 14px;
    margin-top: -2px;
    float: right;
}
```


## Funktionen
* alle Charaktere zu einem User werden aufgelistet und man kann sie über einen Account verwalten
* werden einsortiert in "Bleibt" und "Geht"
* automatischen Zurücksetzten am 01.MM. um 0 Uhr
* abwesende Charaktere werden gesondert gelistet und müssen sich nicht zurückmelden
* Charaktere, die auf Eis liegen, werden gesondert gelistet und müssen sich nicht zurückmelden (falls aktiviert)
* Gruppen können von der Liste ausgeschlossen werden
* es ist einstellbar, dass man nur seine eigenen Charaktere auf der Liste sieht
* nach einem gewissen Datum kann man sich nicht mehr zurückmelden
* man kann seinen Charakter nur auf "Bleibt" setzten, wenn er in den letzten x Monaten/Wochen einen Post geschrieben hat (optional)
* Hinweisbanner erscheint (wegklickbar)


## Voraussetzungen
* [Enhanced Account Switcher](http://doylecc.altervista.org/bb/downloads.php?dlid=26&cat=2) muss installiert sein 
* [Inplaytracker 3.0](https://github.com/ItsSparksFly/mybb-inplaytracker/) muss installiert sein
* FontAwesome muss eingebunden sein, andernfalls muss man die Icons in den PHP-Datein ersetzen


## Template-Änderungen
__Neue globale Templates:__
* whitelist
* whitelist_characters
* whitelist_form
* whitelist_header
* whitelist_ice_td
* whitelist_ice_th
* whitelist_user

__Veränderte Templates:__
* header (wird um die Variable `{$header_whitelist}` erweitert)


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

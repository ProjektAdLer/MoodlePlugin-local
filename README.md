# AdLer Moodle Plugin

[![Coverage Status](https://coveralls.io/repos/github/ProjektAdLer/MoodlePluginLocal/badge.svg?branch=main)](https://coveralls.io/github/ProjektAdLer/MoodlePluginLocal?branch=main)


## Dependencies
> [!NOTE]
> Dieses Projekt folgt nicht vollständig dem Moodle-Ansatz zur Angabe von Abhängigkeiten in der version.php-Datei, 
> da dies Probleme bei Updates und Deinstallationen verursacht.

| Plugin             | Version |
|--------------------|---------|
| availability_adler | ~3.0.0  |
| local_logging      | jede    |


## Kompabilität
Folgende Versionen werden unterstützt (mit mariadb und postresql getestet):

[siehe hier](_dependencies.json)



## MBZ api endpunkt
Damit der mbz api endpunkt auch mit größeren Dateien funktioniert sind folgende Änderungen an der php.ini notwendig:
```
- `post_max_size` auf mindestens 2048M setzen
- `upload_max_filesize` auf 2048M setzen
- `max_input_time` auf 600 setzen
- `memory_limit` auf mindestens 256M setzen
- `max_execution_time` auf mindestens 60 setzen
- `output_buffering` auf 8192 setzen
```


## Setup
1. Dependencies beachten
2. Plugin in moodle in den Ordner `local` entpacken (bspw. moodle/local/adler/lib.php muss es geben)
3. Moodle upgrade ausführen


## Dev Setup / Tests
Dieses Plugin nutzt Mockery für Tests. 
Die composer.json von Moodle enthält Mockery nicht, daher enthält das Plugin eine eigene composer.json.

Für das weitere Test-Setup siehe [AdlerDevelopmentEnvironment](https://github.com/ProjektAdLer/AdlerDevelopmentEnvironment/tree/main/moodle),
dort sind auch die Schritte zur weiteren PHPStorm Konfiguration beschrieben.

Hinweise:
- Um Tests lokal nicht in `@RunTestsInSeparateProcesses` ausführen zu müssen in der Datei `moodle/lib/setuplib.php` in 
  der Funktion `require_phpunit_isolation()` vor der Exception `return;` einfügen. \

- **Achtung**: Dies kann potenziell unerwartete Nebeneffekte haben! Tests werden nicht umsonst normalerweise in 
isolierten Prozessen ausgeführt.


## Löschen eines Kurses / von Lernelementen
- Moodle hat einen Trashbin, das tatsächliche Löschen findet zeitverzögert statt. 
- Wird ein Kurs gelöscht gibt es keine Möglichkeit zu wissen, welche Lernelemente dabei entfernt wurden.
  Nach dem Löschen eines Kurses wird für alle Adler Einträge verglichen, ob das dazugehörige cm noch existiert. 
  Dies könnte bei sehr großen Moodle Instanzen zu Performance Problemen führen.
  Ein workaround wäre eine redundante Datenhaltung der Kurs-ID bei jedem Eintrag.

| Anzahl verbleibende Lernelemente mit Adler-Scores | Anzahl der Lernelemente mit Adler-Scores des gelöschten Kurses | Zeit  |
|---------------------------------------------------|----------------------------------------------------------------|-------|
| 1k                                                | 100                                                            | 0,12s |
| 10k                                               | 100                                                            | 2,5s  |
| 100k                                              | 100                                                            | 186s  |



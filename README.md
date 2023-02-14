# AdLer Moodle Plugin

## Setup
Setup funktioniert exakt wie bei allen anderen Plugins auch (nach dem manuellen Installationsvorgang, da unser Plugin nicht im Moodle Store ist).

1. Plugin In moodle in den Ordner `local` entpacken (bspw moodle/local/adler/lib.php muss es geben)
2. Moodle upgrade ausführen

Damit ist die Installation abgeschlossen. Als nächstes kann ein mbz mit den Plugin-Feldern wiederhergestellt werden.

### Kurs mit dummy Daten seeden
Für Testzwecke können bestehende normale Kurse mit dummy Daten gefüllt werden. 
Dazu liegen im Ordner `dev_utils/seed` zwei Skripte, die das automatisieren.
Zuerst im Script `course.php` die Kurs-ID eintragen, die gefüllt werden soll. 
Dann das Script ausführen `php local/adler/dev_utils/seed/course.php`.
Danach im Script `scores.php` die Kurs-ID eintragen, die gefüllt werden soll (die selbe wie im vorherigen Script).
Dann das Script ausführen `php local/adler/dev_utils/seed/scores.php`.

Nun kann dieser Kurs zum testen genutzt werden.


## Context ID für xapi events
xapi Events nutzen die Context-ID, um die Kurs-ID zu referenzieren.
Diese sind aktuell über keine API des Plugins verfügbar, da sie für das moodle-interne Rechtemanagement gedacht sind.
Um für Testzwecke die context-id eines Lernelements zu erhalten kann wie folgt vorgegangen werden:
1. cmid des Lernelements herausfinden: h5p Element im Browser öffnen, in der URL steht die cmid als Parameter `id=123` (bspw http://localhost/mod/h5pactivity/view.php?id=2)
2. in der DB folgende query ausführen: SELECT id FROM mdl_context where contextlevel = 70 and instanceid = 2;  (instanceid ist die id aus step 1)

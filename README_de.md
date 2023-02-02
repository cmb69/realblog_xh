# Realblog_XH

Realblog_XH ermöglicht die Präsentation eines Blogs auf Ihrer CMSimple_XH
Website. Das Plugin bietet die grundlegenden Blog-Funktionalitäten, wie die
Anzeige einer chronologisch geordneten Übersicht der Beiträge, ein optionales
jährliches Archiv, automatisches zeitgesteuertes Veröffentlichen und
Archivieren von Beiträgen, die Suche in den Blog-Inhalten, einen RSS-Feed und
eine sehr einfache Kategorisierung. Separat geschriebene Teaser werden unterstützt.
Teaser und Artikel können beliebiges CMSimple_XH Skripting enthalten. Jeder
Blog-Beitrag kann individuell kommentierbar gemacht werden, was ein kompatibles
Kommentar-Plugin erfordert.

Auf mehrsprachigen Websites hat jede Sprache ihren eigenen Blog; darüber
hinaus hat Realblog_XH keine mehrsprachigen Fähigkeiten.

- [Voraussetzungen](#voraussetzungen)
- [Download](#download)
- [Installation](#installation)
- [Einstellungen](#einstellungen)
- [Verwendung](#verwendung)
  - [Administration](#administration)
  - [Kategorien](#kategorien)
  - [Archiv](#archiv)
  - [RSS-Feed](#rss-feed)
  - [Kommentare](#kommentare)
- [Abwärtskompatibilität](#abwärtskompatibilität)
- [Problembehebung](#problembehebung)
- [Lizenz](#lizenz)
- [Danksagung](#danksagung)

## Voraussetzungen

Realblog_XH ist ein Plugin für CMSimple_XH.
Es benötigt CMSimple_XH ≥ 1.7.0 und PHP ≥ 5.6.0 mit der sqlite3 Erweiterung.

## Download

Das [aktuelle Release](https://github.com/cmb69/realblog_xh/releases/latest)
kann von Github herunter geladen werden.

## Installation

The Installation erfolgt wie bei vielen anderen CMSimple_XH-Plugins auch.
Im [CMSimple_XH-Wiki](https://wiki.cmsimple-xh.org/de/?fuer-anwender/arbeiten-mit-dem-cms/plugins)
finden Sie weitere Informationen.

1. **Sichern Sie die Daten auf Ihrem Server.**
1. Entpacken Sie die ZIP-Datei auf Ihrem Computer.
1. Laden Sie das gesamte Verzeichnis `realblog/` auf Ihren Server
   in das `plugins/` Verzeichnis von CMSimple_XH hoch.
1. Vergeben Sie Schreibrechte für die Unterverzeichnisse `css/`, `config/`
   und `languages/`.
1. Navigieren Sie zu `Plugins` → `Realblog`,
   und prüfen Sie, ob alle Voraussetzungen für den Betrieb erfüllt sind.

## Einstellungen

Die Konfiguration des Plugins erfolgt wie bei vielen anderen
CMSimple_XH-Plugins auch im Administrationsbereich der Homepage.
Wählen Sie `Plugins` → `Realblog`.

Sie können die Original-Einstellungen von Realblog_XH unter `Konfiguration`
ändern. Beim Überfahren der Hilfe-Icons mit der Maus werden Hinweise zu den
Einstellungen angezeigt.

Die Lokalisierung wird unter `Sprache` vorgenommen. Sie können die
Zeichenketten in Ihre eigene Sprache übersetzen (falls keine entsprechende
Sprachdatei zur Verfügung steht), oder sie entsprechend Ihren Anforderungen
anpassen.

Das Aussehen von Realblog_XH kann unter `Stylesheet` angepasst werden. Sie
können ebenfalls die Icons, die in der Pluginadministration verwendet werden,
austauschen; alternative Icon-Sets finden sich im `images/` Ordner.

## Verwendung

Um den Blog auf einer CMSimple_XH Seite anzuzeigen, schreiben Sie:

    {{{Realblog_blog()}}}

Um ebenfalls das Suchformular anzuzeigen, schreiben Sie:

    {{{Realblog_blog(true)}}}

Um die Liste der neuesten Artikel auf jeder Seite anzuzeigen,
fügen Sie an einer passenden Stelle des Templates folgendes ein:

    <?=Realblog_link('%BLOG_URL%')?>

`%BLOG_URL%` muss durch die URL der Hauptseite des Blogs ersetzt werden. Details
finden Sie in der Beschreibung der [RSS page Einstellung](#rss-feed).

Um ebenfalls die Teaser dieser Artikel anzuzeigen, schreiben Sie:

    <?=Realblog_link('%BLOG_URL%', true)?>

Um die Liste der beliebtesten Artikel auf jeder Seite anzuzeigen, fügen Sie
an einer passende Stelle des Templates folgendes ein:

    <?=Realblog_mostPopular('%BLOG_URL%')?>

Bezüglich `%BLOG_URL%` beachten Sie den Hinweis weiter oben.

### Administration

In der Hauptadministration des Plugins können Sie die Blog-Beiträge
verwalten. Sie können Beiträge erzeugen, bearbeiten und löschen, ihren Status
ändern usw. Die Administration sollte weitgehend selbsterklärend sein.

Im `Datenaustausch` Bereich können die Artikel in eine CSV-Datei exportiert
und Artikel von einer CSV-Datei importiert werden. Die CSV-Datei muss direkt
neben der entsprechenden Datenbankdatei abgelegt sein, und den Namen
`realblog.csv` haben. Es ist zu beachten, dass die CSV-Datei tatsächlich
Tab-Delimited ist, aber nicht das gleiche Format wie das alte `realblog.txt`
hat, also sollte nicht versucht werden alte Daten durch den CSV-Import zu
importieren. Ebenfalls ist zu beachten, dass der Import alle bestenden
Artikel in der Datenbank überschreibt. *Es wird unbedingt empfohlen ein
Backup der Datenbankdatei anzulegen, bevor von CSV importiert wird!*

Soll die Export-/Import-Funktionalität verwendet werden, um Artikel offline
zu bearbeiten, muss sichergestellt werden, dass nach dem Export und vor dem
Import die Datenbank online nicht geändert wird. Andernfalls werden diese
Änderungen beim Re-Import der Artikel überschrieben. Ebenfalls ist zu
beachten, dass die IDs (erste Spalte) nicht geändert werden dürfen, weil
diese verwendet werden, um die Page-Views zu referenzieren. Zeilen dürfen
beliebig gelöscht werden, aber wenn neue Zeilen eingefügt werden sollen,
dann ist dies am Ende der Datei mit fortlaufenden IDs zu tun.

### Kategorien

Realblog_XH hat derzeit nur eine sehr grundlegende Unterstützung von Kategorien.
Um die Kategorien zu definieren, zu denen ein Beitrag gehört, tragen Sie die durch
*Komma getrennte* Kategorienamen in das entsprechende Feld im Artikelformular ein:

    Kategorie 1,Kategorie 2

Beachten Sie, dass Sie beliebig viele Kategorien definieren können.

Es ist Besuchern nicht möglich nach Kategorien zu filtern, aber Sie können
separate CMSimple_XH Seiten für jede Kategorie anlegen, und die entsprechenden
Beiträge auf diesen Seiten anzeigen lassen, wenn Sie ein zweites Argument an
Realblog_blog() übergeben:

    {{{Realblog_blog(false, 'Kategorie 1')}}}

### Archiv

Um das Blog-Archiv auf einer CMSimple_XH Seite anzuzeigen, schreiben Sie:

    {{{Realblog_archive()}}}

Um ebenfalls das Suchformular anzuzeigen, schreiben Sie:

    {{{Realblog_archive(true)}}}

Beachten Sie, dass das Blog-Archiv *nicht* auf der selben Seite
angezeigt werden darf wie der eigentliche Blog.

### RSS-Feed

Wenn die entsprechende Option konfiguriert ist, bietet Realblog_XH
automatisch einen RSS-Feed mit den veröffentlichten Blog-Beiträgen an. Optional
können Sie ein RSS-Feed-Icon, das auf den Feed verlinkt, im Template anzeigen
lassen:

    <?=Realblog_feedLink()?>

Es ist ebenfalls möglich einen einzigen Parameter an diese Funktion zu
übergeben, der den Wert des target Attributs des Hyperlinks angibt. Dies
kann genutzt werden, um den Feed in einem neuen Window/Tab anzuzeigen:

    <?=Realblog_feedLink('_blank')?>

Abgesehen von einigen Einstellung bzgl. des Feeds in der Konfiguration gibt
es einige Einstellungen in der Sprachdatei im Abschnitt `RSS`. Die wichtigste
ist `page`, wo Sie die URL der Seite, auf der der Hauptblog angezeigt wird,
eintragen müssen. Am besten navigieren Sie zu dieser Seite, und kopieren alles
nach dem Fragezeichen bis zum (exklusive) ersten Kaufmanns-Und (`&`), oder bis
zum Ende, falls in der URL kein Kaufmanns-Und enthalten ist, aus der
Adressleiste des Browsers.

### Kommentare

Um eine Kommentar-Möglichkeit zu Ihrem Blog hinzuzufügen, müssen Sie ein
kompatibles Kommentar-Plugin installieren, und dessen Namen in der Konfiguration
von Realblog_XH eintragen.

Hinweis für Implementierer: um mit Realblog_XH kompatibel zu sein, müssen Sie
eine Klasse mit dem Namen `%IHRPLUGIN%\RealblogBridge` definieren, die das
`Interface` `Realblog\CommentsBridge` implementiert, das in
`plugins/realblog/classes/CommentsBridge.php` definiert und dokumentiert ist.
Stellen Sie sicher, dass diese Klasse und ihre Abhängigkeiten geladen sind, wenn
Realblog_XH sie braucht; Autoloading wird empfohlen.

## Abwärtskompatibilität

Realblog_XH ist weitgehend abwärtskompatibel zu Realblog 2.8, so dass Sie
dessen Datendateien (`realblog.txt`) und Pluginaufrufe weiter verwenden können.
Allerdings gelten diese Pluginaufrufe als missbilligt, und können in einer späteren
Version entfernt werden.

Die RSS-Feed-Dateien (`realblog_rss_feed.xm`l`) werden nicht mehr verwendet;
statt dessen werden die Feeds dynamisch generiert. Sie sollten die alten
Dateien löschen, so dass News-Reader nicht die alten Inhalte aufschnappen.

## Problembehebung

Melden Sie Programmfehler und stellen Sie Supportanfragen entweder auf
[Github](https://github.com/cmb69/realblog_xh/issues)
oder im [CMSimple_XH Forum](https://cmsimpleforum.com/).

## Lizenz

Realblog_XH ist freie Software. Sie können es unter den Bedingungen
der GNU General Public License, wie von der Free Software Foundation
veröffentlicht, weitergeben und/oder modifizieren, entweder gemäß
Version 3 der Lizenz oder (nach Ihrer Option) jeder späteren Version.

Die Veröffentlichung von Realblog_XH erfolgt in der Hoffnung, daß es
Ihnen von Nutzen sein wird, aber *ohne irgendeine Garantie*, sogar ohne
die implizite Garantie der *Marktreife* oder der *Verwendbarkeit für einen
bestimmten Zweck*. Details finden Sie in der GNU General Public License.

Sie sollten ein Exemplar der GNU General Public License zusammen mit
Realblog_XH erhalten haben. Falls nicht, siehe <https://www.gnu.org/licenses/>.

© 2006-2010 Jan Kanters  
© 2010-2014 Gert Ebersbach  
© 2014-2023 Christoph M. Becker

Russische Übersetzung © 2012 Lybomyr Kydray  
Slovakische Übersetzung © 2014 Dr. Martin Sereday  
Niederländische Übersetzung © 2015 Emile Bastings

## Danksagung

Realblog_XH ist ein [Fork](https://de.wikipedia.org/wiki/Abspaltung_(Softwareentwicklung))
von Realblog 2.8, das von [Gert Ebersbach](https://www.ge-webdesign.de/)
entwickelt wird. Realblog (das früher Realblog_XH hieß) basiert
auf Advancednews 1.0.5 von Jan Kanters. Vielen Dank an beide, dass sie
diese beliebten und nützlichen Plugins unter GPL zur Verfügung stellen.

Realblog_XH verwendet jscalendar, das von [Mihai Bazon](http://www.dynarch.com/)
entwickelt wurde. Vielen Dank an den Entwickler für die Veröffentlichung
dieser Komponente unter LGPL.

Das Plugin-Icon wurde von [Alessandro Rei](http://www.mentalrey.it/) gestaltet.
Vielen Dank für die Veröffentlichung des Icons unter GPL.

Das Feed-Icon wurde von [Anomie](https://en.wikipedia.org/wiki/User:Anomie)
gestaltet. Vielen Dank für die Veröffentlichung unter GPL.

Dieses Plugin verwendet "material icons" von [Google](https://fonts.google.com/icons?selected=Material+Icons)
und "free applications icons" von [Aha-Soft](http://www.aha-soft.com/).
Vielen Dank für die freie Verwendbarkeit dieser Icons.

Vielen Dank an die Gemeinschaft im [CMSimple_XH-Forum](https://www.cmsimpleforum.com/)
für Tipps und Anregungen und das Testen.
Besonders möchte ich *frase* für viele gute Vorschläge und das Vorantreiben der Entwicklung danken.

Und zu guter letzt vielen Dank an [Peter Harteg](http://www.harteg.dk/),
den „Vater“ von CMSimple, und allen Entwicklern von
[CMSimple_XH](https://www.cmsimple-xh.org/de/) ohne die es dieses
phantastische CMS nicht gäbe.

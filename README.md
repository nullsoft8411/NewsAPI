# NewsAPI Plugin

**Version:** 1.2  
**Autor:** Mustafa Sahin  
**Beschreibung:** Bezieht Nachrichten von NewsAPI und erstellt deutsche Zusammenfassungen. Das Plugin enthält SEO-Optimierungen, automatische Updates, und eine manuelle Freigabeoption.

---

## Übersicht

Das **NewsAPI**-Plugin verbindet sich mit der [NewsAPI](https://newsapi.org) und ruft aktuelle Nachrichten basierend auf einem Keyword ab. Es analysiert und übersetzt diese Nachrichten mit Hilfe der [OpenAI API](https://openai.com) und erstellt deutsche Zusammenfassungen, die dann als Beiträge in WordPress veröffentlicht werden.

### Funktionen:
- Abrufen von Nachrichten von der NewsAPI.
- Automatische Zusammenfassung und Übersetzung der Nachrichten mit OpenAI.
- Automatische Kategorisierung basierend auf dem Inhalt der Nachrichten.
- SEO-Optimierung: Automatische Generierung von Tags und Meta-Beschreibungen.
- Möglichkeit zur manuellen Überprüfung und Freigabe von Nachrichten.
- Cron-Job zur regelmäßigen automatischen Ausführung.
- Automatische Update-Funktion über GitHub.

---

## Installation

### Voraussetzungen:
- WordPress 5.0 oder höher
- NewsAPI- und OpenAI-API-Schlüssel

### Installation des Plugins:
1. Lade das Plugin als ZIP-Datei herunter oder klone das GitHub-Repository:

   ```bash
   git clone https://github.com/nullsoft8411/NewsAPI.git
   ```

2. Lade das Plugin in dein WordPress-Verzeichnis unter wp-content/plugins/ hoch.

3. Gehe in das WordPress-Dashboard zu Plugins, suche das NewsAPI Plugin, und aktiviere es.

4. Gehe zu Einstellungen > NewsAPI, um deine API-Schlüssel für NewsAPI und OpenAI einzugeben und die Einstellungen des Plugins zu konfigurieren.

### API-Schlüssel konfigurieren
Nach der Aktivierung des Plugins musst du die API-Schlüssel für NewsAPI und OpenAI eingeben.

    1. Gehe im WordPress-Dashboard zu Einstellungen > NewsAPI.
    2. Trage deine NewsAPI- und OpenAI-API-Schlüssel in die entsprechenden Felder ein.
    3. Definiere das Keyword, nach dem in der NewsAPI gesucht werden soll.
    4. Wähle den gewünschten Beitragsautor und die maximale Anzahl an Nachrichten aus.
    5. Konfiguriere den Cron-Zeitplan für automatische Ausführungen.

### Automatische Updates
Dieses Plugin unterstützt automatische Updates über GitHub.

    1. Um das Plugin automatisch zu aktualisieren, stelle sicher, dass du dein Plugin in einem öffentlichen GitHub-Repository hostest.
    2. Bei jeder neuen Version, die auf GitHub veröffentlicht wird, wird das Plugin automatisch erkennen, wenn ein Update verfügbar ist.
    3. Du kannst die Updates direkt im WordPress-Dashboard installieren.

### Manuelle Überprüfung von Nachrichten
Wenn der manuelle Modus in den Einstellungen aktiviert ist, kannst du die abgerufenen Nachrichten manuell überprüfen, bevor sie veröffentlicht werden:

    1. Gehe zu Skript-Verwaltung im WordPress-Dashboard.
    2. Sieh dir die Liste der abgerufenen Nachrichten an und entscheide, welche Nachrichten veröffentlicht werden sollen.


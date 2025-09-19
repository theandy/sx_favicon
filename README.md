# sx_favicon

TYPO3 12.4 Extension von **andreas-loewer**, die Favicons aus SVG/PNG/JPG generiert und sie unter stabilen Root-Pfaden wie `/favicon.ico`, `/favicon.svg`, `/apple-touch-icon.png` und `/site.webmanifest` ausliefert – unabhängig vom physischen Speicherort.

## Installation
- In `typo3conf/ext/` ablegen oder via Composer: `composer req andreas-loewer/sx-favicon`
- Datenbank aktualisieren (Install Tool / CLI): `bin/typo3 database:updateschema`

## Nutzung
1. **Backend → Site Management → Favicons** öffnen.
2. Site auswählen, FileReference-UIDs für `svg`, `light`, `dark` eintragen.
3. Speichern – die Favicons werden nach `typo3temp/assets/favicons/<siteIdentifier>/` generiert.
4. Die Middleware liefert die Dateien unter `/favicon.ico`, `/favicon.svg`, `/apple-touch-icon.png`, `/favicon-32x32.png`, `/favicon-32x32-dark.png`, `/site.webmanifest` aus.
5. Im Theme `<head>` die Tags ausgeben:
   ```html
   {sxFavicon:tags() -> f:format.raw()}

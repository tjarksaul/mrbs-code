# Selbstregistrierungs-System - Benutzerhandbuch

## Übersicht

Das Selbstregistrierungs-System ermöglicht es Benutzern, sich eigenständig für ein MRBS-Konto zu registrieren. Der Prozess erfordert eine E-Mail-Verifizierung und eine Freigabe durch einen Administrator.

## Registrierungsablauf

### 1. Benutzer-Registrierung

Der Benutzer:
1. Klickt auf den Link "Neues Konto registrieren" auf der Anmeldeseite
2. Füllt das Registrierungsformular aus mit:
   - Benutzername (nur Buchstaben, Zahlen und Unterstriche)
   - Anzeigename (vollständiger Name)
   - E-Mail-Adresse
   - Verein (Organisation)
   - Funktion im Verein (Rolle)
   - Passwort (zweimal zur Bestätigung)
3. Sendet das Formular ab
4. Erhält eine Bestätigung, dass die Registrierung erfolgreich war

### 2. E-Mail-Verifizierung

Der Benutzer:
1. Erhält eine E-Mail mit einem Bestätigungslink
2. Klickt auf den Link, um die E-Mail-Adresse zu bestätigen
3. Sieht eine Bestätigung, dass die E-Mail verifiziert wurde
4. Wird darüber informiert, dass die Registrierung nun von einem Administrator geprüft wird

### 3. Administrator-Benachrichtigung

Nach der E-Mail-Verifizierung:
1. Erhält der Administrator eine E-Mail mit den Registrierungsdetails
2. Die E-Mail enthält einen Link zur Freigabe-Seite
3. Der Administrator kann die Anfrage genehmigen oder ablehnen

### 4. Administrator-Freigabe

Der Administrator:
1. Klickt auf den Link in der Benachrichtigungs-E-Mail
2. Sieht die vollständigen Registrierungsdetails:
   - Name
   - Benutzername
   - E-Mail-Adresse
   - Verein
   - Funktion im Verein
   - Zeitpunkt der Anfrage
3. Entscheidet:
   - **Genehmigen**: Erstellt das Benutzerkonto und sendet Bestätigung an den Benutzer
   - **Ablehnen**: Lehnt die Registrierung ab (kein Konto wird erstellt)

### 5. Benutzer-Benachrichtigung

Nach der Genehmigung:
1. Erhält der Benutzer eine E-Mail mit der Bestätigung
2. Die E-Mail enthält:
   - Bestätigung der Freischaltung
   - Benutzername für die Anmeldung
   - Link zur Anmeldeseite
3. Der Benutzer kann sich nun anmelden

## Konfiguration

### Administrator-E-Mail-Adresse

Legen Sie in der `config.inc.php` die E-Mail-Adresse für Registrierungsbenachrichtigungen fest:

```php
$registration_approval_email = "admin@ihr-verein.de";
```

Falls nicht gesetzt, wird die Standard-Admin-E-Mail verwendet (`$mail_settings['recipients']`).

### Passwort-Richtlinien

Die bestehenden MRBS-Passwortrichtlinien gelten auch für Registrierungen. Konfigurieren Sie diese in `config.inc.php`:

```php
$pwd_policy['length'] = 8;
$pwd_policy['alpha'] = 1;
$pwd_policy['lower'] = 1;
$pwd_policy['upper'] = 1;
$pwd_policy['numeric'] = 1;
$pwd_policy['special'] = 1;
```

## Datenbank-Einrichtung

### Neue Installation

Bei einer neuen Installation wird die Tabelle `mrbs_registration_requests` automatisch mit den Skripten `tables.my.sql` oder `tables.pg.sql` erstellt.

### Bestehende Installation

Für bestehende Installationen führen Sie das Upgrade-Skript aus:

**MySQL:**
```sql
SOURCE web/upgrade/83/mysql.sql;
```

**PostgreSQL:**
```sql
\i web/upgrade/83/pgsql.sql
```

## Verwaltung von Registrierungen

### Ausstehende Registrierungen anzeigen

Ausstehende Registrierungen können direkt in der Datenbank eingesehen werden:

```sql
SELECT username, display_name, email, organization, role, 
       email_verified, approved, rejected,
       FROM_UNIXTIME(created_at) as created_at
FROM mrbs_registration_requests
WHERE approved = 0 AND rejected = 0
ORDER BY created_at DESC;
```

### Manuelle Freigabe (optional)

Falls die E-Mail-Benachrichtigung nicht funktioniert, können Sie Registrierungen manuell freigeben:

1. Finden Sie den `approval_token` in der Datenbank:
   ```sql
   SELECT approval_token FROM mrbs_registration_requests 
   WHERE email = 'benutzer@example.com' AND email_verified = 1;
   ```

2. Rufen Sie die Freigabe-URL auf:
   ```
   https://ihr-mrbs.de/approve_registration.php?token=APPROVAL_TOKEN
   ```

### Alte Einträge bereinigen

Periodisch sollten alte, abgeschlossene Registrierungen bereinigt werden:

```sql
-- Genehmigte und abgelehnte Registrierungen älter als 90 Tage löschen
DELETE FROM mrbs_registration_requests 
WHERE (approved = 1 OR rejected = 1) 
AND FROM_UNIXTIME(created_at) < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

## Sicherheitshinweise

### Wichtige Sicherheitsmerkmale

1. **Keine Anmeldung ohne Freigabe**: Benutzer können sich erst anmelden, nachdem ihr Konto vom Administrator freigegeben wurde
2. **E-Mail-Verifizierung erforderlich**: Benutzer müssen ihre E-Mail-Adresse bestätigen, bevor die Anfrage an den Administrator gesendet wird
3. **Token-basierte Sicherheit**: Sowohl E-Mail-Verifizierung als auch Freigabe verwenden kryptographisch sichere Tokens
4. **Passwort-Hashing**: Alle Passwörter werden sicher gehasht gespeichert

### Best Practices

1. **Überprüfen Sie Registrierungen zeitnah**: Reagieren Sie schnell auf Registrierungsanfragen
2. **Prüfen Sie die Angaben**: Kontrollieren Sie Verein und Funktion auf Plausibilität
3. **Lehnen Sie verdächtige Anfragen ab**: Bei Zweifeln lehnen Sie die Registrierung ab
4. **Überwachen Sie Missbrauch**: Achten Sie auf mehrfache Registrierungen von derselben Person

## Fehlerbehebung

### Benutzer erhält keine E-Mail

1. Prüfen Sie die E-Mail-Konfiguration in `config.inc.php`
2. Überprüfen Sie die Mail-Server-Logs
3. Stellen Sie sicher, dass SPF/DKIM/DMARC korrekt konfiguriert sind
4. Bitten Sie den Benutzer, den Spam-Ordner zu überprüfen

### Administrator erhält keine Benachrichtigung

1. Prüfen Sie, ob `$registration_approval_email` gesetzt ist
2. Überprüfen Sie, ob die E-Mail-Verifizierung erfolgreich war
3. Schauen Sie in die System-Logs nach Fehlermeldungen

### Registrierung schlägt fehl

1. Prüfen Sie, ob die Datenbank-Tabelle existiert:
   ```sql
   SHOW TABLES LIKE 'mrbs_registration_requests';
   ```
2. Überprüfen Sie die PHP-Error-Logs
3. Stellen Sie sicher, dass alle erforderlichen Felder ausgefüllt sind

### Token ist ungültig

1. Token können nur einmal verwendet werden
2. Stellen Sie sicher, dass der Token korrekt ist
3. Bei Bedarf kann eine neue Registrierung durchgeführt werden

## Häufig gestellte Fragen (FAQ)

### Kann ein Benutzer sich mehrmals registrieren?

Nein, ein Benutzername und eine E-Mail-Adresse können nur einmal registriert werden. Bei einem erneuten Versuch wird eine Fehlermeldung angezeigt.

### Was passiert bei einer Ablehnung?

Die Registrierung wird als abgelehnt markiert. Der Benutzer erhält keine Benachrichtigung und das Konto wird nicht erstellt. Der Benutzer kann sich mit denselben Daten nicht erneut registrieren.

### Können Benutzer ihr Passwort zurücksetzen?

Ja, nach der Freigabe können Benutzer die normale Passwort-Zurücksetzen-Funktion von MRBS verwenden.

### Wie lange sind die Tokens gültig?

Tokens haben keine zeitliche Ablaufzeit, sind aber an die Registrierung gebunden und werden ungültig, sobald die Registrierung genehmigt oder abgelehnt wurde.

### Kann ich die E-Mail-Vorlagen anpassen?

Ja, die E-Mail-Texte sind in `web/lang/lang.de` definiert und können dort angepasst werden:
- `register_verify_subject` / `register_verify_body`
- `register_admin_notification_subject` / `register_admin_notification_body`
- `register_approval_subject` / `register_approval_body`

## Support

Bei Problemen oder Fragen:
1. Überprüfen Sie die MRBS-Dokumentation
2. Schauen Sie in die Log-Dateien
3. Kontaktieren Sie den MRBS-Support oder Ihre IT-Abteilung

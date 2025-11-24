# Deployment Guide - Salesman.no på Domeneshop

## ✅ PRE-DEPLOYMENT CHECKLIST

### 1. **DATABASE CREDENTIALS** (KRITISK SIKKERHET)
Din `config/database.php` bruker nå **environment variables** i stedet for hardkodet passord.

**På live-hosten må du:**

1. Opprett en `.env` fil i prosjektets root-katalog:
```
DB_HOST=salesmanno04.mysql.domeneshop.no
DB_NAME=salesmanno04
DB_USER=salesmanno04
DB_PASSWORD=din-faktiske-passord-her
```

2. **SIKKERHET:** Sikre at `.env` ALDRI committes til Git:
   - `.gitignore` er allerede oppsatt (sjekk at `.env` er på listen)
   - `.env` er inkludert i `.gitignore` for sikkerheten

3. **Alternativ:** Hvis din webhost støtter det, kan du sette environment variables direkte i hosting-panelet istedenfor `.env` fil.

### 2. **FILPERMISSJONER**
Når du laster opp koden til live-hosten:

```bash
chmod 755 api/
chmod 755 modules/
chmod 755 includes/
chmod 644 config/database.php
chmod 644 .gitignore
```

### 3. **DATABASE SETUP**
**Første gang på ny server:**

1. Logg inn i admin-panelet
2. Naviger til: `https://din-domene.no/api/migrate_database_restructure.php`
3. Logg inn med dine admin-credentials
4. Kjør migrasjonen

Gjør det samme for:
- `https://din-domene.no/api/migrate_event_registration_fields.php`
- `https://din-domene.no/api/migrate_events_results_columns.php`

### 4. **PHP VERSJON**
Systemet krever **PHP 8.2+**

Sjekk at din webhost:
- Har PHP 8.2 eller høyere installert
- Har PDO MySQL extension aktivert
- Har `pdo_mysql` i `php.ini`

### 5. **MYSQL DATABASE**
Sørg for at Domeneshop MySQL-databasen:
- ✅ Har `utf8mb4_general_ci` collation
- ✅ Har alle nødvendige tabeller (kjør migrations)
- ✅ Brukeren har FULL tilgang (SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP)

## 🔒 SECURITY CHECKLIST

### Før Live Deployment:

1. **Credentials är ALDRI i Git:**
   ```bash
   git status  # Sjekk at .env IKKE vises
   ```

2. **Error Display er OFF på live:**
   - `config/database.php` viser feil på log, ikke til bruker ✅

3. **Session Security:**
   - `session_start()` brukes konsistent ✅
   - Single-session enforcement aktiv ✅
   - Session timeout implementert ✅

4. **SQL Injection Protection:**
   - Alle SQL queries bruker prepared statements ✅
   - PDO::ERRMODE_EXCEPTION aktivert ✅

5. **Database:**
   - Alle migrations kjørt ✅
   - Alle tabeller har riktig charset (utf8mb4) ✅

## 📦 INSTALLATION STEPS (PÅ LIVE-HOSTEN)

### Steg 1: Last opp filer
```bash
# Via FTP eller hosting-panelet
# Last opp ALL filer til public_html eller webroot
```

### Steg 2: Opprett .env fil
```bash
# SSH eller Terminal i hosting-panelet:
cat > .env << EOF
DB_HOST=salesmanno04.mysql.domeneshop.no
DB_NAME=salesmanno04
DB_USER=salesmanno04
DB_PASSWORD=din-passord
EOF
```

### Steg 3: Sjekk PHP versjon
```bash
php -v  # Må være 8.2+
php -m | grep -i pdo  # Sjekk at PDO er installert
```

### Steg 4: Kjør Database Migrations
1. Åpne nettleser: `https://din-domene.no/`
2. Logg inn som admin
3. Besøk migrations-sidene (lenker i admin-panelet)
4. Kjør hver migrasjon

### Steg 5: Test Systemet
- Logg inn og test alle moduler
- Sjekk at database-tilkoblingen fungerer
- Verifiser at alle filer lastes ned korrekt

## 🚨 TROUBLESHOOTING

### "Database connection failed"
- Sjekk `.env` fil eksisterer og har riktig passord
- Sjekk at Domeneshop MySQL-serveren er nåbar fra hosten
- Kontakt hosting-leverandør hvis MySQL-port blokkert

### "Session not working"
- Sjekk at `/tmp` eller session-dir har skrive-tillatelser
- Kontakt hosting hvis session-dir ikke er tilgjengelig

### "Permission denied" feil
- Kjør: `chmod 755 api/ modules/ includes/`
- Kjør: `chmod 644 config/database.php`

### "MySQL charset error"
- Kjøre migration: `migrate_database_restructure.php`
- Sikre at database bruker `utf8mb4`

## ✅ SYSTEM FEATURES (PRODUCTION-READY)

- ✅ **100% MySQL** - Ingen PostgreSQL fallback
- ✅ **Environment variables** - Sikker credential management
- ✅ **Session-based auth** - Robust single-session enforcement
- ✅ **Email integration** - SMTP/IMAP support
- ✅ **Events module** - Full CRUD + check-in system
- ✅ **Sales module** - Inventory + payment integration
- ✅ **Reservations** - Calendar + staff scheduling
- ✅ **Multi-tenant** - Isolated per company
- ✅ **Error logging** - Production-safe error handling
- ✅ **Database migrations** - Safe schema updates

## 📞 SUPPORT

Hvis du har problemer:
1. Sjekk error logs: Hosting-panelet → Error logs
2. Sjekk MySQL tilkobling: Hosting-panelet → Database info
3. Kontakt Domeneshop support hvis MySQL-tilkobling er problemet

---

**Status:** Klar for live deployment på Domeneshop! 🚀

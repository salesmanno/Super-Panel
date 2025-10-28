# MySQL Database Configuration

## Production Setup (Domeneshop)

Dette systemet er konfigurert for **MySQL BARE** - ingen PostgreSQL-støtte.

### Database Connection

Opprett en `.env`-fil eller sett miljøvariabler:

```bash
MYSQL_HOST=salesmanno04.mysql.domeneshop.no
MYSQL_DATABASE=salesmanno04
MYSQL_USER=salesmanno04
MYSQL_PASSWORD=ditt_passord_her
MYSQL_PORT=3306
```

### Kode-konvertering utført:

✅ **config/database.php** - MySQL PDO DSN
✅ **Boolean-syntaks** - Alle `TRUE/FALSE` endret til `1/0`
✅ **Tidsfunksjoner** - `NOW()` endret til `CURRENT_TIMESTAMP`
✅ **INTERVAL-syntaks** - MySQL-kompatibel (INTERVAL 30 MINUTE)

### Database Schema

Importer schema fra `docs/database_schema.sql` til MySQL-databasen.

### Utviklingsmiljø

For lokal testing, bruk MySQL (ikke PostgreSQL):
- Docker: `docker run --name mysql -e MYSQL_ROOT_PASSWORD=root -p 3306:3306 -d mysql:8.0`
- XAMPP/MAMP med MySQL
- Direkte MySQL-installasjon

## VIKTIG

Dette systemet vil **IKKE** fungere mot PostgreSQL. All kode er optimalisert for MySQL-syntaks.

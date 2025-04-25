# 💻 Online Bank Oppgaven – Brukerveiledning og Dokumentasjon

## 📘 Oversikt

**Online Bank Oppgaven** er et webbasert banksystem utviklet i PHP og MySQL, designet for å simulere grunnleggende banktjenester. Løsningen er ment som en læringsplattform og inkluderer funksjonalitet som:

- Brukerregistrering og innlogging  
- Kontohåndtering (flere kontotyper per bruker)  
- Overføring mellom egne kontoer  
- Mulighet for frysing av konto  
- Administrasjonspanel for brukerstyring (for administratorer)  

---

## 📁 Mappestruktur og Nøkkelfiler

- `connection.php`  
  Konfigurasjonsfil for tilkobling til MySQL-databasen. Alle databaseforespørsler bruker denne sentrale tilkoblingen.

- `create_tables.php`  
  Inneholder SQL-skript for å lage nødvendige tabeller: `users` og `accounts`. Kan kjøres via nettleser for første gangs oppsett.

- `functions.php`  
  Fellesfunksjoner som brukes i flere deler av systemet, f.eks. innloggingssjekk, generering av kontonummer og bruker-ID.

- `login.php`  
  Brukerinnlogging. Validerer brukerinformasjon, og starter en sikker PHP-økt.

- `signup.php`  
  Registrering av nye brukere. Validerer input, hasher passord og genererer unik bruker-ID.

- `index.php`  
  Hoveddashbord for innloggede brukere. Viser en oversikt over tilknyttede kontoer.

- `transfer.php`  
  Lar brukere overføre penger mellom egne kontoer. Validerer eierskap, saldo og kontostatus.

- `logout.php`  
  Logger brukeren trygt ut av systemet.

- `admin_panel.php`  
  Kun tilgjengelig for administratorer. Brukes til å administrere brukere og kontoer.

---

## ⚙️ Krav og Forutsetninger

- PHP versjon 7.4 eller høyere  
- MySQL 5.7 eller nyere  
- Nettserver (f.eks. Apache, Nginx) med støtte for PHP  
- Aktivert MySQLi-utvidelse i PHP  
- Enkel forståelse for hvordan man konfigurerer PHP- og MySQL-miljø  

---

## 🧪 Installasjonsveiledning

### 1. Klargjør prosjektet

Last ned eller klon prosjektmappa, og plasser den i dokumentroten til din nettserver (f.eks. `htdocs/OnlineBank/`).

### 2. Konfigurer database

Åpne `connection.php` og sett inn dine egne databaseinnstillinger:

```php
$dbhost = "localhost";
$dbuser = "din_bruker";
$dbpass = "ditt_passord";
$dbname = "navn_på_database";
```

Opprett databasen i MySQL dersom den ikke eksisterer:

```sql
CREATE DATABASE navn_på_database;
```

### 3. Opprett tabeller

Kjør `create_tables.php` ved å åpne den i nettleseren:

```
http://localhost/OnlineBank/create_tables.php
```

Dette oppretter nødvendige tabeller i databasen.

### 4. Sett riktige filrettigheter

Sørg for at nettserveren har nødvendige lese-/skriverettigheter til prosjektmappen.

### 5. Start applikasjonen

Gå til:

```
http://localhost/OnlineBank/login.php
```

---

## 👤 Brukerveiledning

### 🔐 Registrering

1. Gå til `signup.php`
2. Fyll ut nødvendig informasjon
3. Velg om kontoen skal være for bedrift
4. Klikk "Registrer"

### 🔑 Innlogging

1. Gå til `login.php`
2. Skriv inn e-post og passord
3. Etter vellykket innlogging blir du sendt til dashbordet

### 📊 Dashbord

- Viser alle dine kontoer: type, nummer, saldo og status
- Navigasjonslenker for overføringer, kontoadministrasjon og utlogging

### 💸 Overføringer

1. Gå til `transfer.php`
2. Velg hvilken konto pengene skal trekkes fra og overføres til
3. Skriv inn beløpet
4. Trykk på "Overfør"

**Merk:** Kun overføringer mellom egne kontoer er tillatt. Kontoene må være aktive og saldo må være tilstrekkelig.

### 🚪 Logg ut

Klikk på "Logg ut" for å avslutte økten trygt.

---

## 🔐 Sikkerhetsfunksjoner

- Passord lagres kryptert med `password_hash()`  
- PHP-økter beskytter innlogget tilstand  
- Validering på alle skjemaer  
- Frosne kontoer kan ikke brukes til overføringer  
- Bruker-ID og kontonummer genereres automatisk og unikt  

---

## 🛠 Vedlikehold og Videreutvikling

- Strukturert og modulær kodebase  
- Lett å bygge ut med nye kontotyper eller transaksjonstyper  
- Admin-panel kan utvides med flere funksjoner (f.eks. kontohistorikk)  
- Anbefalt: Bruk `prepared statements` i stedet for manuell escaping  
- Implementer CSRF-beskyttelse for ekstra sikkerhet  

---

## ❓ Feilsøking

| Problem | Løsning |
|--------|----------|
| Får ikke koblet til databasen | Sjekk innstillinger i `connection.php` og at MySQL kjører |
| Nettleseren viser feil ved innlasting av sider | Sjekk PHP-feilloggen og filplasseringer |
| Tabeller mangler | Sørg for at `create_tables.php` er kjørt |
| Sesjoner fungerer ikke | Aktiver session i `php.ini` og sjekk at nettleseren tillater cookies |

---

## ❔ FAQ – Ofte stilte spørsmål

### Hvorfor får jeg feilmelding ved innlogging?
Sjekk at e-post og passord er korrekt, og at brukeren faktisk finnes i databasen. Pass på at `password_verify()` brukes riktig.

### Kan jeg overføre til andres konto?
Nei. Systemet tillater kun overføring mellom dine egne kontoer for å forenkle sikkerheten.

### Hvordan vet jeg om kontoen min er frosset?
Det vises i dashbordet under kontodetaljene. Frosne kontoer kan ikke brukes til overføringer.

### Hvordan lager jeg en admin-bruker?
Du må manuelt sette `is_admin = 1` på en bruker i databasen via SQL:

```sql
UPDATE users SET is_admin = 1 WHERE id = 'bruker_id';
```

### Kan jeg bruke dette prosjektet til noe kommersielt?
Nei. Dette prosjektet er kun ment for læring og utdanning.

---

## 📩 Kontakt og Støtte

Har du spørsmål eller trenger hjelp? Kontakt prosjektansvarlig eller din veileder.

---

**Lisens:** Dette prosjektet er ment for læring og utdanning. Fri bruk med kildehenvisning anbefales.

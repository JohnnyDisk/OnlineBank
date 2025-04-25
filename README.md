# 💻 Online Bank Oppgaven

## 🧾 Innholdsfortegnelse
- [Om prosjektet](#om-prosjektet)
- [Prosjektstruktur og Nøkkelfiler](#prosjektstruktur-og-nøkkelfiler)
- [Funksjonelt Overblikk](#funksjonelt-overblikk)
- [Installasjonsveiledning](#installasjonsveiledning)
- [Brukerveiledning](#brukerveiledning)
- [Sikkerhet](#sikkerhet)
- [Utvidelser og Vedlikehold](#utvidelser-og-vedlikehold)
- [Feilsøking](#feilsøking)
- [FAQ](#faq)
- [Kontakt](#kontakt)

---

## 📌 Om prosjektet
Online Bank Oppgaven er et nettbank-system utviklet i PHP som simulerer grunnleggende bankfunksjonalitet. Brukere kan registrere seg, logge inn, opprette og håndtere kontoer, samt overføre penger mellom egne kontoer. I tillegg finnes det et adminpanel for bruker- og kontoadministrasjon.

Dette prosjektet er laget for å lære webutvikling med fokus på backend og sikkerhet.

---

## 📁 Prosjektstruktur og Nøkkelfiler

- `connection.php`: Kobler applikasjonen til MySQL-databasen.
- `create_tables.php`: Lager nødvendige tabeller (`users`, `accounts`) hvis de ikke finnes.
- `functions.php`: Hjelpefunksjoner som autentisering, ID-generering, og formatering.
- `index.php`: Hovedside etter innlogging med oversikt over brukerens kontoer.
- `login.php` og `signup.php`: Innlogging og registrering. Passord hashes med `password_hash()`.
- `transfer.php`: Lar brukere overføre penger mellom egne kontoer.
- `admin_panel.php`: Admin-grensesnitt for brukerstyring (hvis aktivert).
- `logout.php`: Logger brukeren trygt ut.

---

## ⚙️ Funksjonelt Overblikk

### Brukerhåndtering
- Registrering med navn, e-post, telefon og passord.
- Hashing av passord for sikker lagring.
- Mulighet for bedriftskonto eller admin-status.
- Sessions brukes for autentisering.

### Kontoadministrasjon
- Flere kontoer per bruker (brukskonto, sparekonto, høyrentekonto).
- Kontoer får unike 12-sifrede nummer.
- Kontoer kan fryses.

### Overføringer
- Penger kan overføres mellom brukerens egne kontoer.
- Sjekker saldo, eierskap og kontostatus.
- Oppdaterer saldo atomisk for sikkerhet.

---

## 🧰 Installasjonsveiledning

### Forhåndskrav
- PHP 7.4+ med MySQLi aktivert
- MySQL 5.7+
- Apache/Nginx
- En fungerende `php.ini` med sessions aktivert

### Installasjonstrinn
1. **Last ned prosjektet**
   - `git clone https://github.com/JohnnyDisk/OnlineBank.git`

2. **Plasser filene**
   - Kopier til serverens dokumentmappe (f.eks. `htdocs` for XAMPP)

3. **Konfigurer database**
   - I `connection.php`, legg inn riktige databaseverdier:
     ```php
     $dbhost = "localhost";
     $dbuser = "brukernavn";
     $dbpass = "passord";
     $dbname = "bank";
     ```

4. **Kjør `create_tables.php`**
   - Besøk `http://localhost/OnlineBank/create_tables.php`

5. **Start applikasjonen**
   - Gå til `http://localhost/OnlineBank/login.php`

---

## 🖱️ Brukerveiledning

### Registrering
1. Gå til `signup.php`
2. Fyll inn informasjon
3. Klikk registrer

### Innlogging
1. Gå til `login.php`
2. Skriv inn e-post og passord
3. Klikk logg inn

### Dashboard
- Visning av alle kontoer
- Tilgang til overføring og admin (om aktivert)

### Overføring
1. Gå til `transfer.php`
2. Velg fra-konto og til-konto
3. Skriv inn beløp
4. Klikk send

### Logg ut
- Klikk på "logg ut" for å avslutte sesjonen

---

## 🔐 Sikkerhet
- Passord hashes med `password_hash()`
- Session-baserte innlogginger
- Kontoer kan fryses
- Inndata valideres
- Foreløpig uten prepared statements – bør oppdateres for bedre SQL-beskyttelse
- **Bruk `htaccess` for å beskytte sensitive filer**

---

## 🔧 Utvidelser og Vedlikehold
- Enkelt å legge til nye konto- og transaksjonstyper
- Admin-panelet kan utvides med statistikk og brukersøk
- Bør implementere CSRF-beskyttelse
- Forbedre frontend med f.eks. Bootstrap eller Tailwind CSS
- Planlagt: støtte for eksterne overføringer og transaksjonshistorikk

---

## 🛠️ Feilsøking
- Får du feilmelding? Sjekk `php_error.log`
- Kontroller databaseforbindelse i `connection.php`
- Sjekk at `create_tables.php` har kjørt riktig
- Sørg for at sessions er aktivert i `php.ini`
- Prøv å sette `display_errors = On` i `php.ini` under utvikling

---

## ❓ FAQ

**Spørsmål:** Kan jeg overføre penger mellom kontoene mine?  
**Svar:** Ja! Dette er fullt implementert og fungerer. Bare gå til `transfer.php`.

**Spørsmål:** Kan jeg ha flere kontoer?  
**Svar:** Ja, du kan ha brukskonto, sparekonto og høyrentekonto samtidig.

**Spørsmål:** Er passordene mine trygge?  
**Svar:** Ja, passordene lagres med `password_hash()` og er ikke lesbare i databasen.

**Spørsmål:** Hva skjer hvis jeg prøver å overføre penger fra en frossen konto?  
**Svar:** Systemet blokkerer slike transaksjoner automatisk.

**Spørsmål:** Hvordan blir jeg admin?  
**Svar:** Det må settes manuelt i databasen per nå, via `is_admin`-feltet.

**Spørsmål:** Er prosjektet tilgjengelig på GitHub?  
**Svar:** Ja, hele koden er åpen kildekode og ligger på GitHub.

---

## 📬 Kontakt

For spørsmål eller støtte, kontakt: [JohnnyDisk på GitHub](https://github.com/JohnnyDisk)

---

🧠 Laget med kjærlighet til koding og sikkerhet. Ikke ekte bank – bare læring!


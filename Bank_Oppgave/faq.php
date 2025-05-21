<?php
session_start();
include('connection.php');
include('functions.php');
$user_data = check_login($con);
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>FAQ – Ofte stilte spørsmål</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.9.0/css/foundation.min.css" integrity="sha512-HU1oKdPcZ02o+Wxs7Mm07gVjKbPAn3i0pyud1gi3nAFTVYAVLqe+de607xHer+p9B2I9069l3nCsWFOdID/cUw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <a href="logout.php">Logout</a>
    <a href="index.php">Main</a>
    <a href="account.php">Account</a>
    <a href="transfer.php">Transfer</a>
    <a href="faq.php">FAQ</a>
<?php
    if ($user_data['is_admin']) {
        echo '<a href="admin_panel.php">Admin Panel</a></div>';
    } else {
        echo '</div>';
    }

    ?>

    <br><br>

<div class="grid-container">
    <h1>❓ Ofte stilte spørsmål (FAQ)</h1>

    <h3>🔐 Brukerkonto og sikkerhet</h3>
    <ul>
        <li><strong>Hvordan registrerer jeg meg?</strong><br>
            Gå til <code>signup.php</code>, fyll inn navn, e-post, telefonnummer og passord. Du kan også velge å opprette en firmakonto ved å krysse av for dette.
        </li>
        <li><strong>Hvordan logger jeg inn?</strong><br>
            Besøk <code>login.php</code>, skriv inn din registrerte e-postadresse og passord, og klikk på "Logg inn".
        </li>
        <li><strong>Er passordene mine sikre?</strong><br>
            Ja, passordene dine lagres sikkert ved hjelp av PHPs <code>password_hash()</code>-funksjon, som benytter en sterk hashing-algoritme.
        </li>
        <li><strong>Hvordan blir jeg admin?</strong><br>
            For øyeblikket må admin-status settes manuelt i databasen ved å oppdatere <code>is_admin</code>-feltet for brukeren.
        </li>
    </ul>

    <h3>💰 Konto og overføringer</h3>
    <ul>
        <li><strong>Kan jeg ha flere kontoer?</strong><br>
            Ja, du kan opprette flere kontoer som brukskonto, sparekonto og høyrentekonto.
        </li>
        <li><strong>Hvordan overfører jeg penger mellom kontoer?</strong><br>
            Gå til <code>transfer.php</code>, velg "Overfør mellom egne kontoer", velg fra- og til-konto, skriv inn beløpet og klikk på "Send".
        </li>
        <li><strong>Kan jeg sende penger til andre brukere?</strong><br>
            Ja, velg "Send til en annen bruker" i <code>transfer.php</code>, skriv inn mottakerens kontonummer og beløp, og klikk på "Send".
        </li>
        <li><strong>Hva skjer hvis en konto er frosset?</strong><br>
            Systemet vil blokkere transaksjoner til eller fra frosne kontoer automatisk.
        </li>
    </ul>

    <h3>⚙️ Teknisk og feilsøking</h3>
    <ul>
        <li><strong>Får ikke koblet til databasen</strong><br>
            Sjekk innstillingene i <code>connection.php</code> og sørg for at MySQL-serveren kjører.
        </li>
        <li><strong>Tabeller mangler</strong><br>
            Kjør <code>create_tables.php</code> for å opprette nødvendige tabeller.
        </li>
        <li><strong>Overføring mellom kontoer fungerer ikke</strong><br>
            Sørg for at både fra- og til-kontoene eksisterer og tilhører riktig bruker. Kontroller også at kontoene ikke er frosset.
        </li>
        <li><strong>Sesjoner fungerer ikke</strong><br>
            Aktiver sessions i <code>php.ini</code> og sørg for at nettleseren tillater cookies.
        </li>
    </ul>

    <h3>📬 Kontakt</h3>
    <p>For spørsmål eller støtte, kontakt: <a href="https://github.com/JohnnyDisk">JohnnyDisk på GitHub</a></p>
</div>

</body>
</html>

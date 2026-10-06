<?php
// Alle oefenopdrachten. Elke opdracht wordt nagekeken door het resultaat van de
// student te vergelijken met het resultaat van 'solution'.
//
// Velden:
//   id        unieke code (wordt ook gebruikt om voortgang op te slaan)
//   topic     onderwerp/hoofdstuk
//   title     korte titel
//   from      collega die de vraag stelt (zie PEOPLE in assets/app.js)
//   story     de vraag van die collega, als inkleding van de opdracht
//   text      de precieze opdracht (HTML toegestaan)
//   hint      tip voor de student
//   solution  het modelantwoord
//   type      'select' (resultaat vergelijken) of 'dml' (tabel na wijziging vergelijken)
//   order     alleen bij 'select': null = volgorde telt niet, true = volgorde telt,
//             [kolomindex, ...] = alleen de volgorde van deze kolommen telt (bij gelijke waarden)
//   check     alleen bij 'dml': query die de tabel toont na de wijziging (met ORDER BY)

declare(strict_types=1);

return [
    // ---------------------------------------------------------------- SELECT
    [
        'id' => 'select-1', 'topic' => 'SELECT', 'title' => 'Alle merken',
        'from' => 'manager', 'story' => 'Welkom bij PixelForge! Begin maar eens met rondkijken in onze database. Welke merken verkopen we eigenlijk?',
        'text' => 'Toon <strong>alle kolommen</strong> van <strong>alle merken</strong>.',
        'hint' => 'Met <code>*</code> selecteer je alle kolommen van een tabel.',
        'solution' => 'SELECT * FROM merken;',
        'type' => 'select',
    ],
    [
        'id' => 'select-2', 'topic' => 'SELECT', 'title' => 'Productnamen en prijzen',
        'from' => 'inkoop', 'story' => 'Ik moet de prijslijst checken. Kun je me een lijstje geven met alle producten en wat ze kosten?',
        'text' => 'Toon van alle producten alleen de <strong>naam</strong> en de <strong>prijs</strong> (in die volgorde).',
        'hint' => 'Zet de kolomnamen na SELECT, gescheiden door een komma.',
        'solution' => 'SELECT naam, prijs FROM producten;',
        'type' => 'select',
    ],
    [
        'id' => 'select-3', 'topic' => 'SELECT', 'title' => 'Klantenlijst',
        'from' => 'marketing', 'story' => 'Ik wil weten waar onze klanten vandaan komen. Geef me hun namen en woonplaats.',
        'text' => 'Toon de <strong>voornaam</strong>, <strong>achternaam</strong> en <strong>woonplaats</strong> van alle klanten.',
        'hint' => 'Bekijk in het databaseschema hoe de kolommen precies heten.',
        'solution' => 'SELECT voornaam, achternaam, woonplaats FROM klanten;',
        'type' => 'select',
    ],
    [
        'id' => 'select-4', 'topic' => 'SELECT', 'title' => 'Categorieën',
        'from' => 'webshop', 'story' => 'Ik ben het menu van de webshop aan het vernieuwen. Welke categorieën hebben we, en wat is de omschrijving?',
        'text' => 'Toon de <strong>naam</strong> en <strong>omschrijving</strong> van alle categorieën.',
        'hint' => 'De tabel heet <code>categorieen</code> (zonder trema).',
        'solution' => 'SELECT naam, omschrijving FROM categorieen;',
        'type' => 'select',
    ],

    // ----------------------------------------------------------------- WHERE
    [
        'id' => 'where-1', 'topic' => 'WHERE', 'title' => 'Merken uit Taiwan',
        'from' => 'inkoop', 'story' => 'Volgende week heb ik een meeting met leveranciers uit Taiwan. Welke van onze merken komen daar vandaan?',
        'text' => 'Toon alle kolommen van de merken die uit <strong>Taiwan</strong> komen.',
        'hint' => "Tekst zet je tussen enkele aanhalingstekens: <code>WHERE land = 'Taiwan'</code>.",
        'solution' => "SELECT * FROM merken WHERE land = 'Taiwan';",
        'type' => 'select',
    ],
    [
        'id' => 'where-2', 'topic' => 'WHERE', 'title' => 'Dure producten',
        'from' => 'marketing', 'story' => 'Voor de nieuwsbrief zoek ik de echte high-end spullen. Welke producten kosten meer dan 500 euro?',
        'text' => 'Toon de <strong>naam</strong> en <strong>prijs</strong> van alle producten die <strong>meer dan 500 euro</strong> kosten.',
        'hint' => 'Getallen schrijf je zonder aanhalingstekens. Gebruik <code>&gt;</code>.',
        'solution' => 'SELECT naam, prijs FROM producten WHERE prijs > 500;',
        'type' => 'select',
    ],
    [
        'id' => 'where-3', 'topic' => 'WHERE', 'title' => 'Uitverkocht',
        'from' => 'magazijn', 'story' => 'Volgens mij zijn er een paar dingen uitverkocht. Welke producten hebben een voorraad van 0?',
        'text' => 'Toon de <strong>naam</strong> van alle producten die <strong>niet op voorraad</strong> zijn (voorraad is 0).',
        'hint' => 'Gebruik de kolom <code>voorraad</code>.',
        'solution' => 'SELECT naam FROM producten WHERE voorraad = 0;',
        'type' => 'select',
    ],
    [
        'id' => 'where-4', 'topic' => 'WHERE', 'title' => 'Nieuwsbrief in Zwolle',
        'from' => 'marketing', 'story' => 'We organiseren een LAN-party in Zwolle! Ik wil de klanten uitnodigen die daar wonen en onze nieuwsbrief willen ontvangen.',
        'text' => 'Toon de <strong>voornaam</strong> en <strong>achternaam</strong> van klanten die in <strong>Zwolle</strong> wonen '
            . '<strong>en</strong> zich hebben aangemeld voor de nieuwsbrief (<code>nieuwsbrief</code> is 1).',
        'hint' => 'Combineer twee voorwaarden met <code>AND</code>.',
        'solution' => "SELECT voornaam, achternaam FROM klanten WHERE woonplaats = 'Zwolle' AND nieuwsbrief = 1;",
        'type' => 'select',
    ],
    [
        'id' => 'where-5', 'topic' => 'WHERE', 'title' => 'Mislukte bestellingen',
        'from' => 'manager', 'story' => 'Ik wil weten wat er misgaat met bestellingen. Laat me alle bestellingen zien die geannuleerd zijn of retour kwamen.',
        'text' => 'Toon alle kolommen van de bestellingen met de status <strong>geannuleerd</strong> of <strong>retour</strong>.',
        'hint' => 'Gebruik <code>OR</code>, of <code>IN (...)</code>.',
        'solution' => "SELECT * FROM bestellingen WHERE status = 'geannuleerd' OR status = 'retour';",
        'type' => 'select',
    ],
    [
        'id' => 'where-6', 'topic' => 'WHERE', 'title' => 'Middenklasse',
        'from' => 'service', 'story' => 'Er belt een klant met een budget van 100 tot 200 euro. Wat kunnen we aanraden?',
        'text' => 'Toon de <strong>naam</strong> en <strong>prijs</strong> van producten die <strong>100 tot en met 200 euro</strong> kosten.',
        'hint' => 'Gebruik <code>&gt;=</code> en <code>&lt;=</code>, of <code>BETWEEN ... AND ...</code>.',
        'solution' => 'SELECT naam, prijs FROM producten WHERE prijs BETWEEN 100 AND 200;',
        'type' => 'select',
    ],
    [
        'id' => 'where-7', 'topic' => 'WHERE', 'title' => 'Geen telefoonnummer',
        'from' => 'service', 'story' => 'Ik ga klanten bellen voor een tevredenheidsonderzoek, maar van sommigen hebben we geen nummer. Wie zijn dat?',
        'text' => 'Toon de <strong>voornaam</strong> en <strong>achternaam</strong> van klanten die <strong>geen telefoonnummer</strong> hebben opgegeven.',
        'hint' => 'Een lege waarde is <code>NULL</code>. Daarmee vergelijk je met <code>IS NULL</code>, niet met <code>=</code>.',
        'solution' => 'SELECT voornaam, achternaam FROM klanten WHERE telefoon IS NULL;',
        'type' => 'select',
    ],

    // -------------------------------------------------------------- ORDER BY
    [
        'id' => 'order-1', 'topic' => 'ORDER BY', 'title' => 'Van goedkoop naar duur',
        'from' => 'webshop', 'story' => 'Op de site komt een filter "prijs: laag naar hoog". Laat eens zien hoe die lijst eruit komt te zien.',
        'text' => 'Toon de <strong>naam</strong> en <strong>prijs</strong> van alle producten, gesorteerd van <strong>goedkoop naar duur</strong>.',
        'hint' => '<code>ORDER BY kolom</code> sorteert standaard oplopend (ASC).',
        'solution' => 'SELECT naam, prijs FROM producten ORDER BY prijs;',
        'type' => 'select', 'order' => [1],
    ],
    [
        'id' => 'order-2', 'topic' => 'ORDER BY', 'title' => 'Jongste klanten eerst',
        'from' => 'marketing', 'story' => 'Onze klanten worden steeds jonger, denk ik. Zet ze eens op leeftijd, jongste eerst.',
        'text' => 'Toon de <strong>voornaam</strong>, <strong>achternaam</strong> en <strong>geboortedatum</strong> van alle klanten. '
            . 'De <strong>jongste</strong> klant staat bovenaan.',
        'hint' => 'De jongste klant heeft de meest recente geboortedatum. Gebruik <code>DESC</code>.',
        'solution' => 'SELECT voornaam, achternaam, geboortedatum FROM klanten ORDER BY geboortedatum DESC;',
        'type' => 'select', 'order' => [2],
    ],
    [
        'id' => 'order-3', 'topic' => 'ORDER BY', 'title' => 'Merken per land',
        'from' => 'inkoop', 'story' => 'Ik maak een overzicht van onze merken per land. Netjes gesorteerd graag!',
        'text' => 'Toon de <strong>naam</strong> en het <strong>land</strong> van alle merken, gesorteerd op <strong>land</strong>. '
            . 'Merken uit hetzelfde land staan op alfabetische volgorde van <strong>naam</strong>.',
        'hint' => 'Je kunt op meerdere kolommen sorteren: <code>ORDER BY kolom1, kolom2</code>.',
        'solution' => 'SELECT naam, land FROM merken ORDER BY land, naam;',
        'type' => 'select', 'order' => true,
    ],
    [
        'id' => 'order-4', 'topic' => 'ORDER BY', 'title' => 'Voorraad videokaarten',
        'from' => 'magazijn', 'story' => 'Ik moet weten van welke videokaarten we het meest op voorraad hebben.',
        'text' => 'Toon de <strong>naam</strong> en <strong>voorraad</strong> van alle videokaarten (<code>categorie_id</code> 2). '
            . 'Het product met de <strong>meeste voorraad</strong> staat bovenaan.',
        'hint' => 'Eerst komt WHERE, daarna ORDER BY.',
        'solution' => 'SELECT naam, voorraad FROM producten WHERE categorie_id = 2 ORDER BY voorraad DESC;',
        'type' => 'select', 'order' => [1],
    ],

    // ----------------------------------------------------------------- LIMIT
    [
        'id' => 'limit-1', 'topic' => 'LIMIT', 'title' => 'Top 5 duurste producten',
        'from' => 'marketing', 'story' => 'Voor een Instagram-post zoek ik onze 5 duurste producten. Dure spullen scoren altijd!',
        'text' => 'Toon de <strong>naam</strong> en <strong>prijs</strong> van de <strong>5 duurste</strong> producten, de duurste bovenaan.',
        'hint' => 'Sorteer eerst, en gebruik daarna <code>LIMIT 5</code>.',
        'solution' => 'SELECT naam, prijs FROM producten ORDER BY prijs DESC LIMIT 5;',
        'type' => 'select', 'order' => [1],
    ],
    [
        'id' => 'limit-2', 'topic' => 'LIMIT', 'title' => 'Oudste merken',
        'from' => 'webshop', 'story' => 'Ik schrijf een blog over merken met een lange geschiedenis. Welke 3 merken bestaan het langst?',
        'text' => 'Toon de <strong>naam</strong> en het jaar van <strong>oprichting</strong> van de <strong>3 oudste</strong> merken, het oudste bovenaan.',
        'hint' => 'Het oudste merk is het eerst opgericht: het laagste jaartal.',
        'solution' => 'SELECT naam, opgericht FROM merken ORDER BY opgericht LIMIT 3;',
        'type' => 'select', 'order' => [1],
    ],
    [
        'id' => 'limit-3', 'topic' => 'LIMIT', 'title' => 'Laatste bestellingen',
        'from' => 'manager', 'story' => 'Hoe loopt de verkoop? Laat me de 10 nieuwste bestellingen zien.',
        'text' => 'Toon het <strong>bestelling_id</strong> en de datum (<strong>besteld_op</strong>) van de <strong>10 meest recente</strong> bestellingen, de nieuwste bovenaan.',
        'hint' => 'Sorteer op <code>besteld_op</code>.',
        'solution' => 'SELECT bestelling_id, besteld_op FROM bestellingen ORDER BY besteld_op DESC LIMIT 10;',
        'type' => 'select', 'order' => [1],
    ],
    [
        'id' => 'limit-4', 'topic' => 'LIMIT', 'title' => 'Goedkoopste videokaart',
        'from' => 'service', 'story' => 'Een klant zoekt de goedkoopste videokaart die we hebben. Welke is dat?',
        'text' => 'Toon de <strong>naam</strong> en <strong>prijs</strong> van de <strong>goedkoopste</strong> videokaart (<code>categorie_id</code> 2).',
        'hint' => 'Combineer WHERE, ORDER BY en LIMIT 1.',
        'solution' => 'SELECT naam, prijs FROM producten WHERE categorie_id = 2 ORDER BY prijs LIMIT 1;',
        'type' => 'select',
    ],

    // ------------------------------------------------------------------ LIKE
    [
        'id' => 'like-1', 'topic' => 'LIKE', 'title' => 'Ryzen-processors',
        'from' => 'inkoop', 'story' => 'AMD komt binnenkort met nieuwe processors. Welke Ryzen-modellen verkopen we nu al?',
        'text' => 'Toon de <strong>naam</strong> van alle producten waarvan de naam <strong>begint met "Ryzen"</strong>.',
        'hint' => "Het teken <code>%</code> staat voor \"nul of meer willekeurige tekens\": <code>LIKE 'Ryzen%'</code>.",
        'solution' => "SELECT naam FROM producten WHERE naam LIKE 'Ryzen%';",
        'type' => 'select',
    ],
    [
        'id' => 'like-2', 'topic' => 'LIKE', 'title' => 'Gmail-gebruikers',
        'from' => 'marketing', 'story' => 'Gmail zet onze nieuwsbrief steeds in de spam. Welke klanten hebben een Gmail-adres?',
        'text' => 'Toon de <strong>voornaam</strong>, <strong>achternaam</strong> en <strong>email</strong> van alle klanten met een e-mailadres dat <strong>eindigt op @gmail.com</strong>.',
        'hint' => 'Zet het <code>%</code>-teken nu aan het begin.',
        'solution' => "SELECT voornaam, achternaam, email FROM klanten WHERE email LIKE '%@gmail.com';",
        'type' => 'select',
    ],
    [
        'id' => 'like-3', 'topic' => 'LIKE', 'title' => 'RGB-verlichting',
        'from' => 'webshop', 'story' => 'Gamers zijn gek op RGB! Ik maak een themapagina met alle RGB-producten.',
        'text' => 'Toon de <strong>naam</strong> en <strong>prijs</strong> van alle producten met <strong>"RGB"</strong> ergens in de naam.',
        'hint' => 'Gebruik aan beide kanten een <code>%</code>.',
        'solution' => "SELECT naam, prijs FROM producten WHERE naam LIKE '%RGB%';",
        'type' => 'select',
    ],
    [
        'id' => 'like-4', 'topic' => 'LIKE', 'title' => 'Postcodegebied',
        'from' => 'magazijn', 'story' => 'We gaan zelf bezorgen in de regio Emmen en Coevorden. Welke klanten hebben een postcode die met 78 begint?',
        'text' => 'Toon de <strong>voornaam</strong>, <strong>achternaam</strong> en <strong>postcode</strong> van alle klanten met een postcode die <strong>begint met 78</strong>.',
        'hint' => 'Een postcode is tekst, dus gebruik LIKE met aanhalingstekens.',
        'solution' => "SELECT voornaam, achternaam, postcode FROM klanten WHERE postcode LIKE '78%';",
        'type' => 'select',
    ],
    [
        'id' => 'like-5', 'topic' => 'LIKE', 'title' => 'Korte achternamen',
        'from' => 'service', 'story' => 'Er belde net een klant, maar ik verstond de naam niet goed. Ik weet alleen dat de achternaam 5 letters had. Wie kan het zijn?',
        'text' => 'Toon de <strong>voornaam</strong> en <strong>achternaam</strong> van alle klanten met een achternaam van <strong>precies 5 letters</strong>.',
        'hint' => 'Het teken <code>_</code> staat voor precies één willekeurig teken.',
        'solution' => "SELECT voornaam, achternaam FROM klanten WHERE achternaam LIKE '_____';",
        'type' => 'select',
    ],

    // ----------------------------------------------------------------- COUNT
    [
        'id' => 'count-1', 'topic' => 'COUNT', 'title' => 'Aantal klanten',
        'from' => 'manager', 'story' => 'Voor de jaarvergadering heb ik een getal nodig: hoeveel klanten hebben we eigenlijk?',
        'text' => 'Hoeveel <strong>klanten</strong> zijn er in totaal?',
        'hint' => '<code>COUNT(*)</code> telt het aantal rijen.',
        'solution' => 'SELECT COUNT(*) FROM klanten;',
        'type' => 'select',
    ],
    [
        'id' => 'count-2', 'topic' => 'COUNT', 'title' => 'Dure producten tellen',
        'from' => 'inkoop', 'story' => 'Hoeveel producten in ons assortiment zijn duurder dan 200 euro?',
        'text' => 'Hoeveel producten kosten <strong>meer dan 200 euro</strong>?',
        'hint' => 'COUNT telt alleen de rijen die door de WHERE-voorwaarde komen.',
        'solution' => 'SELECT COUNT(*) FROM producten WHERE prijs > 200;',
        'type' => 'select',
    ],
    [
        'id' => 'count-3', 'topic' => 'COUNT', 'title' => 'Black Friday',
        'from' => 'marketing', 'story' => 'Was onze Black Friday-actie een succes? Hoe vaak is de code BLACKFRIDAY gebruikt?',
        'text' => 'Hoeveel bestellingen zijn er geplaatst met de kortingscode <strong>BLACKFRIDAY</strong>?',
        'hint' => 'Kijk welke kolom in <code>bestellingen</code> de kortingscode bevat.',
        'solution' => "SELECT COUNT(*) FROM bestellingen WHERE kortingscode = 'BLACKFRIDAY';",
        'type' => 'select',
    ],
    [
        'id' => 'count-4', 'topic' => 'COUNT', 'title' => 'Bereikbare klanten',
        'from' => 'service', 'story' => 'Hoeveel klanten kunnen we eigenlijk bellen? Tel de klanten die een telefoonnummer hebben opgegeven.',
        'text' => 'Hoeveel klanten hebben <strong>wel</strong> een telefoonnummer opgegeven?',
        'hint' => '<code>COUNT(kolom)</code> telt alleen de rijen waarin die kolom niet NULL is. Of gebruik <code>IS NOT NULL</code>.',
        'solution' => 'SELECT COUNT(telefoon) FROM klanten;',
        'type' => 'select',
    ],
    [
        'id' => 'count-5', 'topic' => 'COUNT', 'title' => 'Aantal woonplaatsen',
        'from' => 'magazijn', 'story' => 'Naar hoeveel verschillende plaatsen versturen we pakketjes?',
        'text' => 'In hoeveel <strong>verschillende woonplaatsen</strong> wonen klanten?',
        'hint' => 'Met <code>COUNT(DISTINCT kolom)</code> tel je alleen unieke waarden.',
        'solution' => 'SELECT COUNT(DISTINCT woonplaats) FROM klanten;',
        'type' => 'select',
    ],

    // ------------------------------------------------------------------- SUM
    [
        'id' => 'sum-1', 'topic' => 'SUM', 'title' => 'Totale voorraad',
        'from' => 'magazijn', 'story' => 'Inventarisatie! Hoeveel producten liggen er in totaal in het magazijn?',
        'text' => 'Hoeveel producten liggen er <strong>in totaal</strong> op voorraad? Tel de voorraad van alle producten bij elkaar op.',
        'hint' => '<code>SUM(kolom)</code> telt alle waarden in een kolom op.',
        'solution' => 'SELECT SUM(voorraad) FROM producten;',
        'type' => 'select',
    ],
    [
        'id' => 'sum-2', 'topic' => 'SUM', 'title' => 'Voorraad videokaarten',
        'from' => 'inkoop', 'story' => 'Moet ik nieuwe videokaarten bestellen? Hoeveel hebben we er in totaal nog liggen?',
        'text' => 'Wat is de totale voorraad van alle <strong>videokaarten</strong> (<code>categorie_id</code> 2)?',
        'hint' => 'Combineer SUM met een WHERE-voorwaarde.',
        'solution' => 'SELECT SUM(voorraad) FROM producten WHERE categorie_id = 2;',
        'type' => 'select',
    ],
    [
        'id' => 'sum-3', 'topic' => 'SUM', 'title' => 'Verkochte stuks',
        'from' => 'inkoop', 'story' => 'Het Corsair-geheugen (product 21) gaat hard. Hoeveel stuks hebben we er in totaal van verkocht?',
        'text' => 'Hoeveel stuks zijn er in totaal verkocht van het product met <strong>product_id 21</strong>? '
            . 'Gebruik de tabel <code>bestelregels</code>.',
        'hint' => 'In de kolom <code>aantal</code> staat hoeveel stuks er per bestelling zijn gekocht.',
        'solution' => 'SELECT SUM(aantal) FROM bestelregels WHERE product_id = 21;',
        'type' => 'select',
    ],
    [
        'id' => 'sum-4', 'topic' => 'SUM', 'title' => 'Waarde van de voorraad',
        'from' => 'manager', 'story' => 'De verzekering wil weten hoeveel onze voorraad waard is. Kun jij dat uitrekenen?',
        'text' => 'Wat is de totale <strong>waarde van de voorraad</strong>? Dat is per product de prijs keer de voorraad, en dat alles opgeteld.',
        'hint' => 'Je kunt binnen SUM rekenen: <code>SUM(kolom1 * kolom2)</code>.',
        'solution' => 'SELECT SUM(prijs * voorraad) FROM producten;',
        'type' => 'select',
    ],

    // ---------------------------------------------------------------- INSERT
    [
        'id' => 'insert-1', 'topic' => 'INSERT', 'title' => 'Nieuw merk',
        'from' => 'inkoop', 'story' => 'Goed nieuws: we gaan Arctic verkopen! Zet het merk in de database.',
        'text' => 'Voeg het merk <strong>Arctic</strong> toe. Het komt uit <strong>Zwitserland</strong> en is opgericht in <strong>2001</strong>.',
        'hint' => "<code>INSERT INTO tabel (kolom1, kolom2) VALUES ('waarde1', 'waarde2');</code> "
            . 'Het <code>merk_id</code> hoef je niet in te vullen; dat gebeurt automatisch (AUTO_INCREMENT).',
        'solution' => "INSERT INTO merken (naam, land, opgericht) VALUES ('Arctic', 'Zwitserland', 2001);",
        'type' => 'dml',
        'check' => 'SELECT naam, land, opgericht FROM merken ORDER BY naam',
    ],
    [
        'id' => 'insert-2', 'topic' => 'INSERT', 'title' => 'Nieuwe categorie',
        'from' => 'webshop', 'story' => 'We gaan ook kabels verkopen. Maak er een nieuwe categorie voor aan.',
        'text' => 'Voeg de categorie <strong>Kabels</strong> toe met de omschrijving <strong>Stroom-, video- en netwerkkabels</strong>.',
        'hint' => 'Bekijk in het databaseschema welke kolommen de tabel <code>categorieen</code> heeft.',
        'solution' => "INSERT INTO categorieen (naam, omschrijving) VALUES ('Kabels', 'Stroom-, video- en netwerkkabels');",
        'type' => 'dml',
        'check' => 'SELECT naam, omschrijving FROM categorieen ORDER BY naam',
    ],
    [
        'id' => 'insert-3', 'topic' => 'INSERT', 'title' => 'Nieuw product',
        'from' => 'inkoop', 'story' => 'De nieuwe Ryzen 5 9600X is binnen! Voeg hem toe aan het assortiment.',
        'text' => 'Voeg het product <strong>Ryzen 5 9600X</strong> toe met deze gegevens:'
            . '<ul><li>merk_id: <strong>1</strong> (AMD)</li><li>categorie_id: <strong>1</strong> (Processors)</li>'
            . '<li>prijs: <strong>279.00</strong></li><li>voorraad: <strong>10</strong></li>'
            . '<li>garantie_maanden: <strong>36</strong></li><li>toegevoegd_op: <strong>2026-10-06</strong></li></ul>',
        'hint' => "Een datum schrijf je als tekst: <code>'2026-10-06'</code>. Een decimaal getal schrijf je met een punt.",
        'solution' => "INSERT INTO producten (naam, merk_id, categorie_id, prijs, voorraad, garantie_maanden, toegevoegd_op)\n"
            . "VALUES ('Ryzen 5 9600X', 1, 1, 279.00, 10, 36, '2026-10-06');",
        'type' => 'dml',
        'check' => 'SELECT naam, merk_id, categorie_id, prijs, voorraad, garantie_maanden, toegevoegd_op FROM producten ORDER BY naam, toegevoegd_op',
    ],

    // ---------------------------------------------------------------- UPDATE
    [
        'id' => 'update-1', 'topic' => 'UPDATE', 'title' => 'Prijsverlaging',
        'from' => 'manager', 'story' => 'De concurrent is goedkoper met de Ryzen 5 7600. Wij gaan eronder zitten!',
        'text' => 'De prijs van het product <strong>Ryzen 5 7600</strong> (<code>product_id</code> 1) wordt verlaagd naar <strong>179.00</strong>.',
        'hint' => '<code>UPDATE tabel SET kolom = waarde WHERE ...;</code> Vergeet de WHERE niet, anders pas je álle rijen aan!',
        'solution' => 'UPDATE producten SET prijs = 179.00 WHERE product_id = 1;',
        'type' => 'dml',
        'check' => 'SELECT product_id, prijs FROM producten ORDER BY product_id',
    ],
    [
        'id' => 'update-2', 'topic' => 'UPDATE', 'title' => 'Verhuizing',
        'from' => 'service', 'story' => 'Zoë Mulder belde: ze is verhuisd. Kun je het adres aanpassen?',
        'text' => 'Klant <strong>Zoë Mulder</strong> (<code>klant_id</code> 3) is verhuisd naar <strong>Assen</strong>. De nieuwe postcode is <strong>9401 AB</strong>.',
        'hint' => 'Je kunt meerdere kolommen tegelijk aanpassen: <code>SET kolom1 = ..., kolom2 = ...</code>.',
        'solution' => "UPDATE klanten SET woonplaats = 'Assen', postcode = '9401 AB' WHERE klant_id = 3;",
        'type' => 'dml',
        'check' => 'SELECT klant_id, woonplaats, postcode FROM klanten ORDER BY klant_id',
    ],
    [
        'id' => 'update-3', 'topic' => 'UPDATE', 'title' => 'Razer-actie',
        'from' => 'marketing', 'story' => 'Het is Razer-week! Alle Razer-producten worden 10 euro goedkoper.',
        'text' => 'Alle producten van het merk <strong>Razer</strong> (<code>merk_id</code> 16) worden <strong>10 euro goedkoper</strong>.',
        'hint' => 'Je kunt rekenen met de huidige waarde: <code>SET prijs = prijs - 10</code>.',
        'solution' => 'UPDATE producten SET prijs = prijs - 10 WHERE merk_id = 16;',
        'type' => 'dml',
        'check' => 'SELECT product_id, prijs FROM producten ORDER BY product_id',
    ],
    [
        'id' => 'update-4', 'topic' => 'UPDATE', 'title' => 'Nieuwe levering',
        'from' => 'magazijn', 'story' => 'De vrachtwagen is er! Alles wat uitverkocht was, is weer aangevuld.',
        'text' => 'Er is een levering binnengekomen: alle producten die <strong>niet op voorraad</strong> waren, hebben nu een voorraad van <strong>5</strong>.',
        'hint' => 'De WHERE-voorwaarde bepaalt welke producten worden aangepast.',
        'solution' => 'UPDATE producten SET voorraad = 5 WHERE voorraad = 0;',
        'type' => 'dml',
        'check' => 'SELECT product_id, voorraad FROM producten ORDER BY product_id',
    ],

    // ---------------------------------------------------------------- DELETE
    [
        'id' => 'delete-1', 'topic' => 'DELETE', 'title' => 'Categorie opheffen',
        'from' => 'manager', 'story' => 'We stoppen met de verkoop van software. Die categorie mag weg!',
        'text' => 'De webshop verkoopt geen software meer. Verwijder de categorie <strong>Software</strong>.',
        'hint' => "<code>DELETE FROM tabel WHERE ...;</code> Zonder WHERE verwijder je álle rijen!",
        'solution' => "DELETE FROM categorieen WHERE naam = 'Software';",
        'type' => 'dml',
        'check' => 'SELECT categorie_id, naam FROM categorieen ORDER BY categorie_id',
    ],
    [
        'id' => 'delete-2', 'topic' => 'DELETE', 'title' => 'Slechte reviews',
        'from' => 'webshop', 'story' => 'Er staan een hoop nepreviews van 1 ster op de site. Verwijder ze allemaal.',
        'text' => 'Verwijder alle reviews met <strong>1 ster</strong>.',
        'hint' => 'Het aantal sterren staat in de kolom <code>sterren</code>.',
        'solution' => 'DELETE FROM reviews WHERE sterren = 1;',
        'type' => 'dml',
        'check' => 'SELECT review_id FROM reviews ORDER BY review_id',
    ],
    [
        'id' => 'delete-3', 'topic' => 'DELETE', 'title' => 'Lege reviews',
        'from' => 'webshop', 'story' => 'Reviews zonder tekst helpen niemand. Ruim ze op!',
        'text' => 'Verwijder alle reviews <strong>zonder tekst</strong>.',
        'hint' => 'Een lege waarde is NULL; gebruik <code>IS NULL</code>.',
        'solution' => 'DELETE FROM reviews WHERE tekst IS NULL;',
        'type' => 'dml',
        'check' => 'SELECT review_id FROM reviews ORDER BY review_id',
    ],
];

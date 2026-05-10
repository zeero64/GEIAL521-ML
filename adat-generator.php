<?php
/**
 * NK.: D51MXC
 * 
 */

$recordCount = 10000;
$hobbik = ['foci', 'olvasas', 'kodolas', 'turazas', 'zene'];

$jsonData = [];
$xml = new XMLWriter();
$xml->openMemory();
$xml->setIndent(true);
$xml->startDocument('1.0', 'UTF-8');
$xml->startElement('users');

for ($i = 1; $i <= $recordCount; $i++) {
    $id = $i;
    $nev = "Felhasznalo_" . $i;
    $email = "user$i@example.com";
    $datum = date('Y-m-d', strtotime("-" . rand(0, 1000) . " days"));
    $sajatHobbik = array_intersect_key($hobbik, array_flip((array)array_rand($hobbik, rand(1, 3))));

    // JSON struktúra építése
    $jsonData[] = [
        'id' => $id,
        'nev' => $nev,
        'email' => $email,
        'regisztracio' => $datum,
        'hobbik' => array_values($sajatHobbik)
    ];

    // XML struktúra építése
    $xml->startElement('user');
    $xml->writeAttribute('id', $id);
    $xml->writeElement('nev', $nev);
    $xml->writeElement('email', $email);
    $xml->writeElement('regisztracio', $datum);
    $xml->startElement('hobbik');
    foreach ($sajatHobbik as $hobbi) {
        $xml->writeElement('hobbi', $hobbi);
    }
    $xml->endElement(); // hobbik
    $xml->endElement(); // user
}

$xml->endElement(); // users
$xml->endDocument();

// Fájlok mentése
file_put_contents('adatok.json', json_encode($jsonData, JSON_PRETTY_PRINT));
file_put_contents('adatok.xml', $xml->outputMemory());

echo "Generálás kész: 10,000 rekord mentve adatok.json és adatok.xml fájlokba.";



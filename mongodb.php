<?php
/**
 * NK.: D51MXC
 * 
 */


require 'vendor/autoload.php';

$client = new MongoDB\Client("mongodb://localhost:27017");
$dbName = "adatbazisneve";
$collectionName = "gyujtemenyneve";
$collection = $client->$dbName->$collectionName;

try {

    // 1. LÉPÉS: Törlés
    $startDrop = microtime(true);
    $client->$dbName->dropCollection($collectionName);
    $timeDrop = microtime(true) - $startDrop;
    echo "1. Törlés kész " . number_format($timeDrop, 6) . " mp\n";

    // JSON fájl beolvasása
    $jsonData = file_get_contents('adatok.json');
    $data = json_decode($jsonData, true);
    if ($data === null) throw new Exception("Hiba: Érvénytelen JSON!");

    // 2. LÉPÉS: Teljes feltöltés
    $startInsert = microtime(true);
    if (!empty($data)) {
        if (isset($data[0]) && is_array($data[0])) {
            $insertResult = $collection->insertMany($data);
        } else {
            $collection->insertOne($data);
        }
    }
    $timeInsert = microtime(true) - $startInsert;
    echo "2. Feltöltés kész " . number_format($timeInsert, 6) . " mp\n";

    // 3. LÉPÉS: Összes adat lekérdezése
    $startAll = microtime(true);
    $resultsAll = iterator_to_array($collection->find([]));
    $timeAll = microtime(true) - $startAll;
    echo "3. Összes adat (" . count($resultsAll) . ") lekérdezve " . number_format($timeAll, 6) . " mp\n";

    // 4. LÉPÉS: Speciális lekérdezés (1 000 rekord)
    // Csak a userid, nev, hobbi kell. Az _id-t ki kell zárni (0), ha nem akarjuk látni.
    $options1k = [
        'limit' => 1000,
        'projection' => ['userid' => 1, 'nev' => 1, 'hobbi' => 1, '_id' => 0]
    ];
    $start1k = microtime(true);
    $results1k = iterator_to_array($collection->find([], $options1k));
    $time1k = microtime(true) - $start1k;
    echo "4. Speciális lekérdezés (1 000 rekord, 3 mező) " . number_format($time1k, 6) . " mp\n";

    // 5. LÉPÉS: Speciális lekérdezés (10 000 rekord)
    $options10k = [
        'limit' => 10000,
        'projection' => ['userid' => 1, 'nev' => 1, 'hobbi' => 1, '_id' => 0]
    ];
    $start10k = microtime(true);
    $results10k = iterator_to_array($collection->find([], $options10k));
    $time10k = microtime(true) - $start10k;
    echo "5. Speciális lekérdezés (10 000 rekord, 3 mező) " . number_format($time10k, 6) . " mp\n";

    // ÖSSZESÍTÉS
    $totalTime = $timeDrop + $timeInsert + $timeAll + $time1k + $time10k;
    echo "\n\nÖSSZESÍTETT IDŐ: " . number_format($totalTime, 6) . " mp\n";

} catch (Exception $e) {
    echo "\nHIBA: " . $e->getMessage() . "\n";
}
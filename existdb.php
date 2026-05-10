<?php
/**
 * NK.: D51MXC
 * 
 */


require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Beállítások
$baseUrl  = 'http://localhost:8080/exist/rest/db/apps/sajat_alkalmazas/';
$filename = 'adatok.xml';
$fullUrl  = $baseUrl . $filename;

$username = 'admin';
$password = '';

$client = new Client([
    'auth' => [$username, $password]
]);

$stats = [];


// Segédfüggvény a műveletek futtatásához, méréséhez 6 tizedesjegy pontossággal
function run_and_measure($name, $callback, &$stats) {
    $start = microtime(true);
    $result = null;
    $error = null;

    try {
        $result = $callback();
        $status = "SIKERES";
    } catch (\Exception $e) {
        $status = "HIBA";
        $error = $e->getMessage();
    }

    $end = microtime(true);
    $duration = round($end - $start, 6); // Időtartam 6 tizedesjegyig
    $stats[$name] = $duration;

    echo sprintf("%s %12s mp\n", 
        $name, number_format($duration, 6));
    
    if ($error) echo "    --> Hibaüzenet: $error\n";
    
    return $result;
}

try {
    header('Content-Type: text/plain; charset=utf-8');

    // 1. MŰVELET: Törlés
    run_and_measure("1. Régi fájl törlése", function() use ($client, $fullUrl) {
        try {
            return $client->request('DELETE', $fullUrl);
        } catch (RequestException $e) {
            if ($e->getResponse() && $e->getResponse()->getStatusCode() == 404) return;
            throw $e;
        }
    }, $stats);

    // 2. MŰVELET: Feltöltés
    if (!file_exists('adatok.xml')) {
        throw new Exception("A helyi 'adatok.xml' nem található!");
    }
    $xmlContent = file_get_contents('adatok.xml');

    run_and_measure("2. XML feltöltése", function() use ($client, $fullUrl, $xmlContent) {
        return $client->request('PUT', $fullUrl, [
            'body' => $xmlContent,
            'headers' => ['Content-Type' => 'application/xml']
        ]);
    }, $stats);

    // 3. MŰVELET: Teljes nyers lekérdezés
    run_and_measure("3. Összes adat", function() use ($client, $fullUrl) {
        return $client->request('GET', $fullUrl);
    }, $stats);

    // Közös XQuery logika
    $specificXQuery = '
        for $u in doc("' . $filename . '")//user
        return 
            <user_info>
                {$u/userid}
                {$u/nev}
                <szabadido>
                    {$u/hobbik/hobbi}
                </szabadido>
            </user_info>
    ';

    // 4. MŰVELET: Specifikus XQuery (1.000 limit)
    run_and_measure("4. XQuery (1k limit)", function() use ($client, $baseUrl, $specificXQuery) {
        return $client->request('GET', $baseUrl, [
            'query' => [
                '_query'   => $specificXQuery,
                '_wrap'    => 'yes',
                '_indent'  => 'yes',
                '_howmany' => 1000 
            ]
        ]);
    }, $stats);

    // 5. MŰVELET: Specifikus XQuery (10.000 limit)
    $specResponse10k = run_and_measure("5. XQuery (10k limit)", function() use ($client, $baseUrl, $specificXQuery) {
        return $client->request('GET', $baseUrl, [
            'query' => [
                '_query'   => $specificXQuery,
                '_wrap'    => 'yes',
                '_indent'  => 'yes',
                '_howmany' => 10000 
            ]
        ]);
    }, $stats);

    // Összesített statisztika
    $totalTime = array_sum($stats);
    echo sprintf("\n\nÖSSZESÍTETT IDŐ: %s mp\n", number_format($totalTime, 6));

} catch (\Exception $e) {
    echo "\n[KRITIKUS HIBA]: " . $e->getMessage() . "\n";
}
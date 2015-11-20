<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 *  to test run C:\xampp\php\php -f "C:\xampp\htdocs\Eve_industry_data\updateBD.php"
 */

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "test";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully" . PHP_EOL;
//            $sql = $conn->prepare("CREATE TABLE jitamarket (
//    typeID BIGINT PRIMARY KEY,
//    volume DOUBLE,
//    max DECIMAL(19,2),
//    min DECIMAL(19,2)
//    )");
//            $sql->execute();
//            echo "Table created successfully<br>";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
try {
    $query = $conn->prepare("SELECT typeID FROM `invtypes` WHERE `marketGroupID` IS NOT NULL");
    $query->execute();
//            echo gettype($query) . '<br>';
    $item_ids = $query->fetchAll();
//            print_r($item_ids);
//            echo gettype($item_ids) . '<br>';
//            print_r($item_ids);
    echo 'Items retrieved from DB: ' . count($item_ids) . PHP_EOL;
} catch (PDOException $e) {
    echo "Connection failed to invtypes: " . $e->getMessage();
}

foreach ($item_ids as $value) {
    set_time_limit(30);
//            echo gettype($item_id[0]) . '<br>';
//            echo $item_id[0] . '<br>';
//            print_r($item_id);
    $item_id = intval($value['typeID']);
//    $item_id = intval($item_ids[4]['typeID']);
//    print_r($item_ids[4]['typeID']);
//    print_r($item_ids);
    echo 'Quering market data for item: ' . $item_id . PHP_EOL;

    $curl_handle = curl_init('http://api.eve-central.com/api/marketstat?typeid=' . $item_id . '&usesystem=30000142');
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true); // Fetch the contents too
    $http = curl_exec($curl_handle);

    curl_close($curl_handle);

    echo 'Data returned. ';

    $xml = simplexml_load_string($http);
//                echo $xml->asXML();
//                echo gettype($xml) . '<br>';
//                echo $xml->xpath('//buy')[0] . '<br>';
//                print_r((string) $xml->marketstat->type->buy->max->asXML());
//                var_dump((string) $xml->marketstat->type->buy);
//echo $xml->asXML();
    if ($xml != null) {
        $buy = (string) $xml->marketstat->type->buy->max; // max buy price
        $sell = (string) $xml->marketstat->type->sell->min;
        $buy_vol = (string) $xml->marketstat->type->buy->volume;

//                    print_r($buy);
//                    echo gettype($buy) . '<br>';
//                    $max_buy = $buy[0]->attributes();
//                    $test = $buy[0];
//                    echo gettype($max_buy) . '<br>';
//                    echo $max_buy;
//                    print_r($max_buy);
//                    
//                    print_r($buy[0]['volume']);
//                    print_r($sell);
//                    $sql = $conn->prepare("UPDATE jitamarket SET typeID='" . $item_id . "' volume='" . $buy_vol . "'"
//                            . "max='" . $buy . "'"
//                            . "sell='" . $sell . "'");


        try {
            $row_exists = $conn->query("SELECT * FROM `jitamarket` WHERE typeID=" . $item_id);
            if ($row_exists) {
                $sql = $conn->prepare("UPDATE jitamarket SET volume='$buy_vol', max='$buy', min='$sell'");
                $sql->execute();
                echo "Data updated for " . $item_id . PHP_EOL;
            } else {
                $sql = $conn->prepare("INSERT INTO jitamarket (typeID, volume, max, min) "
                        . "VALUES ('$item_id', '$buy_vol', '$buy', '$sell')");
                $sql->execute();
                echo "Data entered for " . $item_id . PHP_EOL;
            }
        } catch (PDOException $e) {
            echo "Cannot add entry: " . $e->getMessage();
        }
    }
//    }
//    $query = $conn->prepare("SELECT * FROM `mapsolarsystems`");
//    $query->execute();
//    foreach ($query->fetchAll() as $value) {
//      echo $value->;
     }
?>
<?php

/*
 * 
 * 
 *  to test run C:\xampp\php\php -f "C:\xampp\htdocs\Eve_industry_data\updateBD.php"
 */

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "test";

//Do the log file as a database log
//The colums should be ID (auto increment), Time, Text
$file = "log.txt";
if (!unlink($file)) {
    echo ("Error deleting $file" . PHP_EOL);
} else {
    echo ("Deleted $file" . PHP_EOL);
}

$log = fopen("log.txt", "w") or die("Unable to open file!");

fwrite($log, "Starting Log!" . PHP_EOL);

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully" . PHP_EOL;

    //Quick query
    $val = $conn->prepare('select 1 from jitamarket LIMIT 1');
    $val->execute();

    //Checking if the table exists and then creating it if it doesn't
    //if ($val !== FALSE) {
    //    echo "Table exists" . PHP_EOL;
    //} else {
        $sql = $conn->prepare("DROP TABLE jitamarket");
        $sql->execute();
        $sql = $conn->prepare("CREATE TABLE jitamarket (
        typeID BIGINT PRIMARY KEY,
        buyVolume DOUBLE,
        sellVolume DOUBLE,
        max DECIMAL(19,2),
        min DECIMAL(19,2),
        delta DECIMAL(19,2),
        profit DECIMAL(19,2)
        )");
        $sql->execute();
        echo "Table created successfully" . PHP_EOL;
    //}
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . PHP_EOL;
}
try {
    //Selecting all itemID's that are buyable on the market and are not BP's or skils
    $query = $conn->prepare("SELECT typeID FROM `invtypes` WHERE `marketGroupID` IS NOT NULL");
    $query->execute();

    $item_ids = $query->fetchAll();

    echo 'Items retrieved from DB: ' . count($item_ids) . PHP_EOL;
} catch (PDOException $e) {
    echo "Could not get items: " . $e->getMessage();
}

//chunk array into smaller arrays
//100is the max possible for the max size URL
$chunked_array = array_chunk($item_ids, 100, TRUE);
$num = 1;
$chunked_array_size = count($chunked_array);

//for each smaller array construct the url
foreach ($chunked_array as $chunk) {
//$chunk = $chunked_array[0];

    echo 'Starting chunk ' . $num . ' of ' . $chunked_array_size . PHP_EOL;
    set_time_limit(30);
    $item_string = '';
    foreach ($chunk as $value) {
        $item_string .= 'typeid=' . intval($value['typeID']) . '&';
    }
    fwrite($log, "Looking for items $item_string" . PHP_EOL);

    echo 'Quering market data for ' . count($chunk) . ' items' . PHP_EOL;

    $curl_handle = curl_init('http://api.eve-central.com/api/marketstat?' . $item_string . 'usesystem=30000142');
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true); // Fetch the contents too
    $http = curl_exec($curl_handle);

    curl_close($curl_handle);

    echo 'Webpage returned.' . PHP_EOL;

    processWebpage($http);

    $num +=1;
}

// Helper function to find needed values from returned webpage
function processWebpage($webpage) {
    $xml = simplexml_load_string($webpage);
    echo 'Data returned ' . $xml->children()->children()->count() . PHP_EOL;

//find the values returned
    foreach ($xml->children()->children() as $item) {

        $item_id = $item->attributes();

        //TODO Have values as string and remove the decimal
        if ($xml !== FALSE) {
            $buy = floatval($item->buy->max); //The price I would have to buy it at to beat others
            $sell = floatval($item->sell->min); //Lowest price I can sell it at
            $buy_vol = floatval($item->buy->volume);
            $sell_vol = floatval($item->sell->volume);

            //add the data returned to the BD
            if ($buy >0 && $sell>0 && $buy_vol>0 && $sell_vol>0){
                enterValues($item_id, $buy, $sell, $buy_vol, $sell_vol);
            }
        }
    }
}

// Helper function to enter values into database
function enterValues($id, $highistBuy, $lowestSell, $buyVolume, $sellVolume) {
    try {
        //Change to using string without the decimal
        //calculations should not change but entering into database needs the decimal
        global $conn;
        
        $delta = ($lowestSell - $highistBuy)/ $highistBuy;
        $profit = $delta * $buyVolume;
        $sql = $conn->query("INSERT INTO jitamarket (typeID, buyVolume, sellVolume, max, min, delta, profit) "
                . "VALUES ('$id', '$buyVolume', '$sellVolume', '$highistBuy', '$lowestSell', '$delta', '$profit') "
                . "ON DUPLICATE KEY "
                . "UPDATE buyVolume=VALUES(buyVolume), sellVolume=VALUES(sellVolume), max=VALUES(max), min=VALUES(min), delta=VALUES(delta), profit=VALUES(profit)");
        $sql->execute();

        echo "Data entered for " . $id . PHP_EOL;
    } catch (PDOException $e) {
        echo "Cannot add entry: " . $e->getMessage();
    }
}

fclose($log);
?>
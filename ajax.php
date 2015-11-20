<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'insert':
            insert();
            break;
        case 'select':
            select();
            break;
        case 'import_SQL':
            import_SQL();
            break;
    }
}

function select() {
    echo "The select function is called.";
    exit;
}

function insert() {
    echo "The insert function is called.";
    exit;
}

function import_SQL() {
    //echo "Import started";
    $command = "python /DB_import/run_SQL_import.py";
//$command .= " $param1 $param2 $param3 2>&1";
    $pid = popen($command, "r");


    while (!feof($pid)) {
        echo fread($pid, 256);
        flush();
        ob_flush();
        echo "<script>window.scrollTo(0,99999);</script>";
        usleep(100000);
    }
    pclose($pid);
    echo "PHP finished";
    exit;
}

function get_prices() {

    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "test";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connected successfully<br>";
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
        $query = $conn->prepare("SELECT typeID FROM `invtypes`");
        $query->execute();
//            echo gettype($query) . '<br>';
        $item_ids = $query->fetchAll();
//            print_r($item_ids);
//            echo gettype($item_ids) . '<br>';
//            print_r($item_ids);
    } catch (PDOException $e) {
        echo "Connection failed to invtypes: " . $e->getMessage();
    }

    foreach ($item_ids as $key => $value) {
        set_time_limit(30);
//            echo gettype($item_id[0]) . '<br>';
//            echo $item_id[0] . '<br>';
//            print_r($item_id);
        $item_id = $value[0];
//                $item_id = "0";
        echo $item_id . '<br>';

        $curl_handle = curl_init('http://api.eve-central.com/api/marketstat?typeid=' . $item_id . '&usesystem=30000142');
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true); // Fetch the contents too
        $http = curl_exec($curl_handle);

        curl_close($curl_handle);

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

            $sql = $conn->prepare("INSERT INTO jitamarket (typeID, volume, max, min) "
                    . "VALUES ('$item_id', '$buy_vol', '$buy', '$sell')");

            try {
                $sql->execute();
                echo "Datered entered for " . $item_id . "<br>";
            } catch (PDOException $e) {
                echo "Cannot add entry: " . $e->getMessage();
            }
        }
    }

//    $query = $conn->prepare("SELECT * FROM `mapsolarsystems`");
//    $query->execute();
//    foreach ($query->fetchAll() as $value) {
//      echo $value->;
//     }
}

?>
<?php
//configure the timezone and memory limit
date_default_timezone_set('America/Denver');

// Get a list of all JSON files in the current directory
$jsonFiles = glob('*.json');

// Initialize arrays to hold the inventory and mobile inventory data
$inventoryData = array();
$mobileInventoryData = array();

// Process each JSON file
foreach ($jsonFiles as $jsonFile) {
    // Skip the response.json file
    if ($jsonFile === 'response.json') {
        continue;
    }
    echo "Processing file: " . $jsonFile . "\n";
    // Read the JSON file
    $json = file_get_contents($jsonFile);

    // Decode the JSON data
    $data = json_decode($json, true);

    // Check if the file is an computer or mobile file
    if (strpos($jsonFile, 'computer_') !== false) {
        // Check if 'results' key exists and is an array
        if (isset($data['results']) && is_array($data['results'])) {
            // Merge the data into the inventoryData array
            $inventoryData = array_merge($inventoryData, $data['results']);
        }
    } elseif (strpos($jsonFile, 'mobile_') !== false) {
        // Check if 'results' key exists and is an array
        if (isset($data['results']) && is_array($data['results'])) {
            // Merge the data into the mobileInventoryData array
            $mobileInventoryData = array_merge($mobileInventoryData, $data['results']);
        }
    }
}

// Connect to the SQL Server
$host = getenv('VAULT_HOST');
$db   = getenv('VAULT_DATABASE');
$user = getenv('VAULT_USER');
$pass = getenv('VAULT_PASSWORD');
$port = getenv('VAULT_PORT');

// Connect to the SQL Server
$mysqli = new mysqli($host, $user, $pass, $db, $port);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Process each entry in the "results" array from inventory.json
foreach ($inventoryData as $entry) {
    try {
        // Get the values from the JSON data
        $deviceName = $entry['general']['name'];
        $lastCheckedIn = $entry['general']['lastContactTime'];

        $serial = $entry['hardware']['serialNumber'];
        $ram = $entry['hardware']['totalRamMegabytes'];
        $cpuType = $entry['hardware']['processorType'];
        $cpuSpeed = $entry['hardware']['processorSpeedMhz'];
        $mac_address = $entry['hardware']['macAddress'];
        $device_manufacturer = $entry['hardware']['make'];
        $device_model = $entry['hardware']['model'];

        $os_name = $entry['operatingSystem']['name'];
        $os_version = $entry['operatingSystem']['version'];

        if ($lastCheckedIn !== null) {
            $timestamp = strtotime($lastCheckedIn);
            if ($timestamp === false) {
                // Skip this entry if the date is invalid
                echo "Invalid date: " . $lastCheckedIn . "\n";
                continue;
            } else {
                $lastCheckedIn = date("Y-m-d H:i:s", $timestamp);
            }
        } else {
            echo "Date is null\n";
            $lastCheckedIn = NULL;
            continue;
        }


        // Update the Vault database with info from JAMF

        // Prepare a SELECT statement to check if a row with the given serial number already exists
        $sql = "SELECT * FROM jamf_data WHERE serial = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $serial);
        $stmt->execute();

        // Fetch the result and check if a row was returned
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            // If a row was returned, prepare an UPDATE statement to update the row with the new data
            $sql = "UPDATE jamf_data SET deviceName = ?, ram = ?, cpuType = ?, cpuSpeed = ?, os_name = ?, os_version = ?, last_check_in = ?, mac_address = ?, device_manufacturer = ?, device_model = ? WHERE serial = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('sisisssssss', $deviceName, $ram, $cpuType, $cpuSpeed, $os_name, $os_version, $lastCheckedIn, $mac_address, $device_manufacturer, $device_model, $serial);
        } else {
            // If no row was returned, prepare an INSERT statement to insert a new row with the data
            $sql = "INSERT INTO jamf_data (deviceName, ram, cpuType, cpuSpeed, os_name, os_version, last_check_in, mac_address, device_manufacturer, device_model, serial) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('sisisssssss', $deviceName, $ram, $cpuType, $cpuSpeed, $os_name, $os_version, $lastCheckedIn, $mac_address, $device_manufacturer, $device_model, $serial);
        }


        // Execute the INSERT or UPDATE statement
        if ($stmt->execute() === false) {
            echo "Error executing statement for serial number: " . $serialNumber . ". Error: " . $stmt->error . "\n";
        } else {
            echo "Inserted or Updated jamf_data for: " . $serial . " From Inventory" . "\n";
        }
    } catch (Exception $e) {
        error_log("Error processing entry: " . $e->getMessage());
        echo "Error processing entry: " . $e->getMessage() . "\n";
        continue;
    }
    // pushes the last checked in date to the assets table into the filewave column
    $sql = "UPDATE assets SET filewave = ? WHERE serial = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $lastCheckedIn, $serial);

    if ($stmt->execute()) {
        echo "Pushed \"Last Check in\" time for: " . $serial . " in assets table" . "\n";
    } else {
        if ($stmt->affected_rows === 0) {
            echo "No rows affected for serial number: " . $serialNumber . "\n";
        }
        echo "Error updating record: " . $stmt->error;
    }
}

// Process each entry in the "results" array from mobile_inventory.json
foreach ($mobileInventoryData as $entry) {
    $deviceName = $entry['general']['displayName'];
    $lastCheckedIn = $entry['general']['lastInventoryUpdateDate'];
    $os_version = $entry['general']['osVersion'];

    $serial = $entry['hardware']['serialNumber'];
    $device_model = $entry['hardware']['model'];
    $mac_address = $entry['hardware']['wifiMacAddress'];

    $os_name = $entry['deviceType'];


    if ($lastCheckedIn !== null) {
        $timestamp = strtotime($lastCheckedIn);
        if ($timestamp === false) {
            // Skip this entry if the date is invalid
            echo "Invalid date: " . $lastCheckedIn . "\n";
            continue;
        } else {
            $lastCheckedIn = date("Y-m-d H:i:s", $timestamp);
        }
    } else {
        echo "Date is null\n";
        $lastCheckedIn = NULL;
        continue;
    }
    // Prepare a SELECT statement to check if a row with the given serial number already exists
    $sql = "SELECT * FROM jamf_data WHERE serial = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $serial);
    $stmt->execute();

    // Fetch the result and check if a row was returned
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        // If a row was returned, prepare an UPDATE statement to update the row with the new data
        $sql = "UPDATE jamf_data SET deviceName = ?, os_name = ?, os_version = ?, last_check_in = ?, mac_address = ?, device_model = ? WHERE serial = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssssss', $deviceName, $os_name, $os_version, $lastCheckedIn, $mac_address, $device_model, $serial);
    } else {
        // If no row was returned, prepare an INSERT statement to insert a new row with the data
        $sql = "INSERT INTO jamf_data (deviceName, os_name, os_version, last_check_in, mac_address, device_model, serial) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssssss', $deviceName, $os_name, $os_version, $lastCheckedIn, $mac_address, $device_model, $serial);
    }
    // Execute the INSERT or UPDATE statement
    if ($stmt->execute() === false) {
        echo "Error executing statement for serial number: " . $serial . ". Error: " . $stmt->error . "\n";
    } else {
        if ($stmt->affected_rows === 0) {
            echo "No rows affected for serial number: " . $serial . "\n";
        }
        echo "Inserted or Updated jamf_data for: " . $serial . " From Mobile Data" . "\n";
    }


    // pushes the last checked in date to the assets table into the filewave column
    $sql = "UPDATE assets SET filewave = ? WHERE serial = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $lastCheckedIn, $serial);

    if ($stmt->execute()) {
        echo "Pushed \"Last Check in\" time for: " . $serial . " in assets table" . "\n";
    } else {
        if ($stmt->affected_rows === 0) {
            echo "No rows affected for serial number: " . $serialNumber . "\n";
        }
        echo "Error updating record: " . $stmt->error;
    }
}
$mysqli->close();

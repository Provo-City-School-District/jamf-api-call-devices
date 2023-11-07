<?php
// Load the JSON data
$data = json_decode(file_get_contents('inventory.json'), true);
date_default_timezone_set('UTC');
ini_set('memory_limit', '256M');
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

// Process each entry in the "results" array
foreach ($data['results'] as $entry) {
    try {
        // Get the values from the JSON data
        $id = $entry['id'];
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

        $lastCheckedIn = str_replace("Z", "", $lastCheckedIn);
        $lastCheckedIn = str_replace("T", " ", $lastCheckedIn);
        $timestamp = strtotime($lastCheckedIn);
        if ($timestamp === false) {
            // Skip this entry if the date is invalid
            echo "Invalid date: " . $lastCheckedIn . "\n";
            continue;
        } else {
            $lastCheckedIn = date("Y-m-d H:i:s", $timestamp);
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
            echo "Error executing statement: " . $stmt->error . "\n";
        } else {
            echo "Processed entry: " . $serial . "\n";
        }
    } catch (Exception $e) {
        error_log("Error processing entry: " . $e->getMessage());
        echo "Error processing entry: " . $e->getMessage() . "\n";
        continue;
    }
}

$mysqli->close();

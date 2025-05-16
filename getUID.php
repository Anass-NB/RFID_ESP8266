<?php
/**
 * RFID UID Receiver
 * 
 * This file receives the RFID UID from the hardware and processes it.
 * It stores the UID in UIDContainer.php and triggers the time tracking functions.
 */

// Include the time tracking functionality
require_once 'time_tracking.php';

$UIDresult = $_POST["UIDresult"];

// Store the UID in UIDContainer.php as the original application does
$Write = "<?php $" . "UIDresult='" . $UIDresult . "'; " . "echo $" . "UIDresult;" . " ?>";
file_put_contents('UIDContainer.php', $Write);

// Process the scan with the TimeTracking class
$scanResult = TimeTracking::recordScan($UIDresult);

// Return JSON response if this is an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($scanResult);
    exit;
}
?>
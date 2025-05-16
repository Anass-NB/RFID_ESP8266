<?php
/**
 * RFID Tag Reading Interface (Legacy Version)
 * 
 * This file is kept for backward compatibility.
 * New installations should use rfid-scan.php instead.
 */

// Include header with proper page title
$pageTitle = "Read RFID Tag";
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="app-card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i> Legacy Interface</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <p><strong>Note:</strong> This is the legacy RFID scanning interface. For the improved interface with automatic entry/exit detection, please use the <a href="rfid-scan.php" class="alert-link">new RFID scanning page</a>.</p>
                </div>
                
                <h3 class="text-center mb-4" id="blink">Please Scan Tag to Display ID or User Data</h3>
                
                <div class="text-center mb-4">
                    <div class="scan-animation mx-auto">
                        <i class="fas fa-id-card scan-icon"></i>
                    </div>
                </div>
                
                <p id="getUID" hidden></p>
                
                <div id="show_user_data">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr class="bg-primary text-white">
                                <th class="text-center">User Data</th>
                            </tr>
                            <tr>
                                <td>
                                    <table class="table mb-0">
                                        <tr>
                                            <td width="30%" class="fw-bold">ID</td>
                                            <td width="2%">:</td>
                                            <td>--------</td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td class="fw-bold">Name</td>
                                            <td>:</td>
                                            <td>--------</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Gender</td>
                                            <td>:</td>
                                            <td>--------</td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td class="fw-bold">Email</td>
                                            <td>:</td>
                                            <td>--------</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Mobile Number</td>
                                            <td>:</td>
                                            <td>--------</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="rfid-scan.php" class="btn btn-primary">
                    <i class="fas fa-sync-alt me-2"></i> Switch to New Interface
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        // Load UID from UIDContainer and refresh every 500ms
        $("#getUID").load("UIDContainer.php");
        setInterval(function() {
            $("#getUID").load("UIDContainer.php");
        }, 500);
        
        // Blinking effect for scan instruction
        var blink = document.getElementById('blink');
        setInterval(function() {
            blink.style.opacity = (blink.style.opacity == 0 ? 1 : 0);
        }, 750);
        
        // RFID scanning logic
        var myVar = setInterval(myTimer, 1000);
        var myVar1 = setInterval(myTimer1, 1000);
        var oldID = "";
        clearInterval(myVar1);

        function myTimer() {
            var getID = document.getElementById("getUID").innerHTML;
            oldID = getID;
            if(getID != "") {
                myVar1 = setInterval(myTimer1, 500);
                showUser(getID);
                clearInterval(myVar);
            }
        }
        
        function myTimer1() {
            var getID = document.getElementById("getUID").innerHTML;
            if(oldID != getID) {
                myVar = setInterval(myTimer, 500);
                clearInterval(myVar1);
            }
        }
        
        function showUser(str) {
            if (str == "") {
                document.getElementById("show_user_data").innerHTML = "";
                return;
            } else {
                if (window.XMLHttpRequest) {
                    // code for IE7+, Firefox, Chrome, Opera, Safari
                    xmlhttp = new XMLHttpRequest();
                } else {
                    // code for IE6, IE5
                    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                }
                xmlhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        document.getElementById("show_user_data").innerHTML = this.responseText;
                    }
                };
                xmlhttp.open("GET","read-tag-user-data.php?id="+str,true);
                xmlhttp.send();
            }
        }
    });
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
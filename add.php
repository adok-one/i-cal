<html>
  <head>
      <title>iCloud principal and calendar settings</title>
<script
  src="https://code.jquery.com/jquery-3.4.1.js"
  integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU="
  crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/datepicker/0.6.5/datepicker.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datepicker/0.6.5/datepicker.css" crossorigin="anonymous" />
        <style type="text/css">
      table {
        border-collapse:collapse;
        border:1px solid;
        border-color:#999;
      }
      table td {
        padding:10px 20px;
      }
      
      #top_bar {
        overflow:hidden;
        width:40%;
        margin: 0 auto;
      }
      
      #result {
        border:1px solid #CCC;
        overflow:auto;
        width:99%;
      }
      
      #copy {
        overflow:hidden;
        width:99%;
        bottom:0px;
        position:absolute;
        height:25px;
        margin-left:5px;
      }
      #copy * {
        font-size:12px;
        color:#333;
      }
      #copy div {
        padding-top:3px;
      }
      
      a, #copy a {
        color:#960;
        text-decoration:none;
      }
      
      a:hover, a:focus, #copy a:hover, #copy a:focus {
        text-decoration:underline;
        color:#300;
      }
    </style>
    <script type="text/javascript">
        $( function() {
          $( ".datepicker" ).datepicker();
        } );

    </script>
    </head>
    <body onLoad="document.getElementById('appleID').focus();">

    <div id="top_bar">
            <h1 style='color:darkred;'>Provide your iCloud login settings:</h1>
            <form action="" method="post">
                <table border='0'>
                    <tr>
                        <td><b style='color:blue;'>Apple ID: </b></td>
                        <td><input type='text' required name='appleID' id='appleID' size='50' value='<?php echo isset($_POST['appleID']) ? $_POST['appleID'] : "";?>'></td>
                    </tr>
                    <tr>
                        <td><b style='color:blue;'>Password: </b></td>
                        <td><input type='password' required name='pw' id='pw' size='50' value='<?php echo isset($_POST['appleID']) ? $_POST['pw'] : "";?>'></td>
                    </tr>
                    <tr>
                        <td><b style='color:blue;'>Calendar ID: </b></td>
                        <td><input type='text' required name='cal' id='cal' size='50' value='<?php echo isset($_POST['appleID']) ? $_POST['cal'] : "";?>'></td>
                    </tr>

                    <tr>
                        <td><b style='color:blue;'>Event Name: </b></td>
                        <td><input type='text' required name='event' id='event' size='50' value='<?php echo isset($_POST['appleID']) ? $_POST['event'] : "";?>'></td>
                    </tr>
                    <tr>
                        <td><b style='color:blue;'>From: </b></td>
                        <td><input type='text' autocomplete="off" class="datepicker" required name='from' id='from' size='50' value='<?php echo isset($_POST['appleID']) ? $_POST['from'] : "";?>'></td>
                    </tr>
                    <tr>
                        <td><b style='color:blue;'>To: </b></td>
                        <td><input class="datepicker" autocomplete="off" type='text' required name='to' id='to' size='50' value='<?php echo isset($_POST['appleID']) ? $_POST['to'] : "";?>'></td>
                    </tr>

                    <tr>
                        <td colspan="2" align="center"><input type='submit' value='Evaluate'></td>
                    </tr>
                </table>
            </form><br>
        </div>
        <div id="result">
<?php
require './src/i-cal/calendar.php';
require './src/i-cal/parser.php';

use ICal\Parser;
use ICal\Calendar;
  if(isset($_POST['appleID'])) {
    
    $username = $_POST['appleID'] ;
    $password = $_POST['pw'] ;
    $calendar_id = $_POST['cal'] ;
    $from = date_parse($_POST['from']) ;
    $to = date_parse($_POST['to']) ;
    $event = $_POST['event'] ;

    $iCal = new Calendar($username, $password,$calendar_id);


    $event_id = $iCal->add_event(date($from['year']."-".$from['month']."-".$from['day']." 00:00:00"), date($from['year']."-".$from['month']."-".$from['day']." 23:59:00"), $event );

      if($event_id){
        echo "New event id ".$event_id;
      }

    }
?>
    </div>
  </body>
</html>



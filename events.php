<html>
  <head>
      <title>iCloud principal and calendar settings</title>
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
        height:270px;
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
    $from = (new DateTime())->setTime(0,0)->format('Y-m-d H:i:s');
    $to = (new DateTime())->modify('last day of +2 month')->setTime(23,59)->format('Y-m-d H:i:s');
    $iCal = new Calendar($username, $password,$calendar_id);

    $events = $iCal->get_events($from,$to);
    if($events){
      $ics = new Parser($events);
      $events = $ics->events();
    }
    else{
      $events = [];
    }
      //Output
      echo "<h1 style='color:darkred;'>Calendar events</h1>";
      echo "<table border='1' style='border-collapse:collapse;'>
          <tr>
            <td align='center'><b style='color:blue;'>Event</td>
            <td align='center'><b style='color:blue;'>From</td>
            <td align='center'><b style='color:blue;'>To</td>
          </tr>";
      foreach($events as $event){
        echo "<tr>
            <td style='color:darkgreen;'>".$event["SUMMARY"]."</td>
            <td>". date("Y-m-d H:i:s", strtotime($event["DTSTART"])) ."</td>
            <td>".date("Y-m-d H:i:s", strtotime($event["DTEND"])) ."</td>
          </tr>";
      }
      echo "</table><br>";

    }
?>
    </div>
  </body>
</html>



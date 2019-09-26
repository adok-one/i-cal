<?php 

namespace ICal;

class Calendar {
  
  private static $app_server = 'https://p34-caldav.icloud.com';

  private $calendars;

  private $server;
  

  private $user_id;

  private $calendar_id;
  

  private $username;
  

  private $password;
  
  
  
  /**
   * Constructor
   * 
   * @access public
   * @param string $server
   * @param string $user_id
   * @param string $calendar_id
   * @param string $username
   * @param string $password
   * @return void
   */
  public function __construct($username, $password, $calendar_id=null, $user_id=null,  $server=null) {
    $this->username = $username;
    $this->password = $password;
    $this->calendars = [];
    if($calendar_id){
      $this->set_calendar($user_id,$calendar_id,$server);
    }
  }

  public function set_calendar($user_id, $calendar_id, $server = null){
    $this->server = $server;
    $this->user_id = $user_id;
    $this->calendar_id = $calendar_id;
  }
  
  
  public function get_calendars(){
    $request_body  = '<?xml version="1.0" encoding="utf-8" ?>';
    $request_body .= '<propfind xmlns="DAV:">';
    $request_body .= '  <prop>';
    $request_body .= '    <displayname />';
    $request_body .= '  </prop>';
    $request_body .= '</propfind>';
    $url = self::$app_server."/".$this->user_id."/calendars/";

    $caldav_answer = simplexml_load_string(
      $this->_do_report_request( 
        $request_body, 
        $url
      )
    );

    foreach($caldav_answer->response as $cal)
    {
      if($cal->propstat[0]->prop[0]->displayname){
        $href = explode("/",rtrim($cal->href, '/'));
        $this->calendars[] = [
          "id" => end($href),
          "name"=> (string)$cal->propstat[0]->prop[0]->displayname,
          "href" => self::$app_server.(string)$cal->href
        ];
      }
    }
    return $this->calendars;
  }

  public function get_user_id(){
    if($this->user_id){
      return $this->user_id;
    }
    $request_body  = '<?xml version="1.0" encoding="utf-8" ?>';
    $request_body .= '<propfind xmlns="DAV:">';
    $request_body .= '  <prop>';
    $request_body .= '    <current-user-principal />';
    $request_body .= '  </prop>';
    $request_body .= '</propfind>';

    $caldav_answer = simplexml_load_string($this->_do_report_request( $request_body, self::$app_server, 0 ));

    $this->user_id = explode("/", $caldav_answer->response[0]->propstat[0]->prop[0]->{'current-user-principal'}->href)[1];
    return $this->user_id;
  }
  
  /**
   * Get iCloud events.
   * 
   * @param string $date_time_range_from Format: yyyy-mm-dd HH:ii:ss
   * @param string $date_time_range_to Format: yyyy-mm-dd HH:ii:ss
   * @access public
   * @return array|FALSE
   */
  public function get_events($date_time_range_from, $date_time_range_to) {
    
    if(!$this->user_id){
      $this->get_user_id();
    }

    // Do CalDAV request to iCloud
    $request_body  = '<?xml version="1.0" encoding="utf-8" ?>';
    $request_body .= '<c:calendar-query xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">';
    $request_body .= '  <d:prop>';
    $request_body .= '    <c:calendar-data />';
    $request_body .= '  </d:prop>';
    $request_body .= '  <c:filter>';
    $request_body .= '    <c:comp-filter name="VCALENDAR">';
    $request_body .= '      <c:comp-filter name="VEVENT">';
    $request_body .= '        <c:time-range start="%sZ" end="%sZ"/>';
    $request_body .= '      </c:comp-filter>';
    $request_body .= '    </c:comp-filter>';
    $request_body .= '  </c:filter>';
    $request_body .= '</c:calendar-query>';
    
    $request_body = sprintf($request_body, date('Ymd\THis', strtotime($date_time_range_from)), date('Ymd\THis', strtotime($date_time_range_to)));

    $url = self::$app_server.'/'.$this->user_id.'/calendars/'.$this->calendar_id.'/';

    $caldav_answer = $this->_do_report_request($request_body,$url,1,true);

    // Load XML into php array
    $caldav_answer_object = simplexml_load_string($caldav_answer, 'SimpleXMLElement', LIBXML_NOCDATA);
    $caldav_answer_array = $this->_object2array($caldav_answer_object);
    
        

    // Iter events
    if (is_array($caldav_answer_array) && count($caldav_answer_array) > 0) {
      if (is_array($caldav_answer_array['response']) && count($caldav_answer_array['response']) > 0) {
        
        // Get ICS content
        $ics_content = '';
        foreach($caldav_answer_array['response'] as $event) {
          if (isset($event['propstat']['prop']['calendar-data'])) {
            $ics_content .= $event['propstat']['prop']['calendar-data'];
          }
          if (isset($event['prop']['calendar-data'])) {
            $ics_content .= $event['prop']['calendar-data'];
          }
        }

        if (!empty($ics_content)) {
          return explode("\n", $ics_content);
        }
        else{
          return false;
        }        
      }
    }
    
    return FALSE;
  }
  
  
  
  /**
   * Add new iCloud event.
   * 
   * @access public
   * @param string $date_time_from Format: yyyy-mm-dd HH:ii:ss
   * @param string $date_time_to Format: yyyy-mm-dd HH:ii:ss
   * @param string $title
   * @param string $description (Optional)
   * @param string $location (Optional)
   * @return string
   */
  public function add_event($date_time_from, $date_time_to, $title, $description = null, $location = null) {
    if(!$this->user_id){
      $this->get_user_id();
    }

    // Set random event_id
    $event_id = md5('event-'.rand(1000000, 9999999).time());
    
    // Get unique event url
    $event_url = $this->_get_add_event_url($event_id);
    
    // Set date start / date end
    $tstart = gmdate("Ymd\THis\Z", strtotime($date_time_from));
    $tend = gmdate("Ymd\THis\Z", strtotime($date_time_to));
    
    // Set current timestamp
    $tstamp = gmdate("Ymd\THis\Z");
    
    // Build ICS content
    $body  = "BEGIN:VCALENDAR\n";
    $body .= "VERSION:2.0\n";
    $body .= "BEGIN:VEVENT\n";
    $body .= "DTSTAMP:".$tstamp."\n";
    $body .= "DTSTART:".$tstart."\n";
    $body .= "DTEND:".$tend."\n";
    $body .= "UID:".$event_id."\n";
    if (!empty($description)) {
      $body .= "DESCRIPTION:".$description."\n";
    }
    if (!empty($location)) {
      $body .= "LOCATION:".$location."\n";
    }
    if (!empty($title)) {
      $body .= "SUMMARY:".$title."\n";
    }
    $body .= "END:VEVENT\n";
    $body .= "END:VCALENDAR\n";
    
    // Do request
    $f = $this->_do_put_request($event_url, $body);

    return $event_id;
  }
  
  
  
  // TODO
  public function modify_event() {
    // ...
  }
  
  
  
  // TODO
  public function remove_event() {
    // ...
  }
  
  
  
  /**
   * Getting add event url.
   * 
   * @access private
   * @param string $event_id
   * @return string
   */
  private function _get_add_event_url($event_id) {
    return 'https://caldav.icloud.com/'.$this->user_id.'/calendars/'.$this->calendar_id.'/'.$event_id.'.ics';
  }
  
  
  
  /**
   * Do a CalDAV PUT request to add an iCloud event.
   * 
   * @access private
   * @param string $url
   * @param string $data
   * @return string
   */
  private function _do_put_request($url, $data) {
    
    // Initialize cURL
    $c = curl_init($url);
    
    // Set headers
    curl_setopt($c, CURLOPT_HTTPHEADER, array(  "Depth: 1", 
                          "Content-Type: text/calendar; charset='UTF-8'", 
                          "If-None-Match: *", 
                          "User-Agent: DAVKit/4.0.1 (730); CalendarStore/4.0.1 (973); iCal/4.0.1 (1374); Mac OS X/10.6.2 (10C540)"
                          ));
    curl_setopt($c, CURLOPT_HEADER, 0);
    
    // Set SSL options
    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    
    // Set HTTP authentication
    curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($c, CURLOPT_USERPWD, $this->username.":".$this->password);
    
    // Set PUT request
    curl_setopt($c, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($c, CURLOPT_POSTFIELDS, $data);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    
    // Execute and return value
    $data = curl_exec($c);
    curl_close($c);
    return $data;
  }
  
  
  
  /**
   * Do a CalDAV REPORT request to get iCloud events.
   * 
   * @access private
   * @param mixed $data
   * @return string
   */
  private function _do_report_request($data, $url, $depth = 1, $report = false) {

    // Initialize cURL
    $c = curl_init($url);
    
    // Set headers
    curl_setopt($c, CURLOPT_HTTPHEADER, array(  "Depth: ".$depth, 
                          "Content-Type: text/xml; charset='UTF-8'", 
                          "User-Agent: DAVKit/4.0.1 (730); CalendarStore/4.0.1 (973); iCal/4.0.1 (1374); Mac OS X/10.6.2 (10C540)"
                          ));
    curl_setopt($c, CURLOPT_HEADER, 0);
    
    // Set SSL options
    curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
    
    // Allow HTTP authentication
    curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($c, CURLOPT_USERPWD, $this->username.":".$this->password);
    
    // Set PUT request
    if($report){
      curl_setopt($c, CURLOPT_CUSTOMREQUEST, "REPORT");
    }
    else{
      curl_setopt($c, CURLOPT_CUSTOMREQUEST, "PROPFIND");
    }
    
    curl_setopt($c, CURLOPT_POSTFIELDS, $data);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    
    // Execute and return value
    $data = curl_exec($c);
    curl_close($c);
    return $data;
  }
  
  
  /**
   * Return an assoziative array from a php object.
   * 
   * @access private
   * @param object $object
   * @return array
   */
  private function _object2array($object) {
    return @json_decode(@json_encode($object),1);
  }
  
}
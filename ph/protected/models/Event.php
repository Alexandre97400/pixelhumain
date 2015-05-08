<?php 
class Event {
	const COLLECTION = "events";
	/**
	 * get an event By Id
	 * @param type $id : is the mongoId of the event
	 * @return type
	 */
	public static function getById($id) {
	  	return PHDB::findOne( PHType::TYPE_EVENTS,array("_id"=>new MongoId($id)));
	}

	public static function getWhere($params) {
	  	return PHDB::findAndSort( self::COLLECTION,$params,array("created"),null);
	}

	/**
	 * Get an event from an id and return filter data in order to return only public data
	 * @param type $id 
	 * @return event structure
	 */
	public static function getPublicData($id) {
		//Public datas 
		$publicData = array (
		);

		//TODO SBAR = filter data to retrieve only publi data	
		$event = Event::getById($id);
		if (empty($event)) {
			throw new CommunecterException("The event id is unknown ! Check your URL");
		}

		return $event;
	}


	/**
	 * Get an event from an id and return filter data in order to return only public data
	 * @param type POST
	 * @return save the event
	*/
	public static function saveEvent($params)
	{
	    //$attendees = array();
	    $id = Yii::app()->session["userId"];
	    $type = PHType::TYPE_CITOYEN;
	    //$attendees[ Yii::app()->session["userId"] ] = array( "type" => PHType::TYPE_CITOYEN );

	    $new = array(
			'email' => Yii::app()->session["userEmail"],
			"name" => $params['title'],
			'type' => $params['type'],
			      'public'=>true,//$params['public'],
			'created' => time(),
			"links" => array( 
				"creator" => array( (string)$id =>array("type" => $type, "isAdmin" => true))  
			),
	        "allDay" => $params['allDay'],
	        'creator' => Yii::app()->session["userId"]
	    );
	    //sameAs      
	    if(!empty($params['content']))
	         $new["description"] = $params['content'];
	    if(!empty($params['end']))
	         $new["endDate"] = $params['end'];
	    if(!empty($params['start']))
	         $new["startDate"] = $params['start'];

	    PHDB::insert(PHType::TYPE_EVENTS,$new);
	    
	    //add the association to the users association list
	    Link::attendee($new["_id"], $id, true);
	    //Link::connect($id, $type, $new["_id"], PHType::TYPE_EVENTS, $id, "events" );
	    // add organization to event
	    if(isset($params["organization"])){
	    	/*PHDB::update( PHType::TYPE_EVENTS , 
				array("_id" => new MongoId($new["_id"])) ,
				array('$addToSet' => array( "links.attendees.".(string)$params["organization"]=>array("type" => Organization::COLLECTION, "isAdmin"=>true )) 
					)
				);*/
	    	Link::addOrganizer($params["organization"], $new["_id"], $id);
	    }

	    //$where = array("_id" => new MongoId(Yii::app()->session["userId"]));
	    //PHDB::update( PHType::TYPE_EVENTS , 
		//					array("_id" => new MongoId($id)) ,
		//					array('$set' => array( "attendees.".(string)$id."type" => $type ) ));
	    
	    //send validation mail
	    //TODO : make emails as cron jobs
	    /*$message = new YiiMailMessage;
	    $message->view = 'validation';
	    $message->setSubject('Confirmer votre compte Pixel Humain');
	    $message->setBody(array("user"=>$new["_id"]), 'text/html');
	    $message->addTo("oceatoon@gmail.com");//$params['registerEmail']
	    $message->from = Yii::app()->params['adminEmail'];
	    Yii::app()->mail->send($message);*/
	    
	    //TODO : add an admin notification
	    //Notification::saveNotification(array("type"=>NotificationType::ASSOCIATION_SAVED,"user"=>$new["_id"]));
	    return array("result"=>true, "msg"=>"Votre evenement est communecté.", "id"=>$new["_id"], "event" => $new );
	}

	/**
	 * Retrieve the list of events, the organization is part of the organizer
	 * @param String $organizationId The organization Id
	 * @return array list of the events the organization is part of the organization array["$eventId"] => $eventValue
	 */
	public static function getListOrganizationEvents($organizationId) {

		$where = array("organizer.".$organizationId => array('$exists' => true));
        $eventOrganization = PHDB::find(PHType::TYPE_EVENTS, $where);

        /*foreach ($eventOrganization as $eventId => $eventValue) {
        	$res["$eventId"] = $eventValue;
        }*/

        return $eventOrganization;
	}

	/**
	* @param List of field, of an event (name, organisation, dates ....)
	* @return true is event existing, false else
	*/
	public static function checkExistingEvents($params){
		$res = false;
		$events = PHDB::find(PHType::TYPE_EVENTS,array( "name" => $params['title']));
		if(!$events){
			$res = false;
		}else{
			foreach ($events as $key => $value) {
				if(isset($params["organization"])){
					if(isset($value["links"]["organizer"])){
						foreach ($value["links"]["organizer"] as $keyEv => $valueEv) {
							if($keyEv==$params["organization"]){
								$startDate = explode(" ", $value["startDate"]);
								$start = explode(" ", $params["start"]);
								if( $startDate[0] == $start[0]){
									$res = true;
								}
							}
						}
					}
				}
				else if(isset($params["userId"])){
					foreach ($value["links"]["attendees"] as $keyEv => $valueEv) {
						if($keyEv==$params["userId"]){
							$startDate = explode(" ", $value["startDate"]);
							$start = explode(" ", $params["start"]);
							if( $startDate[0] == $start[0]){
								$res = true;
							}
						}
					}
				}
			}
		}
		return $res;
	}


	/**
	 * Retrieve the list of events that an user is attending of
	 * @param String $userId is the id of a citoyen
	 * @return array list of the events the person
	 */
	public static function listEventAttending($userId){
		$where = array("links.attendees.".$userId => array('$exists' => true));
		$eventsAttending = PHDB::find(PHType::TYPE_EVENTS, $where);
        return $eventsAttending;
	}
}
?>
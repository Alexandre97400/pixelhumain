<?php 
class Document {

	const COLLECTION = "documents";
	/**
	 * get an project By Id
	 * @param type $id : is the mongoId of the project
	 * @return type
	 */
	public static function getById($id) {
	  	return PHDB::findOne( self::COLLECTION,array("_id"=>new MongoId($id)));
	}

	public static function getWhere($params) {
	  	return PHDB::find( self::COLLECTION,$params);
	}

	/**
	 * save document information
	 * @param $params : a set of information for the document (?to define)
	*/
	public static function save($params){
		//$id = Yii::app()->session["userId"];
	    $new = array(
			"id" => $params['id'],
	  		"type" => $params['type'],
	  		"folder" => $params['folder'],
	  		"moduleId" => $params['moduleId'],
	  		"doctype" => $params['doctype'],	
	  		"author" => $params['author'],
	  		"name" => $params['name'],
	  		"size" => $params['size'],
	  		"category" => $params['category'],
	  		'created' => time()
	    );


	    PHDB::insert(self::COLLECTION,$new);
	    //Link::connect($id, $type, $new["_id"], PHType::TYPE_PROJECTS, $id, "projects" );
	    return array("result"=>true, "msg"=>"Votre document est enregistré.", "id"=>$new["_id"]);	
	}

	
}
?>
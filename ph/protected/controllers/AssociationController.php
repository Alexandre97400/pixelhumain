<?php
/**
 * ActionLocaleController.php
 *
 * tous ce que propose le PH pour les associations
 * comment agir localeement
 *
 * @author: Tibor Katelbach <tibor@pixelhumain.com>
 * Date: 15/08/13
 */
class AssociationController extends Controller {
    const moduleTitle = "Association";
    
	
    public function actionIndex() {
	    $this->layout = "swe";
	    $this->render("index");
	}
    public function actionView($id) {
        $this->layout = "swe";
        $asso = Yii::app()->mongodb->groups->findOne(array("_id"=>new MongoId($id)));
        if(isset($asso["key"]) )
            $this->redirect(Yii::app()->createUrl('index.php/assocation/'.$asso["key"]));
        else    
	        $this->render("view",array('asso'=>$asso));
	}
    public function actionCreer() {
	    $this->render("form");
	}
    public function actionSave() {
	    if(Yii::app()->request->isAjaxRequest && isset($_POST['assoEmail']) && !empty($_POST['assoEmail']))
		{
            $account = Yii::app()->mongodb->groups->findOne(array( "name" => $_POST['assoName']));
            if(!$account)
            { 
               //validate isEmail
               $email = $_POST['assoEmail'];
               if(preg_match('#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#',$email)) { 
                    $newAccount = array(
                    			'email'=>$email,
                    			"name" => $_POST['assoName'],
                                'type'=>PHType::TYPE_ASSOCIATION ,
                                'tobeactivated' => true,
                                'adminNotified' => false,
                                'created' => time()
                                );
                                
                    if(!empty($_POST['assoCP']))
                         $newAccount["cp"] = $_POST['assoCP'];
                    //admin can create association for other people 
                    if( !Citoyen::isAdminUser() ){     
                        $position = array(new MongoId(Yii::app()->session["userId"]));
                        if($_POST['assoPosition']==Association::$positionList[0])
                            $newAccount["membres"] = $position;
                        else if($_POST['assoPosition']==Association::$positionList[4])
                            $newAccount["conseilAdministration"] = $position;
                        else if(in_array($_POST['assoPosition'], array(Association::$positionList[1],Association::$positionList[2],Association::$positionList[3])))
                            $newAccount["bureau"] = $position;
                        else if($_POST['assoPosition']==Association::$positionList[5])
                            $newAccount["benevolesActif"] = $position;
                    }
                    Yii::app()->mongodb->groups->insert($newAccount);
                    
                    //add the association to the users association list
                    $where = array("_id" => new MongoId(Yii::app()->session["userId"]));	
                    Yii::app()->mongodb->citoyens->update($where, array('$push' => array("associations"=>$newAccount["_id"])));
                  
                    //send validation mail
                    //TODO : make emails as cron jobs
                    /*$message = new YiiMailMessage;
                    $message->view = 'validation';
                    $message->setSubject('Confirmer votre compte Pixel Humain');
                    $message->setBody(array("user"=>$newAccount["_id"]), 'text/html');
                    $message->addTo("oceatoon@gmail.com");//$_POST['registerEmail']
                    $message->from = Yii::app()->params['adminEmail'];
                    Yii::app()->mail->send($message);*/
                    
                    //TODO : add an admin notification
                    NotificationBusinessObject::saveNotification(array("type"=>NotificationType::ASSOCIATION_SAVED,
                    						"user"=>$newAccount["_id"]));
                    
                    echo json_encode(array("result"=>true, "msg"=>"Votre association est communecté.", "id"=>$newAccount["_id"]));
               } else
                   echo json_encode(array("result"=>false, "msg"=>"Vous devez remplir un email valide."));
            } else
                   echo json_encode(array("result"=>false, "msg"=>"Cette Association existe déjà."));
		} else
		    echo json_encode(array("result"=>false, "msg"=>"Cette requete ne peut aboutir."));
		exit;
	}
    public function actionGetNames() {
       $assos = array();
       foreach( Yii::app()->mongodb->groups->find( array("name" => new MongoRegex("/".$_GET["typed"]."/i") ),array("name","cp") )  as $a=>$v)
           $assos[] = array("name"=>$v["name"],"cp"=>$v["cp"],"id"=>$a);
       header('Content-Type: application/json');
       echo json_encode( array( "names"=>$assos ) ) ;
	}
	/**
	 * Delete an entry from the group table using the id
	 */
    public function actionDelete() {
	    if(Yii::app()->request->isAjaxRequest && Citoyen::isAdminUser())
		{
            $account = Yii::app()->mongodb->groups->findOne(array("_id"=>new MongoId($_POST["id"])));
            if( $account )
            {
                  Yii::app()->mongodb->groups->remove(array("_id"=>new MongoId($_POST["id"])));
                  //temporary for dev
                  //TODO : Remove the association from all Ci accounts
                  Yii::app()->mongodb->citoyens->update( array( "_id" => new MongoId(Yii::app()->session["userId"]) ) , array('$pull' => array("associations"=>new MongoId( $_POST["id"]))));
                  $result = array("result"=>true,"msg"=>"Donnée enregistrée.");
                  
                  echo json_encode($result); 
            } else 
                  echo json_encode(array("result"=>false,"msg"=>"Cette requete ne peut aboutir."));
		} else
		    echo json_encode(array("result"=>false, "msg"=>"Cette requete ne peut aboutir."));
		exit;
	}
}
<?php
error_reporting(0);
chdir(dirname(__FILE__));
#include_once 'lib.php';
//session_start();
class User{
	//class variables
	var $user_id = null;
	var $user_type = null;
	var $error = null;
	var $error_code = null;
	var $app_config = null;
	var $pdo = null;


public function __construct($user_id=null){
	$this->user_id = $user_id;
	$this->initializeDB();
	if(!empty($user_id)){
		$this->user_profile = $this->getUserDetails($user_id);
		$this->user_type = $this->getUserType();
	}
	//some initialization stuff here
	$this->app_config = $this->getConfig();
}

function initializeDB(){
    include_once 'db_connect.php';
	try{
        $this->pdo = new db("mysql:host=localhost;dbname=$db", $db_user, $db_pass);
	//$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch (PDOException $e) {
        echo "Err: " . $e->getMessage();
        }
}

function __call($functionName, $argumentsArray ){
	//$log = debug_backtrace();
	//$this->createActionLog($log,0);
	//$this->setStdError('undefined_function');
}

function getConfig(){
	#return $this->pdo->select('config');
}

/*
function setError() 
	assign error to the class variable $error.
*/
function setError($error){
	$this->error = $error;
}

/*
function getError() 
	return true if class varible has some error value else return false. 
*/
	function hasError(){
		if(empty($this->error)){
			return false;
		}
		return true;
	}



#
public function authenticateLocal($username, $password){

	try{
		$select = $this->pdo->prepare("SELECT id FROM emp_list WHERE username = ? AND password = ? AND status = 1");
		$select->execute(array($username, md5($password)));
	}
	catch(PDOException $e){
		$this->setError($e->getMessage());
		return false;
	}
	$row = $select->fetch(PDO::FETCH_ASSOC);
	if($row['id']){
		$this->user_id = $row['id'];
		$user_profile = $this->getUserDetails($row['id']);
		$this->user_type = $user_profile['user_type'];
		$action_details=array();
/*
		$action_details['table_name']='users';
		$action_details['row_id']=$this->user_id;
		$action_details['operation']='Logged In';
		$this->createActionLog($action_details);
*/
		return true;
	}
	$this->setError("Invalid username or password.");
	return false;
}

public function syncWithLDAP($username, $password){
        $ldap_server = 'pundc2.win.ansys.com';
        $ldap_port = '389';
        $ldap_user = 'ansys\\'.$username;
        $ds=ldap_connect($ldap_server, $ldap_port);
        //bind only if password is not empty
        // and connection with LDAP server is established
        if($ds && !empty($password)){
                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                if(!ldap_bind($ds, $ldap_user, $password)){
                        $this->setError(ldap_error($ds));
                        return false;
                }
        }
        $ou = $this->getEmployeeOUString($username);
        $attributes = array("displayname", "mail","manager","title","directReports");
        //$username=trim($this->user_profile['username']);
        $filter="(sAMAccountName=$username)";
        $sr=ldap_search($ds, $ou, $filter);
        $info = ldap_get_entries($ds, $sr);

        // my email
        $my_email = $info[0]['mail'][0];
        $this->updateMyEmail($my_email);
// Now enter/update this info in the local db
        $manager_string = $info[0]['manager'][0];
        $manager_fields = explode(",", $manager_string);
        $manager = preg_replace("/^CN=/","", $manager_fields[0]);
        //echo $manager;
//for mgr email
        $filter_mgr="(sAMAccountName=*)";
        $sr_mgr=ldap_search($ds, $info[0]["manager"][0],$filter_mgr);
        $info_mgr = ldap_get_entries($ds, $sr_mgr);
        $manager_email = $info_mgr[0]['mail'][0];
        $manager_login = $info_mgr[0]['samaccountname'][0];
        //echo $manager_email;
        // currently, syncing with LDAP means updating manager info
        // update of cname may not be required
        if(!empty($manager_login)){
                // manager login fetched successfully from LDAP
                //return $this->updateMyManager($username, $manager_login, $manager_email);
        }
        else{
                // manager info could not be fetched from LDAP for some reason
                $this->setError('Your manager info could not be updated.');
                return false;
        }
}
public function getEmployeeOUString($username){
$select = $this->pdo->prepare("SELECT ou_long_string
                FROM fi_ou LEFT JOIN emp_list
                ON fi_ou.id = emp_list.ou
                WHERE emp_list.username = '$username'");
        $select->execute();
        $row = $select->fetch(PDO::FETCH_ASSOC);
        if(empty($row['ou_long_string'])){
                //default OU string
                return "OU=Standard,OU=Users,OU=Pune,OU=RG - India,DC=win,DC=ansys,DC=com";
        }
        return $row['ou_long_string'];
}


/* 
    Create New User Single Sign On
*/

public function createNewUser($username){
	$user_types = $this->getUserTypes();
	$user_type_id = 0;
	foreach($user_types as $k=>$v){
		if($v['type'] == 'Employee'){
			$user_type_id = $v['id'];
		}
	}
        try{
	$stmt = "INSERT INTO emp_list
            (`username`, `status`, `joining_date`, `user_type`)
            VALUES(?,1,NOW(),?)";
	    $stmt = $this->pdo->prepare($stmt);
            $stmt->execute(array($username,$user_type_id));
            $user_id = $this->pdo->lastInsertId();
        }
        catch(PDOException $e){
            // check if username already exists in the system
            //$this->setError($e->getCode());
		//echo $e->getMessage();
            if($e->getCode() == 23000){
                $this->setStdError('user_exists');
            }
            else{
                //$this->setError($e->getMessage());
                $this->setStdError('user_not_created');
            }
            return false;
        }
	return $user_id;
}

/*
	Get user id from username
*/
public function getUserIdFromUsername($username){
	
	try{
	$select = $this->pdo->prepare("SELECT id FROM emp_list WHERE username = ?");
	$select->execute(array($username));
	}
	catch(PDOException $e){
	$this->setError($e->getMessage());
	return false;
	}
	$row = $select->fetch(PDO::FETCH_ASSOC);
	return $row['id'];
}

/* function logout
	added for activity logging purpose
*/
function logout(){
	$action_details=array();
	$action_details['table_name']='users';
	$action_details['row_id']=$this->user_id;
	$action_details['operation']='Logged Out';
	$this->createActionLog($action_details);
	//$_SESSION['user_id'] = null;
	return true;
}

/*
function isAdmin() 
	return true if logged in user type is Admin other wise return false with error message.
*/
	function isAdmin(){

		if($this->user_type == 'Admin'){
			return true;
		}
		return false;
	}

public	function isManager(){
//echo $this->user_type;exit;
		if($this->user_type == 'Manager'){
			return true;
		}
		return false;
	}
	function isEmployee(){

		//if($this->user_type == 'Employee'){
			return true;
		//}
		//return false;
	}
public	function isTravelDesk(){
		if($this->user_type == 'Travel Desk'){
			return true;
		}
		return false;
	}
/*
function getUserType() 
	return user type of logged in user.
*/
function getUserType(){
	//return $this->user_profile['user_type'];
	$select = $this->pdo->prepare("SELECT user_types.type FROM user_types, emp_list
	WHERE user_types.id = emp_list.user_type
	AND emp_list.id = ?");
	$select->execute(array($this->user_id));
	$row = $select->fetch(PDO::FETCH_ASSOC);
//echo $row['type'];
	return $row['type'];	
}

/*
function _loginRedirect() 
	function redirect user to the index page. 
*/
    function _loginRedirect(){
        	// send user to the login page
        	header("Location:/login.php");
    }
    
/*
function getUserDetails($user_id) 
	function accept the user id as parameter and return the users details of that user id. 
*/
    function getUserDetails($userid){
	$records = $this->pdo->select('emp_list', '`id`='.$this->user_id);
	return $records[0];
}
function getUserDetailsid($userid){
	$records = $this->pdo->select('emp_list', '`id`='.$userid);
	return $records[0];
}
/*
function updateMyProfile($data) 
	function accept the data array as parameter and update the logged in user information
	with data in parameter array and return true if update user information successfully else return false with error message. 
*/
function updateMyProfile($data){
	// use $this->pdo->update
	//print_r($data);
	//print_r($_FILES);
	
	try{
	$path = $_SERVER['DOCUMENT_ROOT']."/uploads/passport-copy/";
                if($_FILES['passport_copy']['name']!=''){
                        $fname=$this->user_id."_".$_FILES['passport_copy']['name'];
                        $ftmpname=$_FILES['passport_copy']['tmp_name'];
			if(move_uploaded_file($ftmpname, $path.$fname)){
				$update_qry = $this->pdo->prepare("UPDATE emp_list SET passport_copy=? WHERE id=?");	
				$update_qry->execute(array($fname,$this->user_id));
			}
		}


	$this->pdo->update('emp_list', $data, '`id`='.$this->user_id);
	$pathvisa = $_SERVER['DOCUMENT_ROOT']."/uploads/visa-copy/";
		if(is_array($data['visa-no'])){ //print_r($data);
		//echo $c=count($data['visa-no']);
			for($x = 0; $x < count($data['visa-no']); $x++ )
			{ //echo $c=count($data['visa-no']);
			if($_FILES['visa-copy']['name'][$x]!=''){
                        $f=$this->user_id."_".$_FILES['visa-copy']['name'][$x];
                        $ftmpn=$_FILES['visa-copy']['tmp_name'][$x];

			 $visa_number = $data['visa-no'][$x];
			$stmt3 = "INSERT INTO `visa`  (`emp_id`,`visa_no`,`visa-exp-date`,`visa-country`)VALUES(?,?,?,?)";
			$stmt3 = $this->pdo->prepare($stmt3);
			#$stmt3->execute(array($_SESSION['user_id'],$visa_number,$data['visa-exp-date'][$x],$data['visa-country'][$x]));  
			if(!empty($data['visa-no'][$x])){ //print_r($data);
				$stmt3->execute(array($_SESSION['user_id'],$data['visa-no'][$x],$data['visa-exp-date'][$x],$data['visa-country'][$x]));  
			}
			#$stmt3->execute(array($_SESSION['user_id'],$visa_number,$data['visa-exp-date'][$x],$data['visa-country'][$x]));  
			if(move_uploaded_file($ftmpn, $pathvisa.$f)){
				$updateqry = $this->pdo->prepare("UPDATE visa SET visa_copy=? WHERE emp_id=?");	
				$updateqry->execute(array($f,$this->user_id));
			} # move_uploaded_file ends
			} # $_FILES['visa-copy']['name'][$x] if ends

			} # foreach ends
                
                } #$data['visa-no'] if ends
if(is_array($data['air-co'])){
                     for($y = 0; $y < count($data['air-co']); $y++ )
			{
                           if(!empty($data['air-co'][$y])){
                            $ff = "INSERT INTO `frequent_flyer`  (`emp_id`,`air-co`,`ffp-name`,`ffp-id`)VALUES(?,?,?,?)";   
			   $ff = $this->pdo->prepare($ff);
		            $ff->execute(array($_SESSION['user_id'],$data['air-co'][$y],$data['ffp-name'][$y],$data['ffp-id'][$y]));  
                        } 
			}
                  }

	}
	catch(PDOException $e){
		//echo "error: ".$e->getMessage();
                $this->setError($e->getMessage());
                return false;
        }
	
	
	return true;
}

/*
function resetPassword($username,$userEmail)
	function accept three parameters username password and email address
	and reset the user password and return true after successfully password reset other wise retun false 
	with error message.
*/
public function resetPassword($userName,$userEmail){
    
    $code = generatePassword(64);
	$select = $this->pdo->prepare("SELECT * FROM users 
        WHERE username = ? AND email = ?");
    $select->execute(array($userName, $userEmail));
    $row = $select->fetch(PDO::FETCH_ASSOC);
		if(count($row)!= 0){
		   $update=$this->pdo->prepare("UPDATE users 
                   SET `password`=?  
                   WHERE username=? AND email = ?");
        	        if($update->execute(array($this->getPasswordHashOfUser($userName,$code), $userName, $userEmail))){
						$log = debug_backtrace();
						$this->createActionLog($log);

                        // add to Reset Queue
                        $data['user_id'] = $this->getUserIdFromUsername($userName);
                        $data['code'] = $code;
                        $this->addToPasswordResetQueue($data);
                        //template var data
                        $userid = $this->getUserIdFromUsername($userName);
                        $user_details = $this->getUserDetails($userid);
                        $data = array(
                                'userName'=>$userName,
                                'pwd'=>$code,
                                'name'=> $user_details['first_name'],
                                'last_name'=> $user_details['last_name']
                            );
                        $this->sendTemplateEmail($userEmail, $this->app_config['password_retrieval_subject_path'],
                            $this->app_config['password_retrieval_body_path'],$data);
               	        return true;
	                }else{
                        $this->setError($update);
               	         return false;
	                }
		 }# if of not empty array check
	         else{
        	       $this->setStdError("reset_password_error");
                       return false;
	        }
    }

function aasort (&$array, $key){
	$sorter=array();
	$ret=array();
	reset($array);
	foreach ($array as $ii => $va){
		$sorter[$ii]=$va[$key];
	}
	asort($sorter);
	foreach ($sorter as $ii => $va){
	$ret[$ii]=$array[$ii];
	}
	$array=$ret;
}


/*function createActionLog($details){
            
		$insert = "INSERT INTO action_log 
				(`table_name`,`row_id`,`operation`,`created_on`,`created_by`)
				VALUES(?,?,?,NOW(),?)";
		$insert_args = array($details['table_name'],$details['row_id'],$details['operation'],$this->user_id);
        try{
		    $stmt=$this->pdo->prepare($insert);		
		    $stmt->execute($insert_args);
        }
        catch(PDOException $e){
				$this->setError($e->getMessage());
				return false;
			}	
			return true;
}*/

function getAllUsers(){
	$per_page=10;
	$p =1;
	$offset = ($p - 1) * $per_page;
	$select = "SELECT * ,users.id as user_id, user_types.type AS user_type FROM users 
		LEFT JOIN user_types ON user_types.id=users.type WHERE username !='cron'";
		//LIMIT $offset, $per_page";
		
		$res = $this->pdo->query($select);
		return $res->fetchAll(PDO::FETCH_ASSOC);
}

function getUserTypes(){
	return $this->pdo->select('user_types');
}

function sendTemplateEmail($to,$subject_path,$body_path,$template_vars){
$app_config = $this->app_config;
$email_from_address='no-reply@aad.org';
//include 'config.php';
$subject_path = $app_config['document_root']."/../".$subject_path;
$body_path = $app_config['document_root']."/../".$body_path;
//$headers = "From:$email_from_address\n";
$headers = "From:$email_from_address\n";
$email_subject_body = getEmailTemplateBody($subject_path);
$email_template_body = getEmailTemplateBody($body_path);
$email_body = $this->getEmailBody($email_template_body,$template_vars);
$email_subject = $this->getEmailBody($email_subject_body,$template_vars);
$this->sendSMTPEmail($to, $email_subject, $email_body);
}

public function getEmailBody($template_body,$arr_of_variable){
$body = $template_body;
//$subdomain = $this->getMySubdomain().'.'.$this->app_config['http_host'];
#$http_host = empty($_SERVER['HTTP_HOST'] || preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $_SERVER['HTTP_HOST']))?$subdomain:$_SERVER['HTTP_HOST'];
//$http_host = !empty($this->getMySubdomain())?$subdomain:$_SERVER['HTTP_HOST'];

foreach($arr_of_variable as $k => $v){
        $pattern[$k]="/\[\[$k\]\]/";
        $replacement[$k] = str_replace('$', '\$', $v);
        $body = preg_replace($pattern,$replacement,$body);
}
$pattern= '/\[\[server\]\]/';
$body = preg_replace($pattern,$http_host,$body);
return $body;
}


public function sendSMTPEmail($to, $email_subject, $email_body){
if(empty($to)){return false;}
############ Send mail by SMTP
$app_config = $this->app_config;
$mail = new PHPMailer(true);
$mail->IsSMTP(); // set mailer to use SMTP
//$mail->SMTPDebug = 1;  // debugging: 1 = errors and messages, 2 = messages only
$mail->SMTPAuth = true;
$mail->SMTPKeepAlive = true;
$mail->SMTPSecure = $app_config['smtp_protocol'];
$mail->Port = $app_config['smtp_port'];
//echo $app_config['smtp_host'];
$mail->Host = $app_config['smtp_host']; // specify main and backup server
$mail->Username = $app_config['smtp_username'];
$mail->Password = $app_config['smtp_password'];
$mail->From = $app_config['smtp_from'];
$mail->FromName = $app_config['smtp_fromname'];
$mail->AddAddress($to);   // name is optional
$mail->IsHTML(false);    // set email format to HTML
$mail->Subject = $email_subject;
$mail->Body = $email_body;
try{
    $mail->Send();
}
catch(Exception $e){
	logMessage($to.' | '.$email_subject. ' | '.$e->getMessage());
	return false;
}
/*
if(!$mail->Send())
{
    //echo "Mailer Error: " . $mail->ErrorInfo;
}
else
{
    //echo 'Mail sent!';
}
*/
return true;
}

/*
Functions related to parameter tables
*/
function getParamInfo($type='case'){ // default type = case
    
#### returns all the info stored in params table
	if($type == 'all'){
		$select = "SELECT * FROM parameters 
            WHERE `org_id` = $this->org_id";
        $args = array();
	}
	else{
		$select = "SELECT parameters.* FROM parameters, parameter_classes 
		WHERE parameter_classes.id = parameters.class 
        AND `org_id` = $this->org_id 
		AND parameter_classes.name = ?";
        $args = array($type);
	}

	try{
	$stmt = $this->pdo->prepare($select);
    	$stmt->execute($args);
	}
	catch(PDOException $e){
		$this->setError($e->getMessage());
	}
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
prepareParams(data)
accepts an array which has param names as keys
and returns an array of arrays which has param ids as keys 
names are replaced with ids from the db table parameters
first value of each 2 element array is text value and the second is option value (an int)
*/
function prepareParams($data){
	$param_info = $this->getParamInfo();
	$param_ids = array();
	$param_types = array();

	foreach($param_info as $p_i){
		$param_ids[$p_i['param']] = $p_i['id'];
		$param_types[$p_i['param']] = $p_i['param_type'];
	}
	$new_params = array();
	foreach($data as $k=>$v){
		if(isset($param_ids[$k]) && $param_types[$k] == 'text'){
			$new_params[$param_ids[$k]][0] = $v;
		}
		elseif(isset($param_ids[$k])){
			if(is_array($v)){
				$v = implode(',',$v);
			}
			$new_params[$param_ids[$k]][1] = $v;
			$new_params[$param_ids[$k]][0] = $data[$k.'_text_value'];
		}
	}
	return $new_params;
}
/*
Does reverse of prepareParams.
*/
function prepareParamsReverse($data){
	$param_info = $this->getParamInfo();
	$param_ids = array();
	foreach($param_info as $p_i){
		$param_ids[$p_i['id']] = $p_i['param'];
	}
	$new_params = array();
	foreach($data as $k=>$v){
		if(isset($param_ids[$k])){
		#	$new_params[$param_ids[$k]] = $v;//this is the text value
			$new_params[$param_ids[$k]][0] = $v[0];//this is the text value
			$new_params[$param_ids[$k]][1] = $v[1];//this is the option value
		}
	}
	return $new_params;
}

/*
    Create password hash of user
*/
public function getPasswordHashOfUser($username,$password){
	$options = array('cost'=>12, 'salt'=>md5(strtolower($username)));
	return password_hash($password, PASSWORD_DEFAULT, $options);
}

/*
Inform user that their password has been updated
*/
public function notifyPasswordUpdate($data){
	$this->sendTemplateEmail($data['email'], $this->app_config['password_reset_notification_subject'], $this->app_config['password_reset_notification_body'], $data);
}


/*added by Rupali*/
function airlines(){
	return $this->pdo->select('airlines',' 1 ORDER BY name ASC');
}

/*added by Rupali*/
function cities(){
	return $this->pdo->select('cities', '1 ORDER BY city_name ASC');
}
/*added by Rupali*/
function hotels(){
	return $this->pdo->select('hotels', '1 ORDER BY hotel_name ASC');
}
/*added by Rupali*/
function cars(){
	return $this->pdo->select('car_companies', '1 ORDER BY name ASC');
}

public function getTravelRequestsById($start_from, $limit){
	try{
		$select = $this->pdo->prepare("select trips.id,trips.date,trips.manager_approved,emp_list.firstname,
			emp_list.middlename,emp_list.lastname,emp_list.ou,emp_list.entity from trips 
			left join emp_list on emp_list.id = trips.emp_id Where emp_list.id = ? ORDER BY trips.id DESC LIMIT $start_from, $limit");
		$select->execute(array($_SESSION['user_id']));
        }
        catch(PDOException $e){
                $this->setError($e->getMessage());
                return false;
        }
        while ($row = $select->fetch(PDO::FETCH_ASSOC)){
		$trips[] = $row;
	}
		return $trips;
	} 
public function getTravelRequestsByIdpagination(){
	try{
		$select = $this->pdo->prepare("select trips.id,trips.date,trips.manager_approved,emp_list.firstname,
			emp_list.middlename,emp_list.lastname,emp_list.ou,emp_list.entity from trips 
			left join emp_list on emp_list.id = trips.emp_id Where emp_list.id = ? ORDER BY trips.id DESC");
		$select->execute(array($_SESSION['user_id']));
        }
        catch(PDOException $e){
                $this->setError($e->getMessage());
                return false;
        }
        while ($row = $select->fetch(PDO::FETCH_ASSOC)){
		$trips[] = $row;
	}
		return $trips;
	} 

public function getVisaDetails(){
	try{
		$select = $this->pdo->prepare("select * from visa where emp_id = ?");
		$select->execute(array($_SESSION['user_id']));
        }
        catch(PDOException $e){
                $this->setError($e->getMessage());
                return false;
        }
        while ($row = $select->fetch(PDO::FETCH_ASSOC)){
		$trips[] = $row;
	}
		return $trips;
	}
public function getff(){
	try{
		$select = $this->pdo->prepare("select * from frequent_flyer where emp_id = ?");
		$select->execute(array($_SESSION['user_id']));
        }
        catch(PDOException $e){
                $this->setError($e->getMessage());
                return false;
        }
        while ($row = $select->fetch(PDO::FETCH_ASSOC)){
		$trips[] = $row;
	}
		return $trips;
	}
/*added by Rupali*/

public function getcity($id){

try{
		$select = $this->pdo->prepare("SELECT * FROM cities WHERE id = ? ORDER BY city_name ASC");
	$select->execute(array($id));
	}
	catch(PDOException $e){
		$this->setError($e->getMessage());
		return false;
	}
	$row = $select->fetch(PDO::FETCH_ASSOC);
	return $row;
}
/*added by Rupali*/
public function gethotel($id){

try{
		$select = $this->pdo->prepare("SELECT * FROM hotels WHERE id = ? ORDER BY hotel_name ASC");
	$select->execute(array($id));
	}
	catch(PDOException $e){
		$this->setError($e->getMessage());
		return false;
	}
	$row = $select->fetch(PDO::FETCH_ASSOC);
	return $row;
}

/*added by Rupali*/
public function getcars($id){

try{
		$select = $this->pdo->prepare("SELECT * FROM car_companies WHERE id = ?  ORDER BY name ASC");
	$select->execute(array($id));
	}
	catch(PDOException $e){
		$this->setError($e->getMessage());
		return false;
	}
	$row = $select->fetch(PDO::FETCH_ASSOC);
	return $row;
}
/*added by Rupali*/
public function getairlines($id){

try{  //echo "SELECT * FROM airlines WHERE id =".$id;
		$select = $this->pdo->prepare("SELECT * FROM airlines WHERE id = ? ORDER BY name ASC");
	$select->execute(array($id));
	}
	catch(PDOException $e){
		$this->setError($e->getMessage());
		return false;
	}
	$row = $select->fetch(PDO::FETCH_ASSOC);
	return $row;
}

/*added by Rupali*/
  public function getTripDetails($trip_id){

	try{
	$select = $this->pdo->prepare("select trips.*,emp_list.firstname,
			emp_list.middlename,emp_list.lastname,emp_list.ou from trips left join emp_list on trips.emp_id = emp_list.id WHERE trips.id = ?");
	$select->execute(array($trip_id));
	}
	catch(PDOException $e){
	$this->setError($e->getMessage());
	return false;
	}
	$row = $select->fetch(PDO::FETCH_ASSOC);
	return $row;


}/*added by Rupali*/
public function getdestdep($id){
//echo $id;
	try{
	$select = $this->pdo->prepare("SELECT * FROM destination_and_departure WHERE trip_id = ? ORDER BY id ASC");
	$select->execute(array($id));
	}
	catch(PDOException $e){
	$this->setError($e->getMessage());
	return false;
	}
	//$row = $select->fetch(PDO::FETCH_ASSOC);


$row = $select->fetchAll(PDO::FETCH_ASSOC);//print_r($row);
	return $row;
}
/*added by Rupali*/
function travelrequestsuggestedplan($data){//print_r($data);
        try{
		$id = $data['trip_id'];

		if(is_array($data['book_airline'])){
		//echo $c=count($data['travel_from']);
		for($x = 0; $x < count($data['book_airline']); $x++ )
		    {

			$query1 = $this->pdo->prepare("UPDATE trips SET `manager_approved` = '1' WHERE `id` = ?");	
			$query1->execute(array($id));

		    }


		}

		return true;


        }
        catch(PDOException $e){
            // check if username already exists in the system
            //$this->setError($e->getCode());
          		$this->setError($e->getMessage());

        }
}
## function ends

function getAirBookings($trip_id){
        $records = $this->pdo->select('air_bookings', '`trip_id`='.$trip_id);
        return $records;
}

function getCarBookings($trip_id){
        $records = $this->pdo->select('car_bookings', '`trip_id`='.$trip_id);
        return $records;
}

function getHotelBookings($trip_id){
        $records = $this->pdo->select('hotel_bookings', '`trip_id`='.$trip_id);
        return $records;
}

function getTrainBookings($trip_id){
	$records = $this->pdo->select('train_bookings', '`trip_id`='.$trip_id);
        return $records;
}
/*added by Rupali*/
function getou(){
	return $this->pdo->select('fi_ou', '1 ORDER BY ou_short_name ASC');
}

/*added by Rupali*/
function office_locations(){
	return $this->pdo->select('fi_office_locations', '1 ORDER BY location ASC');
}

function getoffice_locations($loc_id){
        $records = $this->pdo->select('fi_office_locations', '`id`='.$loc_id);
//print_r($records);
        return $records[0];
}
function updatemyrequestbooking($data)
{
	$this->pdo->update('trips', $data, '`trip_id`='.$trip_id);
	$this->pdo->update('destination_and_departure', $data, '`trip_id`='.$trip_id);
}
 /*added by Rupali*/
function getous($id){

try{
		$select = $this->pdo->prepare("SELECT * FROM fi_ou WHERE id = ?");
	$select->execute(array($id));
	}
	catch(PDOException $e){
		$this->setError($e->getMessage());
		return false;
	}
	$row = $select->fetch(PDO::FETCH_ASSOC);
	return $row;
}

function isMyProfileComplete(){
 	try{
                $select = $this->pdo->prepare("SELECT ou,email,firstname,lastname,contact_no FROM emp_list WHERE id='$this->user_id'");
                $select->execute();
        }
        catch(PDOException $e){
                $this->setError($e->getMessage());
                return false;
        }
        $row = $select->fetch(PDO::FETCH_ASSOC);
	if(empty($row['ou']) || empty($row['email']) || empty($row['firstname']) || empty($row['lastname']) || empty($row['contact_no']))
	{
		return false;
	}
	return true;
}

function deleteVISA($id){
    try{
	$select = $this->pdo->prepare("DELETE FROM visa WHERE id = ? AND emp_id=$this->user_id");
	$select->execute(array($id));
	}
	catch(PDOException $e){
		$this->setError($e->getMessage());
		return false;
	}
	return true;
}	

function deleteFFP($id){
    try{
        $select = $this->pdo->prepare("DELETE FROM frequent_flyer WHERE id = ? AND emp_id=$this->user_id");
	$select->execute(array($id));
	}
	catch(PDOException $e){
	    echo $e->getMessage();
		$this->setError($e->getMessage());
		return false;
	}
	return true;
}

function sendemail($to,$subject,$body,$formemail,$id,$flag){
	//Send Bookings Mail	
	//echo $uid;
//echo $to;echo "<br />";
try{    $headers  = "MIME-Version: 1.0\n";
	$headers .= "Content-type: text/html; charset=us-ascii\n";
	$headers .= "Content-Transfer-Encoding: 7bit\n";
	$headers .= "X-Priority: 3\n";
	$headers .= "X-MSMail-Priority: Normal\n";
	$headers .= "X-Mailer: FluentMail\n";
        if($flag=='request'){
		$to=' ';
		$row = $this->pdo->select('emp_list', '`id`='.$this->user_id);
		$sendername=$row[0]['firstname'].'  '. $row[0]['lastname'];
		$senderemail=$row[0]['email'];
		$to.=$senderemail.',';
		$traveldesks=$this->getalltraveldesk();		
		$memail=$this->getmanageremail($row[0]['manager']);
		$mangeremail=$memail['email'];
	
		foreach($traveldesks as $traveldesk){
		$to.=$traveldesk['email'].',';
		$headers .= "From: ".$sendername." <".$senderemail.">\n";
	         }
        }else{ 
		$to .=',';
		$memail=$this->getmanageremail($row[0]['manager']);
		 $mangeremail=$memail['email']; 
		}
	$to.= $mangeremail;
	if($mangeremail){$body.='<a href="http://atp.carvingit.com/request_approved.php?flag=App&uid='.base64_encode($memail['id']).'&id='.base64_encode($id).'">Approve</a><br/>
	<a href="http://atp.carvingit.com/request_approved.php?flag=dis&uid='.base64_encode($memail['id']).'&id='.base64_encode($id).'">Disapprove</a>';}
	//echo $to;echo $body;
//echo $to;echo "<br />";echo $subject;echo "<br />";echo $formemail;echo "<br />";echo $id; echo "<br />";echo $flag;echo "<br />";
//exit;			
	mail($to, $subject, $body,$headers); 	
}##try
catch(PDOException $e){
	//echo $e->getMessage();
	$this->setError($e->getMessage());
	return false;
}
return true;	
}


function getalltraveldesk()
{
$records = $this->pdo->select('emp_list', '`user_type`=1');
        return $records;
}
function getmanageremail($mangerid)
{
	$row = $this->pdo->select('emp_list', '`id`='.$mangerid);
        return $row[0];
}
/*added by Rupali*/
function requestapproved($id){//print_r($data);

$this->pdo->update('trips', array('manager_approved'=>1),  array('id'=>$id)); return true;	
}

/*added by Rupali*/
function requestdisapproved($id){//print_r($data);

$this->pdo->update('trips', array('manager_approved'=>0),  array('id'=>$id)); return true;	

	
}
/*added by Rupali*/
function requestopen($id){

$this->pdo->update('trips', array('status'=>'Open'), '`id`='.$id); return true;	
}

/*added by Rupali*/
function requestclosed($id){

$this->pdo->update('trips', array('status'=>'Closed'), '`id`='.$id);return true;	
}
/*added by Rupali*/
function allrequestopen($start_from, $limit){
try{
		$select = $this->pdo->prepare("select trips.id,	trips.trip_type,trips.date,trips.manager_approved,emp_list.firstname,
			emp_list.middlename,emp_list.lastname,emp_list.ou from trips left join emp_list on trips.emp_id = emp_list.id  Where trips.status='Open' ORDER BY trips.id DESC LIMIT $start_from, $limit");
		       $select->execute();
        }
        catch(PDOException $e){
                $this->setError($e->getMessage());
                return false;
        }
        while ($row = $select->fetch(PDO::FETCH_ASSOC)){
		$trips[] = $row;
	}
		return $trips;
}

/*added by Rupali*/
function allrequestclosed($start_from, $limit){
try{
		 $select = $this->pdo->prepare("select trips.id,trips.date,trips.manager_approved,emp_list.firstname,
			emp_list.middlename,emp_list.lastname,emp_list.ou,emp_list.entity from trips 
			left join emp_list on emp_list.id = trips.emp_id Where trips.status='Closed' ORDER BY trips.id DESC LIMIT $start_from, $limit");
		$select->execute(array($_SESSION['user_id']));
        }
        catch(PDOException $e){
                $this->setError($e->getMessage());
                return false;
        }
        while ($row = $select->fetch(PDO::FETCH_ASSOC)){
		$trips[] = $row;
	}
		return $trips;
}

/*added by Rupali*/
function allrequestclosedpagination(){
try{
	$select = $this->pdo->prepare("select trips.id,trips.date,trips.manager_approved,emp_list.firstname,
			emp_list.middlename,emp_list.lastname,emp_list.ou,emp_list.entity from trips 
			left join emp_list on emp_list.id = trips.emp_id Where trips.status='Closed' ORDER BY trips.id DESC");
		$select->execute(array($_SESSION['user_id']));
        }
        catch(PDOException $e){
                $this->setError($e->getMessage());
                return false;
        }
        while ($row = $select->fetch(PDO::FETCH_ASSOC)){
		$trips[] = $row;
	}
		return $trips;
}

/* added by mayur*/

public function getMetaSearch($search){
$qu="SELECT * FROM amazon_data WHERE  MATCH(title) AGAINST ('$search' IN BOOLEAN MODE)";
$statment = $this->pdo->prepare($qu);
$statment->execute();
	while($result = $statment->fetch(PDO::FETCH_ASSOC)){ 
		$meta_result[]=$result;
		
	}
//;
//print_r($meta_result);
return $meta_result;
}

public function getIsbndetails($search){
	//echo $search;

		$qu="SELECT * FROM amazon_data WHERE isbn_10='$search'";
		//echo $qu;
		$statment = $this->pdo->prepare($qu);
		$statment->execute();
			while($result = $statment->fetch(PDO::FETCH_ASSOC)){ 
				$meta_result[]=$result;
					}
		//;
		//print_r($meta_result);
		return $meta_result;
	}

// Converts ISBN-13 to ISBN-10
// Leaves ISBN-10 numbers (or anything else not matching 13 consecutive numbers) alone
function ISBN13toISBN10($isbn) {
    if (preg_match('/^\d{3}(\d{9})\d$/', $isbn, $m)) {
        $sequence = $m[1];
        $sum = 0;
        $mul = 10;
        for ($i = 0; $i < 9; $i++) {
            $sum = $sum + ($mul * (int) $sequence{$i});
            $mul--;
        }
        $mod = 11 - ($sum%11);
        if ($mod == 10) {
            $mod = "X";
        }
        else if ($mod == 11) {
            $mod = 0;
        }
        $isbn = $sequence.$mod;
    }
    return $isbn;
}




public function outputCsv($fileName, $assocDataArray)
{
    ob_clean();

    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=' . $fileName);    
    if(isset($assocDataArray['0'])){
        $fp = fopen('php://output', 'w');
        fputcsv($fp, array_keys($assocDataArray['0']));
        foreach($assocDataArray AS $values){
            fputcsv($fp, $values);
        }
        fclose($fp);
    }
    
    ob_flush();
    //header("Location: http://isbn-search/index.php");

}

function removeElementWithValue($array, $key, $value){
     foreach($array as $subKey => $subArray){
          if($subArray[$key] == $value){
               unset($array[$subKey]);
          }
     }
     return $array;
}

function insertIntoUsers($data)
{
	echo $data[password];
	$data[password]=md5($data[password]);
	$data['created_at']=date("Y-m-d H:i:s");

$this->pdo->insert('users', $data);
}


public function authenticate($username, $password){
	$password= md5($password);
	//echo $password;
	$record = $this->pdo->select('users','`email`="'.$username.'"');
	//print_r($record[0][password]);
	if ($password ==$record[0]['password']) {
		//$this->user_id = $record[0]['borrowernumber'];
		//echo "verified";

		$_SESSION['email']=$record[0]['email'];
		return true;
	} 
	//echo '<span style="color:red">Email or password not match</span>';
	return false;
}
################################################
}//User class ends here	$records = $this->pdo->select('emp_list', '`id`='.$this->user_id);
?>

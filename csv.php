<?php
session_start();
include 'User.class.php';
include 'class.db.php';
$u=new User;
//print_r($_SESSION);

//$remove_last=array_pop($_POST);
//print_r($_POST);

$list_arr=array_values($_POST);

foreach ($list_arr as $key => $la) {
	//echo $sl;
	//$query_result_list=$u->getIsbndetails($sl);

	//print_r($query_result_list[0]);
	# code...
$isbn_details=$u->getIsbndetails($la);
array_shift($isbn_details[0]);
	$isbn_array_result[]=$isbn_details[0];
//printf($isbn_details);

}

//print_r($isbn_array_result);
 $file_name='isbn'.date('m-d-Y-hia').'.csv';
//session_destroy();
unset($_SESSION['isbn_list']);
$u->outputCsv( $file_name,$isbn_array_result);

//header("Location: http://isbn-search/index.php");



 ?>


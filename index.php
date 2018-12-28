<?php
session_start();
error_reporting(E_ALL);

if(empty($_SESSION))
{
	$_SESSION[isbn_list]=array();
}

include 'header.php';
include 'User.class.php';
include 'class.db.php';
header("Content-Type: text/html; charset=ISO-8859-8");
$u=new User;

if(!empty($_GET['search'])){

	$search_terms = $_GET['search'];
	//echo $search_terms;
	//$search_terms = str_replace('+',' ',$search_terms);
	
//if($_GET['select_type1'] =="Isbn"){
	

//$isbn10=$u->ISBN13toISBN10($search_terms);
//print_r($isbn10);

//}	
}
?>
<html>

<head>
<link rel="stylesheet" type="text/css" href="/css/style.css">
<meta charset="ISO-8859-1"> 
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
 <meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-8"> 
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="/js/functions.js"></script> 
</head>


<body>
<form method="get" id="form">
<div class="search_box">

<br>


<select  name="select_type1"  id="select_t1" >
	<option value="Isbn" selected >ISBN</option>
	<option value="Title">TITLE</option>

  
  
<script>
document.getElementById('select_t1').value = "<?php echo $_GET['select_type1'];?>";
  </script>
</select>



<input type="text" class="input_search" name="search" placeholder="Search ..." value="<?php echo $search_terms?>"/>
<button type="submit" onclick="function openCity(evt, cityName)" class="btn-style" id="Go">Go</button>
<br>
<br>
<br>
<br>
</div>
</form>
<div class="loader" style="visibility:hidden;"></div>
<?php
if(empty($_GET['search'])&& $_GET['submit'] !="List"){
?>
<div class="wrapper">
<div class ="content">

<p align="justify"><br/><span style="font-size: 1.3vw;line-height: 1.5;"> 
What would it mean to you if, needing knowledge for your studies, work, a project, or to develop an idea, you just had to step through one door to have your wishes made reality?  Without the hassle of browsing numerous sites, you can access any information needed at just one click. We at First Ray Consulting, have developed a utility to search Books, Journals, Theses, Papers, Periodical, Databases, Repositories like DSPACE, Eprints..
</span></p>
</div>
<br/>

<div class="slider" id="main-slider"><!-- outermost container element -->
	<div class="slider-wrapper"><!-- innermost wrapper element -->
		<img src="images/whdl-slider-1.jpg" alt="First" class="slide" /><!-- slides -->
		<img src="images/bookmark-books-bokeh-hd-wallpaper.jpg" alt="Second" class="slide" />
		<!--<img src="http://acert.hunter.cuny.edu/files/2015/04/10671587203_327d4e7b20_z.jpg" alt="Second" class="slide" />-->
                   <img src="images/fonstola.ru-89861.jpg"alt="Second" class="slide" />

		<img src="images/referensi.jpg"alt="Third" class="slide" />		
		<img src="images/books_library_old_111388_2048x1152.jpg" alt="Four" class="slide" />
	</div>
</div>	
</div>

<?php 
}
?>
<ul class="tab">
  

<?php 
	if( $_GET['select_type1'] =="Title")
	{
	$count_metasearch=0;
	
	
	$query_result=$u->getMetaSearch($search_terms);
		

	foreach ($query_result as $qr){
	
	
	//	$search_meta= $qr['title'].'<br/>&nbsp <i>  by, </i>'.$qr['publisher']; 
		$search_meta='<tr><td><input type="checkbox" name="chklist'.$qr['isbn_10'].'" value="'.$qr['isbn_10'].'"></td><td>'.$qr['isbn_10'].'</td><td>'.$qr['isbn_13'].'</td><td>'.$qr['title'].'</td><td>'.$qr["author"].'</td><td>'.$qr["publisher"].'</td><td>'.$qr["language"].'</td><td>'.$qr["Subjectdb"].'</td><td>'.substr($qr["Details"], 0, 100).'..</td></tr>';


   $result_metasearch.=$search_meta;
   $count_metasearch++;	

   }
   }
if( $_GET['select_type1'] =="Isbn")
	{
		//$trim_search_term=trim($search_terms,'-');
	$trim_search_term=str_replace("-","",$search_terms);

	//echo $trim_search_term;

	 if (is_numeric($trim_search_term)) {

	 	

				$isbn10=$u->ISBN13toISBN10($trim_search_term);
				
			$count_metasearch=0;
			$query_result=$u->getIsbndetails($isbn10);

			if(empty($query_result)){
				$input="python book_fetched.py $isbn10" ;
			$output = exec($input); 
			//print_r($output);
			$query_result=$u->getIsbndetails($isbn10);
			}
	}else{
			//for alphanumeric isbn
			//echo $trim_search_term;
			$count_metasearch=0;
			$query_result=$u->getIsbndetails($trim_search_term);
			
			if(empty($query_result)){
				
				$input="python book_fetched.py $trim_search_term" ;
			$output = exec($input); 
			//print_r($output);
			$query_result=$u->getIsbndetails($trim_search_term);
			}

	}

	foreach ($query_result as $qr){
	
		$search_meta='<tr><td><input type="checkbox" name="chklist'.$qr['isbn_10'].'" value="'.$qr['isbn_10'].'"></td><td>'.$qr['isbn_10'].'</td><td>'.$qr['isbn_13'].'</td><td>'.$qr['title'].'</td><td>'.$qr["author"].'</td><td>'.$qr["publisher"].'</td><td>'.$qr["language"].'</td><td>'.$qr["Subjectdb"].'</td><td>'.substr($qr["Details"], 0, 100).'..</td></tr>';


   $result_metasearch.=$search_meta;
   $count_metasearch++;	

   			}
   }

?>


<!--for csv list -->
<?php if($_GET['submit'] =="List" && empty($_GET['search']))
{
$remove_last=array_pop($_GET); //for removing last element of array like word list

$list_value=$_GET;
//print_r($list_value);
foreach ($list_value as $key => $value) {
array_push($_SESSION[isbn_list],$value);
//print_r($_SESSION[isbn_list]);
	
}
//print_r($session_list_isbn);
$session_list_isbn=array_unique($_SESSION[isbn_list]);
$count_metasearch=0;
foreach ($session_list_isbn as $key => $sl) {
	//echo $sl;
	$query_result_list=$u->getIsbndetails($sl);
	$isbn_array_result[]=$query_result_list[0];
	//print_r($query_result_list[0]);
	# code...


	$search_meta='<tr><td><input type="checkbox"  checked="checked" name="chklist'.$query_result_list[0]['isbn_10'].'" value="'.$query_result_list[0]['isbn_10'].'"></td><td>'.$query_result_list[0]['isbn_10'].'</td><td>'.$query_result_list[0]['isbn_13'].'</td><td>'.$query_result_list[0]['title'].'</td><td>'.$query_result_list[0]["author"].'</td><td>'.$query_result_list[0]["publisher"].'</td><td>'.$query_result_list[0]["language"].'</td><td>'.$query_result_list[0]["Subjectdb"].'</td><td>'.substr($query_result_list[0]["Details"], 0, 100).'..</td></tr>';

	$result_metasearch.=$search_meta;
   $count_metasearch++;	

}
//print_r($_SESSION);
//unset($_GET);

?>
<form  method="post" action="csv.php" id="DownloadCSVform">

	<li><a href="#" class="tablinks" >Selected Results For CSV(<?php echo $count_metasearch ?>)</a></li>
	<div id="contentwrapper">
	<div id="Doag" class="tabcontent">
	<!--<h5 style="color:#003366">Results From DatabaseResources</h5>-->
	<h5 style="color:#003366">Please select following results to generate CSV</h5>	
	<?php echo "<table><tbody>";
	echo '<tr><th><input type="checkbox" checked="checked" onclick="toggle(this);"/></th><th>ISBN10</th><th>ISBN13</th><th>TITLE</th><th>AUTHOR</th><th>PUBLISHER</th><th>LANGUAGE</th><th>SUBJECT</th><th>SUMMARY</th></tr>'; ?>
	<?php echo   $result_metasearch;
	echo "</table>";
	if(!empty($_SESSION[email])){

	echo '<input type="button" name ="DownloadCSV" value="Download CSV" onclick="submit_form()">';
}else{
echo '<input type="button" name ="loginto" value="Login to Download CSV" onclick="login();">';
}
    echo '</form>';
   // print_r($isbn_array_result);

//$u->array_to_csv_download($isbn_array_result);


}


//print_r($isbn_array_result);

//if($_GET[Download CSV]=='Download CSV'){

	//$u->array_to_csv_download($isbn_array_result);
	//$u->convert_to_csv($isbn_array_result);

	//$u->outputCsv('isbn.csv',$isbn_array_result);
//}
?>
<script type="text/javascript">
	
function submit_form(){
$("#DownloadCSVform").submit();
setTimeout(function(){ window.location.href="index.php"; }, 3000);

//alert("hii");
	}

function login(){
	window.location.href="login.php";
}



</script>

<!--for csv list end -->


<!--for display tables for isbn and title -->
<?php if (!empty($result_metasearch)){ 
if($_GET['select_type1'] =="Title" ||$_GET['select_type1'] =="Isbn")
{
	?>


<form  method="get" id="form">

	<li><a href="#" class="tablinks" ><?php echo $_GET['select_type1'];?> Search Results(<?php echo $count_metasearch ?>)</a></li>
	<div id="contentwrapper">
	<div id="Doag" class="tabcontent">
	<h5 style="color:#003366">Please select following results to generate CSV</h5> 	
	<?php echo "<table><tbody>";
	echo '<tr><th><input type="checkbox"  onclick="toggle(this);"/></th><th>ISBN10</th><th>ISBN13</th><th>TITLE</th><th>AUTHOR</th><th>PUBLISHER</th><th>LANGUAGE</th><th>SUBJECT</th><th>SUMMARY</th></tr>'; ?>
	<?php echo   $result_metasearch;
	echo "</table>";
	if($_GET['submit'] !="List"){
	//echo '<input type="submit" name ="submit" value="List">';
	echo '<button type="submit" name="submit" value="List" class="orange_btn">Add To List</button>';
	}
    echo '</form>';
	}
	 
	 }else if(!empty($search_terms)){
echo '<div id="contentwrapper">';
        
echo '<h5 style="color:#003366"> &nbsp;&nbsp;&nbsp;Result`s Not Found ..</h5>';
echo '</div>';
         }

	//unset($_SESSION[isbn_list]);

	 ?>
	
	
	</div>
	</div>

</ul>
<!--for display table end -->


<br/>
<br/>

<script type="text/javascript">
$('#form').submit(function() {
    $('.loader').css('visibility', 'visible');
});
</script>

<script>
eventFire(document.getElementById('firsttab'), 'click');



function toggle(source) {
    var checkboxes = document.querySelectorAll('input[type="checkbox"]');
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i] != source)
            checkboxes[i].checked = source.checked;
    }
}
</script>

<?php #include("footer.php"); ?>

</body>
</html>

<?php
function domNodeList_to_string($DomNodeList) {
    $output = '';
    $doc = new DOMDocument;
    while ( $node = $DomNodeList->item($i) ) {
        // import node
        $domNode = $doc->importNode($node, true);
        // append node
        $doc->appendChild($domNode);
        $i++;
    }
    $output = $doc->saveXML();
    $output = print_r($output, 1);
    // I added this because xml output and ajax do not like each others
    $output = htmlspecialchars($output);
    return $output;
}

?>


<link rel="stylesheet" type="text/css" href="/css/style.css">
<nav>
<?php
session_start();

 if(empty($_SESSION['email'])){
echo '<a href="login.php"><i class="fa fa-user"></i> Login </a>&nbsp;';
}else{
echo '<a href="logout.php"><i class="fa fa-user"></i> Logout </a>&nbsp;';
}
?>
<a href="/?submit=List"><i class="fa fa-tasks"></i> List </a> 

</nav>

<div id="header " class ="header">
<a href="index.php"> <IMG  src="images/cropped-logo3-1.png" ></a>
<h1>Knowbees Consulting Cataloging Tool <h1>

</div>


<!-- Favicon Path -->
<link rel="shortcut icon" type="image/png" href="http://knowbees.in/wp-content/uploads/2018/04/cropped-logo3-1.png"/>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

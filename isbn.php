
<!DOCTYPE HTML>
<html>  
<body>

<form  method="post">
ISBN: <input type="text" name="ISBN"><br>

<input type="submit">
</form>

</body>
</html>
<?php 

print_r($_POST[ISBN]);
$ans=$_POST[ISBN];

$output = exec('python data_fetched.py print_r($ans)'); 

 echo $output;


?>
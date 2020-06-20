<?php 
$dir = 'C:\php_logs';
$files2 = scandir($dir, 1);
$currentdayminus1 = date('Y-m-d',strtotime(date('Y-m-d') . ' -1 day'));
// $currentdayminus1 = (isset($_GET['date']) && !empty($_GET['date']))?date('Y_m_d',strtotime($_GET['date'])):$currentdayminus1;

foreach ($files2 as $key => $filename) {
	
	$filenameexplode = explode('.',$filename);
	$fileextension = $filenameexplode[array_key_last($filenameexplode)];
	// var_dump($filenameexplode);
	if($fileextension == 'log'){
		if($filename == 'log_'.$currentdayminus1.'.log'){
			$myfile = fopen($dir . '/'.$filename, "r") or die("Unable to open file!");
			$counter = 0;
			$string = '';
			while(!feof($myfile)) {
			  	$char = fgetc($myfile);
			  	if($char != ']' || $char =! 'C'  || $char =! 'U' || $char =! 'T'  ){

			  		$counter++;
			  	}else{
			  		$counter = 0;
			  	}
			  	if($counter == 4){
			  		$itemlist[] = trim($string);
			  		$string = '';

			  	}else{
			  		$string .= $char;
			  	}
			  
			}
			// print_r($itemlist);
		}
		
	}

}
foreach ($itemlist as $key => $error) {
	if($key > 2){
		$exploded = explode(': ', $error);
		$errorinfo = [];
		$type = $exploded[0];
		$explodedin = explode(' in ', $error);

		$location = (isset($explodedin[1]) && !empty($explodedin[1]))?substr($explodedin[1], 0, strpos($explodedin[1], "[")):'Undefined';
		$errorinfo['location'] = substr($location, 0, strpos($location, "on line"));
		$errorinfo['location'] = str_replace('\\', '/', trim($errorinfo['location'])) ;
		$errorinfo['tool'] = str_replace('D:/WEB/', '', $errorinfo['location']);
		$errorinfo['tool'] = substr($errorinfo['tool'],0,strpos($errorinfo['tool'], '/'));
		if($errorinfo['location'] != 'Undefined'){
			$lineexplode = explode('on',$location);
			$errorinfo['linenumber'] = (isset($lineexplode[1]) && !empty($lineexplode[1]))?preg_replace('/\D/', '', $lineexplode[1]):0;
			// substr($lineexplode[1], strpos($lineexplode[1], "on line"));
		}else{
			$errorinfo['linenumber'] = 'Undefined';
		}
		if($type == 'Warning'){
			$explodedcountedwarningtype = count($exploded);
			if($explodedcountedwarningtype == 2){
				$errorinfo['error'] = $exploded[0];
				$errorinfo['errordescription'] = $exploded[1];
			}else if($explodedcountedwarningtype == 3){
				$errorinfo['error'] = $exploded[1];
				$errorinfo['errordescription'] = $exploded[2];
			}else if($explodedcountedwarningtype == 4){
				$errorinfo['error'] = $exploded[1];
				// print_r($exploded);
				$errorinfo['errordescription'] = $exploded[2] . '-' .  $exploded[3];
			}else{
				// if for some reason a other data type pop'ups this line will be triggered
				// print_r($explodedcountedwarningtype);
			}
		}else if($type == 'Notice'){
			$explodedcountedwnoticetype = count($exploded);
			if($explodedcountedwnoticetype == 2){
				$errorinfo['error'] = $exploded[0];
				$errorinfo['errordescription'] = $exploded[1];
			}else if($explodedcountedwnoticetype == 3){
				$errorinfo['error'] = $exploded[1];
				$errorinfo['errordescription'] = $exploded[2];
			}else{
				// if for some reason a other data type pop'ups this line will be triggered
				// print_r($explodedcountedwnoticetype);

			}
		}else if($type == 'Fatal error' || $type == 'Deprecated'){
			$errorinfo['error'] = $type;
			$explodedcopy = $exploded;
			unset($explodedcopy[0]);
			$errorinfo['errordescription'] = join(': ',$explodedcopy);
		}else{
			$errorinfo['error'] = $error;
			$errorinfo['errordescription'] = NULL;
			$type = 'none';
		}
		$errorinfo['errordescription'] = substr($errorinfo['errordescription'], 0, strpos($errorinfo['errordescription'], "on line"));
		$errorinfo['type'] = $type;
		$errors[$type][] = $errorinfo; 
	}
}
// print_r(['d',2,5]);


$servername = "localhost";
$username = "root";
$password = "root";
$db       = "php_logs";


try {
  
  $pdo = new PDO("mysql:host=$servername;dbname=$db", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $stmt = $pdo->prepare("INSERT INTO logs (location, linenumber, error, errordescription, type,tool) VALUES (:location, :linenumber, :error, :errordescription, :type,:tool)");
  foreach ($errors as $type => $line) {
  	try {
	    $pdo->beginTransaction();
	    foreach ($line as $row)
	    {
	        $stmt->execute($row);
	    }
	    $pdo->commit();
	}catch (Exception $e){
	    $pdo->rollback();
	    throw $e;
	}

  }
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}
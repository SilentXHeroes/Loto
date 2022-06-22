<?php
	$type = $_REQUEST["type"];
	if(isset($_REQUEST["save"])) {
		file_put_contents($type . "-all-results.json", $_REQUEST["JSON"]);
	}
	else {
		echo file_get_contents("https://www.lesbonsnumeros.com/$type/resultats/tirages-". $_REQUEST["month"] ."-". $_REQUEST["year"] .".htm");
	}
?>
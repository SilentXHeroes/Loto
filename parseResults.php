<?php
	$type = isset($_REQUEST["type"]) ? $_REQUEST["type"] : "loto";
	$file = $type . "-all-results.json";
	$results = file_exists($file) ? file_get_contents($file) : '{}';
?>
<!DOCTYPE html>
<html>
<head>
	<title>Récupération des résultats de l'Euromillion</title>
	<meta charset="utf-8">
	<style>
		#content {
			display: none;
		}
	</style>
</head>
<body>
	<div id="content"></div>
	<div id="results"></div>
	<script>
		var results = <?= $results ?>;
		var type = <?= json_encode($type); ?>;
		var months = ["janvier","fevrier","mars","avril","mai","juin","juillet","aout","septembre","octobre","novembre","decembre"];
		var month = type === "loto" ? 9 : 1;
		var year = type === "loto" ? 2008 : 2004;
		var enableLoop = true;
		var xhr = new XMLHttpRequest();
		var thisMonth = new Date();
		var currentMonth = thisMonth.getMonth() + 1;
		var currentYear = thisMonth.getFullYear();
		var currentDate = (currentMonth < 10 ? '0' : '') + currentMonth +'/'+ currentYear;
		
		currentMonth--;
		if(currentMonth === 0) {
			currentMonth = 12;
			currentYear--;
		}
		var prevCurrentDate = (currentMonth < 10 ? '0' : '') + currentMonth +'/'+ currentYear;
		
		if(typeof results.games === "undefined") results.games = {};
		if(typeof results.parsed === "undefined") results.parsed = [];
		
		function parseContent() {
			let strMonth = month + 1;
			if(strMonth < 10) strMonth = '0' + strMonth;
			
			if(currentDate !== strMonth +'/'+ year && prevCurrentDate !== strMonth +'/'+ year && results.parsed.includes(strMonth +'/'+ year)) {
				nextMonth(true);
				return;
			}
			
			xhr.open("POST", "getContent.php");
			xhr.onloadend = function() {
				document.getElementById("content").innerHTML = xhr.responseText;
				
				document.querySelectorAll("#page tbody tr").forEach(nodeRow => {
					let date = parseInt(nodeRow.firstElementChild.innerText.trim().split(' ')[0]);
					if(date < 10) date = '0' + date;
					
					let strDate = date +'/'+ strMonth +'/'+ year;
					
					if(typeof results.games[strDate] === "undefined") {					
						results.games[strDate] = {
							numbers: [],
							chance: []
						};					
						nodeRow.querySelectorAll(type === "loto" ? ".boule" : ".carre").forEach(boule => {
							results.games[strDate].numbers.push(boule.innerText.trim())
						});
						nodeRow.querySelectorAll(type === "loto" ? ".chance" : ".etoile").forEach(chance => {
							results.games[strDate].chance.push(chance.innerText.trim())
						});
					}
					
					document.getElementById("results").innerHTML += "<p>Date: "+ strDate +" => "+ results.games[strDate].numbers.join(' - ') +" // "+ results.games[strDate].chance.join(' - ') +"</p>";
				});
				
				results.parsed.push(strMonth +'/'+ year);
				
				nextMonth();
			};
			
			let form = new FormData();
			form.append("type", type);
			form.append("month", months[month]);
			form.append("year", year);
			form.append("JSON", JSON.stringify(results));
			
			xhr.send(form);
		}
		
		function nextMonth(force = false) {				
			month++;
			
			if(month > 11) {
				month = 0;
				year++;
			}
			
			if(force || document.querySelector("#main .prevnext > .next > a")) parseContent();
			else {
				let form = new FormData();
				form.append("type", type);
				form.append("JSON", JSON.stringify(results));
				form.append("save", true);
				
				let lastXHR = new XMLHttpRequest();
				lastXHR.open("POST", "getContent.php");
				lastXHR.send(form);
			}
		}
		
		parseContent();
	</script>
</body>
</html>
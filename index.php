<?php
	$type = isset($_REQUEST["type"]) ? $_REQUEST["type"] : "loto";
	$from = isset($_REQUEST['xml']) ? 'XML' : 'JSON';
	
	if($from === 'XML') {
		$RSS = "https://www.lesbonsnumeros.com/rss.xml";
		$contentRSS = file_get_contents($RSS);
		$xml = new SimpleXMLElement($contentRSS);
		
		$results = json_decode(file_get_contents("results.json"), TRUE);
		
		foreach($xml->channel->item as $result) {
			$desc = (string) $result->description;
			$game = strpos($desc, 'loto/resultats') !== FALSE ? "loto" : "euromillion";
			$date = (new DateTime((string) $result->pubDate))->format("d/m/Y H:i:s");
			
			if(isset($results[$game][$date])) continue;
			
			preg_match_all("/<h3>.+:(.+)<\/h3>/", $desc, $matches);
			$numbers = $matches[1];
			$results[$game][$date] = [
				'numbers' => explode(' - ', trim($numbers[0])),
				'chance' => explode(' - ', trim($numbers[1]))
			];
			var_dump($results[$game][$date]);
			echo "<br><br>";
		}
		
		file_put_contents("results.json", json_encode($results));
	}
	else {
		$filename = $type . "-all-results.json";
		if(file_exists($filename)) {
			$allResults = json_decode(file_get_contents($filename), TRUE);
		}
		else {
			$allResults = [ "games" => [] ];
		}
		$results = [ $type => $allResults["games"] ];
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Prévisions LOTO</title>
		<meta charset="utf-8">
		<style>
			body, html {
				width: 100%;
				height: 100%;
			}
		
			body {
				display: flex;
				justify-content: center;
				align-items: center;
			}
			
			body > div {
				width: 90%;
			}
			
			.flex {
				display: flex;
			}
			.flex-col {
				display: flex;
				flex-direction: column;
			}
			
			#range {
				width: 100%;
			}
		</style>
	</head>
	<body>
		<div>
			<!--<canvas id="chart1"></canvas>
			<canvas id="chart2"></canvas>-->
			<div id="chart3"></div>
			<p>
				<label>-20 jours</label>
				<input id="range" type="range" value="-30" min="-100" max="-30"/>
			</p>
		</div>
		<!--<div id="games">
			<div class="flex-col">
			<?php
				for($i = 0; $i < 49; $i++) echo '<div><span>'. ($i + 1) .'</span><span></span></div>';
			?>
			</div>
			<div class="flex">
			<?php
				$dates = array_keys($results["euromillion"]);
				$lastDates = [];
				for($i = count($dates) - 1; $i >= count($dates) - 20; $i--) {
					$date = explode('/', $dates[$i]);
					$lastDates[$dates[$i]] = intval($date[2] . $date[1] . $date[0]);
				}
				asort($lastDates);
				$dates = array_keys($lastDates);
				foreach($dates as $date) {
					echo '<div><span>'. $date .'</span><span></span></div>';
				}
			?>
			</div>
		</div>-->
		<script type="text/javascript" src="Chart.min.js"></script>
		<script>
			var from = <?= json_encode($from); ?>;
			var type = <?= json_encode($type); ?>;
			var data = <?= json_encode($results); ?>;
			var countBallsPlayed = 6;
			var countBalls = 49;
			var countChances = 10;
			var datasets = [];
			var dataType = data[type];
			var allNumbers = [];
			var allChances = [];
			
			if(type === "euromillion") {
				countBallsPlayed++;
				countBalls++;
				countChances += 2;
			}
			
			var totalBalls = countBalls + countChances;
			
			for(let i = 0; i < countBallsPlayed; i++) {
				let color = i < 5 ? "rgb(0,0,0)" : "rgb(255, 218, 126)";
				datasets.push({
					label: '',
					data: [],
					borderColor: color,
					backgroundColor: color
				});
			}
			for(var date in dataType) {
				let result = dataType[date];
				result.numbers.forEach((number, i) => {
					number = parseInt(number);
					datasets[i].data.push(number);
					allNumbers.push(number);
				});
				result.chance.forEach((chance, i) => {
					allChances.push(parseInt(chance));
				});
			}
			
			var nextNumbersToPlay = {};
			var nextChanceToPlay = {};
			for(let i = 0; i < countBalls; i++) {
				let number = (i + 1).toString();
				let a = Object.values(dataType).map((result, index) => result.numbers.includes(number) ? index : -1).filter(index => index > -1);
				let b = [];
				
				a.forEach((index, i) => { 
					if(typeof a[i + 1] !== "undefined") {
						let diff = i === 0 ? index : a[i + 1] - index;
						b.push(diff);
					}
				});
				nextNumbersToPlay[number] = parseInt(b.reduce((x,y) => x + y) / b.length);
			}
			
			for(let i = 0; i < countChances; i++) {
				let chance = (i + 1).toString();
				let a = Object.values(dataType).map((result, index) => result.chance.includes(chance) ? index : -1).filter(index => index > -1);
				let b = [];
				
				a.forEach((index, i) => { 
					if(typeof a[i + 1] !== "undefined") {
						let diff = i === 0 ? index : a[i + 1] - index;
						b.push(diff);
					}
				});
				nextChanceToPlay[chance] = parseInt(b.reduce((x,y) => x + y) / b.length);
			}
			
			// var chart = new Chart(document.getElementById("chart1"), {
				// type: "bar",
				// data: {
					// labels: Object.keys(dataType).map(date => date.split(' ')[0]),
					// datasets: datasets
				// },
				// options: {
					// responsive: true,
					// legend: {
						// display: false
					// }
				// }
			// });
			
			let labelsNumbers = Array.from({ length: countBalls }, (val, i) => i + 1);
			let labelsChances = Array.from({ length: countChances }, (val, i) => i + 1);
			let dataset = {
				label: '',
				data: [],
				borderColor: "rgb(0,0,0)",
				backgroundColor: "rgb(0,0,0)"
			};
			labelsNumbers.forEach(label => dataset.data.push(allNumbers.filter(number => label === number).length / allNumbers.length * 100));
			labelsChances.forEach(label => dataset.data.push(allChances.filter(chance => label === chance).length / allChances.length * 100));
			
			// var chart = new Chart(document.getElementById("chart2"), {
                // type: "bar",
                // data: {
					// labels: labelsNumbers.concat(labelsChances),
					// datasets: [dataset]
				// },
                // options: {
                    // responsive: true,
					// legend: {
						// display: false
					// }
                // }
            // });
			
			var range = document.getElementById("range");
			range.addEventListener("input", function(){
				this.previousElementSibling.innerText = this.value + " jours";
				print();
			});
			
			let dates = Object.fromEntries(Object.keys(dataType).map(date => [parseInt(date.split('/')[2] + date.split('/')[1] + date.split('/')[0]), date]));
			let allDates = Object.keys(dates).sort().map(date => dates[date]);
			var currentDate = new Date();
			var dateDiff = 0;
			var nextDates = [];
			while(dateDiff < 10) {
				var strDate = formatDate(currentDate);
				if(typeof dataType[strDate] === "undefined") {
					if(currentDate.getDay() === 2 || currentDate.getDay() === 5) {
						nextDates.push(strDate);
						dateDiff++;
					}
				}
				currentDate.setDate(currentDate.getDate() + 1);
			}
			var fullDates = allDates.concat(nextDates);
				
			function print() {
				let maxLen = fullDates.length + parseInt(range.value) + 30;
				let labels = fullDates.slice(range.value, maxLen);
				
				datasets = [];
				for(let i = 0; i <= totalBalls; i++) {
					datasets.push({
						label: '',
						data: [],
						fill: false,
						borderColor: "rgb(0,0,0)",
						backgroundColor: "rgb(0,0,0)"
					});
				}
				datasets.forEach((dataset, i) => {
					labels.forEach(date => {
						let value = NaN;
						let typeNumber = i > countBalls ? "chance" : "numbers";
						let number = i;
						
						if(i > countBalls) number -= countBalls;
						
						if( ! dataType[date]) return;
						
						if(dataType[date][typeNumber].includes(number.toString())) {
							value = { x: NaN, y: i };
						}
						dataset.data.push(value);
					});
				});
				for(let i = 0; i <= totalBalls; i++) {
					let dataset = datasets[i];
					let last = dataset.data.map((x, index) => ( {value: x, index: index } )).filter(x => x.value);
					last = last.pop();
					
					if(typeof last === "undefined") continue;
					
					let number = i;
					let nextNumberIndex = i;
					let dataDataset = [];
					let nextTableToPlay = i > countBalls ? nextChanceToPlay : nextNumbersToPlay;
					
					if(i > countBalls) {
						number -= countBalls;
						nextNumberIndex = number;
					}
					
					for(let y = 0; y < last.index; y++) dataDataset.push(NaN);
					for(let y = 0; y < nextTableToPlay[nextNumberIndex]; y++) dataDataset.push({ x: NaN, y: i });
					
					datasets.push({
						label: '',
						data: dataDataset,
						fill: false,
						borderColor: "rgba(255,0,0,.3)",
						backgroundColor: "rgba(255,0,0,.3)"
					});
				}
				
				let node = document.getElementById("chart3");
				node.innerHTML = "<canvas></canvas>";
				new Chart(node.firstElementChild, {
					type: "line",
					data: {
						labels: labels,
						datasets: datasets
					},
					options: {
						animation: false,
						legend: {
							display: false
						},
						scales: {
							yAxes: [{
								ticks: {
									min: 0,
									max: totalBalls,
									stepSize: 1,
									callback: function(label, index, labels) {
										return label - (label > countBalls ? countBalls : 0);
									}
								}
							}]
						}
					}
				});
			}
			
			print();
			
			function formatDate(date) {
				let month = date.getMonth() + 1;
				let day = date.getDate();
				return (day < 10 ? '0' : '') + day +'/'+ (month < 10 ? '0' : '') + month +'/'+ date.getFullYear();
			}
		</script>
	</body>
</html>
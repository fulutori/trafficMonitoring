<!-- https://php-archive.net/php/filesize_unit/ -->
<?php
	$device_color = [
		'デスクトップPC'=>'0, 0, 119',
		'ノートPC'=>'51, 153, 34',
		'Zenfone5'=>'228, 4, 28',
		'SH-M09'=>'0, 221, 221',
		'Alexa'=>'68, 136, 153',
		'PS4'=>'221, 0, 136',
		'RasberryPi4'=>'34, 170, 119',
		'TL-SG108E'=>'221, 0, 119'
	];

	$date_day = date('Ymd');
	$date_month = date('Ym');
	
	ini_set('display_errors', "On");
	$pdo = new PDO('sqlite:/mnt/hdd/RoomManager/db/traffic.db');
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

	// 端末一覧を取得
	$device_db = new PDO('sqlite:/mnt/hdd/RoomManager/db/device.db');
	$device_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$device_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

	$device_sql = $device_db->prepare('SELECT * FROM devices');
	$device_sql->execute();
	$device_list = $device_sql->fetchAll(PDO::FETCH_ASSOC);


	// 最終更新日時を取得
	$last_date_sql = $pdo->prepare('SELECT date FROM log ORDER BY date DESC LIMIT 1');
	$last_date_sql->execute();
	$temp_date = $last_date_sql->fetchAll(PDO::FETCH_ASSOC)[0]['date'];
	$log_date = strtotime(substr($temp_date, 0, 4)."-".substr($temp_date, 4, 2)."-".substr($temp_date, 6, 2)." ".substr($temp_date, 8, 2).":".substr($temp_date, 10, 2));
	$update_date = strtotime('+10 minute' , $log_date);
	$last_date = date("Y年m月d日H時i分", $update_date);


	// 最新データから1000件取得
	$data_sql = $pdo->prepare('SELECT * FROM log GROUP BY date ORDER BY date DESC LIMIT 1000');
	$data_sql->execute();
	$data_list = $data_sql->fetchAll(PDO::FETCH_ASSOC);


	// データセット初期化
	$datasets = array();
	foreach ($device_list as $key => $value) {
		$datasets[$value['device']] = ['temp_upload'=>0, 'temp_download'=>0, 'upload'=>'', 'download'=>'', 'sum'=>0];
	}

	$labels = "";
	$tooltips_labels = "";
	$max_upload_byte = 0;
	$max_download_byte = 0;
	$max_sum_byte = 0;
	$cnt = 0;
	foreach ($data_list as $key => $value) {
		// ラベル作成
		if (substr($value['date'], -2, 2) == "00") {
			if ($cnt < 24) {
				if ($labels !== "") {
					$labels = substr($value['date'], 8, 2).", ".$labels;
					$tooltips_labels = substr($value['date'], 8, 2).":\"".substr($value['date'], 4, 2)."月".substr($value['date'], 6, 2)."日".substr($value['date'], 8, 2)."時\", ".$tooltips_labels;
				} else {
					$labels = substr($value['date'], 8, 2);
					$tooltips_labels = substr($value['date'], 8, 2).":\"".substr($value['date'], 4, 2)."月".substr($value['date'], 6, 2)."日".substr($value['date'], 8, 2)."時\"";
				}

				foreach ($device_list as $devie_key => $device_value) {
					if ($datasets[$device_value['device']]['temp_download'] < 1048576) {
						$decimal = 2;
					} else {
						$decimal = 1;
					}

					// 1時間ごとのアップロード、ダウンロードサイズを取得
					$datasets[$device_value['device']]['upload'] = round($datasets[$device_value['device']]['temp_upload'] / 1048576, $decimal).','.$datasets[$device_value['device']]['upload'];
					$datasets[$device_value['device']]['download'] = round($datasets[$device_value['device']]['temp_download'] / 1048576, $decimal).','.$datasets[$device_value['device']]['download'];
					
					// echo byte_format($datasets[$device_value['device']]['temp_download'], 0, true, true)."<br>";


					// アップロードとダウンロードの合計
					$datasets[$device_value['device']]['sum'] = round(($datasets[$device_value['device']]['temp_upload']+$datasets[$device_value['device']]['temp_download']) / 1048576, $decimal).','.$datasets[$device_value['device']]['sum'];

					// 最大合計サイズ取得
					if ($datasets[$device_value['device']]['temp_upload']+$datasets[$device_value['device']]['temp_download'] > $max_sum_byte) {
						$max_sum_byte = $datasets[$device_value['device']]['temp_upload']+$datasets[$device_value['device']]['temp_download'];
					}

					// 最大アップロードサイズ取得
					if ($datasets[$device_value['device']]['temp_upload'] > $max_upload_byte) {
						$max_upload_byte = $datasets[$device_value['device']]['temp_upload'];
					}

					// 最大ダウンロードサイズ取得
					if ($datasets[$device_value['device']]['temp_download'] > $max_download_byte) {
						$max_download_byte = $datasets[$device_value['device']]['temp_download'];
					}

					// 仮トラフィック量を初期化
					$datasets[$device_value['device']]['temp_upload'] = 0;
					$datasets[$device_value['device']]['temp_download'] = 0;
				}
			} else {
				break;
			}

			$cnt = $cnt+1;
		}


		$datasets[$value['device']]['temp_upload'] = $datasets[$value['device']]['temp_upload']+$value['upload'];
		$datasets[$value['device']]['temp_download'] = $datasets[$value['device']]['temp_download']+$value['download'];
	}
	// print_r($datasets);
	
	// 最大単位取得
	$max_unit = "\"".substr(byte_format($max_upload_byte, 0, true), -2, 2)."\"";

	// データセット作成
	$make_datasets = "";
	foreach ($datasets as $key => $value) {
		$zero_check = explode(",", $value['download']);
		if (count($zero_check)-1 === array_count_values($zero_check)[0]) {
			continue;
		}
		

		$make_datasets = $make_datasets."
		{
		 	label: '".$key."',
		 	data: [".$value['download']."],
		 	borderColor: \"rgba(".$device_color[$key].", 1)\",
		 	backgroundColor: \"rgba(".$device_color[$key].", 0.3)\",
		},";
	}
	

	// 最大値取得
	$max_upload_byte = byte_format($max_upload_byte, 0, true, true);
	$max_download_byte = byte_format($max_download_byte, 0, true, true);
	$max_sum_byte = byte_format($max_sum_byte, 0, true, true);
	

	// y軸のステップ数
	$step = round($max_download_byte / 7, 0);


	// 端末ごとの合計：今日
	$day_sql = $pdo->prepare('SELECT device,SUM(upload) as upload,SUM(download) as download FROM log WHERE date LIKE ? GROUP BY device');
	$day_sql->execute([$date_day."%"]);
	$day = $day_sql->fetchAll(PDO::FETCH_ASSOC);


	// 端末ごとの合計：今月
	$month_sql = $pdo->prepare('SELECT device,SUM(upload) as upload,SUM(download) as download FROM log WHERE date LIKE ? GROUP BY device');
	$month_sql->execute([$date_month."%"]);
	$month = $month_sql->fetchAll(PDO::FETCH_ASSOC);


	// 端末ごとの合計：累計
	$sum_sql = $pdo->prepare('SELECT device,SUM(upload) as upload,SUM(download) as download FROM log GROUP BY device');
	$sum_sql->execute();
	$sum = $sum_sql->fetchAll(PDO::FETCH_ASSOC);


	$traffic_list = [];

	// 端末別の通信量を出力
	foreach ($device_list as $devie_key => $device_value) {
		$sum_upload = 0;
		$sum_download = 0;
		$month_upload = 0;
		$month_download = 0;
		$day_upload = 0;
		$day_download = 0;

		$device = $device_value['device'];
		foreach ($sum as $sum_key => $sum_value) {
			foreach ($month as $month_key => $month_value) {
				foreach ($day as $day_key => $day_value) {
					if ($day_value['device'] == $device) {
						$day_upload = $day_value['upload'];
						$day_download = $day_value['download'];
						break;
					}
				}
				if ($month_value['device'] == $device) {
					$month_upload = $month_value['upload'];
					$month_download = $month_value['download'];
					break;
				}
			}
			if ($sum_value['device'] == $device) {
				$sum_upload = $sum_value['upload'];
				$sum_download = $sum_value['download'];
				break;
			}
		}

		$traffic_list[] = ['device'=>$device, 'day_upload'=>$day_upload, 'day_download'=>$day_download, 'month_upload'=>$month_upload, 'month_download'=>$month_download, 'sum_upload'=>$sum_upload, 'sum_download'=>$sum_download];	
	}
	

	// 「今日」の通信量が多い順にソート
	foreach ((array) $traffic_list as $key => $value) {
		$sort[$key] = $value['day_download'];
	}
	array_multisort($sort, SORT_DESC, $traffic_list);


	// 通信量の単位を変換
	$shaped_traffic = [];
	foreach ($traffic_list as $key => $value) {
		if ($value['sum_upload'] === 0 AND $value['sum_download'] === 0) {
			continue;
		}
		$shaped_traffic[$value['device']]['device'] = $value['device'];
		$shaped_traffic[$value['device']]['day_upload'] = byte_format($value['day_upload'], 1, true);
		$shaped_traffic[$value['device']]['day_download'] = byte_format($value['day_download'], 1, true);
		$shaped_traffic[$value['device']]['month_upload'] = byte_format($value['month_upload'], 1, true);
		$shaped_traffic[$value['device']]['month_download'] = byte_format($value['month_download'], 1, true);
		$shaped_traffic[$value['device']]['sum_upload'] = byte_format($value['sum_upload'], 1, true);
		$shaped_traffic[$value['device']]['sum_download'] = byte_format($value['sum_download'], 1, true);
	}


	// 単位変換関数
	function byte_format($size, $dec=-1, $separate=false, $opt=false){
		$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		$digits = ($size == 0) ? 0 : floor( log($size, 1024) );

		$over = false;
		$max_digit = count($units) -1 ;

		if($digits == 0){
			$num = $size;
		} else if(!isset($units[$digits])) {
			$num = $size / (pow(1024, $max_digit));
			$over = true;
		} else {
			$num = $size / (pow(1024, $digits));
		}

		// GB以上の単位では小数点第二位まで表示
		if ($units[$digits] == "GB" OR $units[$digits] == "TB" OR $units[$digits] == "PB") {
			$dec++;
		}

		if($dec > -1 && $digits > 0) $num = sprintf("%.{$dec}f", $num);
		if($separate && $digits > 0) $num = number_format($num, $dec);

		if ($opt) {
			return ($over) ? $num . $units[$max_digit] : $num;
		}

		return ($over) ? $num . $units[$max_digit] : $num . $units[$digits];
	}
?>
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Room Manager</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="Refresh" content="600">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.js"></script>
	<link rel="stylesheet" href="umi/css/bootstrap.min.css">
</head>
<body>
	<div class="container">
	<!-- <div class="page-header" id="banner">
		<div class="row my-2">
			<div class="col-12 text-center">
				<div class="lead mx-1 mb-5 mt-2"><h1>Room Manager</h1></div>
			</div>
		</div>
	</div> -->
	<div class="w-100">
		<div class="pt-3"><hr color="#00f" class="pt-1 bg-primary"></div>
		<div id="time" class="float-left">現在日時</div>
		<div class="float-right">最終更新日時：<span id="last"></span></div>
		<div class="pt-4"><hr color="#00f" class="pt-1 bg-primary"></div>
		<div class="my-2">
			<canvas id="trafficChart" height="80px"></canvas>
		</div>
		<div class="pt-1 pb-3"><hr color="#00f" class="pt-1 bg-primary"></div>
	

		<!-- 表を出力 -->
		<?php
			echo "<table class=\"table table-hover\">\n";
			echo "\t\t<thead>\n";
			echo "\t\t\t<tr align=\"center\" style=\"font-size: 2em; font-weight: bold;\">\n\t\t\t\t<th rowspan=\"2\" style=\"vertical-align: middle;\">機器名</th>\n\t\t\t\t<td colspan=\"2\" style=\"padding: 2 0 0 0;\">今日</td>\n\t\t\t\t<td colspan=\"2\" style=\"padding: 2 0 0 0;\">今月</td>\n\t\t\t\t<td colspan=\"2\" style=\"padding: 2 0 0 0;\">累計</td>\n\t\t\t</tr>\n";
			echo "\t\t\t<tr align=\"center\">\n\t\t\t\t<th>アップロード</th>\n\t\t\t\t<th>ダウンロード</th>\n\t\t\t\t<th>アップロード</th>\n\t\t\t\t<th>ダウンロード</th>\n\t\t\t\t<th>アップロード</th>\n\t\t\t\t<th>ダウンロード</th>\n\t\t\t</tr>\n";
			echo "\t\t</thead>\n";
			echo "\t\t<tbody align=\"right\">\n";


			foreach ($shaped_traffic as $key => $value) {
				echo "\t\t\t<tr>\n\t\t\t\t<th class=\"align-middle p-3\">".$value['device']."</th>\n\t\t\t\t<td class=\"align-middle p-3\">".$value['day_upload']."</td>\n\t\t\t\t<td class=\"align-middle p-3\">".$value['day_download']."</td>\n\t\t\t\t<td class=\"align-middle p-3\">".$value['month_upload']."</td>\n\t\t\t\t<td class=\"align-middle p-3\">".$value['month_download']."</td>\n\t\t\t\t<td class=\"align-middle p-3\">".$value['sum_upload']."</td>\n\t\t\t\t<td class=\"align-middle p-3\">".$value['sum_download']."</td>\n\t\t\t</tr>\n";
			}

			echo "\t\t</tbody>\n";
			echo "\t</table>\n";
		?>

		<div class="pb-3"><hr color="#00f" class="pt-1 bg-primary"></div>
	</div>
	</div>

	<script type="text/javascript">
		// 折れ線グラフ描画
		var tooltips_labels = {<?php print_r($tooltips_labels); ?>};
		var ctx = document.getElementById("trafficChart");
		var trafficChart = new Chart(ctx, {
			type: 'line',
			data: {
				labels: [<?php print_r($labels); ?>],
				datasets: [
					<?php print_r($make_datasets); ?>
				]
			},
			options: {
				title: {
					display: true,
					text: '時間別トラフィック量(ダウンロード)'
				},
				scales: {
					yAxes: [{
						ticks: {
							suggestedMax: 30,
							suggestedMin: 0,
							stepSize: <?php print_r($step); ?>,
							callback: function(value, index, values) {
								return value+<?php print_r($max_unit); ?>;
							}
						}
					}]
				},
				tooltips: {
					mode: 'label',
					callbacks: {
						title: function(tooltipItem, data) {
							
							// return tooltipItem[0].xLabel;
							// return tooltips_labels+"時";
							return tooltips_labels[tooltipItem[0].xLabel];
						},
						label: function(tooltipItem, data) {
							return data.datasets[tooltipItem.datasetIndex].label+": "+tooltipItem.yLabel+" "+<?php print_r($max_unit); ?>;
						}
					}
				}
			}
		})

		// 日時取得関数
		var dayweek = ["月", "火", "水", "木", "金", "土", "日"];
		window.addEventListener("load", function(){
			var element=document.getElementById("time");
			setInterval(function(){
				var now = new Date();

				var year = now.getFullYear();
				var month = now.getMonth()+1;
				var day = now.getDate();
				var hour = now.getHours();
				var minute = now.getMinutes();
				var second = now.getSeconds();
				var week = dayweek[now.getDay()];
				if (minute % 10 === 1 && second === 59) {
					var reload = function() {
						location.reload();
					}
					setTimeout(reload, 1000);
				}

				if (hour < 10) { hour = "0"+hour; }
				if (minute < 10) { minute = "0"+minute; }
				if (second < 10) { second = "0"+second; }

				
				element.innerHTML = year+"年"+month+"月"+day+"日("+week+") "+hour+"時"+minute+"分"+second+"秒";
			}, 100);
		}, false)
		document.getElementById("last").innerHTML = <?php print_r(json_encode($last_date)); ?>;
	</script>
</body>
</html>
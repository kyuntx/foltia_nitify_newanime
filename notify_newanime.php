<?php
/*
 Anime recording system foltia
 http://www.dcc-jpl.com/soft/foltia/

notify_newanime.php
 https://github.com/kyuntx/foltia_notify_newanime

目的
アニメ新番組をメールで通知します。

事前にサーバ側でメールの送信設定が行われていること。
（postfix に relay_host の設定を行うなど）

 DCC-JPL Japan/foltia project

*/

include("/home/foltia/php/phpcommon/foltialib.php");

// foltia ANIME LOCKER のURI
$foltiauri = "http://192.168.xxx.xxx/";

// メールの送信先アドレス、送信元アドレス、サブジェクト
$mailto = "to@example.jp";
$mailfrom = "from@example.com";
$mailsubj = "[foltia ANIME LOCKER] New program notification";

// CSV形式で送信する場合は 1 とする
$mailcsv = 0;

mb_language("uni");
mb_internal_encoding("UTF-8");

$con = m_connect();
readconfig($con);

$now = getgetnumform(date);
if(($now < 200001010000 ) || ($now > 209912342353 )){ 
	$now = date("YmdHi");   
}


//同一番組他局検索tv_recordテーブル開いて比較
$query = "
SELECT
tid,
stationid
FROM foltia_tvrecord 
ORDER BY \"tid\" ASC 
";
$reservedtvrecord = sql_query($con, $query, "DBクエリに失敗しました");
$rowdata = $reservedtvrecord->fetch();
if ($rowdata) {
	do {
		$reservedtvrecordtidsidarray[] = $rowdata[0]."-".$rowdata[1];
		$reservedtvrecordtidarray[] = $rowdata[0];
	} while ($rowdata = $reservedtvrecord->fetch());
	$rowdata = "";
}else{
		$reservedtvrecordtidsidarray = array();
		$reservedtvrecordtidarray = array();
}//end if
$reservedtvrecord->closeCursor();

//新番組表示モード
$query = "
SELECT 
 foltia_program.tid, stationname,
 foltia_program.title,
 foltia_subtitle.countno,
 foltia_subtitle.subtitle,
 foltia_subtitle.startdatetime,
 foltia_subtitle.lengthmin,
 foltia_subtitle.pid,
 foltia_subtitle.startoffset ,
 foltia_subtitle.syobocalflag ,
 foltia_subtitle.stationid 
FROM foltia_subtitle , foltia_program ,foltia_station  
WHERE foltia_program.tid = foltia_subtitle.tid 
AND foltia_station.stationid = foltia_subtitle.stationid 
AND foltia_subtitle.enddatetime >= ?  
AND foltia_subtitle.countno = '1' 
AND foltia_subtitle.tid >= 1 
UNION 
SELECT 
 foltia_program.tid, 
 stationname,
 foltia_program.title,
 foltia_subtitle.countno,
 foltia_subtitle.subtitle,
 foltia_subtitle.startdatetime,
 foltia_subtitle.lengthmin,
 foltia_subtitle.pid, 
 foltia_subtitle.startoffset ,
 foltia_subtitle.syobocalflag,
 foltia_subtitle.stationid 
FROM foltia_subtitle , foltia_program ,foltia_station  
WHERE foltia_program.tid = foltia_subtitle.tid 
AND foltia_station.stationid = foltia_subtitle.stationid 
AND foltia_subtitle.enddatetime >= ? 
AND foltia_subtitle.syobocalflag = foltia_subtitle.syobocalflag | 2
ORDER BY startdatetime  ASC 
LIMIT 300
";

$rs = sql_query($con, $query, "DBクエリに失敗しました",array($now,$now));
$rowdata = $rs->fetch();


//放映予定のデータがないとき
if (! $rowdata) {
	die_exit("番組データがありません");
}//endif

     do {
	 	//再放送判定
		$rclass = "";//初期化
		$syobocalflag = $rowdata[9];
		$chk_bit=( 1 << 3 );
		if( $syobocalflag & $chk_bit ) // ビットが立ってる
		{
			$rclass = "repeat";
		}
		//他局で同一番組録画済み
		if (in_array($rowdata[0], $reservedtvrecordtidarray)) {
			$rclass = "already";
		}
		//録画予約済み
		$tidsidall = $rowdata[0]."-0";//もし全局予約だったら
		$tidsid = $rowdata[0]."-".$rowdata[10];
		if (in_array($tidsidall, $reservedtvrecordtidsidarray)) {
			$rclass = "planned";
		}elseif(in_array($tidsid, $reservedtvrecordtidsidarray)){
			$rclass = "planned";
		}
		$pid = htmlspecialchars($rowdata[7]);
		$tid = htmlspecialchars($rowdata[0]);
		$sid = htmlspecialchars($rowdata[10]);
		$title = htmlspecialchars($rowdata[2]);
		$subtitle =  htmlspecialchars($rowdata[4]);
		$pdate = foldate2print($rowdata[5]);
		$station = $rowdata[1];
		// 上記いずれのフラグもついていないもの($rclass="")を抽出
		if( $rclass == "" ){
			$newprogs[] = $rowdata;
		}
	} while ($rowdata = $rs->fetch());
	// 前回実行時の情報と比較
	$prevprogs = unserialize(file_get_contents("/home/foltia/newprogram.txt"));
	// 二次元配列の差分取得 http://stackoverflow.com/questions/11821680/array-diff-with-multidimensional-arrays
	$diffprogs = array_filter($newprogs, function ($element) use ($prevprogs) {
	    return !in_array($element, $prevprogs);
	});
	file_put_contents( "/home/foltia/newprogram.txt",  serialize($newprogs));
	//print_r($prevprogs);
	//print_r($newprogs);
	//print_r($diffprogs);

	if(count($diffprogs) > 0){
		$msg = "New Anime programs are available.\r\n".$foltiauri."animeprogram/index.php?mode=new\r\n\r\n";
		if ($mailcsv == 0){
			foreach($diffprogs as $progdata){
				$msg .= "TID: ".$progdata[0]."\r\n"."放送局: ".$progdata[1]."\r\n"."番組名: ".$progdata[2]."\r\n"."放送日時: ".foldate2print($progdata[5])."(".$progdata[8].")\r\n"."syobocal: http://cal.syoboi.jp/tid/".$progdata[0]."\r\n"."予約: ".$foltiauri."reservation/reserveprogram.php?tid=".$progdata[0]."\r\n\t\n";
			}
		}else{
			$msg .= "TID,放送局,番組名,話数,サブタイトル,放送日時,放送時間／分\r\n";
			foreach($diffprogs as $progdata){
				$msg .= $progdata[0].",".$progdata[1].",".$progdata[2].",".$progdata[3].",".$progdata[4].",".foldate2print($progdata[5])."(".$progdata[8]."),".$progdata[6]."\r\n";
			}
		}
		mb_send_mail($mailto, $mailsubj, $msg, "From: ".$mailfrom);
	}else{
		//print "no now programs\n";
	} 
?>

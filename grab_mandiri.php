<?php

$u = "username"; //username bank Mandiri
$p = "123456";       //password bank Mandiri
$norek = "1260001234567"; 
$startDate = "2013-12-01"; //awal mutasi
$endDate = "2013-12-31";   //akhir mutasi	

$ckfile = "C:\data\project\bankgrab\cookmandiri.txt"; //sesuaikan dengan path server --> "/home/<username>/public_html/folder/cookbca.txt";

$usingProxy = true;
$proxyServer = "proxy_ip_hostname:port"; //192.168.1.15:80
$proxyAccount = "usernameproxy:passwordproxy"; //account Proxy


//================================NO NEED TO CHANGE=============================
function getBlock($txt,$startTxt,$endTxt){
	$stPos = strpos($txt,$startTxt)-1;
	$edPos = (strpos($txt,$endTxt,$stPos) + strlen($endTxt)) - $stPos;
	return substr($txt,$stPos,$edPos);
}

function getBlockInside($txt,$startTxt,$endTxt){
	$stPos = strpos($txt,$startTxt)+strlen($startTxt);
	$edPos = strpos($txt,$endTxt,$stPos) - $stPos;
	return substr($txt,$stPos,$edPos);
}

$ipkoe = "xx.xx.xx.xx";  //dummy no need to change
$stDate = date("j",strtotime($startDate));
$stMonth = date("n",strtotime($startDate));
$stYear = date("Y",strtotime($startDate));
$edDate = date("j",strtotime($endDate));
$edMonth = date("n",strtotime($endDate));
$edYear = date("Y",strtotime($endDate));

$agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1664.3 Safari/537.36";
$ch = curl_init();
//proxy
if ($usingProxy){
	curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
	curl_setopt($ch, CURLOPT_PROXY, $proxyServer);
	curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAccount);
}
//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE); 
curl_setopt($ch, CURLOPT_USERAGENT, $agent);
curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile); //file yang nyimpen cookie untuk di pake selanjutnya.... 
curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);    // yang bakalan  nyimpen cookie... kalo session curl di close
curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

//get login page and get the initial cookies session
$url = "https://ib.bankmandiri.co.id/retail/Login.do?action=form&lang=in_ID";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, FALSE);
$result = curl_exec($ch);

//go login
$url = "https://ib.bankmandiri.co.id/retail/Login.do";
$postFIELDS = "action=result&userID=".$u."&password=".$p."&image.x=0&image.y=0";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFIELDS);
$result = curl_exec($ch);

//get id rekening
$url = "https://ib.bankmandiri.co.id/retail/TrxHistoryInq.do?action=form";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, FALSE);
$result = curl_exec($ch);
$posNorek = strpos($result,$norek);
$posAwalIdNorek = strpos($result,'<option value="',$posNorek-40)+15;
$idNorek = substr($result,$posAwalIdNorek,($posNorek-2)-$posAwalIdNorek);

//get mutasi
$url = "https://ib.bankmandiri.co.id/retail/TrxHistoryInq.do";
$postFIELDS = "action=result&fromAccountID=".$idNorek."&searchType=R&fromDay=".$stDate."&fromMonth=".$stMonth."&fromYear=".$stYear."&toDay=".$edDate."&toMonth=".$edMonth."&toYear=".$edYear."&sortType=Date&orderBy=ASC";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFIELDS);
$resultMutasi = curl_exec($ch);

//logout
$url = "https://ib.bankmandiri.co.id/retail/Logout.do?action=result";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, FALSE);
$result = curl_exec($ch);


//process data
$resultMutasi = file_get_contents("./mandiri_mutasi2.html"); //dummy

$blockMutasi = getBlockInside($resultMutasi,'<!-- Start of Item List -->','</table>').'</table>';
$blockMutasi = getBlock($blockMutasi,'<tr height="25">','</table>');
$arrF = array("#DDF2FA","</table>",'align="right" ',"    ","   ","\n\r","\r\n");
$blockMutasi = str_replace($arrF,"",$blockMutasi);
$blockMutasi = str_replace(' <tr height="25">','<tr height="25">',$blockMutasi);
$blockMutasi = str_replace('</tr>',";",$blockMutasi);
$blockMutasi = str_replace('<tr height="25">',"",$blockMutasi);
$blockMutasi = str_replace('</td><td height="25" class="tabledata" bgcolor="">',"|",$blockMutasi);
$blockMutasi = str_replace('<td height="25" class="tabledata" bgcolor="">',"",$blockMutasi);
$blockMutasi = str_replace('</td>',"",$blockMutasi);
$arrResult = explode(";",$blockMutasi);

foreach($arrResult as $key=>$value){
	$arrValue = explode("|",$value);
	if($arrValue[2]!='0,00'){
		$db_cr = 'DB';
		$remark_total = $arrValue[2]; 
	} else {
		$db_cr = 'CR';
		$remark_total = $arrValue[3];
	}
	if ($remark_total=='') continue;
	$arrRemark = explode("<br>",$arrValue[1]);
	$typeTrans = $arrRemark[0];
	unset($arrRemark[0]);
	$arrResult2[] = array(
						'date' 		=> $arrValue[0],
						'type'		=> $typeTrans,
						'remark'	=> implode(";",$arrRemark),
						'total'		=> $remark_total,
						'txtype' 	=> $db_cr
	);
}

print_r($arrResult2);



?>
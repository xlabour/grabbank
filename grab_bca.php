<?php
echo mktime()."\n";
$u = "usernamegue"; //username bank BCA
$p = "123456";       //pin angka 6 digit password bank BCA
$norek = "0951234567"; //rekening BCA
$startDate = '2014-11-30'; //date("Y-m-d",mktime());; //awal mutasi date("Y-m-d",mktime());
$endDate = '2014-11-31'; //date("Y-m-d",mktime());;   //akhir mutasi	



$ckfile = "D:\cookbca.txt"; //sesuaikan dengan path server --> "/home/<username>/public_html/folder/cookbca.txt";

$usingProxy = false;
$proxyServer = "proxy_ip_hostname:port"; //192.168.1.15:80
$proxyAccount = "usernameproxy:passwordproxy"; //account Proxy


//================================NO NEED TO CHANGE=============================
function getBlock($txt,$startTxt,$endTxt){
	$stPos = strpos($txt,$startTxt)-1;
	$edPos = (strpos($txt,$endTxt,$stPos) + strlen($endTxt)) - $stPos;
	return substr($txt,$stPos,$edPos);
}

$ipkoe = "xx.xx.xx.xx";  //dummy no need to change
$stDate = date("d",strtotime($startDate));
$stMonth = date("m",strtotime($startDate));
$stYear = date("Y",strtotime($startDate));
$edDate = date("d",strtotime($endDate));
$edMonth = date("m",strtotime($endDate));
$edYear = date("Y",strtotime($endDate));

$agent = "Opera/10.61 (J2ME/MIDP; Opera Mini/5.1.21219/19.999; en-US; rv:1.9.3a5) WebKit/534.5 Presto/2.6.30";
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

//get login page and get session for login
$url = "https://m.klikbca.com/login.jsp";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, FALSE);
$result = curl_exec($ch);

//go login
$url = "https://m.klikbca.com/authentication.do";
$postFIELDS = "value(user_id)=".$u."&value(pswd)=".$p."&value(Submit)=LOGIN&value(actions)=login&value(user_ip)=" . $ipkoe . "&user_ip=" . $ipkoe . "&value(mobile)=true&mobile=true";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFIELDS);
$result = curl_exec($ch);

//get id rekening
$url = "https://m.klikbca.com/accountstmt.do?value(actions)=acct_stmt";
$postFIELDS = "value(actions)=acct_stmt";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFIELDS);
$result = curl_exec($ch);
$posNorek = strpos($result,$norek);
$posAwalIdNorek = strpos($result,'<option value="',$posNorek-20)+15;
$idNorek = substr($result,$posAwalIdNorek,($posNorek-2)-$posAwalIdNorek);
//echo " - ".$idNorek." - ";

//get mutasi
$url = "https://m.klikbca.com/accountstmt.do?value(actions)=acctstmtview";
$postFIELDS = "r1=1&value(D1)=".$idNorek."&value(startDt)=".$stDate."&value(startMt)=".$stMonth."&value(startYr)=".$stYear."&value(endDt)=".$edDate."&value(endMt)=".$edMonth."&value(endYr)=".$edYear;
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFIELDS);
$resultMutasi = curl_exec($ch);

//logout
$url = "https://m.klikbca.com/authentication.do?value(actions)=logout";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, FALSE);
$result = curl_exec($ch);

echo $resultMutasi;

//process result here ==========================================================


$blockMutasi = getBlock($resultMutasi,'<table width="100%" class="blue">','</table>');
$blockMutasi = str_replace("><td valign='top'>","><td  valign='top'>",$blockMutasi);
$blockMutasi = str_replace("<td valign='top'>","</td><td valign='top'>",$blockMutasi);
$blockMutasi = getBlock($blockMutasi,'</tr>','<!--<tr>');
$arrRemove = array("</tr>","<tr bgcolor='#e0e0e0'>","<tr bgcolor='#f0f0f0'>","<!--<tr>");
$blockMutasi = str_replace($arrRemove,"",$blockMutasi);
$arrResult = explode("\n",$blockMutasi);

foreach($arrResult as $key=>$value){
	if (strpos($value,'td')<=0) continue;
	$arrA = array("</td><td valign='top'>","</td><td>","<td  valign='top'>","</td>");
	$arrB = array(";",";","","");
	$arrValue = explode(";",str_replace($arrA,$arrB,$value));
	$remark = explode('<br>',$arrValue[1]);
	$remark_type = $remark[0];
	$remark_total = $remark[count($remark)-1];
	unset($remark[0]);
	array_pop($remark);
	$remark_desc = implode("; ",$remark);
	
	$arrResult2[] = array(
						'date' 		=> $arrValue[0],
						'type'		=> $remark_type,
						'remark'	=> $remark_desc,
						'total'		=> $remark_total,
						'txtype' 	=> $arrValue[2]
	);
}

print_r($arrResult2);

?>
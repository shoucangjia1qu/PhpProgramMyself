<?php
require_once "conn.php";
header("Content-type: text/html; charset=gb2312");
echo "<pre>";
$nowyear = date(Y);
$nowmonth = date(m);
if ($nowmonth == 1) {
    $month = 12;
    $year = $nowyear - 1;
} else {
    $month = $nowmonth - 1;
    $year = $nowyear;
}
$singlemonth =array(1,3,5,7,8,10,12);
foreach ($singlemonth as $value) {
	if ($month==$value) {
		$day = 31;
		break;
	} else {
		$day = 30;
	}
}

echo "<center>";
echo "<h1>私行客户经营<h1>";
echo "<h2>{$year}年{$month}月{$day}日<h2>";
echo "</center>";

/*######################
“数据库中读取一列形成数组”的函数
 #####################*/
function sqlselect($x) {
	Global $year,$month,$day;
    $link = mysqli_connect("localhost","root","root1234") or die("连接数据库失败");
    mysqli_set_charset($link,"GBK");
    $db_bank1 = mysqli_select_db($link,"bank1");
	//查询X语句
	$sqlword_x = "select {$x} from businesscheck where business_date='{$year}-{$month}-{$day}'";
	$sqlselect_x = mysqli_query($link, $sqlword_x);
	//查询x行数
	$row_x = mysqli_num_rows($sqlselect_x);
	//将x赋值给新的数组
	for ($i=0; $i <$row_x ; $i++) { 
		$re_x = mysqli_fetch_assoc($sqlselect_x);
		$arr_x[$i] = "$re_x[$x]";
	}
	return $arr_x;
}


/*################
从MYSQL中读取已有参数    
################*/
$area = sqlselect('2ndbank');  //支行
//print_r($area);
$cus_begin = sqlselect('cus_begin');   //客户年初数
$aum_begin = sqlselect('aum_begin');   //AUM年初数
$cus_target = sqlselect('cus_target');   //客户目标数
$aum_target = sqlselect('aum_target');   //AUM目标数
$cus_now = sqlselect('cus_now');   //当前客户数
$aum_now = sqlselect('aum_now');   //当前AUM数
$allaum_begin = sqlselect('allaum_begin');   //全量AUM年初数
$allaum_now = sqlselect('allaum_now');   //全量AUM期末数

/*遍历数组
foreach ($targetaum as $value) {
	echo "'$value'"."<br>";
}
*/


/*###########
中位数得分函数
 */##########
function mark($x,$y) {
	$max = max($y);
	$min = min($y);
	//按顺序排列得到新数组
	asort($y);
	$y_median = array();
	$j = 1;
    foreach ($y as $value) {
	$y_median[$j]=$value;
	$j++;
    }
    //求中位数
    $count = count($y_median);
    if ($count%2 == 0) {
    	$median = 0.5*($y_median["$count"/2]+$y_median[("$count"/2)+1]) ;
    } else {
        $median = $y_median[("$count"+1)/2] ; 
    }
    //求得分
	if ($x>=$median) {
		$mark = 100+30*($x-$median)/($max-$median);
	}  else {
		$mark = 100+30*($x-$median)/($median-$min);
	}
	return $mark;
}

/*#################
设置完成率得分函数
 */################
function rate($r) {
    //计算月份
    $nowm = date(m);
    if ($nowm == 1) {
    $m = 12;
    } else {
    $m = $nowm - 1;
    };
    //区分旺季和全年的得分
    if ($m>3) {
    	if (12*$r/$m< 0.7) {
        	$mark = 70;
    	} elseif(12*$r/$m> 1.3) {
        	$mark = 130;
    	} else{
        	$mark = 100 * 12 *$r/$m;
    	}
    	return $mark;
    } else{
    	if (3*$r/$m< 0.7) {
        	$mark = 70;
    	} elseif(3*$r/$m> 1.3) {
        	$mark = 130;
    	} else{
        	$mark = 100 * 3 *$r/$m;
    	}
    	return $mark;
	}
}

    //计算完成率得分
    


/*####################
设置空数组
 */###################
$cus_add = array();
$cus_speed = array();
$cus_rate = array();
$aum_add = array();
$aum_speed = array();
$aum_rate = array();
$allaumratio_now = array();
$allaumratio_begin = array();
$allaumratio_add = array();


/*####################
其他项目进行计算
 */###################
for ($i=0; $i <24 ; $i++) { 
	$cus_add[$i] = $cus_now[$i] - $cus_begin[$i];//客户增量
	$cus_rate[$i] = $cus_add[$i]/$cus_target[$i];//客户完成率
    $aum_add[$i] = $aum_now[$i] - $aum_begin[$i];//AUM增量
    $aum_rate[$i] = $aum_add[$i]/$aum_target[$i];//AUM完成率
    
    if ($cus_now[$i]==0 and $cus_begin[$i]==0) {
        $cus_speed[$i] = 0;
        $aum_speed[$i] = 0;
        $allaumratio_now[$i] = 0;
        $allaumratio_add[$i] = 0;
    } elseif ($cus_now[$i]==0 and $cus_begin[$i]!=0) {
        $cus_speed[$i] = $cus_add[$i]/$cus_begin[$i];
        $aum_speed[$i] = $aum_add[$i]/$aum_begin[$i];
        $allaumratio_now[$i] = 0;
        $allaumratio_add[$i] = 0;
    } elseif ($cus_now[$i]!=0 and $cus_begin[$i]==0) {
        $cus_speed[$i] = 0;
        $aum_speed[$i] = 0;
        $allaumratio_now[$i] = $aum_now[$i]/$allaum_now[$i];
        $allaumratio_add[$i] = 0;
    } else {
        $cus_speed[$i] = $cus_add[$i]/$cus_begin[$i];//客户增速
        $aum_speed[$i] = $aum_add[$i]/$aum_begin[$i];//AUM增速
        $allaumratio_now[$i] = $aum_now[$i]/$allaum_now[$i];//当前人均AUM
        $allaumratio_begin[$i] = $aum_begin[$i]/$allaum_begin[$i];//201712人均AUM
        $allaumratio_add[$i] = $allaumratio_now[$i] - $allaumratio_begin[$i];//人均AUM新增
    }
}


/*##########
具体分项得分
 */#########

for ($i=0; $i <24 ; $i++) { 
    $cus_add_mark[$i] = mark($cus_add[$i],$cus_add);
    $cus_speed_mark[$i] = mark($cus_speed[$i],$cus_speed);
    $aum_add_mark[$i] = mark($aum_add[$i],$aum_add);
    $aum_speed_mark[$i] = mark($aum_speed[$i],$aum_speed);
    $allaumratio_now_mark[$i] = mark($allaumratio_now[$i],$allaumratio_now);
    $allaumratio_add_mark[$i] = mark($allaumratio_add[$i],$allaumratio_add);
    $cus_rate_mark[$i] = rate($cus_rate[$i]);
    $aum_rate_mark[$i] = rate($aum_rate[$i]);

    $cus_mark[$i] = 0.25*$cus_add_mark[$i] + 0.25*$cus_speed_mark[$i] + 0.5*$cus_rate_mark[$i];
    $aum_mark[$i] = 0.25*$aum_add_mark[$i] + 0.25*$aum_speed_mark[$i] + 0.5*$aum_rate_mark[$i];   
    $allaumratio_mark[$i] = 0.5*$allaumratio_now_mark[$i] + 0.5*$allaumratio_add_mark[$i]; 
    $business_mark[$i] = 0.3*$cus_mark[$i] + 0.3*$aum_mark[$i] + 0.4*$allaumratio_mark[$i];


/*##################################
得分更新MYSQL的business表
 */#################################
	$sqlword_update_business = "update businesscheck set cus_mark='$cus_mark[$i]', aum_mark='$aum_mark[$i]',  allaumratio_mark='$allaumratio_mark[$i]', business_mark='$business_mark[$i]' where 2ndbank='$area[$i]' and business_date='{$year}-{$month}-{$day}'";
	$sqlupdate_business = mysqli_query($link,$sqlword_update_business);
	/*
	if ($sqlupdate_business) {
			echo "添加成功";
		}  else{
			echo "加入失败";
		}
	*/
	$sqlword_insert_allmark = "insert into allmark(2ndbank, check_id, check_mark, check_type, check_date) values('$area[$i]', 'business_mark', '$business_mark[$i]', 'businesscheck', '{$year}-{$month}-{$day}')";
	$sqlinsert_allmark = mysqli_query($link,$sqlword_insert_allmark);

}



/*########
表格输出
 */#######


echo "<html>";
echo "<center>";
echo "<body>";
echo "<table border=1px cellspacing=0>";

echo "<tr>
<th width='120px'>支行</th>
<th width='80px'>{$month}月私行客户数</th>
<th width='80px'>{$month}月私行AUM</th>
<th width='80px' bgcolor='#99CCFF'>私行客户得分</th>
<th width='80px' bgcolor='#99CCFF'>私行AUM得分</th>
<th width='80px' bgcolor='#99CCFF'>私行资产占比得分</th>
<th width='80px' bgcolor='#66CCFF'>私行业务经营得分</th>
</tr>";

$sqlword_select_business= "select 2ndbank, cus_now, aum_now, cus_mark, aum_mark, allaumratio_mark, business_mark from businesscheck where business_date='{$year}-{$month}-{$day}'";
$sqlselect_business = mysqli_query($link,$sqlword_select_business);
while ($reseclect_business = mysqli_fetch_assoc($sqlselect_business)) {
	echo "<tr>";
    echo "<td>{$reseclect_business['2ndbank']}</td>";
    echo "<td align='center'>{$reseclect_business['cus_now']}</td>";
    echo "<td align='center'>{$reseclect_business['aum_now']}</td>";
    echo "<td align='center'>{$reseclect_business['cus_mark']}</td>";
    echo "<td align='center'>{$reseclect_business['aum_mark']}</td>";  
    echo "<td align='center'>{$reseclect_business['allaumratio_mark']}</td>";
    echo "<td align='center'>{$reseclect_business['business_mark']}</td>";
    echo "</tr>";
}

echo "</table>";
echo "<br>";
echo "<br>";
//echo "<h3><a href=relationship.php><<客户关系管理 </a>  ";
echo "</body>";
echo "</center>";
echo "</html>";



?>








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
echo "<h1>至尊客户经营<h1>";
echo "<h2>{$year}年{$month}月{$day}日<h2>";
echo "</center>";

/*######################
“数据库中读取column形成数组”的函数
 #####################*/
function sqlselect($x) {
	Global $year,$month,$day;
    $link = mysqli_connect("localhost","root","root1234") or die("连接数据库失败");
    mysqli_set_charset($link,"GBK");
    $db_bank1 = mysqli_select_db($link,"bank1");
	//查询X语句
	$sqlword_x = "select {$x} from vbusinesscheck where vbusiness_date='{$year}-{$month}-{$day}'";
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
$vcus_begin = sqlselect('vcus_begin');   //至尊客户年初数
$vaum_begin = sqlselect('vaum_begin');   //至尊AUM年初数
//$vcus_target = sqlselect('vcus_target');   //暂无客户目标
//$vaum_target = sqlselect('vaum_target');   //暂无AUM目标
$vcus_now = sqlselect('vcus_now');   //当前至尊客户数
$vaum_now = sqlselect('vaum_now');   //当前至尊AUM数
$vmeet = sqlselect('vmeet');   //当前的约见率

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



/*####################
设置空数组
 */###################
$vcus_add = array();
$vcus_speed = array();
$vcus_rate = array();
$vaum_add = array();
$vaum_speed = array();
$vaum_rate = array();



/*####################
其他项目进行计算
 */###################
for ($i=0; $i <24 ; $i++) { 
	$vcus_add[$i] = $vcus_now[$i] - $vcus_begin[$i];//客户增量
	//$vcus_rate[$i] = $vcus_add[$i]/$vcus_target[$i];//客户完成率
    $vaum_add[$i] = $vaum_now[$i] - $vaum_begin[$i];//AUM增量
    //$vaum_rate[$i] = $vaum_add[$i]/$vaum_target[$i];//AUM完成率
    
    if ($vcus_now[$i]==0 and $vcus_begin[$i]==0) {
        $vcus_speed[$i] = 0;
        $vaum_speed[$i] = 0;
    } elseif ($vcus_now[$i]==0 and $vcus_begin[$i]!=0) {
        $vcus_speed[$i] = $vcus_add[$i]/$vcus_begin[$i];
        $vaum_speed[$i] = $vaum_add[$i]/$vaum_begin[$i];
    } elseif ($vcus_now[$i]!=0 and $vcus_begin[$i]==0) {
        $vcus_speed[$i] = 0;
        $vaum_speed[$i] = 0;
    } else {
        $vcus_speed[$i] = $vcus_add[$i]/$vcus_begin[$i];//客户增速
        $vaum_speed[$i] = $vaum_add[$i]/$vaum_begin[$i];//AUM增速
    }
}


/*##########
具体分项得分
 */#########

for ($i=0; $i <24 ; $i++) { 
    $vcus_add_mark[$i] = mark($vcus_add[$i],$vcus_add);
    $vcus_speed_mark[$i] = mark($vcus_speed[$i],$vcus_speed);
    $vaum_add_mark[$i] = mark($vaum_add[$i],$vaum_add);
    $vaum_speed_mark[$i] = mark($vaum_speed[$i],$vaum_speed);
    $vmeet_mark[$i] = mark($vmeet[$i],$vmeet);

    $vcus_mark[$i] = 0.5*$vcus_add_mark[$i] + 0.5*$vcus_speed_mark[$i];
    $vaum_mark[$i] = 0.5*$vaum_add_mark[$i] + 0.5*$vaum_speed_mark[$i];   
    $vbusiness_mark[$i] = 0.4*$vcus_mark[$i] + 0.4*$vaum_mark[$i] + 0.2*$vmeet_mark[$i];


/*##################################
得分更新MYSQL的vbusiness表
 */#################################
	$sqlword_update_vbusiness = "update vbusinesscheck set vcus_mark='$vcus_mark[$i]', vaum_mark='$vaum_mark[$i]',  vmeet_mark='$vmeet_mark[$i]', vbusiness_mark='$vbusiness_mark[$i]' where 2ndbank='$area[$i]' and vbusiness_date='{$year}-{$month}-{$day}'";
	$sqlupdate_vbusiness = mysqli_query($link,$sqlword_update_vbusiness);
	/*
	if ($sqlupdate_vbusiness) {
			echo "添加成功";
		}  else{
			echo "加入失败";
		}
	*/
	$sqlword_insert_allmark = "insert into allmark(2ndbank, check_id, check_mark, check_type, check_date) values('$area[$i]', 'vbusiness_mark', '$vbusiness_mark[$i]', 'vbusinesscheck', '{$year}-{$month}-{$day}')";
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
<th width='80px'>{$month}月至尊客户数</th>
<th width='80px'>{$month}月至尊AUM</th>
<th width='80px' bgcolor='#99CCFF'>至尊客户得分</th>
<th width='80px' bgcolor='#99CCFF'>至尊AUM得分</th>
<th width='80px' bgcolor='#99CCFF'>至尊约见得分</th>
<th width='80px' bgcolor='#66CCFF'>至尊业务经营得分</th>
</tr>";

$sqlword_select_vbusiness= "select 2ndbank, vcus_now, vaum_now, vcus_mark, vaum_mark, vmeet_mark, vbusiness_mark from vbusinesscheck where vbusiness_date='{$year}-{$month}-{$day}'";
$sqlselect_vbusiness = mysqli_query($link,$sqlword_select_vbusiness);
while ($reseclect_vbusiness = mysqli_fetch_assoc($sqlselect_vbusiness)) {
	echo "<tr>";
    echo "<td>{$reseclect_vbusiness['2ndbank']}</td>";
    echo "<td align='center'>{$reseclect_vbusiness['vcus_now']}</td>";
    echo "<td align='center'>{$reseclect_vbusiness['vaum_now']}</td>";
    echo "<td align='center'>{$reseclect_vbusiness['vcus_mark']}</td>";
    echo "<td align='center'>{$reseclect_vbusiness['vaum_mark']}</td>";  
    echo "<td align='center'>{$reseclect_vbusiness['vmeet_mark']}</td>";
    echo "<td align='center'>{$reseclect_vbusiness['vbusiness_mark']}</td>";
    echo "</tr>";
}

echo "</table>";
echo "<br>";
echo "<br>";
//echo "<h3><a href=businesscheck.php><<客户关系管理 </a>   ";
echo "</body>";
echo "</center>";
echo "</html>";



?>
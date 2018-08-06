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
echo "<h1>投资管理能力<h1>";
echo "<h2>{$year}年{$month}月<h2>";
echo "</center>";


/*######################
“数据库中读取column形成数组”的函数
 #####################*/
function sqlselect_invest($x) {
    Global $year,$month,$day;
    $link = mysqli_connect("localhost","root","root1234") or die("连接数据库失败");
    mysqli_set_charset($link,"GBK");
    $db_bank1 = mysqli_select_db($link,"bank1");
    //查询X语句
    $sqlword_x = "select {$x} from investcheck where invest_date='{$year}-{$month}-{$day}'";
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


function sqlselect_business($x) {
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
$area = sqlselect_invest('2ndbank');  //支行
$invest_product_begin = sqlselect_invest('invest_product_begin');  //产品个数年初数
$invest_product_now = sqlselect_invest('invest_product_now');  //产品个数期末数
$cash_aum_begin = sqlselect_invest('cash_aum_begin');  //投资理财AUM年初数
$cash_aum_now = sqlselect_invest('cash_aum_now');  //投资理财AUM期末数
$ftrust_finishrate = sqlselect_invest('ftrust_finishrate');  //家族信托签约完成率
$ftrust_investrate = sqlselect_invest('ftrust_investrate');  //家族信托配置完成率
$largesafe_rate = sqlselect_invest('largesafe_rate');  //大额保单完成率
$ccbsafe_rate = sqlselect_invest('ccbsafe_rate');  //建信保险完成率
$keypoint_cus = sqlselect_invest('keypoint_cus');  //重点产品人数
$eva = sqlselect_invest('eva');  //eva总量

$cus_begin = sqlselect_business('cus_begin');   //私行客户年初数
$cus_now = sqlselect_business('cus_now');  //私行客户期末数
$aum_begin = sqlselect_business('aum_begin');   //私行AUM年初数
$aum_now = sqlselect_business('aum_now');  //私行AUM期末数


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
$invest_aum_begin = array();
$invest_aum_now = array();
$investproduct_rate_begin = array();
$investproduct_rate_now = array();
$investproduct_rate_add = array();
$investaum_rate_begin = array();
$investaum_rate_now = array();
$investaum_rate_add = array();
$cashaum_retain = array();
$keypoint_rate =array();
$avgeva = array();

/*####################
投资理财其他项目进行计算
 */###################

for ($i=0; $i <24 ; $i++) { 
    $invest_aum_begin[$i] = $aum_begin[$i] - $cash_aum_begin[$i];
    $invest_aum_now[$i] = $aum_now[$i] - $cash_aum_now[$i];
    
    if ($aum_begin[$i]==0 and $aum_now[$i]==0) {
        $investproduct_rate_begin[$i] = 0;
        $investproduct_rate_now[$i] = 0;
        $investproduct_rate_add[$i] = 0;
        $investaum_rate_begin[$i] = 0;
        $investaum_rate_now[$i] = 0;
        $investaum_rate_add[$i] = 0;
        $cashaum_retain[$i] = 0;
        $keypoint_rate[$i] = 0;   
        $avgeva[$i] = 0;
    } elseif ($aum_begin[$i]==0 and $aum_now[$i]!=0) {
        $investproduct_rate_begin[$i] = 0;
        $investproduct_rate_now[$i] = $invest_product_now[$i]/$cus_now[$i];
        $investproduct_rate_add[$i] = 0;
        $investaum_rate_begin[$i] = 0;
        $investaum_rate_now[$i] = $invest_aum_now[$i]/$aum_now[$i];
        $investaum_rate_add[$i] = 0;
        $cashaum_retain[$i] = 1;
        $keypoint_rate[$i] = $keypoint_cus[$i]/$cus_now[$i];   
        $avgeva[$i] = $eva[$i]/$cus_now[$i];
    } elseif ($aum_begin[$i]!=0 and $aum_now[$i]==0) {
        $investproduct_rate_now[$i] = 0;
        $investproduct_rate_begin[$i] = $invest_product_begin[$i]/$cus_now[$i];
        $investproduct_rate_add[$i] = 0;
        $investaum_rate_now[$i] = 0;
        $investaum_rate_begin[$i] = $invest_aum_begin[$i]/$aum_now[$i];
        $investaum_rate_add[$i] = 0;
        $cashaum_retain[$i] = 0;
        $keypoint_rate[$i] = 0;   
        $avgeva[$i] = 0;
    } else {
        $investproduct_rate_begin[$i] = $invest_product_begin[$i]/$cus_begin[$i];
        $investproduct_rate_now[$i] = $invest_product_now[$i]/$cus_now[$i];
        $investproduct_rate_add[$i] = $investproduct_rate_now[$i] - $investproduct_rate_begin[$i];
        $investaum_rate_begin[$i] = $invest_aum_begin[$i]/$aum_begin[$i];
        $investaum_rate_now[$i] = $invest_aum_now[$i]/$aum_now[$i];
        $investaum_rate_add[$i] = $investaum_rate_now[$i] - $investaum_rate_begin[$i];
        $cashaum_retain[$i] = $cash_aum_now[$i]/$cash_aum_begin[$i];
        $keypoint_rate[$i] = $keypoint_cus[$i]/$cus_now[$i];   
        $avgeva[$i] = $eva[$i]/$cus_now[$i];
    }
}


/*##########
投资理财具体分项得分
 */#########

for ($i=0; $i <24 ; $i++) { 
    $investproduct_now_mark[$i] = mark($investproduct_rate_now[$i],$investproduct_rate_now);
    $investproduct_add_mark[$i] = mark($investproduct_rate_add[$i],$investproduct_rate_add);
    $invest_product_mark[$i] = 0.5*$investproduct_now_mark[$i] + 0.5*$investproduct_add_mark[$i];

    $investaum_now_mark[$i] = mark($investaum_rate_now[$i],$investaum_rate_now);
    $investaum_add_mark[$i] = mark($investaum_rate_add[$i],$investaum_rate_add);
    $invest_aum_mark[$i] = 0.5*$investaum_now_mark[$i] + 0.5*$investaum_add_mark[$i];

    $cashaum_retain_mark[$i] = mark($cashaum_retain[$i],$cashaum_retain);

    $invest_mark1[$i] = 0.15*$invest_product_mark[$i] + 0.15*$invest_aum_mark[$i] + 0.2*$cashaum_retain_mark[$i];
}     



/*##########
重点产品具体分项得分
 */#########
for ($i=0; $i <24; $i++) { 
    $ftrust_finishrate_mark[$i] = rate($ftrust_finishrate[$i],$ftrust_finishrate);
    $ftrust_investrate_mark[$i] = rate($ftrust_investrate[$i],$ftrust_investrate);
    $ftrust_mark[$i] = 0.3*$ftrust_finishrate_mark[$i] + 0.7*$ftrust_investrate_mark[$i];
    
    $largesafe_rate_mark[$i] = rate($largesafe_rate[$i],$largesafe_rate);
    $ccbsafe_rate_mark[$i] = rate($ccbsafe_rate[$i],$ccbsafe_rate);
    
    $keypoint_rate_mark[$i] = mark($keypoint_rate[$i],$keypoint_rate);

    $invest_mark2[$i] = 0.1*$ftrust_mark[$i] + 0.1*$largesafe_rate_mark[$i] +0.1*$ccbsafe_rate_mark[$i] + 0.1*$keypoint_rate_mark[$i];
}


/*##########
eva具体分项得分
 */#########
for ($i=0; $i <24 ; $i++) { 
    $avgeva_mark[$i] = mark($avgeva[$i],$avgeva); 
    $invest_mark3[$i] = 0.1*$avgeva_mark[$i];
}



/*##########
投资管理总得分
 */#########
for ($i=0; $i <24 ; $i++) { 
    $invest_mark[$i] = $invest_mark1[$i] + $invest_mark2[$i] + $invest_mark3[$i];

/*##################################
得分更新MYSQL的invest表
 */#################################
    $sqlword_update_invest = "update investcheck set invest_mark1='$invest_mark1[$i]', invest_mark2='$invest_mark2[$i]',  invest_mark3='$invest_mark3[$i]', invest_mark='$invest_mark[$i]' where 2ndbank='$area[$i]' and invest_date='{$year}-{$month}-{$day}'";
    $sqlupdate_invest = mysqli_query($link,$sqlword_update_invest);
    /*
    if ($sqlupdate_vbusiness) {
            echo "添加成功";
        }  else{
            echo "加入失败";
        }
    */
    $sqlword_insert_allmark = "insert into allmark(2ndbank, check_id, check_mark, check_type, check_date) values('$area[$i]', 'invest_mark', '$invest_mark[$i]', 'investcheck', '{$year}-{$month}-{$day}')";
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
<th width='120px'>投资理财配置</th>
<th width='120px'>重点产品推进</th>
<th width='120px'>客户贡献</th>
<th width='120px'>总得分</th>
</tr>";

$sqlword_select_invest= "select 2ndbank, invest_mark1, invest_mark2, invest_mark3, invest_mark from investcheck where invest_date='{$year}-{$month}-{$day}'";
$sqlselect_invest = mysqli_query($link,$sqlword_select_invest);
while ($reseclect_invest = mysqli_fetch_assoc($sqlselect_invest)) {
    echo "<tr>";
    echo "<td>{$reseclect_invest['2ndbank']}</td>";
    echo "<td align='center'>{$reseclect_invest['invest_mark1']}</td>";
    echo "<td align='center'>{$reseclect_invest['invest_mark2']}</td>";
    echo "<td align='center'>{$reseclect_invest['invest_mark3']}</td>";
    echo "<td align='center'>{$reseclect_invest['invest_mark']}</td>";  
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
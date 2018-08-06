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
echo "<h1>客户维护能力<h1>";
echo "<h2>{$year}年{$month}月<h2>";
echo "</center>";


/*######################
“数据库中读取column形成数组”的函数
 #####################*/
function sqlselect_relationship($x) {
    Global $year,$month,$day;
    $link = mysqli_connect("localhost","root","root1234") or die("连接数据库失败");
    mysqli_set_charset($link,"GBK");
    $db_bank1 = mysqli_select_db($link,"bank1");
    //查询X语句
    $sqlword_x = "select {$x} from relationshipcheck where relationship_date='{$year}-{$month}-{$day}'";
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
$area = sqlselect_relationship('2ndbank');  //支行
$allpts_begin = sqlselect_relationship('allpts_begin');  //年初产品覆盖度
$allpts_now = sqlselect_relationship('allpts_now');  //期末产品覆盖度
$basepts_begin = sqlselect_relationship('basepts_begin');  //年初基础产品覆盖度
$basepts_now = sqlselect_relationship('basepts_now');  //期末基础产品覆盖度
$vcard = sqlselect_relationship('vcard');  //私行卡持卡率
$dabiao = sqlselect_relationship('dabiao');  //达标客户数
$baoyou = sqlselect_relationship('baoyou');  //保有客户数
$chance = sqlselect_relationship('chance');  //商机提升量
$chance_rate = sqlselect_relationship('chance_rate');  //商机提升率
$basefile = sqlselect_relationship('basefile');  //基础信息档案维护率
$interestfile = sqlselect_relationship('interestfile');  //偏好信息档案维护率
$housekeeper_rate = sqlselect_relationship('housekeeper_rate');  //金管家完成率
$housekeeper = sqlselect_relationship('housekeeper');  //金管家渗透率
$cus_begin = sqlselect_business('cus_begin');   //私行客户年初数
$cus_now = sqlselect_business('cus_now');  //私行客户期末数


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
$allpts_add = array();
$basepts_add = array();
$dabiao_rate = array();
$baoyou_rate = array();



/*##########
产品覆盖度和达标保有的具体分项
 */#########

for ($i=0; $i <24 ; $i++) { 
    if ($cus_now[$i]==0 and $cus_begin[$i]==0) {
        $allpts_add[$i] = 0;
        $basepts_add[$i] = 0;
        $dabiao_rate[$i] = 0;
        $baoyou_rate[$i] = 0;
    } elseif ($cus_now[$i]!=0 and $cus_begin[$i]==0) {
        $allpts_add[$i] = 0;
        $basepts_add[$i] = 0;
        $dabiao_rate[$i] = $dabiao[$i]/$cus_now[$i];
        $baoyou_rate[$i] = 1;
    } elseif ($cus_now[$i]==0 and $cus_begin[$i]!=0) {
        $allpts_add[$i] = 0 - $allpts_begin[$i];
        $basepts_add[$i] = 0 - $basepts_begin[$i];
        $dabiao_rate[$i] = 0;
        $baoyou_rate[$i] = $baoyou[$i]/$cus_begin[$i];
    } else {
        $allpts_add[$i] = $allpts_now[$i] - $allpts_begin[$i];
        $basepts_add[$i] = $basepts_now[$i] - $basepts_begin[$i];
        $dabiao_rate[$i] = $dabiao[$i]/$cus_now[$i];
        $baoyou_rate[$i] = $baoyou[$i]/$cus_begin[$i];
    }
}


/*##########
产品覆盖度具体分项得分
 */#########
for ($i=0; $i <24 ; $i++) { 
    $allpts_now_mark[$i] = mark($allpts_now[$i],$allpts_now);
    $allpts_add_mark[$i] = mark($allpts_add[$i],$allpts_add);
    $basepts_now_mark[$i] = mark($basepts_now[$i],$basepts_now);
    $basepts_add_mark[$i] = mark($basepts_add[$i],$basepts_add);
    $vcard_mark[$i] = mark($vcard[$i],$vcard);
    $allpts_mark[$i] = 0.5*$allpts_now_mark[$i] + 0.5*$allpts_add_mark[$i];
    $basepts_mark[$i] = 0.5*$basepts_now_mark[$i] + 0.5*$basepts_add_mark[$i];
    $relationship_mark1[$i] = 0.1*$allpts_mark[$i] + 0.1*$basepts_mark[$i] + 0.05*$vcard_mark[$i];
}
    

/*##########
达标保有具体分项得分
 */#########
for ($i=0; $i <24 ; $i++) { 
    $dabiao_rate_mark[$i] = mark($dabiao_rate[$i],$dabiao_rate);
    $baoyou_rate_mark[$i] = mark($baoyou_rate[$i],$baoyou_rate);
    $baoyou_dabiao_mark[$i] = 0.5*$baoyou_rate_mark[$i] + 0.5*$dabiao_rate_mark[$i];
    $relationship_mark2[$i] = 0.2*$baoyou_dabiao_mark[$i];
}


/*##########
商机提升具体分项得分
 */#########
for ($i=0; $i <24 ; $i++) { 
    $chance_mark[$i] = mark($chance[$i],$chance);
    $chance_rate_mark[$i] = mark($chance_rate[$i],$chance_rate);
    $chance_all_mark[$i] = 0.5*$chance_mark[$i] + 0.5*$chance_rate_mark[$i];
    $relationship_mark3[$i] = 0.2*$chance_all_mark[$i];
}


/*##########
金管家具体分项得分
 */#########
for ($i=0; $i <24 ; $i++) { 
    $housekeeper_mark[$i] = mark($housekeeper[$i],$housekeeper);  //金管家渗透率
    $housekeeper_rate_mark[$i] = rate($housekeeper_rate[$i],$housekeeper_rate);  //金管家完成率
    $housekeeper_all_mark[$i] = 0.5*$housekeeper_mark[$i] + 0.5*$housekeeper_rate_mark[$i];
    $relationship_mark4[$i] = 0.25*$housekeeper_all_mark[$i];
}


/*##########
档案维护具体分项得分
 */#########
for ($i=0; $i <24 ; $i++) { 
    $basefile_mark[$i] = mark($basefile[$i],$basefile);      
    $interestfile_mark[$i] = mark($interestfile[$i],$interestfile);
    $file_mark[$i] = 0.5*$basefile_mark[$i] + 0.5*$interestfile_mark[$i];
    $relationship_mark5[$i] = 0.1*$file_mark[$i];
}



/*##########
客户维护总得分
 */#########
for ($i=0; $i <24 ; $i++) { 
    $relationship_mark[$i] = $relationship_mark1[$i] + $relationship_mark2[$i] + $relationship_mark3[$i] + $relationship_mark4[$i] + $relationship_mark5[$i];

/*##################################
得分更新MYSQL的relationship表
 */#################################
    $sqlword_update_relationship = "update relationshipcheck set relationship_mark1='$relationship_mark1[$i]', relationship_mark2='$relationship_mark2[$i]',  relationship_mark3='$relationship_mark3[$i]', relationship_mark4='$relationship_mark4[$i]' ,relationship_mark5='$relationship_mark5[$i]', relationship_mark='$relationship_mark[$i]' where 2ndbank='$area[$i]' and relationship_date='{$year}-{$month}-{$day}'";
    $sqlupdate_relationship = mysqli_query($link,$sqlword_update_relationship);
    /*
    if ($sqlupdate_vbusiness) {
            echo "添加成功";
        }  else{
            echo "加入失败";
        }
    */
    $sqlword_insert_allmark = "insert into allmark(2ndbank, check_id, check_mark, check_type, check_date) values('$area[$i]', 'relationship_mark', '$relationship_mark[$i]', 'relationshipcheck', '{$year}-{$month}-{$day}')";
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
<th width='120px'>产品覆盖度</th>
<th width='120px'>客户保有</th>
<th width='120px'>商机提升</th>
<th width='120px'>金管家</th>
<th width='120px'>客户档案维护</th>
<th width='120px'>总得分</th>
</tr>";

$sqlword_select_relationship= "select 2ndbank, relationship_mark1, relationship_mark2, relationship_mark3, relationship_mark4, relationship_mark5, relationship_mark from relationshipcheck where relationship_date='{$year}-{$month}-{$day}'";
$sqlselect_relationship = mysqli_query($link,$sqlword_select_relationship);
while ($reseclect_relationship = mysqli_fetch_assoc($sqlselect_relationship)) {
    echo "<tr>";
    echo "<td>{$reseclect_relationship['2ndbank']}</td>";
    echo "<td align='center'>{$reseclect_relationship['relationship_mark1']}</td>";
    echo "<td align='center'>{$reseclect_relationship['relationship_mark2']}</td>";
    echo "<td align='center'>{$reseclect_relationship['relationship_mark3']}</td>";
    echo "<td align='center'>{$reseclect_relationship['relationship_mark4']}</td>";
    echo "<td align='center'>{$reseclect_relationship['relationship_mark5']}</td>";
    echo "<td align='center'>{$reseclect_relationship['relationship_mark']}</td>";  
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
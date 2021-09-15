<?php

if (! function_exists('get_client_ip')) {
    /**
     * 获取客户端 IP
     * @return string 客户端 IP
     */
    function get_client_ip() {

        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key)
        {
            if (array_key_exists($key, $_SERVER) === true)
            {
                foreach (explode(',', $_SERVER[$key]) as $ipAddress)
                {
                    $ipAddress = trim($ipAddress);

                    if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false)
                    {
                        return $ipAddress;
                    }
                    return $_SERVER['REMOTE_ADDR'];
                }
            }
        }
    }
}


if (! function_exists('coord_gaode_to_baidu')) {
    /**
     * 把高德坐标系转换成百度坐标系
     * @param  $gd_lon 高德经度
     * @param  $gd_lat 高德纬度
     * @return array('bd_lon'=>'百度经度','bd_lat'=>'百度纬度') 
     */    
    function coord_gaode_to_baidu($gd_lon,$gd_lat) {
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0; 
        $x = $gd_lon; 
        $y = $gd_lat; 
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $x_pi); 
        $theta = atan2($y, $x) + 0.000003 * cos($x * $x_pi); 
        $data['bd_lon'] = $z * cos($theta) + 0.0065; 
        $data['bd_lat'] = $z * sin($theta) + 0.006; 
        $data['bd_lon'] = round($data['bd_lon'],6);
        $data['bd_lat'] = round($data['bd_lat'],6);
        return $data; 
    } 
}

if (! function_exists('coord_baidu_to_gaode')) {
    /**
     * 把百度坐标系转换成高德坐标系
     * @param  $bd_lon 百度经度
     * @param  $bd_lat 百度纬度
     * @return array('gd_lon'=>'高德经度','gd_lat'=>'高德纬度') 
     */    
    function coord_baidu_to_gaode($bd_lon,$bd_lat) {
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0; 
        $x = $bd_lon - 0.0065; 
        $y = $bd_lat - 0.006; 
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi); 
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi); 
        $data['gd_lon'] = $z * cos($theta); 
        $data['gd_lat'] = $z * sin($theta); 
        $data['gd_lon'] = round($data['gd_lon'],6);
        $data['gd_lat'] = round($data['gd_lat'],6);
        return $data;  
    } 
}

if (! function_exists('coord_gps_to_gaode')) {
    /**
     * 把gps坐标系转换成高德坐标系
     * @param  $gps_lon gps经度
     * @param  $gps_lat gps纬度
     * @return array('gd_lon'=>'高德经度','gd_lat'=>'高德纬度') 
     */    
    function coord_gps_to_gaode($gps_lon, $gps_lat) {
        $x_pi = 3.14159265358979324*3000.0/180.0;
        $pi = 3.14159265358979324;
        $a = 6378245.0;  
        $ee = 0.00669342162296594323;  

        $x = $gps_lon - 105.0;
        $y = $gps_lat - 35.0;

        $dLat = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));  
        $dLat += (20.0 * sin(6.0 * $x * $pi) + 20.0 * sin(2.0 * $x * $pi)) * 2.0 / 3.0;  
        $dLat += (20.0 * sin($y * $pi) + 40.0 * sin($y / 3.0 * $pi)) * 2.0 / 3.0;  
        $dLat += (160.0 * sin($y / 12.0 * $pi) + 320 * sin($y * $pi / 30.0)) * 2.0 / 3.0;  

        $dLon = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
        $dLon += (20.0 * sin(6.0 * $x * $pi) + 20.0 * sin(2.0 * $x * $pi)) * 2.0 / 3.0;
        $dLon += (20.0 * sin($x * $pi) + 40.0 * sin($x / 3.0 * $pi)) * 2.0 / 3.0;
        $dLon += (150.0 * sin($x / 12.0 * $pi) + 300.0 * sin($x / 30.0 * $pi)) * 2.0 / 3.0;

        $radLat = $gps_lat / 180.0 * $pi;  
        $magic = sin($radLat);  
        $magic = 1 - $ee * $magic * $magic;  
        $sqrtMagic = sqrt($magic);  
        $dLat = ($dLat * 180.0) / (($a * (1 - $ee)) / ($magic * $sqrtMagic) * $pi);  
        $dLon = ($dLon * 180.0) / ($a / $sqrtMagic * cos($radLat) * $pi);  
    
        $data['gd_lon'] = $gps_lon + $dLon;
        $data['gd_lat'] = $gps_lat + $dLat;
        $data['gd_lon'] = round($data['gd_lon'],6);
        $data['gd_lat'] = round($data['gd_lat'],6);

        return $data;
    } 
}

if (! function_exists('get_dest_coord')) {
    define('YN_COORD_SYS_DEFAULT',0); //没有设置坐标系，默认为0, 百度坐标系
    define('YN_COORD_SYS_BAIDU',1); //百度坐标系为1
    define('YN_COORD_SYS_GAODE',2); //高德坐标系为2

    /**
     * 把输入的坐标系转换为目标坐标系 
     * @param  $longitude 经度
     * @param  $latitude 纬度
     * @param  $orig_coord_sys 数据本身的坐标系
     * @param  $dest_coord_sys 数据转换后的坐标系
     * @return array('longitude'=>'经度','latitude'=>'纬度') 
     */    
    function get_dest_coord($longitude,$latitude,$orig_coord_sys,$dest_coord_sys) {
        if ($orig_coord_sys == YN_COORD_SYS_DEFAULT) {
            $orig_coord_sys = YN_COORD_SYS_BAIDU;
        }

        if ($dest_coord_sys == YN_COORD_SYS_DEFAULT) {
            $dest_coord_sys = YN_COORD_SYS_BAIDU;
        }

        $data = array('longitude'=>0,'latitude'=>0);
        if ($dest_coord_sys == $orig_coord_sys) {
             $data['longitude'] = $longitude;
             $data['latitude'] = $latitude;
        } elseif ($dest_coord_sys == YN_COORD_SYS_BAIDU && $orig_coord_sys == YN_COORD_SYS_GAODE) {
             $t = coord_gaode_to_baidu($longitude,$latitude);
             $data['longitude'] = $t['bd_lon'];
             $data['latitude'] = $t['bd_lat'];
        } elseif($dest_coord_sys == YN_COORD_SYS_GAODE && $orig_coord_sys == YN_COORD_SYS_BAIDU) {
             $t = coord_baidu_to_gaode($longitude,$latitude);
             $data['longitude'] = $t['gd_lon'];
             $data['latitude'] = $t['gd_lat'];
        } 
          
        return $data;
    }
}


if (!function_exists('fs_guid')) {
    /**
     * uuid生成函数 36位。
     * @return string 
     */
    function fs_guid(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            .chr(125);// "}"
            return $uuid;
        }
    }
}

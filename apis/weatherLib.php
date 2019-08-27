<?php
global $conn;
function GetCityXY($city) {
    global $conn;
    $city                = sqlstr($city);
    list($cityx, $cityy) = $conn->queryRow("SELECT city_x, city_y FROM weather_city WHERE city_county = '{$city}' LIMIT 1", 1);
    if ($cityx <= 0) {
        return 0;
    }
    return array($cityx, $cityy);
}
function GetNormalWeather($city) {
    $cityxy = GetCityXY($city);
    if (!is_array($cityxy)) {
        return 0;
    }
    list($cityx, $cityy) = $cityxy;
    $json                = json_decode(file_get_contents("http://api.caiyunapp.com/v2/" . WeatherAPIKey . "/{$cityx},{$cityy}/realtime.json?unit=metric:v2"));
    if (strtolower($json->status) != "ok") {
        return 0;
    }
    $result = $json->result;
    if (strtolower($result->status) != "ok") {
        return 0;
    }
    $arr = array('temperature' => $result->temperature, 'skycon' => $result->skycon, 'windspeed' => $result->wind->speed, 'pm25' => $result->pm25, 'cloudrate' => $result->cloudrate, 'humidity' => $result->humidity);
    if (isset($result->precipitation->nearest) && strtolower($result->precipitation->nearest->status) == "ok") {
        if ($result->precipitation->nearest->intensity != 0) {
            $arr['nearest'] = array('distance' => $result->precipitation->nearest->distance, 'intensity' => $result->precipitation->nearest->intensity);
        }
    }
    return $arr;
}
?>

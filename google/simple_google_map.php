<?php
/**
 * 
 * Google Static Maps APIを簡単に呼び出す
 * 
 * @author tozawa
 *
 */
/* 
// サンプル１ 東京測地系で指定
$latlng = "35/39/17.5148,139/44/40.5020";
$gm = new GoogleStaticMap($latlng);
echo $gm->getImgTag();

// サンプル２　世界測地系で指定しつつマーカーを立てる
$latlng = "35.694003,139.753595";
$gm = new GoogleStaticMap($latlng, true);
echo $gm->getImgTag();

// サンプル３　住所で指定しつつ別の場所にマーカーを立て、ズームレベルも指定する
$address = "東京都千代田区";
$marker = array("lat" => 35.683851, "lng" => 139.753973);
$gm = new GoogleStaticMap($address, $marker);
$gm->zoom = 13;
echo $gm->getImgTag();

// サンプル４　地図、縮尺切り替え、移動ボタンをすべて表示させる
$lat = 35.694003;
$lng = 139.753595;
$hiddenObj = array("mode" => "test");
$gm = new GoogleStaticMap($lat, $lng, true);
echo $gm->full($hiddenObj);
*/

class ConvertGoogleMapGeo {
	
	private $a_w = 6378137;          // 赤道半径
	private $f_w;   // 扁平率
	private $e2_w; // 第1離心率
	// (Tokyo)
	private $a_t = 6377397.155;
	private $f_t;
	private $e2_t;
	// 並行移動量 [m]
	// e.g. x_t + dx_t = x_w etc.
	private $dx_t = -148;
	private $dy_t =  507;
	private $dz_t =  681;
	
	public $x = 0;
	public $y = 0;
	public $z = 0;
	
	public $lat = 0;
	public $lon = 0;
	
	public $lat_ = 0;
	public $lon_ = 0;
	
	function ConvertGoogleMapGeo() {
		$this->f_w = 1 / 298.257223;
		$this->e2_w = 2 * $this->f_w - $this->f_w * $this->f_w;
		$this->f_t = 1 / 299.152813;
		$this->e2_t = 2 * $this->f_t - $this->f_t * $this->f_t;
	}
	
	// Tokyo(Yahoo!Map) -> WGS 84(Google Maps)
	function tky2wgs($lat, $lon) {
		$ele = split('/', $lat);
		if(count($ele) != 3) return;
		$b = floatval($ele[0]) + floatval($ele[1])/60 + floatval($ele[2])/3600;
	 
		$ele = split('/', $lon);
		if(count($ele) != 3) return;
		$l = floatval($ele[0]) + floatval($ele[1])/60 + floatval($ele[2])/3600;
	 
		// 測地系変換
		$h = 0;
		$this->llh2xyz($b, $l, $h, $this->a_t, $this->e2_t);
		$this->x += $this->dx_t;
		$this->y += $this->dy_t;
		$this->z += $this->dz_t;
		$this->xyz2llh($this->a_w, $this->e2_w);
		
		$this->lat_ = $this->deg2dms($this->lat);
		$this->lon_ = $this->deg2dms($this->lon);
	}
	
	// WGS 84(Google Maps) -> Tokyo(Yahoo!Map)
	function wgs2tky($lat, $lon) {
		$ele = split('/', $this->deg2dms($lat));
		$b = floatval($ele[0]) + floatval($ele[1])/60 + floatval($ele[2])/3600;
	 
		$ele = split('/', $this->deg2dms($lon));
		$l = floatval($ele[0]) + floatval($ele[1])/60 + floatval($ele[2])/3600;
	 
		// 測地系変換
		$h = 0;
		$this->llh2xyz($b, $l, $h, $this->a_w, $this->e2_w);
		$this->x -= $this->dx_t;
		$this->y -= $this->dy_t;
		$this->z -= $this->dz_t;
		$this->xyz2llh($this->a_t, $this->e2_t);
		
		$this->lat_ = $this->deg2dms($this->lat);
		$this->lon_ = $this->deg2dms($this->lon);
	}
	
	function llh2xyz($b, $l, $h, $a, $e2) { // 楕円体座標 -> 直交座標
		$rd = M_PI / 180;
		$b *= $rd;
		$l *= $rd;
		$sb = sin($b);
		$cb = cos($b);
		$rn = $a / sqrt(1 - $e2 * $sb * $sb);
	 
		$this->x = ($rn+$h) * $cb * cos($l);
		$this->y = ($rn+$h) * $cb * sin($l);
		$this->z = ($rn * (1 - $e2) + $h) * $sb;
	}
	 
	function xyz2llh($a, $e2) { // 直交座標 -> 楕円体座標
		$rd = M_PI / 180;
		$bda = sqrt(1 - $e2);
	 
		$p = sqrt($this->x * $this->x + $this->y * $this->y);
		$t = atan2($this->z, $p * $bda);
		$st = sin($t);
		$ct = cos($t);
		$b = atan2($this->z + $e2 * $a / $bda * $st * $st * $st, $p - $e2 * $a * $ct * $ct * $ct);
		$l = atan2($this->y, $this->x);
	 
		$sb = sin($b);
		$rn = $a / sqrt(1 - $e2 * $sb * $sb);
		$h = $p / cos($b) - $rn;
		
		$this->lat = $b / $rd;
		$this->lon = $l / $rd;
		$this->h = $h;
	}
	 
	function deg2dms($d) { // 度 -> 度分秒
		$sf = round($d * 360000);
		$s = floor($sf / 100) % 60;
		$m = floor($sf / 6000) % 60;
		$d = floor($sf / 360000);
		$sf %= 100;
		if($m < 10) $m = "0" . $m;
		if($s < 10) $s = "0" . $s;
		if($sf < 10) $sf = "0" . $sf;
		return "" . $d . "/" . $m . "/" . $s . "." . $sf;
	}
}

class GoogleMapBase {
	protected $_lat;
	protected $_lng;
	protected $_address = "";
	protected $_center = "";
	
	public $autoGeocoding = false;
	
	function __construct($lat = null, $lng = null, $option = null) {
		if ($lat === null && $lng === null && $option === null) {
			return;
		} elseif ($lng === null && $option === null) {
			$this->parseLat($lat);
		} elseif ($option === null) {
			if (is_array($lng) || is_bool($lng)) {
				$this->parseLat($lat);
			} elseif (preg_match("/^(\d+\/\d+\/)?\d+(\.\d+)?$/", $lng)) {
				$this->setLat($lat);
				$this->setLng($lng);
			} else {
				$this->parseLat($lat);
			}
		} else {
			$this->setLat($lat);
			$this->setLng($lng);
		}
	}

	function __get($name) {
		$ret = null;
		switch ($name) {
			case "lat": $ret = $this->_lat; break;
			case "lng": case "lon": $ret = $this->_lng; break;
			case "center": $ret = $this->_center; break;
			case "address": $ret = $this->_address; break;
			default:
		}
		return $ret;
	}
	
	function __set($name, $value) {
		switch ($name) {
			case "lat": $this->setLat($value); break;
			case "lng":	case "lon": $this->setLng($value); break;
			case "center": $this->setCenter($value); break;
			case "address": $this->setAddress($value); break;
			default:
		}
	}
	
	protected function parseLat($arg) {
		$str = $arg;
		if (is_array($arg)) {
			if (isset($arg['lat']) && (isset($arg['lng']) || isset($arg['lon']))) {
				$str = $arg['lat'] . "," . ((isset($arg['lng'])) ? $arg['lng'] : $arg['lon']);
			} else if (isset($arg['address']) || isset($arg['center'])) {
				$str = (isset($arg['address'])) ? $arg['address'] : $arg['center'];
			} else {
				$str = $arg[0] . "," . $arg[1];
			}
		}
		$this->setCenter($str);
	}
	
	public function setCenter($str) {
		if (preg_match("/^\d+\.?\d*,\d+\.?\d*$/", $str)) {
			$this->_center = $str;
			list($this->_lat, $this->_lng) = split(",", $this->_center);
			$this->_address = "";
		} else if (preg_match("/^(\d+\/\d+\/\d+\.?\d*),(\d+\/\d+\/\d+\.?\d*)$/", $str, $m)) {
			$geo = new ConvertGoogleMapGeo();
			$geo->tky2wgs($m[1], $m[2]);
			$this->_lat = $geo->lat;
			$this->_lng = $geo->lon;
			$this->_center = $this->_lat . "," . $this->_lng;
			$this->_address = "";
		} else {
			$this->setAddress($str);
		}
	}
	
	public function setAddress($str, $geo = false) {
		if (!$this->autoGeocoding) $this->autoGeocoding = $geo;
		
		$this->_address = $str;
		$this->_center = urlencode($this->address);
		if ($this->autoGeocoding) {
			list($this->lat, $this->lng) = $this->geocode($this->_center);
		} else {
			$this->_lat = $this->_lng = 0;
		}
	}
	
	public function setLat($lat) {
		$this->_lat = (is_string($lat)) ? $lat : "$lat";
		if ($this->_lat && $this->_lng) {
			$this->_center = $this->_lat . "," . $this->_lng;
		}
	}
	
	public function setLng($lng) {
		$this->_lng = (is_string($lng)) ? $lng : "$lng";
		if ($this->_lat && $this->_lng) {
			$this->_center = $this->_lat . "," . $this->_lng;
		}
	}
	
	public function geocode($str) {
		$address = $str;
		$url = "http://maps.googleapis.com/maps/api/geocode/xml?sensor=false&address=" . $address;
		$xml = file_get_contents($url);
		$xmlObj = simplexml_load_string($xml);
		$arrXml = get_object_vars($xmlObj);
		$result_ = $arrXml['result'];
		if (isset($result_[0])) $result_ = $result_[0];
		$result = get_object_vars($result_);
		$geometry = get_object_vars($result['geometry']);
		$locate = get_object_vars($geometry['location']);
		return ($locate) ? array($locate["lat"], $locate["lng"]) : array(null, null);
	}
}

class GoogleStaticMap extends GoogleMapBase {
	const STATICAPI = "http://maps.google.com/maps/api/staticmap";
	public $format = "gif";
	public $hl = "ja";
	public $zoom = 15;
	public $size = "230x240";
	public $sensor = "false";
	
	public $url = "";
	public $tag = "";
	
	// マーカー管理
	private $markerList = array();
	private $markerIndex = array();
	
	// 地図操作
	private $_directButton = array();
	public $gsma = null;
	public $moveForm = "";
	public $zoomForm = "";
	public $zoomOptionTag = "";
	public $dx = 0;
	public $dy = 0;
	
	function __construct($lat = null, $lng = null, $option = null) {
		parent::__construct($lat, $lng, $option);
		if ($option === null) {
			if (is_bool($lng)) {
				if ($lng) $this->addMarker($this->_center);
			} elseif (is_array($lng) || !preg_match("/^(\d+\/\d+\/)?\d+(\.\d+)?$/", $lng)) {
				$this->addMarker($lng);
			}
		} elseif (is_array($option)) {
			$this->addMarker($this->_center, $option);
		} elseif (is_bool($option) && $option) {
			$this->addMarker($this->_center);
		}
	}
	
	function __set($name, $value) {
		if (preg_match("/^mv[1-9]$/", $name)) {
			$this->_directButton[$name] = $value;
			return;
		}
		switch ($name) {
			default: parent::__set($name, $value);
		}
	}
	
	function __call($name, $args) {
		if (preg_match("/^(get)?(map)?ima?ge?(tag)?$/i", $name)) {
			call_user_func_array(Array($this, "makeStaticMapImgTag"), $args);
			return $this->tag;
		}
	}
	
	private function makeStaticMapImgTag($attrObj = null) {
		if ($attrObj === null) $attrObj = array();
		if (!isset($attrObj["src"])) {
			$this->makeURL();
			$attrObj["src"] = $this->url;
		}
		$attrArr = array();
		foreach ($attrObj as $key => $value) $attrArr[] = $key . "='" . $value . "'";
		$this->tag = "<img " . join(" ", $attrArr) . " />";
	}
	
	private function makeURL() {
		if (is_bool($this->sensor)) $this->sensor = ($this->sensor) ? "true" : "false";
		$paramArr = array("center", "zoom", "size", "format", "hl", "sensor");
		$paramObj = array();
		foreach ($paramArr as $param) $paramObj[$param] = $this->$param;
		$arr = array();
		foreach ($paramObj as $key => $value) $arr[] = $key . "=" . $value;
		// markerパラメータは複数指定できる
		foreach ($this->markerList as $marker) $arr[] = "markers=" . $marker->get();
		$this->url = self::STATICAPI . "?" . join("&", $arr);
	}
	
	public function addMarker($lat = null, $lng = null, $option = null) {
		$marker = new GoogleStaticMapMarker($lat, $lng, $option);
		$this->markerList[] = $marker;
		if (!isset($this->markerIndex[$marker->center])) $this->markerIndex[$marker->center] = array();
		$this->markerIndex[$marker->center][] = $marker;
	}
	
	public function removeMarker($index = null, $all = false) {
		if ($index === null) {
			$this->markerList = array();
			$this->markerIndex = array();
		} elseif (isset($this->markerIndex[$index])) {
			foreach ($this->markerIndex[$index] as $marker) {
				foreach ($this->markerList as $k => $v) {
					if ($marker === $v) {
						array_splice($this->markerList, $k, 1);
						break;
					}
				}
				if (!$all) break; 
			}
		}
	}
	
	public function resetMarker() {
		$this->removeMarker();
	}
	
	public function move($hiddenObj = null, $paramObj = null) {
		$this->gsma = new GoogleStaticMapAction($this->center);
		$this->gsma->zoom = $this->zoom;
		foreach ($this->_directButton as $num => $tag) $this->gsma->$num = $tag;
		$this->center = $this->gsma->adjust();
		$this->zoom = $this->gsma->zoom;
		$this->dx = $this->gsma->dx;
		$this->dy = $this->gsma->dy;
		if ($hiddenObj === false) return;
		
		$this->zoomOptionTag = $this->gsma->getZoomOptionTag();
		$this->zoomForm = $this->gsma->getZoomForm($hiddenObj, $paramObj);
		return $this->moveForm = $this->gsma->getMoveForm($hiddenObj, $paramObj);
	}
	
	public function full($hiddenObj = null, $paramStr = null) {
		$this->move(false);
		$image_tag = $this->getImg();
		$select_tag = $this->gsma->getZoomSelectTag(true);
		$input_tag = $this->gsma->getMoveInput($hiddenObj, true);
		$param = ($paramStr === null) ? "" : "?" . $paramStr;
		return <<<EOH
<form action="{$_SERVER['PHP_SELF']}$param" method="POST">
$select_tag
<br />
$image_tag
<br />
$input_tag
</form>
EOH;
	}
}

class GoogleStaticMapMarker extends GoogleMapBase {
	private $SIZE = array("tiny", "mid", "small");
	public $icon;
	public $color;
	public $shadow;
	private $_size;
	private $_label;
	
	function __construct($lat = null, $lng = null, $option = null) {
		parent::__construct($lat, $lng, $option);
		if ($lat === null && $lng === null && $option === null) {
			return;
		} elseif ($lng === null && $option === null) {
			$this->setOption($lat);
		} elseif ($option === null) {
			if (is_array($lng)) $this->setOption($lng);
		} else {
			if (is_array($option)) $this->setOption($option);
		}
	}

	function __get($name) {
		$ret = null;
		switch ($name) {
			case "size": $ret = $this->_size; break;
			case "label": $this->_label; break;
			default: $ret = parent::__get($name);
		}
		return $ret;
	}
	
	function __set($name, $value) {
		switch ($name) {
			case "size": $this->setOption(array("size" => $value)); break;
			case "label": $this->setOption(array("label" => $value)); break;
			default: parent::__set($name, $value);
		}
	}
	
	function __toString() {
		return $this->get();
	}
	
	public function setOption($opt) {
		if (!is_array($opt)) return;
		if (isset($opt['center'])) $this->setCenter($opt['center']);
		if (isset($opt['address'])) $this->setAddress($opt['address']);
		if (isset($opt["size"]) && array_search($opt["size"], $this->SIZE)) $this->_size = $opt["size"];
		if (isset($opt["color"])) $this->color = $opt["color"];
		if (isset($opt["label"]) && preg_match("/^[A-Z0-9]$/", $opt)) $this->_label = $opt["label"];
	}
	
	public function get() {
		$paramArr = array("size", "color", "label", "center");
		// sizeがtiny,smallの場合はlabelは利用不可
		if ($this->_size == "tiny" || $this->_size == "small") array_splice($paramArr, 2, 1);
		$paramObj = array();
		foreach ($paramArr as $param) if ($this->$param) $paramObj[$param] = $this->$param;
		$arr = array();
		foreach ($paramObj as $key => $value) $arr[] = ($key == "center") ? $value : $key . ":" . $value;
		return join("|", $arr);
	}
}

class GoogleStaticMapAction extends GoogleMapBase {
	//zoomレベルに対応する縮尺値
	public $scale = array('','','','','','全国','','広域','','100万','','50万','','1/10万','','1/32,000','','1/8,000','','1/2,000','');
	public $direction = 0;
	public $zoom = 15;
	// 現在の横方向にずらした位置(ピクセル単位)
	public $dx = 0;
	// 現在の縦方向にずらした位置(ピクセル単位)
	public $dy = 0;
	// ずらす量(ピクセル単位)
	public $move = 70;
	
	public $origin;
	public $originZoom = 15;
	
	// カスタム方向指定ボタン用
	private $_mv = array();
	
	function __construct($center) {
		parent::__construct($center);
		$this->origin = $this->center;
		
		if (isset($_REQUEST['direction'])) {
			$this->direction = $_REQUEST['direction'];
		} else {
			for ($i=1;$i<=9;$i++) {
				if (isset($_REQUEST[$i])) {
					$this->direction = $i;
				}
			}
		}
		if (isset($_REQUEST['zoom'])) $this->zoom = $_REQUEST['zoom'];
		if (isset($_REQUEST['dx'])) $this->dx = $_REQUEST['dx'];
		if (isset($_REQUEST['dy'])) $this->dy = $_REQUEST['dy'];
		if (isset($_REQUEST['origin_zoom'])) $this->originZoom = $_REQUEST['origin_zoom'];
	}

	function __get($name) {
		$ret = null;
		if (preg_match("/^mv([1-9])$/", $name, $m)) {
			if (!isset($this->_mv[$name])) $this->makeMoveSubmitTag(intval($m[1]));
			$ret = $this->_mv[$name]; 
		} else {
			$ret = parent::__get($name);
		}
		return $ret;
	}
	
	function __set($name, $value) {
		if (preg_match("/^mv[1-9]$/", $name, $m)) {
			$this->_mv[$name] = $value;
			return;
		}
		switch ($name) {
			default: parent::__set($name, $value);
		}
	}

	public function getZoomForm($hiddenObj = null, $paramStr = null) {
		$param = ($paramStr === null) ? "" : "?" . $paramStr;
		$tag = array();
		if (!is_array($hiddenObj)) $hiddenObj = array();
		if (!isset($hiddenObj["dx"])) $hiddenObj["dx"] = $this->dx;
		if (!isset($hiddenObj["dy"])) $hiddenObj["dy"] = $this->dy;
		foreach ($hiddenObj as $name => $value) {
			$tag[] = $this->getInputTag($name, $value);
		}
		$input_tag = join("\n", $tag);
		$select_tag = $this->getZoomSelectTag();
		return <<<EOH
<form action="{$_SERVER['PHP_SELF']}$param" method="POST">
$input_tag
$select_tag
<input type="submit" name="zooming" value="縮尺切替" />
</form>
EOH;
	}
	
	public function getZoomSelectTag($submitFlag = false) {
		$option_tag = $this->getZoomOptionTag();
		$input_tag = ($submitFlag) ? '<input type="submit" name="zooming" value="縮尺切替" />' : '';
		return <<<EOH
<select name="zoom">
$option_tag
</select>
$input_tag
EOH;
	}
	
	public function getZoomOptionTag() {
		$option = array();
		for ($i=19;$i>=5;$i-=2) {
			$selected = ($i == $this->zoom) ? "selected" : "";
			$option[] = "<option value='$i' $selected>{$this->scale[$i]}</option>";
		}
		return join("\n", $option);
	}
	
	public function getMoveForm($hiddenObj = null, $paramStr = null) {
		$param = ($paramStr === null) ? "" : "?" . $paramStr;
		$input_tag = $this->getMoveInput($hiddenObj);
		return <<<EOH
<form action="{$_SERVER['PHP_SELF']}$param" method="POST">
$input_tag
</form>
EOH;
	}
	
	public function getMoveInput($hiddenObj = null, $zoomFlag = false) {
		$tag = array();
		if (!is_array($hiddenObj)) $hiddenObj = array();
		if (!$zoomFlag && !isset($hiddenObj["zoom"])) $hiddenObj["zoom"] = $this->zoom;
		if (!isset($hiddenObj["dx"])) $hiddenObj["dx"] = $this->dx;
		if (!isset($hiddenObj["dy"])) $hiddenObj["dy"] = $this->dy;
		foreach ($hiddenObj as $name => $value) {
			$tag[] = $this->getInputTag($name, $value);
		}
		for ($i=1;$i<=9;$i++) {
			$n = "mv" . $i;
			$tag[] = $this->$n;
			if ($i < 9 && $i % 3 == 0) $tag[] = "<br />";
		}
		return join("\n", $tag);
	}
	
	protected function makeMoveSubmitTag($directionNum = 0) {
		if (!is_int($directionNum) || $directionNum < 1 || $directionNum > 9) return "";
		return $this->_mv["mv" . $directionNum] = $this->getInputTag($directionNum, $directionNum, "submit", "google_static_map_move_submit");
	}
	
	public function getInputTag($name, $value, $type = "hidden", $class = "") {
		$accesskey = ($name === $value && $name > 0 && $name < 10) ? 'accesskey="' . $name . '"' : '';
		$class_attr = ($class === "") ? '' : 'class="' . $class . '"';
		return <<<EOT
<input type="$type" name="$name" value="$value" $accesskey $class_attr />
EOT;
	}
	
	public function adjust($direction = null) {
		if ($direction) $this->direction = $direction;
		
		if($this->direction == 1) {
			$this->dx -= $this->move;
			$this->dy -= $this->move;
		} elseif ($this->direction == 2) {
			$this->dy -= $this->move;
		} elseif ($this->direction == 3) {
			$this->dx += $this->move;
			$this->dy -= $this->move;
		} elseif ($this->direction == 4) {
			$this->dx -= $this->move;
		} elseif ($this->direction == 5) {
			$this->dx = 0;
			$this->dy = 0;
			$this->zoom = $this->originZoom;
		} elseif ($this->direction == 6) {
			$this->dx += $this->move;
		} elseif ($this->direction == 7) {
			$this->dx -= $this->move;
			$this->dy += $this->move;
		} elseif ($this->direction == 8) {
			$this->dy += $this->move;
		} elseif ($this->direction == 9) {
			$this->dx += $this->move;
			$this->dy += $this->move;
		}
		
		if ($this->dx || $this->dy) {
			$offset=268435456;
			$radius=$offset / pi();
			$this->center = join(",", array(
				(pi() / 2 - 2 * atan(exp((round(round($offset - $radius * log((1 + sin($this->_lat * pi() / 180))/(1 - sin($this->_lat * pi() / 180))) / 2)+($this->dy << (21-$this->zoom))) - $offset) / $radius))) * 180 / pi(),
				((round(round($offset + $radius * $this->_lng * pi()/180)+($this->dx << (21-$this->zoom))) - $offset) / $radius) * 180 / pi()
			));
		}
		return $this->center;
	}
}
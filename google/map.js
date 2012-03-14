function Map(domObj, lat, lon) {
	if (lat) this.lat = lat;
	if (lon) this.lon = lon;
	this.createMap(domObj);
}
Map.prototype = {
	lat : '35.681544',
	lon : '139.767036',
	map : null,
	mapLL : null,
	marker : {},
	info : null,
	createMap : function(domObj) {
		this.map = new google.maps.Map(domObj, {
			zoom: 17,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		});
		this.setCenter(this.lat, this.lon);
		// 地図をクリックしたらポップアップを消去
		var self = this;
		google.maps.event.addListener(this.map, 'click', function(){
			self.closeInfo();
		});
	},
	setCenter : function(lat, lon) {
		this.mapLL = new google.maps.LatLng(lat, lon);
		this.map.setCenter(this.mapLL);
	},
	createMarker : function(lat, lon, id, content) {
		// 位置が同じマーカーの場合に備えて、contentsは配列として持つようにする
		if (!this.marker[lat+lon]) {
			this.marker[lat+lon] = {
				point : new google.maps.Marker({
					map: this.map,
					position: (lat && lon) ? new google.maps.LatLng(lat, lon) : this.mapLL,
					icon: this.getImage()
				}),
				ids : [],
				contents : []
			};
			// マーカーに触れたらポップアップ
			var self = this;
			google.maps.event.addListener(this.marker[lat+lon]['point'], 'mouseover', function(){
				self.popupMall(lat+lon);
			});
		} else {
			// 複数の場合、それとわかるようにアイコンを変更する
			this.marker[lat+lon]['point'].setIcon(this.getImage('./img/multiple.jpg'));
		}
		
		if (id) this.marker[lat+lon]['ids'].push(id);
		if (content) this.marker[lat+lon]['contents'].push(content);
	},
	getImage : function(img) {
		return new google.maps.MarkerImage((img)?img:'./img/marker.jpg',
				new google.maps.Size(15, 15),
				new google.maps.Point(0,0),
				new google.maps.Point(15,15),
				new google.maps.Size(15,15)
		);
	},
	popupMall : function(key) {
		this.openInfo(key);
	}, 
	openInfo : function(key) {
		var content = this.marker[key]['contents'].join('<br />');
		if (this.info && this.info.getContent() == content) return false;
		this.closeInfo();
		this.info = new google.maps.InfoWindow();
		this.info.setContent(content);
		this.info.open(this.map, this.marker[key]['point']);
		return true;
	},
	closeInfo : function() {
		if (this.info) {
			this.info.close();
			this.info = null;
		}
	}
};


if (!window.dos) dos = {};

dos.mapping = {

    init: function (mapElem, data) {
        var bounds = data.focus ? dos.mapping.boundsFromWKTPolygon(data.focus) : null;
        var mapOptions = {
          zoom: 11,
          center: bounds ? bounds.getCenter() : { lat: -33.9, lng: 151.2 },
        };
        this.gmap = new google.maps.Map(mapElem, mapOptions);
        if (bounds) {
            this.gmap.fitBounds(bounds);
        }

        for (var i = 0; i < data.layers.length; i++) {
            var layer = dos.mapping.addImageLayer(this.gmap, data.layers[i]);
        };
    },


    boundsFromWKTPolygon: function (wkt) {
        var bounds = null;
        var matches = wkt.match(/^POLYGON\(\((.*)\)\)$/);
        if (matches) {
            bounds = new google.maps.LatLngBounds();
            var coords = matches[1].split(",");
            for (var i = 0; i < coords.length; ++i) {
                var lnglat = coords[i].split(" ");
                if (lnglat.length == 2) {
                    bounds.extend(new google.maps.LatLng(lnglat[1], lnglat[0]));
                }
            }
        }
        return bounds;
    },


    addImageLayer: function (map, layer) {
        var getMapTilerTileURL = function (coords, zoom) {
            console.log(coords.x, coords.y);
            var y = (1 << zoom) - coords.y - 1;
            return layer.url + zoom + "/" + coords.x + "/" + y + (layer.mime_type == "image/png" ? ".png" : ".gif");
        };
        var getVirtualEarthTileURL = function (coords, zoom) {
            return layer.url + dos.mapping.tileToQuadKey(coords.x,coords.y,zoom) + (layer.mime_type == "image/png" ? ".png" : ".gif");
        };
        var mapType = new google.maps.ImageMapType({
            name: layer.title,
            alt: layer.title,
            maxZoom: layer.max_zoom,
            minZoom: layer.min_zoom,
            getTileUrl: layer.type == "maptiler" ? getMapTilerTileURL : getVirtualEarthTileURL,
            tileSize: new google.maps.Size(256, 256),
            opacity: 1,
        });
        map.overlayMapTypes.push(mapType);
        return mapType;
    },


    toggleOpacity: function () {
        dos.mapping.gmap.overlayMapTypes.getArray().forEach(function (l) {
            l.setOpacity(l.opacity == 1 ? 0.5 : 1);
        });
    },


    tileToQuadKey: function (x, y, zoom) {
        var i, mask, cell, quad = "";
        for (i = zoom; i > 0; i--) {
            mask = 1 << (i - 1);
            cell = 0;
            if ((x & mask) != 0) cell++;
            if ((y & mask) != 0) cell += 2;
            quad += cell;
        }
        return quad;
    },


};



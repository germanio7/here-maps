<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.8.0/dist/leaflet.css"
        integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ=="
        crossorigin="" />
    <!-- Make sure you put this AFTER Leaflet's CSS -->
    <script src="https://unpkg.com/leaflet@1.8.0/dist/leaflet.js"
        integrity="sha512-BB3hKbKWOc9Ez/TAwyWxNXeoV9c1v6FIeYiBieIWkpLjauysF18NzgR1MBNBXf8/KABdlkX68nAhlwcDFLGPCQ=="
        crossorigin=""></script>

    <title>INEGI</title>
    <script src="{{ mix('js/app.js') }}" defer></script>
</head>

<body>

    <div style="display: flex;">
        <div style="display: flex-1;">
            <label for="start">Inicio</label>
            <input onkeyup="buscar(value, 'listStart'); loadingDisplay()" type="search" name="" id="start" value="">
            <ul style="cursor: pointer;" id="listStart"></ul>
        </div>
        <div style="display: flex-1;">
            <label for="end">Destino</label>
            <input onkeyup="buscar(value, 'listEnd'); loadingDisplay()" type="search" name="" id="end" value="">
            <ul id="listEnd" style="cursor: pointer;"></ul>
        </div>

    </div>

    <div style="display: flex;">
        <button disabled id="searching" onclick="searchRoute()">Calcular ruta</button>
        <div class="loader"></div>
    </div>

    <div id="map" style="width: 80rem; height: 30rem;"></div>
    <div id="panel"></div>

</body>

</html>

<script>
    let inputStart = document.getElementById('start');
    let inputEnd = document.getElementById('end');
    let panel = document.getElementById('panel');
    var map = L.map('map').setView([19.432, -99.134], 11);
    var geojson = null;
    let markers = [];
    let mark1 = null;
    let mark2 = null;
    let totalCasetas = 0;
    var origin = null;
    var destination = null;

    L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
        attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
        maxZoom: 18,
        id: 'mapbox/streets-v11',
        tileSize: 512,
        zoomOffset: -1,
        accessToken: 'pk.eyJ1IjoiZ2VybWFuaW83IiwiYSI6ImNsM2tyMHFuZDBlejMza3Bnd2Q1N2F2dzYifQ.x7Pao_41LrGYXvxkGYjY5w',
    }).addTo(map);

    function loadingDisplay() {
        document.getElementsByClassName("loader")[0].style.display = "block";
    }

    function loadingHidden() {
        document.getElementsByClassName("loader")[0].style.display = "none";
    }

    async function buscar(value, lista) {
        panel.innerHTML = '';
        totalCasetas = 0;
        // const results = await searchPlace(value);
        const results = await searchGeocode(value);
        // addOptions(results.data, lista)

        addOptionsForGeocode(results.items, lista)

        loadingHidden()
    }

    function searchGeocode(place) {
        return new Promise((resolve) => {
            axios
                .get('/api/geocode', {
                    params: {
                        q: place
                    }
                })
                .then((response) => {
                    resolve(response.data)
                })
                .catch((error) => {
                    alert(error.data)
                });
        });
    }

    function searchLine(x, y) {
        return new Promise((resolve) => {
            axios
                .post('/api/buscar-linea', {
                    x: x,
                    y: y,
                })
                .then((response) => {
                    resolve(response.data)
                })
                .catch((error) => {
                    alert(error.data)
                });
        });
    }

    function searchPlace(place) {
        return new Promise((resolve) => {
            axios
                .post('/api/buscar-destino', {
                    buscar: place,
                    type: 'json',
                    num: 10,
                })
                .then((response) => {
                    resolve(response.data)
                })
                .catch((error) => {
                    alert(error.data)
                });
        });
    }

    function addOptionsForGeocode(params, lista) {
        let list = document.getElementById(lista);
        list.innerHTML = "";

        params.forEach((element) => {
            addMarker(element, list, lista);
        })
    }

    function addMarker(element, list, lista) {
        var li = document.createElement("li");
        li.appendChild(document.createTextNode(element.title));
        li.onclick = async function() {
            panel.innerHTML = '';
            totalCasetas = 0;
            if (markers) {
                markers.map(function(item) {
                    map.removeLayer(item);
                });
            }
            if (geojson) {
                map.removeLayer(geojson)
            }
            if (lista == 'listStart') {
                if (mark1) {
                    map.removeLayer(mark1)
                }

                const line1 = await searchLine(element.position.lng, element.position.lat);
                origin = {
                    id_routing_net: line1.data.id_routing_net,
                    source: line1.data.source,
                    target: line1.data.target,
                }

                mark1 = L.marker([element.position.lat, element.position.lng])
                map.addLayer(mark1);
                map.panTo(mark1.getLatLng());
                inputStart.value = element.title
            }
            if (lista == 'listEnd') {
                if (mark2) {
                    map.removeLayer(mark2)
                }

                const line2 = await searchLine(element.position.lng, element.position.lat);
                destination = {
                    id_routing_net: line2.data.id_routing_net,
                    source: line2.data.source,
                    target: line2.data.target,
                }

                mark2 = L.marker([element.position.lat, element.position.lng])
                map.addLayer(mark2);
                map.panTo(mark2.getLatLng());
                inputEnd.value = element.title
            }
            document.getElementById(lista).innerHTML = "";
            if (origin || destination) {
                document.getElementById('searching').disabled = false;
            }
        };
        list.appendChild(li);
    }

    function addOptions(params, lista) {
        let list = document.getElementById(lista);
        list.innerHTML = "";

        params.forEach((element) => {
            var li = document.createElement("li");
            li.appendChild(document.createTextNode(element.nombre));
            li.onclick = function() {
                panel.innerHTML = '';
                totalCasetas = 0;
                if (markers) {
                    markers.map(function(item) {
                        map.removeLayer(item);
                    });
                }
                if (geojson) {
                    map.removeLayer(geojson)
                }
                if (lista == 'listStart') {
                    if (mark1) {
                        map.removeLayer(mark1)
                    }
                    let point = JSON.parse(element.geojson)
                    mark1 = L.marker([point.coordinates[1], point.coordinates[0]])
                    map.addLayer(mark1);
                    map.panTo(mark1.getLatLng());
                    inputStart.value = element.nombre
                }
                if (lista == 'listEnd') {
                    if (mark2) {
                        map.removeLayer(mark2)
                    }
                    let point = JSON.parse(element.geojson)
                    mark2 = L.marker([point.coordinates[1], point.coordinates[0]])
                    map.addLayer(mark2);
                    map.panTo(mark2.getLatLng());
                    inputEnd.value = element.nombre
                }
                document.getElementById(lista).innerHTML = "";
                if (origin || destination) {
                    document.getElementById('searching').disabled = false;
                }
            };
            list.appendChild(li);
        })
    }

    async function searchRoute() {
        loadingDisplay();
        panel.innerHTML = '';
        totalCasetas = 0;
        if (markers) {
            markers.map(function(item) {
                map.removeLayer(item);
            });
        }
        if (geojson) {
            map.removeLayer(geojson)
        }
        if (origin && destination) {
            const route = await calculateRoute();
            const details = await calculateRouteDetails();
            if (details.data && route.data) {

                details.data.map(function(item) {
                    this.addDetailsPanel(item);
                    totalCasetas += item.costo_caseta;

                    if (item.punto_caseta) {
                        let punto_caseta = JSON.parse(item.punto_caseta);
                        mark = L.marker([punto_caseta.coordinates[1], punto_caseta.coordinates[0]]);
                        markers.push(mark);
                        mark.addTo(map)
                            .bindPopup(item.direccion + ' Costo: $' + item.costo_caseta);
                    }
                })

                var coordinates = JSON.parse(route.data.geojson);
                geojson = L.geoJSON(coordinates, {
                    color: 'red'
                }).addTo(map);

                map.fitBounds(geojson.getBounds());
                this.showInfo(route);
            }
        }
        loadingHidden()
    }

    function showInfo(info) {
        var summaryDiv = document.createElement('div'),
            content = '<hr />' +
            '<b>Distancia total</b>: ' + info.data.long_km + ' Km <br />' +
            '<b>Tiempo de viaje</b>: ' + info.data.tiempo_min + ' minutos. <br />' +
            // '<b>Precio total</b>: $' + info.data.costo_caseta + ' <br />' +
            '<b>Precio total</b>: $' + totalCasetas + ' <br />' +
            '<hr />' +
            '<b>Info</b>: ' + info.meta.fuente + ' <br />';

        summaryDiv.style.fontSize = 'small';
        summaryDiv.style.marginLeft = '5%';
        summaryDiv.style.marginRight = '5%';
        summaryDiv.innerHTML = content;
        panel.appendChild(summaryDiv);
    }

    function addDetailsPanel(detail) {
        var summaryDiv = document.createElement('div'),
            content = '<b>Maniobra</b>: ' + detail.direccion + '<br />';

        summaryDiv.style.fontSize = 'small';
        summaryDiv.style.marginLeft = '5%';
        summaryDiv.style.marginRight = '5%';
        summaryDiv.innerHTML = content;
        panel.appendChild(summaryDiv);
    }

    function calculateRoute() {

        return new Promise((resolve) => {
            axios
                .post('/api/calcular-ruta', {
                    id_i: origin.id_routing_net,
                    source_i: origin.source,
                    target_i: origin.target,
                    id_f: destination.id_routing_net,
                    source_f: destination.source,
                    target_f: destination.target,
                    type: 'json',
                    v: 1,
                })
                .then((response) => {
                    resolve(response.data)
                })
                .catch((error) => {
                    alert(error.data)
                });
        });
    }

    function calculateRouteDetails() {

        return new Promise((resolve) => {
            axios
                .post('/api/detalles-calcular-ruta', {
                    id_i: origin.id_routing_net,
                    source_i: origin.source,
                    target_i: origin.target,
                    id_f: destination.id_routing_net,
                    source_f: destination.source,
                    target_f: destination.target,
                    type: 'json',
                    v: 1,
                })
                .then((response) => {
                    resolve(response.data)
                })
                .catch((error) => {
                    alert(error.data)
                });
        });
    }
</script>

<style>
    .loader {
        border: 8px solid #f3f3f3;
        /* Light grey */
        border-top: 8px solid #3498db;
        /* Blue */
        border-radius: 50%;
        width: 16px;
        height: 16px;
        animation: spin 2s linear infinite;
        display: none;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

</style>

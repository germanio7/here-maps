<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta http-equiv="Content-type" content="text/html;charset=UTF-8">
    <title>Easytrip</title>
    <link rel="stylesheet" type="text/css" href="https://js.api.here.com/v3/3.1/mapsjs-ui.css" />
    <link rel="stylesheet" type="text/css" href="demo.css" />
    <link rel="stylesheet" type="text/css" href="styles.css" />
    <link rel="stylesheet" type="text/css" href="../template.css" />
    <script type="text/javascript" src='../test-credentials.js'></script>
    <script type="text/javascript" src="https://js.api.here.com/v3/3.1/mapsjs-core.js"></script>
    <script type="text/javascript" src="https://js.api.here.com/v3/3.1/mapsjs-service.js"></script>
    <script type="text/javascript" src="https://js.api.here.com/v3/3.1/mapsjs-ui.js"></script>
    <script type="text/javascript" src="https://js.api.here.com/v3/3.1/mapsjs-mapevents.js"></script>
    <script src="{{ mix('js/app.js') }}" defer></script>
    <style type="text/css">
        .directions li span.arrow {
            display: inline-block;
            min-width: 28px;
            min-height: 28px;
            background-position: 0px;
            background-image: url("https://heremaps.github.io/maps-api-for-javascript-examples/map-with-route-from-a-to-b/img/arrows.png");
            position: relative;
            top: 8px;
        }

        .directions li span.depart {
            background-position: -28px;
        }

        .directions li span.rightturn {
            background-position: -224px;
        }

        .directions li span.leftturn {
            background-position: -252px;
        }

        .directions li span.arrive {
            background-position: -1288px;
        }
    </style>
    <script>
        window.ENV_VARIABLE = 'developer.here.com'
    </script>
    <script src='../iframeheight.js'></script>
</head>

<body id="markers-on-the-map">
    <div style="contain: content">
        <div style="display: flex;">
            <div style="display: flex-1;">
                <label for="start">Inicio</label>
                <input type="search" name="" id="start" value="">
                <ul style="cursor: pointer;" id="listStart"></ul>
            </div>
            <div style="display: flex-1;">
                <label for="end">Destino</label>
                <input type="search" name="" id="end" value="">
                <ul id="listEnd" style="cursor: pointer;"></ul>
            </div>
            <label for="mode">Camino Rápido</label>
            <input type="radio" name="mode" id="fast" checked>
            <label for="mode">Camino Corto</label>
            <input type="radio" name="mode" id="short">

        </div>

        <div style="display: flex;">
            <button disabled id="searching" onclick="searchRoute()">Buscar ruta</button>
            <div class="loader"></div>
        </div>

        @php
            $casetas = file_get_contents(base_path('easytrip.json'));
        @endphp

        <div id="map" style="width: 88rem; height: 40rem;"></div>
        <div id="panel"></div>

        <script type="text/javascript" src='demo.js'></script>
    </div>
</body>

</html>

<script>
    let inputStart = document.getElementById('start');
    let inputEnd = document.getElementById('end');
    let origin = '19.43195,-99.13315';
    let originINEGI = '';
    let coordinates = origin.split(",")
    let destination = '';
    let destinationINEGI = '';
    let totalCasetas = 0;

    function alternativeCalculate() {
        let mode = document.getElementById("fast").checked ? 'fast' : 'short';

        return new Promise((resolve) => {
            axios
                .get('/api/calculate-route', {
                    params: {
                        routingMode: mode,
                        transportMode: 'car',
                        origin: origin,
                        destination: destination,
                        return: 'polyline,turnByTurnActions,actions,instructions,travelSummary',
                        lang: 'es'
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

    function loadingDisplay() {
        document.getElementsByClassName("loader")[0].style.display = "block";
    }

    function loadingHidden() {
        document.getElementsByClassName("loader")[0].style.display = "none";
    }

    async function buscar(value, lista) {
        const results = await searchPlace(value);
        addOptions(results.items, lista)
        loadingHidden()
    }

    inputStart.addEventListener('keyup', function(e) {
        loadingDisplay()
        setTimeout(() => {
            buscar(e.target.value, 'listStart');
        }, 100);
    });

    inputEnd.addEventListener('keyup', function(e) {
        loadingDisplay()
        setTimeout(() => {
            buscar(e.target.value, 'listEnd');
        }, 100);
    })

    async function searchRoute() {
        loadingDisplay()
        if (origin && destination) {
            const result = await alternativeCalculate();
            let route = result.routes[0];
            if (route) {
                addRouteShapeToMap(route);
                addManueversToMap(route);
                addWaypointsToPanel(route);
                addManueversToPanel(route);
                addSummaryToPanel(route);
            } else {
                alert('Ruta no encontrada.');
            }
        }
        loadingHidden()
    }

    function addOptions(params, lista) {
        let list = document.getElementById(lista);
        list.innerHTML = "";

        params.forEach((element) => {
            var li = document.createElement("li");
            li.appendChild(document.createTextNode(element.title));
            li.onclick = async function() {
                if (lista == 'listStart') {
                    origin = element.position.lat + ',' + element.position.lng;
                    coordinates = origin;
                    inputStart.value = element.title
                    const line1 = await searchLine(element.position.lng, element.position.lat);
                    originINEGI = {
                        id_routing_net: line1.data.id_routing_net,
                        source: line1.data.source,
                        target: line1.data.target,
                    }
                }
                if (lista == 'listEnd') {
                    destination = element.position.lat + ',' + element.position.lng;
                    inputEnd.value = element.title
                    const line2 = await searchLine(element.position.lng, element.position.lat);
                    destinationINEGI = {
                        id_routing_net: line2.data.id_routing_net,
                        source: line2.data.source,
                        target: line2.data.target,
                    }
                }
                document.getElementById(lista).innerHTML = "";
                if (origin && destination) {
                    document.getElementById('searching').disabled = false;
                }
            };
            list.appendChild(li);
        })
    }

    function searchPlace(place) {
        return new Promise((resolve) => {
            axios
                .get('/api/search-place', {
                    params: {
                        q: place,
                        in: 'countryCode:MEX',
                        at: origin
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

    function calculateRouteDetails() {

        return new Promise((resolve) => {
            axios
                .post('/api/detalles-calcular-ruta', {
                    id_i: originINEGI.id_routing_net,
                    source_i: originINEGI.source,
                    target_i: originINEGI.target,
                    id_f: destinationINEGI.id_routing_net,
                    source_f: destinationINEGI.source,
                    target_f: destinationINEGI.target,
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

    async function addCustomMarker(items) {
        var nodeOL = document.createElement('ol');

        nodeOL.style.fontSize = 'small';
        nodeOL.style.marginLeft = '5%';
        nodeOL.style.marginRight = '5%';
        nodeOL.className = 'directions';

        items.forEach(element => {
            var svgCustom = `<svg xmlns="http://www.w3.org/2000/svg" style="color: red;" width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
              </svg>`,
                dotIconCustom = new H.map.Icon(svgCustom, {
                    anchor: {
                        x: 8,
                        y: 8
                    }
                }),
                group = new H.map.Group(),
                i,
                j;

            // Add custom marker
            var marker = new H.map.Marker({
                lat: element.latitud,
                lng: element.longitud,
            }, {
                icon: dotIconCustom
            });

            marker.instruction = element.description + '. Costo: $' + element.costo;
            group.addObject(marker);

            group.addEventListener('tap', function(evt) {
                map.setCenter(evt.target.getGeometry());
                openBubble(evt.target.getGeometry(), evt.target.instruction);
            }, false);

            map.addObject(group);

            var li = document.createElement('li'),
                spanArrow = document.createElement('span'),
                spanInstruction = document.createElement('span');

            spanArrow.className = 'arrow up';
            spanInstruction.innerHTML = element.description + '. Costo: $' + element.costo;
            li.appendChild(spanArrow);
            li.appendChild(spanInstruction);

            nodeOL.appendChild(li);
        });

        routeInstructionsContainer.appendChild(nodeOL);
    }

    /**
     * Boilerplate map initialization code starts below:
     */

    // set up containers for the map + panel
    var mapContainer = document.getElementById('map'),
        routeInstructionsContainer = document.getElementById('panel');

    // Step 1: initialize communication with the platform
    // In your own code, replace variable window.apikey with your own apikey
    var platform = new H.service.Platform({
        apikey: '{{ config('services.here.api_key') }}'
    });

    var defaultLayers = platform.createDefaultLayers({
        lg: 'es'
    });

    // Step 2: initialize a map - this map is centered over Berlin
    var map = new H.Map(mapContainer,
        defaultLayers.vector.normal.map, {
            center: {
                lat: coordinates[0],
                lng: coordinates[1]
            },
            zoom: 10,
            pixelRatio: window.devicePixelRatio || 1
        });

    // add a resize listener to make sure that the map occupies the whole container
    window.addEventListener('resize', () => map.getViewPort().resize());

    // Step 3: make the map interactive
    // MapEvents enables the event system
    // Behavior implements default interactions for pan/zoom (also on mobile touch environments)
    var behavior = new H.mapevents.Behavior(new H.mapevents.MapEvents(map));

    // Create the default UI components
    var ui = H.ui.UI.createDefault(map, defaultLayers, 'es-ES');

    // Hold a reference to any infobubble opened
    var bubble;

    /**
     * Opens/Closes a infobubble
     * @param {H.geo.Point} position The location on the map.
     * @param {String} text          The contents of the infobubble.
     */
    function openBubble(position, text) {
        if (!bubble) {
            bubble = new H.ui.InfoBubble(
                position,
                // The FO property holds the province name.
                {
                    content: text
                });
            ui.addBubble(bubble);
        } else {
            bubble.setPosition(position);
            bubble.setContent(text);
            bubble.open();
        }
    }

    /**
     * Creates a H.map.Polyline from the shape of the route and adds it to the map.
     * @param {Object} route A route as received from the H.service.RoutingService
     */
    function addRouteShapeToMap(route) {
        if (route.sections) {

            map.removeObjects(map.getObjects());

            route.sections.forEach((section) => {
                // decode LineString from the flexible polyline
                let linestring = H.geo.LineString.fromFlexiblePolyline(section.polyline);

                // Create a polyline to display the route:
                let polyline = new H.map.Polyline(linestring, {
                    style: {
                        lineWidth: 4,
                        strokeColor: 'rgba(0, 128, 255, 0.7)'
                    }
                });

                // Add the polyline to the map
                map.addObject(polyline);
                // And zoom to its bounding rectangle
                map.getViewModel().setLookAtData({
                    bounds: polyline.getBoundingBox()
                });
            });
        }
    }

    /**
     * Creates a series of H.map.Marker points from the route and adds them to the map.
     * @param {Object} route A route as received from the H.service.RoutingService
     */
    async function addManueversToMap(route) {
        const details = await calculateRouteDetails();

        route.sections.forEach((section) => {
            let poly = H.geo.LineString.fromFlexiblePolyline(section.polyline).getLatLngAltArray();
            let intersection = [];

            // Match coordenadas casetas con coordenadas ruta
            for (let i = 0; i < poly.length; i = i + 3) {
                details.data.map(function(item) {
                    if (item.punto_caseta) {
                        let punto_caseta = JSON.parse(item.punto_caseta);

                        let point1 = new H.geo.Point(poly[i], poly[i + 1]),
                            point2 = new H.geo.Point(punto_caseta.coordinates[1], punto_caseta
                                .coordinates[0]);


                        distance = point1.distance(point2);

                        if (distance <= 7) {
                            let auxCaseta = Object.assign({
                                latitud: punto_caseta.coordinates[1],
                                longitud: punto_caseta.coordinates[0],
                                description: item.direccion,
                                costo: item.costo_caseta
                            });
                            intersection.push(auxCaseta)
                        }
                    }

                })
            }

            let uniqueArray = intersection.filter(function(item, pos, self) {
                return self.indexOf(item) == pos;
            })

            if (uniqueArray.length > 0) {

                let totalCasetas = uniqueArray.reduce((accumulator, object) => {
                    return accumulator + object.costo;
                }, 0);

                alert('Costo total: $ ' + totalCasetas)

                var summaryDiv = document.createElement('div'),
                    content = '<b>Precio total</b>: $' + totalCasetas + ' <br />'; // Total casetas

                summaryDiv.style.fontSize = 'small';
                summaryDiv.style.marginLeft = '5%';
                summaryDiv.style.marginRight = '5%';
                summaryDiv.innerHTML = content;
                routeInstructionsContainer.appendChild(summaryDiv);

                addCustomMarker(uniqueArray);
            }

            var svgMarkup = '<svg width="18" height="18" ' +
                'xmlns="http://www.w3.org/2000/svg">' +
                '<circle cx="8" cy="8" r="8" ' +
                'fill="#1b468d" stroke="white" stroke-width="1" />' +
                '</svg>',
                dotIcon = new H.map.Icon(svgMarkup, {
                    anchor: {
                        x: 8,
                        y: 8
                    }
                }),
                group = new H.map.Group(),
                i,
                j;

            route.sections.forEach((section) => {
                let poly = H.geo.LineString.fromFlexiblePolyline(section.polyline)
                    .getLatLngAltArray();

                let actions = section.actions;
                // Add a marker for each maneuver
                for (i = 0; i < actions.length; i += 1) {
                    let action = actions[i];
                    var marker = new H.map.Marker({
                        lat: poly[action.offset * 3],
                        lng: poly[action.offset * 3 + 1]
                    }, {
                        icon: dotIcon
                    });
                    marker.instruction = action.instruction;
                    group.addObject(marker);
                }

                group.addEventListener('tap', function(evt) {
                    map.setCenter(evt.target.getGeometry());
                    openBubble(evt.target.getGeometry(), evt.target.instruction);
                }, false);

                // Add the maneuvers group to the map
                map.addObject(group);
            });

        });
    }

    /**
     * Creates a series of H.map.Marker points from the route and adds them to the map.
     * @param {Object} route A route as received from the H.service.RoutingService
     */
    function addWaypointsToPanel(route) {
        var nodeH3 = document.createElement('h3'),
            labels = [];

        route.sections.forEach((section) => {
            labels.push(
                section.turnByTurnActions[0].nextRoad.name[0].value)
            labels.push(
                section.turnByTurnActions[section.turnByTurnActions.length - 1].currentRoad.name[0].value)
        });

        nodeH3.textContent = labels.join(' - ');
        routeInstructionsContainer.innerHTML = '';
        routeInstructionsContainer.appendChild(nodeH3);
    }

    /**
     * Creates a series of H.map.Marker points from the route and adds them to the map.
     * @param {Object} route A route as received from the H.service.RoutingService
     */
    function addSummaryToPanel(route) {
        let duration = 0,
            distance = 0;

        route.sections.forEach((section) => {
            distance += section.travelSummary.length;
            duration += section.travelSummary.duration;
        });

        var summaryDiv = document.createElement('div'),
            content = '<b>Distancia total</b>: ' + toKM(distance) + ' <br />' +
            '<b>Tiempo de viaje</b>: ' + toMMSS(duration) + ' (en el tráfico actual) <br />';

        summaryDiv.style.fontSize = 'small';
        summaryDiv.style.marginLeft = '5%';
        summaryDiv.style.marginRight = '5%';
        summaryDiv.innerHTML = content;
        routeInstructionsContainer.appendChild(summaryDiv);
    }

    /**
     * Creates a series of H.map.Marker points from the route and adds them to the map.
     * @param {Object} route A route as received from the H.service.RoutingService
     */
    function addManueversToPanel(route) {
        var nodeOL = document.createElement('ol');

        nodeOL.style.fontSize = 'small';
        nodeOL.style.marginLeft = '5%';
        nodeOL.style.marginRight = '5%';
        nodeOL.className = 'directions';

        route.sections.forEach((section) => {
            section.actions.forEach((action, idx) => {
                var li = document.createElement('li'),
                    spanArrow = document.createElement('span'),
                    spanInstruction = document.createElement('span');

                spanArrow.className = 'arrow ' + (action.direction || '') + action.action;
                spanInstruction.innerHTML = section.actions[idx].instruction;
                li.appendChild(spanArrow);
                li.appendChild(spanInstruction);

                nodeOL.appendChild(li);
            });
        });

        routeInstructionsContainer.appendChild(nodeOL);
    }

    function toMMSS(duration) {
        return Math.floor(duration / 3600) + ' horas ' + Math.floor(duration / 360) + ' minutos.';
    }

    function toKM(distance) {
        return Number(distance / 1000).toFixed(2) + ' Km ';
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

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

    <div style="display: flex;">
        <label for="start">Inicio</label>
        <input type="search" name="" id="start" value="Bosque de las Lomas, Ciudad de México">
        <label for="end">Destino</label>
        <input type="search" name="" id="end" value="Tepotzotlán, México">
        <label for="mode">Camino Rápido</label>
        <input type="radio" name="mode" id="fast" checked>
        <label for="mode">Camino Corto</label>
        <input type="radio" name="mode" id="short">
        <button onclick="searchAddress()">ir</button>
        <div class="loader"></div>
    </div>

    @php
    $casetas = file_get_contents(base_path('easytrip.json'));
    @endphp

    <div id="map" style="width: 88rem; height: 40rem;"></div>
    <div id="panel"></div>

    <script type="text/javascript" src='demo.js'></script>
</body>

</html>

<script>
    let inputStart = document.getElementById('start');
    let inputEnd = document.getElementById('end');
    let loading = document.getElementsByClassName("loader")[0].style.display
    let origen = '';
    let destino = '';
    var total = 0;
    const casetas = JSON.parse(@json($casetas))

function alternativeCalculate() {
    let mode = document.getElementById("fast").checked ? 'fast' : 'short';

    return new Promise((resolve) => {
        axios
            .get('/api/calculate-route', {params: {
            routingMode: mode,
            transportMode: 'car',
            origin: origen,
            destination: destino,
            return: 'polyline,turnByTurnActions,actions,instructions,travelSummary',
            lang: 'es'
        }})
            .then((response) => {
                resolve(response.data)
            })
            .catch((error) => {
                alert(error.data)
            });
    });
}

async function searchAddress() {
    document.getElementsByClassName("loader")[0].style.display = "block";

    if (inputStart.value) {
        origen = null;
        const result1 = await searchPlace(inputStart.value);
        origen = result1.items[0].position.lat+','+result1.items[0].position.lng;
    }
    if (inputEnd.value) {
        destino = null;
        const result2 = await searchPlace(inputEnd.value);
        destino = result2.items[0].position.lat+','+result2.items[0].position.lng;
    }

    if (origen && destino) {
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
    document.getElementsByClassName("loader")[0].style.display = "none";
}

function searchPlace(place){
    return new Promise((resolve) => {
        axios
            .get('/api/search-place', { params: {q:  place} })
            .then((response) => {
                resolve(response.data)
            })
            .catch((error) => {
                alert(error.data)
            });
    });
}

function addCustomMarker(items){
    items.forEach((element) => {
        var svgCustom = `<svg xmlns="http://www.w3.org/2000/svg" style="color: red;" width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
          </svg>`,
              dotIconCustom = new H.map.Icon(svgCustom, {anchor: {x:8, y:8}}),
              group = new H.map.Group(),
              i,
              j;

              // Add custom marker
              var marker = new H.map.Marker({
                  lat: element.Latitud,
                  lng: element.Longitud
                },
                  {icon: dotIconCustom});
              marker.instruction = element.description;
              group.addObject(marker);

              group.addEventListener('tap', function (evt) {
            map.setCenter(evt.target.getGeometry());
            openBubble(evt.target.getGeometry(), evt.target.instruction);
          }, false);

          map.addObject(group);

        //   total += element.price;
      });
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
  center: {lat: 19.43195, lng: -99.13315},
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
      {content: text});
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

    map.removeObjects(map.getObjects ());

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
function addManueversToMap(route) {  
  total = 0;
  route.sections.forEach((section) => {
    let poly = H.geo.LineString.fromFlexiblePolyline(section.polyline).getLatLngAltArray();
    let intersection = [];

    // Match coordenadas casetas con coordenadas ruta
    for (let i = 0; i < poly.length; i = i+3) {
      casetas.map(function(caseta) {
        let point1 = new H.geo.Point(poly[i], poly[i + 1]),
        point2 = new H.geo.Point(caseta.Latitud, caseta.Longitud);

        distance = point1.distance(point2);
        if (distance <= 25) {
          intersection.push(caseta)
        }
      })
      
    }

    uniqueArray = intersection.filter(function(item, pos, self) {
        return self.indexOf(item) == pos;
    })

    if (uniqueArray.length > 0) {
        addCustomMarker(uniqueArray);
    }

    var svgMarkup = '<svg width="18" height="18" ' +
        'xmlns="http://www.w3.org/2000/svg">' +
        '<circle cx="8" cy="8" r="8" ' +
          'fill="#1b468d" stroke="white" stroke-width="1" />' +
        '</svg>',
    dotIcon = new H.map.Icon(svgMarkup, {anchor: {x:8, y:8}}),
    group = new H.map.Group(),
    i,
    j;

    route.sections.forEach((section) => {
        let poly = H.geo.LineString.fromFlexiblePolyline(section.polyline).getLatLngAltArray();

        let actions = section.actions;
        // Add a marker for each maneuver
        for (i = 0; i < actions.length; i += 1) {
          let action = actions[i];
          var marker = new H.map.Marker({
            lat: poly[action.offset * 3],
            lng: poly[action.offset * 3 + 1]},
            {icon: dotIcon});
          marker.instruction = action.instruction;
          group.addObject(marker);
        }

        group.addEventListener('tap', function (evt) {
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
    content = '<b>Distancia total</b>: ' + distance + 'm. <br />' +
      '<b>Tiempo de viaje</b>: ' + toMMSS(duration) + ' (en el tráfico actual) <br />' +
      '<b>Precio total</b>: $' + total + ' <br />'; // Total casetas

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
  nodeOL.style.marginLeft ='5%';
  nodeOL.style.marginRight ='5%';
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
  return Math.floor(duration / 60) + ' minutos ' + (duration % 60) + ' segundos.';
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
// views/js/arcgisterrain3d.js
console.log('[ArcGIS Terrain3D] arcgisterrain3d.js cargado');
(function () {
  function initArcgisTerrain3D() {
    var container = document.getElementById('arcgis-terrain3d-container');
    if (!container) {
      return;
    }

    var apiKey = container.getAttribute('data-api-key') || '';

    // Esperamos a que ArcGIS JS esté cargado (define "require")
    if (typeof require === 'undefined') {
      console.error('[ArcGIS Terrain3D] require() no está definido. ¿Se ha cargado js.arcgis.com correctamente?');
      return;
    }

    require([
      "esri/config",
      "esri/Map",
      "esri/views/SceneView",
      "esri/layers/GraphicsLayer",
      "esri/Graphic",
      "esri/widgets/Sketch"
    ], function (esriConfig, Map, SceneView, GraphicsLayer, Graphic, Sketch) {

      if (apiKey && apiKey.trim() !== '') {
        esriConfig.apiKey = apiKey.trim();
      }

      // Capa para las geometrías dibujadas y la malla
      var graphicsLayer = new GraphicsLayer();

      // Mapa 3D global
      var map = new Map({
        basemap: "satellite",
        ground: "world-elevation",
        layers: [graphicsLayer]
      });

      var view = new SceneView({
        container: "arcgis-terrain3d-container",
        map: map,
        qualityProfile: "high",
        camera: {
          position: {
            latitude: 20,
            longitude: 0,
            z: 25000000
          },
          tilt: 0,
          heading: 0
        },
        environment: {
          lighting: {
            directShadowsEnabled: true,
            ambientOcclusionEnabled: true
          }
        }
      });

      var loadingNode = container.querySelector('.arcgis-terrain3d-loading');
      if (loadingNode) {
        view.when(function () {
          loadingNode.style.display = 'none';
        });
      }

      var sketch = new Sketch({
        layer: graphicsLayer,
        view: view,
        creationMode: "single",
        availableCreateTools: ["polygon", "rectangle"],
        visibleElements: {
          selectionTools: {
            "lasso-selection": false,
            "rectangle-selection": false
          }
        }
      });

      view.ui.add(sketch, "top-right");

      var currentMeshGraphic = null;

      function createClosedMeshFromPolygon(polygon) {
        if (!polygon) {
          return;
        }

        // Eliminamos la malla anterior, si existe
        if (currentMeshGraphic) {
          graphicsLayer.remove(currentMeshGraphic);
          currentMeshGraphic = null;
        }

        // En esta versión inicial creamos una malla 3D extruida
        // (volumen cerrado) a partir del polígono.
        //
        // Más adelante se puede conectar con ElevationLayer para que la malla
        // siga exactamente el relieve real.

        var meshGraphic = new Graphic({
          geometry: polygon,
          symbol: {
            type: "polygon-3d",
            symbolLayers: [{
              type: "extrude",
              size: 2000, // altura de extrusión en metros (ajustable)
              material: {
                color: [255, 170, 0, 0.9]
              },
              edges: {
                type: "solid",
                color: [255, 255, 255, 0.8],
                size: 1
              }
            }]
          }
        });

        graphicsLayer.add(meshGraphic);
        currentMeshGraphic = meshGraphic;

        // Ajustamos la cámara para centrar la malla en vista 3D
        view.goTo({
          target: meshGraphic,
          tilt: 60
        }, {
          duration: 1500,
          easing: "ease-out"
        });
      }

      // Evento del Sketch al crear geometría
      sketch.on("create", function (event) {
        if (event.state === "complete") {
          var geom = event.graphic.geometry;
          createClosedMeshFromPolygon(geom);
        }
      });

      console.log('[ArcGIS Terrain3D] Inicializado correctamente.');
    });
  }

  // Iniciar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initArcgisTerrain3D);
  } else {
    initArcgisTerrain3D();
  }
})();

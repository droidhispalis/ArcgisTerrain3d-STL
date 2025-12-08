{* modules/arcgisterrain3d/views/templates/front/map.tpl *}
{extends file='page.tpl'}

{block name='page_title'}
  {l s='Mapa global 3D (ArcGIS)' mod='arcgisterrain3d'}
{/block}

{block name='page_content'}

<link rel="stylesheet" href="https://js.arcgis.com/4.30/esri/themes/light/main.css" />

<section class="arcgis-terrain3d-page">
    <div class="arcgis-terrain3d-layout">
        {* Columna izquierda: Mapa *}
        <div class="arcgis-terrain3d-map-column">
            <div id="arcgis-terrain3d-container" class="arcgis-terrain3d-container"
                data-api-key="{$arcgis_terrain3d_api_key|escape:'html':'UTF-8'}">
                <div class="arcgis-terrain3d-loading">
                    {l s='Cargando mapa 3D...' mod='arcgisterrain3d'}
                </div>
            </div>
        </div>

        {* Columna derecha: Panel de control *}
        <div class="arcgis-terrain3d-panel-column">
            <div class="arcgis-terrain3d-panel">
                <h2>{l s='Terreno 3D' mod='arcgisterrain3d'}</h2>

                {* Selector de producto *}
                {if $available_products && count($available_products) > 0}
                <div class="panel-section">
                    <label for="arcgis-terrain3d-product-select">
                        <strong>{l s='1. Selecciona producto:' mod='arcgisterrain3d'}</strong>
                    </label>
                    <select id="arcgis-terrain3d-product-select" class="form-control">
                        <option value="">{l s='-- Elige un producto --' mod='arcgisterrain3d'}</option>
                        {foreach from=$available_products item=product}
                            <option value="{$product.id_product}" 
                                    data-price="{$product.price}" 
                                    data-reference="{$product.reference}"
                                    data-name="{$product.name|escape:'html':'UTF-8'}">
                                {$product.name|escape:'html':'UTF-8'} - {$product.price|string_format:"%.2f"} €
                            </option>
                        {/foreach}
                    </select>
                </div>
                {else}
                <div class="panel-section">
                    <div class="alert alert-warning">
                        <strong>{l s='No hay productos disponibles' mod='arcgisterrain3d'}</strong><br>
                        {if isset($debug_info)}
                            <small>
                                Categoría ID: {$debug_info.category_id|default:'No configurada'}<br>
                                {if isset($debug_info.category_name)}
                                    Categoría: {$debug_info.category_name}<br>
                                {/if}
                                {if isset($debug_info.products_found)}
                                    Productos encontrados: {$debug_info.products_found}<br>
                                {/if}
                                {if isset($debug_info.active_products)}
                                    Productos activos: {$debug_info.active_products}<br>
                                {/if}
                            </small>
                        {/if}
                        <em>{l s='Por favor, configura la categoría de productos en el backoffice del módulo y asegúrate de que la categoría tiene productos activos.' mod='arcgisterrain3d'}</em>
                    </div>
                </div>
                {/if}

                {* Instrucciones *}
                <div class="panel-section">
                    <label><strong>{l s='2. Dibuja el área:' mod='arcgisterrain3d'}</strong></label>
                    <p class="instructions-text">{l s='Usa las herramientas del mapa para dibujar un polígono o rectángulo sobre el terreno.' mod='arcgisterrain3d'}</p>
                </div>

                {* Panel de administrador *}
                {if isset($is_admin) && $is_admin}
                <div class="panel-section admin-section">
                    <label><strong>{l s='Admin: Cargar pedido' mod='arcgisterrain3d'}</strong></label>
                    <div class="admin-loader">
                        <input type="number" 
                               id="arcgis-terrain3d-order-id" 
                               class="form-control" 
                               placeholder="{l s='Nº pedido' mod='arcgisterrain3d'}">
                        <button id="arcgis-terrain3d-load-order" class="btn btn-info btn-sm">
                            {l s='Cargar' mod='arcgisterrain3d'}
                        </button>
                    </div>
                </div>
                {/if}

                {* Botones principales *}
                <div class="panel-section">
                    <label><strong>{l s='3. Acciones:' mod='arcgisterrain3d'}</strong></label>
                    <div class="action-buttons">
                        <button id="arcgis-terrain3d-generate" class="btn btn-secondary btn-block" disabled>
                            {l s='Generar malla 3D' mod='arcgisterrain3d'}
                        </button>
                        <button id="arcgis-terrain3d-open-preview" class="btn btn-outline-secondary btn-block" disabled>
                            {l s='Vista previa 3D' mod='arcgisterrain3d'}
                        </button>
                        <button id="arcgis-terrain3d-add-to-cart" class="btn btn-success btn-block" disabled>
                            {l s='Añadir al carrito' mod='arcgisterrain3d'}
                        </button>
                        {if isset($is_admin) && $is_admin}
                        <button id="arcgis-terrain3d-export-stl" class="btn btn-primary btn-block" disabled>
                            {l s='Exportar STL' mod='arcgisterrain3d'}
                        </button>
                        {/if}
                    </div>
                </div>

                {* Estado *}
                <div class="panel-section">
                    <small id="arcgis-terrain3d-export-status" class="status-text"></small>
                </div>

                {* Límites *}
                <div class="panel-section limits-section">
                    <small>
                        <strong>{l s='Límites:' mod='arcgisterrain3d'}</strong><br>
                        STL: {$arcgis_terrain3d_max_faces_stl|intval|number_format:0:',':'.'} tri.<br>
                        Vista: {$arcgis_terrain3d_max_faces_preview|intval|number_format:0:',':'.'} tri.<br>
                        Área: {$arcgis_terrain3d_max_area_km2|floatval} km²
                    </small>
                </div>
            </div>
        </div>
    </div>

    {* Modal vista previa 3D *}
    <div id="arcgis-terrain3d-modal" class="arcgis-terrain3d-modal" aria-hidden="true">
        <div class="arcgis-terrain3d-modal-backdrop"></div>
        <div class="arcgis-terrain3d-modal-dialog">
            <button type="button" class="arcgis-terrain3d-modal-close" aria-label="Cerrar">&times;</button>
            <h2>{l s='Vista previa 3D del terreno' mod='arcgisterrain3d'}</h2>
            <div id="arcgis-terrain3d-preview" class="arcgis-terrain3d-preview"></div>
            <p class="arcgis-terrain3d-modal-hint">
                {l s='Botón izquierdo: rotar · Rueda: zoom · Botón derecho: desplazar' mod='arcgisterrain3d'}
            </p>
        </div>
    </div>
</section>

<script>
    console.log('[ArcGIS Terrain3D v14] map.tpl cargado');

    // Valores de configuración desde PHP
    window.ARC3D_MAX_FACES_STL     = {$arcgis_terrain3d_max_faces_stl|intval};
    window.ARC3D_MAX_FACES_PREVIEW = {$arcgis_terrain3d_max_faces_preview|intval};
    window.ARC3D_MAX_AREA_KM2      = {$arcgis_terrain3d_max_area_km2|floatval};
    window.ARC3D_AJAX_URL          = '{$link->getModuleLink("arcgisterrain3d", "savemesh", [], true)|escape:"javascript":"UTF-8"}';
    window.ARC3D_IS_LOGGED         = {if $customer.is_logged}true{else}false{/if};
    window.ARC3D_IS_ADMIN          = {if isset($is_admin) && $is_admin}true{else}false{/if};
    console.log('[ArcGIS Terrain3D] Usuario logueado:', window.ARC3D_IS_LOGGED, 'Es admin:', window.ARC3D_IS_ADMIN);
</script>

<!-- ArcGIS JS API -->
<script src="https://js.arcgis.com/4.30/"></script>

<!-- Desactivar AMD mientras cargamos Three.js -->
<script>
    window._terrain3dDefineBackup = window.define;
    try { window.define = undefined; } catch (e) { }
</script>

<!-- Three.js y controles locales -->
<script>
    (function() {
        var baseUrl = window.location.origin + '/';
        var scripts = [
            'modules/arcgisterrain3d/views/js/vendor/three.min.js',
            'modules/arcgisterrain3d/views/js/vendor/OrbitControls.js',
            'modules/arcgisterrain3d/views/js/vendor/TrackballControls.js'
        ];
        scripts.forEach(function(src) {
            var script = document.createElement('script');
            script.src = baseUrl + src;
            document.head.appendChild(script);
        });
    })();
</script>

<!-- Restaurar AMD -->
<script>
    try { window.define = window._terrain3dDefineBackup; } catch (e) { }
</script>

<script>
    console.log('[ArcGIS Terrain3D v14] después de cargar ArcGIS + Three');

    require([
        "esri/config",
        "esri/Map",
        "esri/views/SceneView",
        "esri/layers/GraphicsLayer",
        "esri/Graphic",
        "esri/widgets/Sketch",
        "esri/geometry/support/meshUtils"
    ], function (esriConfig, Map, SceneView, GraphicsLayer, Graphic, Sketch, meshUtils) {

        console.log('[ArcGIS Terrain3D v14] require() callback ejecutado');

        var mainContainer = document.getElementById('arcgis-terrain3d-container');
        var generateButton = document.getElementById('arcgis-terrain3d-generate');
        var previewButton = document.getElementById('arcgis-terrain3d-open-preview');
        var exportButton = document.getElementById('arcgis-terrain3d-export-stl');
        var addToCartButton = document.getElementById('arcgis-terrain3d-add-to-cart');
        var exportStatus = document.getElementById('arcgis-terrain3d-export-status');
        var modal = document.getElementById('arcgis-terrain3d-modal');
        var modalClose = modal ? modal.querySelector('.arcgis-terrain3d-modal-close') : null;
        var modalBackdrop = modal ? modal.querySelector('.arcgis-terrain3d-modal-backdrop') : null;
        var previewContainer = document.getElementById('arcgis-terrain3d-preview');

        if (!mainContainer) {
            console.error('[ArcGIS Terrain3D v14] Contenedor principal no encontrado');
            return;
        }

        function setStatus(msg) {
            if (exportStatus) exportStatus.textContent = msg || '';
        }

        function disableAllButtons() {
            if (generateButton) generateButton.disabled = true;
            if (previewButton) previewButton.disabled = true;
            if (exportButton) exportButton.disabled = true;
            if (addToCartButton) addToCartButton.disabled = true;
        }

        function openModal() {
            if (!modal) return;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            // Forzar redimensionamiento después de que el modal se expanda
            setTimeout(function() {
                resizeThreeRenderer();
            }, 50);
        }

        function closeModal() {
            if (!modal) return;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
        }

        if (modalClose) modalClose.addEventListener('click', closeModal);
        if (modalBackdrop) modalBackdrop.addEventListener('click', closeModal);

        // --- Config ArcGIS ---
        var apiKey = mainContainer.getAttribute('data-api-key') || '';
        if (apiKey.trim() !== '') {
            esriConfig.apiKey = apiKey.trim();
            console.log('[ArcGIS Terrain3D v14] API Key aplicada');
        } else {
            console.warn('[ArcGIS Terrain3D v14] Sin API Key; se usarán recursos públicos limitados.');
        }

        var graphicsLayer = new GraphicsLayer();

        var map = new Map({
            basemap: "satellite",
            ground: "world-elevation",
            layers: [graphicsLayer]
        });

        var view = new SceneView({
            container: "arcgis-terrain3d-container",
            map: map,
            qualityProfile: "medium",
            camera: {
                position: { latitude: 20, longitude: 0, z: 25000000 },
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

        var loadingNode = mainContainer.querySelector('.arcgis-terrain3d-loading');
        if (loadingNode) {
            view.when(function () {
                loadingNode.style.display = 'none';
                console.log('[ArcGIS Terrain3D v14] Vista 3D lista');
            });
        }

        var sketch = new Sketch({
            layer: graphicsLayer,
            view: view,
            creationMode: "single",
            visibleElements: {
                selectionTools: {
                    "lasso-selection": false,
                    "rectangle-selection": false
                }
            }
        });

        view.ui.add(sketch, "top-right");

        var selectionGraphic = null;
        var selectionGeometry = null;
        var selectionRingXY = null; // [ [x,y], ... ] del polígono/círculo
        var currentMesh = null;

        // THREE preview
        var threeScene = null;
        var threeCamera = null;
        var threeRenderer = null;
        var threeControls = null;
        var threeMesh = null;
        var threeAnimating = false;

        function clearPreview() {
            currentMesh = null;
            if (threeScene && threeMesh) {
                threeScene.remove(threeMesh);
                if (threeMesh.geometry) threeMesh.geometry.dispose();
                if (threeMesh.material) threeMesh.material.dispose();
                threeMesh = null;
            }
            if (previewButton) previewButton.disabled = true;
            if (exportButton) exportButton.disabled = true;
        }

        function clearSelection() {
            if (selectionGraphic && graphicsLayer) {
                try { graphicsLayer.remove(selectionGraphic); } catch (e) { }
            }
            selectionGraphic = null;
            selectionGeometry = null;
            selectionRingXY = null;
            disableAllButtons();
            clearPreview();
            setStatus('');
        }

        function initThreeIfNeeded(callback) {
            if (typeof THREE === 'undefined' || !THREE) {
                console.error('[ArcGIS Terrain3D v14] THREE no está disponible');
                setStatus('No se ha podido inicializar Three.js para la vista previa 3D.');
                return;
            }
            if (typeof THREE.TrackballControls === 'undefined') {
                console.error('[ArcGIS Terrain3D v14] THREE.TrackballControls no está disponible');
                setStatus('No se ha podido cargar TrackballControls para la vista previa 3D.');
                return;
            }

            if (threeScene && threeCamera && threeRenderer && threeControls) {
                if (callback) callback();
                return;
            }

            if (!previewContainer) {
                setStatus('No hay contenedor para la vista previa 3D.');
                return;
            }

            threeScene = new THREE.Scene();
            threeScene.background = new THREE.Color(0xf5f5f5);

            var w = previewContainer.clientWidth || previewContainer.offsetWidth || 800;
            var h = previewContainer.clientHeight || previewContainer.offsetHeight || 450;

            threeCamera = new THREE.PerspectiveCamera(45, w / h, 0.1, 5000);

            threeRenderer = new THREE.WebGLRenderer({ antialias: true });
            threeRenderer.setPixelRatio(window.devicePixelRatio || 1);
            threeRenderer.setSize(w, h, false);

            previewContainer.innerHTML = '';
            previewContainer.appendChild(threeRenderer.domElement);
            
            // Forzar que el canvas ocupe todo el contenedor
            threeRenderer.domElement.style.width = '100%';
            threeRenderer.domElement.style.height = '100%';
            threeRenderer.domElement.style.display = 'block';

            var ambient = new THREE.AmbientLight(0xffffff, 0.5);
            threeScene.add(ambient);

            var dir = new THREE.DirectionalLight(0xffffff, 0.8);
            dir.position.set(1, 1, 1);
            threeScene.add(dir);

            threeControls = new THREE.TrackballControls(threeCamera, threeRenderer.domElement);
            threeControls.rotateSpeed = 5.5;
            threeControls.zoomSpeed = 1.2;
            threeControls.panSpeed = 0.3;
            threeControls.noZoom = false;
            threeControls.noPan = false;
            threeControls.staticMoving = false;
            threeControls.dynamicDampingFactor = 0.2;
            
            // Inicializar el tamaño de los controles
            threeControls.handleResize();
            
            // Desactivar menú contextual en el canvas
            threeRenderer.domElement.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }, false);

            function animate() {
                if (!threeRenderer) return;
                requestAnimationFrame(animate);
                if (threeControls) threeControls.update();
                threeRenderer.render(threeScene, threeCamera);
            }
            if (!threeAnimating) {
                threeAnimating = true;
                animate();
            }

            if (callback) callback();
        }

        // --- Utilidades de recorte por polígono/círculo ---

        function pointInRing(x, y, ring) {
            // Algoritmo clásico de "ray casting" (par/impar)
            var inside = false;
            for (var i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                var xi = ring[i][0], yi = ring[i][1];
                var xj = ring[j][0], yj = ring[j][1];

                var intersect =
                    ((yi > y) !== (yj > y)) &&
                    (x < (xj - xi) * (y - yi) / ((yj - yi) || 1e-12) + xi);

                if (intersect) inside = !inside;
            }
            return inside;
        }

        function resizeThreeRenderer() {
            if (!threeRenderer || !previewContainer || !threeCamera) return;
            var w = previewContainer.clientWidth || previewContainer.offsetWidth || 800;
            var h = previewContainer.clientHeight || previewContainer.offsetHeight || 450;
            
            // Actualizar tamaño del renderer
            threeRenderer.setSize(w, h, false);
            
            // Forzar estilos en el canvas
            if (threeRenderer.domElement) {
                threeRenderer.domElement.style.width = '100%';
                threeRenderer.domElement.style.height = '100%';
                threeRenderer.domElement.style.display = 'block';
            }
            
            // Actualizar cámara
            threeCamera.aspect = w / h;
            threeCamera.updateProjectionMatrix();
            
            // Actualizar controles
            if (threeControls && threeControls.handleResize) {
                threeControls.handleResize();
            }
        }

        window.addEventListener('resize', resizeThreeRenderer);


        function filterFacesByRing(positions, faceIndices, ring) {
            if (!ring || !ring.length) return faceIndices;

            var filtered = [];
            for (var f = 0; f < faceIndices.length; f++) {
                var tri = faceIndices[f];

                var i0 = tri[0] * 3;
                var i1 = tri[1] * 3;
                var i2 = tri[2] * 3;

                var x0 = positions[i0], y0 = positions[i0 + 1];
                var x1 = positions[i1], y1 = positions[i1 + 1];
                var x2 = positions[i2], y2 = positions[i2 + 1];

                var cx = (x0 + x1 + x2) / 3;
                var cy = (y0 + y1 + y2) / 3;

                if (pointInRing(cx, cy, ring)) {
                    filtered.push(tri);
                }
            }
            return filtered;
        }


        function updateThreePreviewFromMesh(mesh) {
            initThreeIfNeeded(function () {
                if (!threeScene || !threeCamera) {
                    setStatus('No se pudo inicializar la escena 3D.');
                    return;
                }
                if (!mesh || !mesh.vertexAttributes || !mesh.vertexAttributes.position) {
                    setStatus('La malla devuelta por ArcGIS no contiene vértices.');
                    return;
                }

                if (threeScene && threeMesh) {
                    threeScene.remove(threeMesh);
                    if (threeMesh.geometry) threeMesh.geometry.dispose();
                    if (threeMesh.material) threeMesh.material.dispose();
                    threeMesh = null;
                }

                var positions = mesh.vertexAttributes.position;
                var components = mesh.components || [];
                var vertexCount = positions.length / 3;
                var faceIndices = [];

                if (components.length && components[0].faces && components[0].faces.length) {
                    var faces = components[0].faces;
                    for (var i = 0; i < faces.length; i += 3) {
                        faceIndices.push([faces[i], faces[i + 1], faces[i + 2]]);
                    }
                } else {
                    for (var v = 0; v < vertexCount; v += 3) {
                        faceIndices.push([v, v + 1, v + 2]);
                    }
                }
                // Recortamos los triángulos con el polígono/círculo original si existe
                if (selectionRingXY && selectionRingXY.length >= 3) {
                    var clipped = filterFacesByRing(positions, faceIndices, selectionRingXY);
                    if (clipped.length > 0) {
                        faceIndices = clipped;
                    } else {
                        console.warn('[ArcGIS Terrain3D v14] El recorte por polígono ha dejado 0 triángulos; se usa la malla rectangular.');
                    }
                }


                var previewFaces = faceIndices.length;
                var maxFacesPreview = (typeof window.ARC3D_MAX_FACES_PREVIEW === 'number' && window.ARC3D_MAX_FACES_PREVIEW > 0)
                    ? window.ARC3D_MAX_FACES_PREVIEW
                    : 2000000;

                if (previewFaces > maxFacesPreview) {
                    console.warn('[ArcGIS Terrain3D v14] Malla demasiado densa para vista previa:', previewFaces);
                    setStatus(
                        'La malla generada es muy densa para la vista previa 3D (' + previewFaces +
                        ' triángulos). Aún puedes exportar a STL si no superas el límite configurado.'
                    );
                    if (previewButton) previewButton.disabled = true;
                    return;
                }

                var minX = Infinity, minY = Infinity, minZ = Infinity;
                var maxX = -Infinity, maxY = -Infinity, maxZ = -Infinity;
                for (var j = 0; j < positions.length; j += 3) {
                    var x = positions[j];
                    var y = positions[j + 1];
                    var z = positions[j + 2];
                    if (x < minX) minX = x;
                    if (y < minY) minY = y;
                    if (z < minZ) minZ = z;
                    if (x > maxX) maxX = x;
                    if (y > maxY) maxY = y;
                    if (z > maxZ) maxZ = z;
                }

                var sizeX = maxX - minX;
                var sizeY = maxY - minY;
                var sizeZ = maxZ - minZ;
                var maxDim = Math.max(sizeX, sizeY, sizeZ);
                var scaleFactor = maxDim > 0 ? 300 / maxDim : 1.0;

                function getTopVertex(idx) {
                    return {
                        x: (positions[3 * idx] - minX) * scaleFactor,
                        y: (positions[3 * idx + 1] - minY) * scaleFactor,
                        z: (positions[3 * idx + 2] - minZ) * scaleFactor
                    };
                }

                var triCount = faceIndices.length;
                var buffer = new Float32Array(triCount * 9);
                var offset = 0;

                for (var f = 0; f < faceIndices.length; f++) {
                    var tri = faceIndices[f];
                    var v1 = getTopVertex(tri[0]);
                    var v2 = getTopVertex(tri[1]);
                    var v3 = getTopVertex(tri[2]);

                    buffer[offset] = v1.x;
                    buffer[offset + 1] = v1.y;
                    buffer[offset + 2] = v1.z;
                    buffer[offset + 3] = v2.x;
                    buffer[offset + 4] = v2.y;
                    buffer[offset + 5] = v2.z;
                    buffer[offset + 6] = v3.x;
                    buffer[offset + 7] = v3.y;
                    buffer[offset + 8] = v3.z;

                    offset += 9;
                }

                var geom = new THREE.BufferGeometry();
                geom.setAttribute('position', new THREE.BufferAttribute(buffer, 3));
                geom.computeVertexNormals();

                var material = new THREE.MeshStandardMaterial({
                    color: 0xcccccc,
                    metalness: 0.1,
                    roughness: 0.8
                });

                threeMesh = new THREE.Mesh(geom, material);
                threeScene.add(threeMesh);

                geom.computeBoundingBox();
                var box = geom.boundingBox;
                var center = new THREE.Vector3();
                box.getCenter(center);

                var size = new THREE.Vector3();
                box.getSize(size);
                var maxSize = Math.max(size.x, size.y, size.z);

                threeCamera.position.set(
                    center.x + maxSize * 1.1,
                    center.y + maxSize * 1.1,
                    center.z + maxSize * 1.2
                );
                threeCamera.lookAt(center);

                if (threeControls) {
                    threeControls.target.copy(center);
                    // Distancias para que el zoom mínimo/máximo tenga sentido
                    threeControls.minDistance = maxSize * 0.4;
                    threeControls.maxDistance = maxSize * 4;
                    threeControls.update();
                }
                if (previewButton) previewButton.disabled = false;
            });
        }

        // --- Generación de malla desde selección ---
        function createMeshFromSelection() {
            if (!selectionGeometry) {
                setStatus('No hay ninguna área seleccionada. Dibuja o selecciona un polígono primero.');
                return;
            }

            var extent = selectionGeometry.extent;
            if (!extent ||
                !isFinite(extent.width) || !isFinite(extent.height) ||
                extent.width <= 0 || extent.height <= 0) {
                console.warn('[ArcGIS Terrain3D v14] Extent inválido', extent);
                setStatus('El área seleccionada es demasiado pequeña o degenerada. Dibuja un rectángulo más grande.');
                return;
            }

            var maxAreaKm2 = (typeof window.ARC3D_MAX_AREA_KM2 === 'number' && window.ARC3D_MAX_AREA_KM2 > 0)
                ? window.ARC3D_MAX_AREA_KM2
                : 50;
            var maxSideMeters = Math.sqrt(maxAreaKm2) * 1000;

            if (extent.width > maxSideMeters || extent.height > maxSideMeters) {
                var sizeKmX = (extent.width / 1000).toFixed(1);
                var sizeKmY = (extent.height / 1000).toFixed(1);
                setStatus(
                    'El área seleccionada es muy grande (' + sizeKmX + ' x ' + sizeKmY +
                    ' km). Reduce la selección para que el área total sea menor de ' +
                    maxAreaKm2 + ' km².'
                );
                if (generateButton) generateButton.disabled = false;
                return;
            }

            // ⚠️ IMPORTANTE: volvemos a usar el EXTENT RECTANGULAR
            // para evitar el error elevation-query:invalid-extent.
            var geometryForMesh = extent.clone();

            console.log('[ArcGIS Terrain3D v14] createFromElevation con extent:',
                geometryForMesh.xmin, geometryForMesh.ymin,
                geometryForMesh.xmax, geometryForMesh.ymax
            );

            disableAllButtons();
            clearPreview();
            setStatus('Generando malla 3D a partir del relieve (puede tardar unos segundos)...');

            meshUtils.createFromElevation(map.ground, geometryForMesh, {
                demResolution: 10,
                material: { color: [255, 170, 0, 0.95] }
            }).then(function (mesh) {
                currentMesh = mesh;

                var vCount = mesh.vertexAttributes && mesh.vertexAttributes.position
                    ? mesh.vertexAttributes.position.length / 3
                    : 0;

                setStatus(
                    'Malla 3D generada (' + vCount +
                    ' vértices aprox.). Pulsa "Ver vista previa 3D" o exporta a STL.'
                );

                updateThreePreviewFromMesh(mesh);

                if (exportButton) exportButton.disabled = false;
                if (previewButton) previewButton.disabled = false;
                if (addToCartButton) addToCartButton.disabled = false;
            }).catch(function (error) {
                console.error('[ArcGIS Terrain3D v14] Error al crear la malla desde la elevación', error);
                var msg = (error && error.message) ? error.message : String(error || '');
                setStatus('Error al generar la malla: ' + msg);
                clearPreview();
                if (generateButton && selectionGeometry) generateButton.disabled = false;
            });
        }

        // Eventos Sketch
        sketch.on("create", function (event) {
            if (event.state === "complete") {
                clearSelection();
                selectionGraphic = event.graphic;
                selectionGeometry = event.graphic.geometry;

                // Guardamos el anillo del polígono si existe (círculo o polígono)
                selectionRingXY = null;
                if (selectionGeometry &&
                    selectionGeometry.type === "polygon" &&
                    selectionGeometry.rings &&
                    selectionGeometry.rings.length > 0) {
                    selectionRingXY = selectionGeometry.rings[0].map(function (pt) {
                        return [pt[0], pt[1]]; // x, y
                    });
                }

                // Verificar que haya producto seleccionado antes de habilitar
                var productSelect = document.getElementById('arcgis-terrain3d-product-select');
                var hasProduct = productSelect && productSelect.value;
                
                if (generateButton) {
                    if (hasProduct) {
                        generateButton.disabled = false;
                        setStatus('Área seleccionada. Pulsa "Generar malla 3D".');
                    } else {
                        generateButton.disabled = true;
                        setStatus('⚠ Primero selecciona un producto antes de generar la malla.');
                    }
                }
            }
        });

        sketch.on("update", function (event) {
            if (event.state === "start" || event.state === "active") {
                disableAllButtons();
                setStatus('Modificando selección... suelta el ratón para actualizar.');
                return;
            }
            if (event.state === "complete") {
                if (!event.graphics || event.graphics.length === 0) {
                    clearSelection();
                    return;
                }
                selectionGraphic = event.graphics[0];
                selectionGeometry = selectionGraphic.geometry;

                // Actualizamos también el anillo del polígono
                selectionRingXY = null;
                if (selectionGeometry &&
                    selectionGeometry.type === "polygon" &&
                    selectionGeometry.rings &&
                    selectionGeometry.rings.length > 0) {
                    selectionRingXY = selectionGeometry.rings[0].map(function (pt) {
                        return [pt[0], pt[1]];
                    });
                }

                clearPreview();
                
                // Verificar que haya producto seleccionado antes de habilitar
                var productSelect = document.getElementById('arcgis-terrain3d-product-select');
                var hasProduct = productSelect && productSelect.value;
                
                if (generateButton) {
                    if (hasProduct) {
                        generateButton.disabled = false;
                        setStatus('Selección actualizada. Pulsa "Generar malla 3D".');
                    } else {
                        generateButton.disabled = true;
                        setStatus('⚠ Primero selecciona un producto antes de generar la malla.');
                    }
                }
            }

        });

        if (typeof sketch.on === 'function') {
            sketch.on("delete", function () {
                clearSelection();
            });
        }

        // Listener para el selector de productos
        var productSelect = document.getElementById('arcgis-terrain3d-product-select');
        if (productSelect) {
            productSelect.addEventListener('change', function() {
                var hasProduct = this.value !== '';
                var hasSelection = selectionGeometry !== null;
                
                if (hasSelection) {
                    if (hasProduct) {
                        if (generateButton) {
                            generateButton.disabled = false;
                            setStatus('Producto seleccionado. Pulsa "Generar malla 3D".');
                        }
                    } else {
                        disableAllButtons();
                        setStatus('⚠ Selecciona un producto para continuar.');
                    }
                } else {
                    if (hasProduct) {
                        setStatus('Producto seleccionado. Dibuja un área en el mapa.');
                    }
                }
            });
        }

        if (generateButton) {
            generateButton.addEventListener('click', function() {
                // Validar producto antes de generar
                var productSelect = document.getElementById('arcgis-terrain3d-product-select');
                if (!productSelect || !productSelect.value) {
                    alert('⚠ Primero debes seleccionar un producto antes de generar la malla.');
                    return;
                }
                createMeshFromSelection();
            });
        }

        if (previewButton) {
            previewButton.addEventListener('click', function () {
                if (!currentMesh || !threeScene) {
                    setStatus('Primero genera la malla 3D para ver la vista previa.');
                    return;
                }
                openModal();
            });
        }

        // --- STL binario ---
        function meshToSTLBinary(mesh) {
            if (!mesh || !mesh.vertexAttributes || !mesh.vertexAttributes.position) {
                throw new Error('Malla sin vértices');
            }

            var positions = mesh.vertexAttributes.position;
            var components = mesh.components || [];
            var vertexCount = positions.length / 3;
            var faceIndices = [];

            if (components.length && components[0].faces && components[0].faces.length) {
                var faces = components[0].faces;
                for (var i = 0; i < faces.length; i += 3) {
                    faceIndices.push([faces[i], faces[i + 1], faces[i + 2]]);
                }
            } else {
                for (var v = 0; v < vertexCount; v += 3) {
                    faceIndices.push([v, v + 1, v + 2]);
                }
            }

            // Recorte por polígono/círculo antes de calcular paredes y límites
            if (selectionRingXY && selectionRingXY.length >= 3) {
                var clippedFaces = filterFacesByRing(positions, faceIndices, selectionRingXY);
                if (clippedFaces.length > 0) {
                    faceIndices = clippedFaces;
                } else {
                    console.warn('[ArcGIS Terrain3D v14] El recorte por polígono ha dejado 0 triángulos para STL; se exportaría vacío.');
                    throw new Error('La selección es demasiado pequeña o el recorte ha eliminado toda la malla.');
                }
            }

            var minX = Infinity, minY = Infinity, minZ = Infinity;
            for (var j = 0; j < positions.length; j += 3) {
                var x = positions[j];
                var y = positions[j + 1];
                var z = positions[j + 2];
                if (x < minX) minX = x;
                if (y < minY) minY = y;
                if (z < minZ) minZ = z;
            }

            function getTopVertex(idx) {
                return {
                    x: positions[3 * idx] - minX,
                    y: positions[3 * idx + 1] - minY,
                    z: positions[3 * idx + 2] - minZ
                };
            }

            function getBottomVertex(idx) {
                return {
                    x: positions[3 * idx] - minX,
                    y: positions[3 * idx + 1] - minY,
                    z: 0
                };
            }

            var edgeMap = {};
            function addEdge(a, b) {
                var key = a < b ? (a + "_" + b) : (b + "_" + a);
                edgeMap[key] = (edgeMap[key] || 0) + 1;
            }

            for (var f = 0; f < faceIndices.length; f++) {
                var tri = faceIndices[f];
                addEdge(tri[0], tri[1]);
                addEdge(tri[1], tri[2]);
                addEdge(tri[2], tri[0]);
            }

            var boundaryEdges = [];
            for (var key in edgeMap) {
                if (edgeMap.hasOwnProperty(key) && edgeMap[key] === 1) {
                    var parts = key.split('_');
                    boundaryEdges.push([parseInt(parts[0], 10), parseInt(parts[1], 10)]);
                }
            }

            var topFacesCount = faceIndices.length;
            var bottomFacesCount = faceIndices.length;
            var wallFacesCount = boundaryEdges.length * 2;
            var totalFaces = topFacesCount + bottomFacesCount + wallFacesCount;

            console.log('[ArcGIS Terrain3D v14] Triángulos STL estimados:', totalFaces);

            var maxFacesStl = (typeof window.ARC3D_MAX_FACES_STL === 'number' && window.ARC3D_MAX_FACES_STL > 0)
                ? window.ARC3D_MAX_FACES_STL
                : 5000000;

            if (totalFaces > maxFacesStl) {
                var msg = 'El área seleccionada genera ' + totalFaces +
                    ' triángulos, por encima del límite configurado de ' + maxFacesStl +
                    '. Reduce el tamaño del área o aumenta demResolution en el módulo para poder exportar desde el navegador.';
                // Mensaje visible al usuario
                setStatus('Error exportando STL: ' + msg);
                if (window && window.alert) {
                    alert('Error exportando STL:\n\n' + msg);
                }
                throw new Error(msg);
            }

            var buffer = new ArrayBuffer(84 + totalFaces * 50);
            var view = new DataView(buffer);

            for (var h = 0; h < 80; h++) view.setUint8(h, 0);
            view.setUint32(80, totalFaces, true);

            var offset = 84;

            function writeFacet(v1, v2, v3) {
                var ux = v2.x - v1.x;
                var uy = v2.y - v1.y;
                var uz = v2.z - v1.z;
                var vx = v3.x - v1.x;
                var vy = v3.y - v1.y;
                var vz = v3.z - v1.z;

                var nx = uy * vz - uz * vy;
                var ny = uz * vx - ux * vz;
                var nz = ux * vy - uy * vx;
                var length = Math.sqrt(nx * nx + ny * ny + nz * nz) || 1.0;
                nx /= length; ny /= length; nz /= length;

                view.setFloat32(offset, nx, true);
                view.setFloat32(offset + 4, ny, true);
                view.setFloat32(offset + 8, nz, true);

                view.setFloat32(offset + 12, v1.x, true);
                view.setFloat32(offset + 16, v1.y, true);
                view.setFloat32(offset + 20, v1.z, true);

                view.setFloat32(offset + 24, v2.x, true);
                view.setFloat32(offset + 28, v2.y, true);
                view.setFloat32(offset + 32, v2.z, true);

                view.setFloat32(offset + 36, v3.x, true);
                view.setFloat32(offset + 40, v3.y, true);
                view.setFloat32(offset + 44, v3.z, true);

                view.setUint16(offset + 48, 0, true);
                offset += 50;
            }

            for (var fTop = 0; fTop < faceIndices.length; fTop++) {
                var triTop = faceIndices[fTop];
                writeFacet(
                    getTopVertex(triTop[0]),
                    getTopVertex(triTop[1]),
                    getTopVertex(triTop[2])
                );
            }

            for (var fBot = 0; fBot < faceIndices.length; fBot++) {
                var triBot = faceIndices[fBot];
                writeFacet(
                    getBottomVertex(triBot[2]),
                    getBottomVertex(triBot[1]),
                    getBottomVertex(triBot[0])
                );
            }

            for (var e = 0; e < boundaryEdges.length; e++) {
                var edge = boundaryEdges[e];
                var a = edge[0];
                var b = edge[1];
                var vTopA = getTopVertex(a);
                var vTopB = getTopVertex(b);
                var vBotA = getBottomVertex(a);
                var vBotB = getBottomVertex(b);

                writeFacet(vTopA, vBotA, vBotB);
                writeFacet(vTopA, vBotB, vTopB);
            }

            return buffer;
        }

        if (exportButton) {
            exportButton.addEventListener('click', function () {
                if (!currentMesh) {
                    setStatus('Primero genera la malla 3D antes de exportar.');
                    return;
                }
                try {
                    setStatus('Generando STL (binario)...');
                    var buffer = meshToSTLBinary(currentMesh);
                    var blob = new Blob([buffer], { type: 'application/octet-stream' });
                    var url = URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = 'terrain3d_arcgis.stl';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                    setStatus('STL descargado correctamente.');
                } catch (e) {
                    console.error('[ArcGIS Terrain3D v14] Error exportando STL', e);
                    setStatus('Error exportando STL: ' + (e.message || e));
                }
            });
        }

        if (addToCartButton) {
            addToCartButton.addEventListener('click', function () {
                if (!selectionGeometry) {
                    setStatus('Primero dibuja un área en el mapa.');
                    return;
                }

                if (!window.ARC3D_IS_LOGGED) {
                    alert('Debes iniciar sesión para añadir productos al carrito.');
                    return;
                }

                // Verificar que se haya seleccionado un producto
                var productSelect = document.getElementById('arcgis-terrain3d-product-select');
                if (!productSelect || !productSelect.value) {
                    alert('Por favor, selecciona un producto antes de añadir al carrito.');
                    return;
                }

                var selectedOption = productSelect.options[productSelect.selectedIndex];
                var productId = parseInt(productSelect.value);
                var productName = selectedOption.getAttribute('data-name');
                var productPrice = parseFloat(selectedOption.getAttribute('data-price'));
                var productReference = selectedOption.getAttribute('data-reference');

                try {
                    setStatus('Preparando pedido...');
                    
                    // Calcular coordenadas centrales y área
                    var extent = selectionGeometry.extent;
                    var centerLat = (extent.ymin + extent.ymax) / 2;
                    var centerLon = (extent.xmin + extent.xmax) / 2;
                    var widthKm = extent.width / 1000;
                    var heightKm = extent.height / 1000;
                    var areaKm2 = widthKm * heightKm;

                    // Determinar tipo de forma
                    var shapeType = 'rectangle';
                    if (selectionGeometry.type === 'polygon') {
                        if (selectionGeometry.rings && selectionGeometry.rings.length > 0) {
                            var ringLength = selectionGeometry.rings[0].length;
                            if (ringLength > 20) {
                                shapeType = 'circle';
                            } else {
                                shapeType = 'polygon';
                            }
                        }
                    }

                    // Calcular tamaño estimado basado en el área (no en la malla)
                    // Estimación: ~100 triángulos por km² para terreno de calidad media
                    var estimatedTriangles = areaKm2 * 100000;
                    var estimatedSizeMB = ((estimatedTriangles * 50) / (1024 * 1024)).toFixed(2);

                    // Guardar geometría de selección en variable global para uso posterior
                    window.ARC3D_LAST_SELECTION = {
                        geometry: selectionGeometry,
                        centerLat: centerLat,
                        centerLon: centerLon,
                        areaKm2: areaKm2,
                        shapeType: shapeType
                    };

                    // Enviar solo metadatos al servidor (SIN archivo STL)
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', window.ARC3D_AJAX_URL, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    
                    xhr.onload = function() {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                setStatus('✓ Producto añadido al carrito');
                                if (confirm('Producto añadido al carrito:\n\n' + 
                                          productName + '\n' +
                                          'Precio: ' + productPrice.toFixed(2) + ' €\n' +
                                          'Terreno: ' + areaKm2.toFixed(2) + ' km²\n' +
                                          'Tamaño estimado: ' + estimatedSizeMB + ' MB\n\n' +
                                          'IMPORTANTE: El archivo STL se generará tras confirmar el pago.\n\n' +
                                          '¿Deseas ir al carrito para completar el pedido?')) {
                                    window.location.href = response.cart_url;
                                }
                            } else {
                                setStatus('Error: ' + response.error);
                                alert('Error: ' + response.error);
                            }
                        } catch (e) {
                            console.error('[ArcGIS Terrain3D] Error parseando respuesta', e, xhr.responseText);
                            setStatus('Error de comunicación');
                            alert('Error de comunicación con el servidor');
                        }
                    };

                    xhr.onerror = function() {
                        setStatus('Error de red');
                        alert('Error de red. Por favor, inténtalo de nuevo.');
                    };

                    var params = 'ajax=1' +
                        '&product_id=' + productId +
                        '&latitude=' + centerLat +
                        '&longitude=' + centerLon +
                        '&area_km2=' + areaKm2.toFixed(2) +
                        '&shape_type=' + encodeURIComponent(shapeType) +
                        '&file_size_mb=' + estimatedSizeMB;

                    xhr.send(params);

                } catch (e) {
                    console.error('[ArcGIS Terrain3D v14] Error', e);
                    setStatus('Error: ' + (e.message || e));
                    alert('Error al procesar los datos: ' + (e.message || e));
                }
            });
        }

        // Botón de administrador para cargar pedidos
        var loadOrderButton = document.getElementById('arcgis-terrain3d-load-order');
        if (loadOrderButton) {
            loadOrderButton.addEventListener('click', function() {
                var orderIdInput = document.getElementById('arcgis-terrain3d-order-id');
                var orderId = parseInt(orderIdInput.value);
                
                if (!orderId || orderId <= 0) {
                    alert('Por favor, introduce un número de pedido válido.');
                    return;
                }

                setStatus('Cargando pedido #' + orderId + '...');
                
                // Llamar al servidor para obtener los datos del pedido
                var xhr = new XMLHttpRequest();
                xhr.open('POST', window.ARC3D_AJAX_URL.replace('savemesh', 'loadorder'), true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            var data = response.data;
                            setStatus('Pedido #' + orderId + ' cargado. Generando terreno...');
                            
                            // Crear geometría basada en las coordenadas guardadas
                            var centerLat = parseFloat(data.latitude);
                            var centerLon = parseFloat(data.longitude);
                            var areaKm2 = parseFloat(data.area_km2);
                            
                            // Calcular dimensiones aproximadas (asumiendo cuadrado)
                            var sideKm = Math.sqrt(areaKm2);
                            var sideDegrees = sideKm / 111; // Aproximación: 1 grado ≈ 111 km
                            
                            // Crear polígono rectangular
                            var rings = [[
                                [centerLon - sideDegrees/2, centerLat - sideDegrees/2],
                                [centerLon + sideDegrees/2, centerLat - sideDegrees/2],
                                [centerLon + sideDegrees/2, centerLat + sideDegrees/2],
                                [centerLon - sideDegrees/2, centerLat + sideDegrees/2],
                                [centerLon - sideDegrees/2, centerLat - sideDegrees/2]
                            ]];
                            
                            selectionGeometry = new Polygon({
                                rings: rings,
                                spatialReference: { wkid: 4326 }
                            });
                            
                            // Añadir gráfico de selección al mapa
                            graphicsLayer.removeAll();
                            var fillSymbol = {
                                type: "simple-fill",
                                color: [255, 255, 0, 0.3],
                                outline: {
                                    color: [255, 255, 0],
                                    width: 2
                                }
                            };
                            
                            var graphic = new Graphic({
                                geometry: selectionGeometry,
                                symbol: fillSymbol
                            });
                            
                            graphicsLayer.add(graphic);
                            
                            // Centrar vista en la selección
                            view.goTo(selectionGeometry.extent.expand(1.5));
                            
                            // Generar malla automáticamente
                            setTimeout(function() {
                                if (generateButton) {
                                    generateButton.click();
                                    setStatus('✓ Pedido #' + orderId + ' cargado. Generando malla...');
                                    
                                    // Después de generar, habilitar botón de exportar STL
                                    setTimeout(function() {
                                        if (exportButton) {
                                            alert('Malla generada para pedido #' + orderId + '.\n\n' +
                                                  'Producto: ' + data.product_name + '\n' +
                                                  'Coordenadas: ' + centerLat.toFixed(4) + ', ' + centerLon.toFixed(4) + '\n' +
                                                  'Área: ' + areaKm2.toFixed(2) + ' km²\n\n' +
                                                  'Pulsa "Exportar modelo STL" para descargar el archivo.');
                                        }
                                    }, 2000);
                                }
                            }, 500);
                            
                        } else {
                            setStatus('Error: ' + response.error);
                            alert('Error al cargar pedido: ' + response.error);
                        }
                    } catch (e) {
                        console.error('[ArcGIS Terrain3D] Error parseando respuesta', e, xhr.responseText);
                        setStatus('Error de comunicación');
                        alert('Error al procesar la respuesta del servidor');
                    }
                };
                
                xhr.onerror = function() {
                    setStatus('Error de red');
                    alert('Error de red al cargar el pedido.');
                };
                
                xhr.send('ajax=1&order_id=' + orderId);
            });
        }

        console.log('[ArcGIS Terrain3D v14] Inicializado completamente');
    });
</script>

{/block}

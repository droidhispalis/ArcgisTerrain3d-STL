{*
  Plantilla donde se monta el contenedor del mapa 3D.
  Se puede mover a otra posición usando otro hook si quieres.
*}

<div class="arcgis-terrain3d-wrapper">
  <h3 class="arcgis-terrain3d-title">
    {l s='Mapa global 3D (ArcGIS)' mod='arcgisterrain3d'}
  </h3>

  <div
    id="arcgis-terrain3d-container"
    class="arcgis-terrain3d-container"
    style="width:{$arcgis_terrain3d_width|intval}%;height:{$arcgis_terrain3d_height|intval}px;"
    data-api-key="{$arcgis_terrain3d_api_key|escape:'html':'UTF-8'}"
  >
    <div class="arcgis-terrain3d-loading">
      {l s='Cargando mapa 3D de ArcGIS...' mod='arcgisterrain3d'}
    </div>
  </div>

  <div class="arcgis-terrain3d-instructions">
    <p>{l s='Dibuja un polígono sobre el terreno para generar la malla 3D cerrada en la vista.' mod='arcgisterrain3d'}</p>
    <ul>
      <li>{l s='Usa la herramienta de dibujo (Sketch) para seleccionar un área.' mod='arcgisterrain3d'}</li>
      <li>{l s='Tras cerrar el polígono, se creará una malla 3D extruida con iluminación básica.' mod='arcgisterrain3d'}</li>
      <li>{l s='Puedes orbitar la cámara, hacer zoom y mover la vista libremente.' mod='arcgisterrain3d'}</li>
    </ul>
  </div>
</div>

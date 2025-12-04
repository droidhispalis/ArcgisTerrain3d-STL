{extends file='page.tpl'}

{block name='page_content'}
<div class="arc3d-download-page">
    {if $errors|@count > 0}
        <div class="alert alert-danger">
            {foreach from=$errors item=error}
                <p>{$error}</p>
            {/foreach}
        </div>
    {else}
        <div class="alert alert-success">
            <h3>Su pedido se ha enviado</h3>
            <p><strong>Nos pondremos en contacto con usted con las instrucciones para su proceso.</strong></p>
        </div>

        <div class="card">
            <div class="card-header">
                <h4>Pedido #{$order_id} - {$order_reference}</h4>
            </div>
            <div class="card-body">
                {if $has_session_data}
                    <div class="terrain-info">
                        <p><strong>Tipo:</strong> {$order_data.shape_type|capitalize}</p>
                        <p><strong>Coordenadas:</strong> {$order_data.latitude|string_format:"%.4f"}, {$order_data.longitude|string_format:"%.4f"}</p>
                        <p><strong>Área:</strong> {$order_data.area_km2|string_format:"%.2f"} km²</p>
                        <p><strong>Tamaño estimado:</strong> {$order_data.file_size_mb|string_format:"%.2f"} MB</p>
                        <p><strong>Precio:</strong> {$order_data.price|string_format:"%.2f"} €</p>
                    </div>
                    
                    <hr>
                    
                    <div class="download-section">
                        <h5>Descargar archivo STL</h5>
                        <p>Haga clic en el botón para descargar su modelo 3D personalizado:</p>
                        <button id="btnDownloadSTL" class="btn btn-primary btn-lg">
                            <i class="material-icons">cloud_download</i>
                            Descargar Terreno 3D (.stl)
                        </button>
                        <p class="text-muted mt-3"><small>Si el archivo no se descarga automáticamente, asegúrese de que el navegador permite descargas desde este sitio.</small></p>
                    </div>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var btnDownload = document.getElementById('btnDownloadSTL');
                        
                        btnDownload.addEventListener('click', function() {
                            // Intentar recuperar desde sessionStorage
                            var stlData = sessionStorage.getItem('arc3d_stl_data');
                            
                            if (!stlData) {
                                alert('El archivo no está disponible en esta sesión. Por favor, vuelva a generar el terreno desde el mapa.');
                                return;
                            }

                            try {
                                // Convertir base64 a blob
                                var byteCharacters = atob(stlData);
                                var byteNumbers = new Array(byteCharacters.length);
                                for (var i = 0; i < byteCharacters.length; i++) {
                                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                                }
                                var byteArray = new Uint8Array(byteNumbers);
                                var blob = new Blob([byteArray], { type: 'application/octet-stream' });
                                
                                // Crear enlace de descarga
                                var url = URL.createObjectURL(blob);
                                var link = document.createElement('a');
                                link.href = url;
                                link.download = 'terreno_3d_{$order_data.latitude|string_format:"%.4f"}_{$order_data.longitude|string_format:"%.4f"}.stl';
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                                URL.revokeObjectURL(url);
                                
                                // Limpiar sessionStorage después de descargar
                                sessionStorage.removeItem('arc3d_stl_data');
                                
                                alert('Descarga iniciada correctamente.');
                            } catch (e) {
                                console.error('Error al descargar:', e);
                                alert('Error al procesar el archivo. Por favor, contacte con soporte técnico.');
                            }
                        });
                    });
                    </script>
                {else}
                    <div class="alert alert-warning">
                        <p><strong>El archivo no está disponible en esta sesión.</strong></p>
                        <p>Los archivos STL se generan en el navegador y se almacenan temporalmente. Si ha cerrado la ventana o navegador, deberá volver a generar el terreno desde el mapa.</p>
                        <p><a href="{$link->getModuleLink('arcgisterrain3d', 'view')}" class="btn btn-secondary">Volver al mapa</a></p>
                    </div>
                {/if}
            </div>
        </div>

        <div class="mt-4">
            <a href="{$link->getPageLink('history')}" class="btn btn-outline-secondary">Ver mis pedidos</a>
            <a href="{$link->getModuleLink('arcgisterrain3d', 'view')}" class="btn btn-outline-primary">Generar nuevo terreno</a>
        </div>
    {/if}
</div>

<style>
.arc3d-download-page {
    max-width: 800px;
    margin: 2rem auto;
    padding: 1rem;
}

.terrain-info p {
    margin: 0.5rem 0;
    font-size: 1.1rem;
}

.download-section {
    text-align: center;
    padding: 2rem 0;
}

#btnDownloadSTL {
    padding: 1rem 2rem;
    font-size: 1.2rem;
}

#btnDownloadSTL i {
    vertical-align: middle;
    margin-right: 0.5rem;
}
</style>
{/block}

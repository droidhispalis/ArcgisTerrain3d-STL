# ArcGIS Terrain3D STL - Módulo PrestaShop

Módulo para PrestaShop 1.7+ que permite a los clientes generar y comprar modelos 3D STL de terrenos reales utilizando datos geográficos de ArcGIS.

## 🌟 Características

- **Visualización 3D Interactiva**: Navegación completa con mouse (rotación, zoom, paneo) usando Three.js
- **Integración ArcGIS**: Obtención de datos de elevación en tiempo real desde ArcGIS JS API 4.30
- **E-commerce Completo**: Integración nativa con el carrito de PrestaShop
- **Generación STL**: Conversión de datos geográficos a archivos STL imprimibles en 3D
- **Sistema de Pago**: Los archivos solo se generan después de confirmar el pago
- **Panel de Administración**: Carga de pedidos pagados para generar y descargar STL
- **Selección de Productos**: Sistema basado en categorías para diferentes precios/zonas
- **Validación de Pedidos**: Control de acceso y verificación de estado de pago

## 📋 Requisitos

- PrestaShop 1.7 o superior
- PHP 7.0 o superior
- MySQL 5.6 o superior
- Navegador compatible con WebGL
- Conexión a Internet (para API de ArcGIS)

## 🚀 Instalación

1. **Descargar el módulo**
   ```bash
   git clone https://github.com/droidhispalis/ArcgisTerrain3d-STL.git
   ```

2. **Subir a PrestaShop**
   - Copiar la carpeta `arcgisterrain3d` a `/modules/`
   - O comprimir en ZIP y subir desde el backoffice: Módulos > Module Manager > Subir un módulo

3. **Instalar el módulo**
   - Ir a Módulos > Module Manager
   - Buscar "ArcGIS Terrain3D"
   - Hacer clic en "Instalar"

4. **Configurar**
   - Ir a la configuración del módulo
   - Seleccionar la categoría de productos asociada
   - Configurar email de notificaciones (opcional)
   - Guardar cambios

## ⚙️ Configuración

### Crear Categoría de Productos

1. Crear una categoría en PrestaShop (ej: "Terrenos 3D")
2. Añadir productos con diferentes precios según área/región:
   - Producto básico (ej: 10€ - área pequeña)
   - Producto premium (ej: 25€ - área grande)
   - Producto personalizado (ej: 50€ - áreas especiales)

### Configuración del Módulo

En el backoffice de PrestaShop:
- **Módulos** > **ArcGIS Terrain3D** > **Configurar**
- Seleccionar la categoría de productos asociada
- Los productos de esta categoría aparecerán en el selector del frontend

## 📖 Uso

### Para Clientes

1. **Acceder al módulo**
   - Navegar a la URL del módulo (ej: `tutienda.com/module/arcgisterrain3d/view`)

2. **Seleccionar producto**
   - Elegir el producto deseado del menú desplegable

3. **Dibujar área**
   - Usar las herramientas de dibujo en el mapa ArcGIS
   - Seleccionar forma: rectángulo o círculo
   - Ajustar el área deseada

4. **Generar malla 3D**
   - Hacer clic en "Generar malla 3D"
   - Esperar a que se procesen los datos de elevación

5. **Vista previa**
   - Hacer clic en "Vista previa 3D"
   - Rotar con botón derecho del mouse
   - Zoom con rueda del mouse
   - Mover con botón izquierdo

6. **Añadir al carrito**
   - Hacer clic en "Añadir al carrito"
   - Completar el proceso de pago

7. **Descargar STL** (después del pago)
   - Volver al módulo
   - El administrador cargará tu pedido
   - Descargar el archivo STL

### Para Administradores

1. **Acceder como admin**
   - Iniciar sesión con cuenta de empleado de PrestaShop
   - Navegar al módulo desde el frontend

2. **Panel de administración**
   - Introducir número de pedido
   - Hacer clic en "Cargar"

3. **Verificación automática**
   - El sistema verifica que el pedido esté pagado
   - Carga los datos geográficos guardados

4. **Generar y exportar**
   - La malla 3D se regenera automáticamente
   - Hacer clic en "Exportar STL" para descargar

## 🗂️ Estructura del Proyecto

```
arcgisterrain3d/
├── arcgisterrain3d.php          # Módulo principal
├── controllers/
│   └── front/
│       ├── view.php              # Controlador de vista
│       ├── savemesh.php          # Guardar en carrito (AJAX)
│       └── loadorder.php         # Cargar pedido pagado (AJAX)
├── views/
│   ├── templates/
│   │   └── front/
│   │       └── map.tpl           # Template principal
│   ├── css/
│   │   └── arcgisterrain3d.css   # Estilos
│   └── js/
│       └── vendor/               # Librerías Three.js
├── uploads/                      # Directorio de archivos temporales
└── README.md                     # Este archivo
```

## 🔧 Tecnologías Utilizadas

- **Backend**: PHP, MySQL, PrestaShop API
- **Frontend**: JavaScript, Smarty Template Engine
- **3D Rendering**: Three.js r128 (TrackballControls)
- **Mapas**: ArcGIS JavaScript API 4.30
- **Formato 3D**: STL (Standard Tessellation Language)

## 📊 Base de Datos

El módulo crea automáticamente la tabla `arc3d_terrain_data`:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id_terrain | INT | ID único |
| id_cart | INT | ID del carrito |
| id_order | INT | ID del pedido (null hasta pagar) |
| id_product | INT | ID del producto seleccionado |
| product_name | VARCHAR | Nombre del producto |
| latitude | DECIMAL | Latitud del centro |
| longitude | DECIMAL | Longitud del centro |
| area_km2 | DECIMAL | Área en km² |
| shape_type | VARCHAR | Tipo: rectangle/circle |
| file_size_mb | DECIMAL | Tamaño estimado del STL |
| date_add | DATETIME | Fecha de creación |

## 🔐 Seguridad

- ✅ Validación de productos activos
- ✅ Verificación de inicio de sesión
- ✅ Control de acceso administrador
- ✅ Validación de estado de pago
- ✅ Escapado SQL con `pSQL()`
- ✅ Sin almacenamiento de archivos STL (se generan on-demand)
- ✅ Archivos PHP sin BOM (evita errores JSON)

## 🎨 Personalización

### Modificar Estilos

Editar `views/css/arcgisterrain3d.css`:
- Layout del mapa y panel
- Colores de botones
- Diseño del modal de vista previa
- Sección de administración

### Ajustar Calidad 3D

En `map.tpl`, modificar parámetros de TrackballControls:
```javascript
controls.rotateSpeed = 5.5;    // Velocidad de rotación
controls.zoomSpeed = 1.2;      // Velocidad de zoom
controls.panSpeed = 0.3;       // Velocidad de paneo
```

### Cambiar Resolución del Mesh

Ajustar `sampleSize` en la función de generación de malla:
```javascript
const sampleSize = 150;  // Más alto = más detalle, más lento
```

## 📧 Notificaciones por Email

El módulo envía emails automáticos:

1. **Email de carrito pendiente**: Cuando el cliente añade al carrito
2. **Email de confirmación de pago**: Cuando el pedido se marca como pagado

Configurar en: Módulos > ArcGIS Terrain3D > Configuración

## 🐛 Solución de Problemas

### Error: "Unexpected token in JSON"
- **Causa**: Archivos PHP con BOM
- **Solución**: Ya corregido. Los archivos se recrearon sin BOM

### La malla 3D no se genera
- Verificar selección de producto
- Comprobar conexión a Internet (API ArcGIS)
- Revisar consola del navegador (F12)

### No aparecen productos
- Verificar que la categoría seleccionada tenga productos activos
- Comprobar configuración del módulo

### Error al añadir al carrito
- Verificar que el cliente esté logueado
- Comprobar que el producto sea válido y activo

## 📄 Licencia

Este proyecto es de código abierto. Puedes usarlo, modificarlo y distribuirlo libremente.

## 👨‍💻 Autor

**droidhispalis**
- GitHub: [@droidhispalis](https://github.com/droidhispalis)
- Email: droidhispalis@gmail.com

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:
1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📝 Changelog

### v1.0.0 (2025-12-04)
- ✅ Implementación de rotación 3D libre con TrackballControls
- ✅ Integración completa con carrito de PrestaShop
- ✅ Sistema de categorías y productos
- ✅ Panel de administración para pedidos pagados
- ✅ Validación de productos antes de generar malla
- ✅ CSS organizado en archivo externo
- ✅ Corrección de errores BOM en archivos PHP
- ✅ Layout responsive 60/40 (mapa/panel)
- ✅ Emails de notificación automáticos
- ✅ Base de datos con tabla personalizada
- ✅ Generación STL on-demand (sin almacenamiento)

## 🔮 Roadmap

- [ ] Soporte para múltiples formatos de exportación (OBJ, FBX)
- [ ] Histórico de pedidos del cliente
- [ ] Texturas y colores personalizables
- [ ] Ajuste de escala vertical del terreno
- [ ] API REST para integraciones externas
- [ ] Soporte multiidioma
- [ ] Modo oscuro en la interfaz

---

**¿Necesitas ayuda?** Abre un [issue en GitHub](https://github.com/droidhispalis/ArcgisTerrain3d-STL/issues)

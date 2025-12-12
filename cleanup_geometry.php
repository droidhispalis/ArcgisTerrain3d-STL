<?php
/**
 * Script para limpiar geometry_json corrupto en la base de datos
 * Ejecutar una sola vez desde el navegador
 */

require_once(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/../../init.php');

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Limpieza de geometry_json corrupto</h2>\n";
echo "<pre>\n";

try {
    $sql = 'SELECT id_terrain, fingerprint, geometry_json FROM ' . _DB_PREFIX_ . 'arc3d_terrain_data';
    $results = Db::getInstance()->executeS($sql);
    
    echo "Registros encontrados: " . count($results) . "\n\n";
    
    $corrupted = 0;
    $fixed = 0;
    
    foreach ($results as $row) {
        $id = $row['id_terrain'];
        $fingerprint = $row['fingerprint'];
        $geomJson = $row['geometry_json'];
        
        // Verificar si es JSON válido
        $isValid = false;
        if (!empty($geomJson)) {
            $decoded = @json_decode($geomJson);
            $isValid = (json_last_error() === JSON_ERROR_NONE);
        }
        
        if (!$isValid) {
            $corrupted++;
            echo "ID $id ($fingerprint): JSON CORRUPTO\n";
            echo "  Contenido: " . substr($geomJson, 0, 100) . "...\n";
            
            // Limpiar: poner NULL
            $updateSql = 'UPDATE ' . _DB_PREFIX_ . 'arc3d_terrain_data SET geometry_json = NULL WHERE id_terrain = ' . (int)$id;
            if (Db::getInstance()->execute($updateSql)) {
                echo "  → LIMPIADO (NULL)\n";
                $fixed++;
            } else {
                echo "  → ERROR al limpiar\n";
            }
            echo "\n";
        } else {
            echo "ID $id ($fingerprint): OK\n";
        }
    }
    
    echo "\n=================================\n";
    echo "Resumen:\n";
    echo "  Total: " . count($results) . "\n";
    echo "  Corruptos: $corrupted\n";
    echo "  Limpiados: $fixed\n";
    echo "=================================\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
echo "<p><a href='view'>Volver al módulo</a></p>\n";
?>

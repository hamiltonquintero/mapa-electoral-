-- Inicialización de PostgreSQL con PostGIS para Mapa Electoral

-- Crear extensión PostGIS
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS postgis_topology;

-- Crear extensión para UUID
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Crear extensión para funciones criptográficas
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Verificar instalación
SELECT PostGIS_Version();

-- Configurar búsqueda de schemas
ALTER DATABASE mapa_electoral SET search_path TO public, postgis;

-- Comentarios
COMMENT ON EXTENSION postgis IS 'PostGIS geometry and geography spatial types and functions';

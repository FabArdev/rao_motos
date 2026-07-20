package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.LinkedHashMap;
import java.util.Map;

/**
 * DAO de `configuracion` (parámetros clave/valor con defaults): tasas, márgenes,
 * días entre cuotas, etc. Fuente de los valores configurables del negocio.
 */
public class ConfiguracionM {

    /** Valor de una clave, o el default indicado si no existe. */
    public static String valor(String clave, String porDefecto) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement("SELECT valor FROM configuracion WHERE clave = ?")) {
            ps.setString(1, clave);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return rs.getString("valor");
            }
        }
        return porDefecto;
    }

    /** Valor numérico de una clave, o el default si no existe / no es número. */
    public static double valorNum(String clave, double porDefecto) throws SQLException {
        String v = valor(clave, null);
        if (v == null) return porDefecto;
        try { return Double.parseDouble(v.trim()); } catch (NumberFormatException e) { return porDefecto; }
    }

    /** Crea o actualiza una clave de configuración. */
    public static void set(String clave, String valor) throws SQLException {
        String sql = "INSERT INTO configuracion (clave, valor) VALUES (?, ?) " +
                     "ON CONFLICT (clave) DO UPDATE SET valor = EXCLUDED.valor, actualizado_en = CURRENT_TIMESTAMP";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, clave);
            ps.setString(2, valor);
            ps.executeUpdate();
        }
    }

    /** Todas las configuraciones (clave → valor), ordenadas por clave. */
    public static Map<String, String> obtenerTodas() throws SQLException {
        Map<String, String> mapa = new LinkedHashMap<>();
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement("SELECT clave, valor FROM configuracion ORDER BY clave");
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) mapa.put(rs.getString("clave"), rs.getString("valor"));
        }
        return mapa;
    }
}

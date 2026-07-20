package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * DAO de la tabla `rol` (admin | vendedor | almacenero | cliente).
 * Reemplaza al antiguo `propietario`: ahora el rol del usuario es una FK rol_id → rol.
 */
public class RolM {
    private int id;
    private String nombre;
    private String descripcion;

    public RolM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public String getNombre() { return nombre; }
    public void setNombre(String nombre) { this.nombre = nombre; }
    public String getDescripcion() { return descripcion; }
    public void setDescripcion(String descripcion) { this.descripcion = descripcion; }

    private static RolM mapear(ResultSet rs) throws SQLException {
        RolM r = new RolM();
        r.setId(rs.getInt("id"));
        r.setNombre(rs.getString("nombre"));
        r.setDescripcion(rs.getString("descripcion"));
        return r;
    }

    public static List<RolM> obtenerTodos() throws SQLException {
        List<RolM> lista = new ArrayList<>();
        String sql = "SELECT * FROM rol ORDER BY id";
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(sql);
             ResultSet rs = pstmt.executeQuery()) {
            while (rs.next()) lista.add(mapear(rs));
        }
        return lista;
    }

    /** Busca un rol por nombre (case-insensitive). Retorna null si no existe. */
    public static RolM buscarPorNombre(String nombre) throws SQLException {
        String sql = "SELECT * FROM rol WHERE LOWER(nombre) = LOWER(?)";
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setString(1, nombre);
            try (ResultSet rs = pstmt.executeQuery()) {
                if (rs.next()) return mapear(rs);
            }
        }
        return null;
    }

    /** Devuelve el id del rol por su nombre, o 0 si no existe. */
    public static int idPorNombre(String nombre) throws SQLException {
        RolM r = buscarPorNombre(nombre);
        return r != null ? r.getId() : 0;
    }

    /** Devuelve el nombre del rol por su id, o null. */
    public static String nombrePorId(int id) throws SQLException {
        String sql = "SELECT nombre FROM rol WHERE id = ?";
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            try (ResultSet rs = pstmt.executeQuery()) {
                if (rs.next()) return rs.getString("nombre");
            }
        }
        return null;
    }
}

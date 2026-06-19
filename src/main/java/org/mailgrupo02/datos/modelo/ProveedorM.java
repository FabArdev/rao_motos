package org.mailgrupo02.datos.modelo;

import org.mailgrupo02.datos.conexion.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class ProveedorM {
    private int id;
    private String razonSocial;
    private String contactoPrincipal;
    private String telefono;
    private boolean activo;

    public ProveedorM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public String getRazonSocial() { return razonSocial; }
    public void setRazonSocial(String razonSocial) { this.razonSocial = razonSocial; }
    public String getContactoPrincipal() { return contactoPrincipal; }
    public void setContactoPrincipal(String contactoPrincipal) { this.contactoPrincipal = contactoPrincipal; }
    public String getTelefono() { return telefono; }
    public void setTelefono(String telefono) { this.telefono = telefono; }
    public boolean isActivo() { return activo; }
    public void setActivo(boolean activo) { this.activo = activo; }

    // ── Crear proveedor independiente; retorna el id generado ─────────────────
    public int crear() throws SQLException {
        String sql = "INSERT INTO proveedor (razon_social, contacto_principal, telefono, activo) " +
                     "VALUES (?, ?, ?, ?) RETURNING id";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, this.razonSocial);
            ps.setString(2, this.contactoPrincipal);
            ps.setString(3, this.telefono);
            ps.setBoolean(4, this.activo);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return rs.getInt(1);
            }
        }
        throw new SQLException("No se pudo crear el proveedor.");
    }

    public static ProveedorM leer(int id) throws SQLException {
        String sql = "SELECT * FROM proveedor WHERE id = ?";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return mapear(rs);
            }
        }
        return null;
    }

    public static List<ProveedorM> obtenerTodos() throws SQLException {
        List<ProveedorM> lista = new ArrayList<>();
        String sql = "SELECT * FROM proveedor ORDER BY id";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql);
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) lista.add(mapear(rs));
        }
        return lista;
    }

    public String actualizar() throws SQLException {
        String sql = "UPDATE proveedor SET razon_social=?, contacto_principal=?, telefono=?, activo=? WHERE id=?";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, this.razonSocial);
            ps.setString(2, this.contactoPrincipal);
            ps.setString(3, this.telefono);
            ps.setBoolean(4, this.activo);
            ps.setInt(5, this.id);
            ps.executeUpdate();
        }
        return "Proveedor actualizado exitosamente";
    }

    public static String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM proveedor WHERE id = ?";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, id);
            int rows = ps.executeUpdate();
            return rows > 0
                ? "Proveedor eliminado exitosamente"
                : "Error: Proveedor no encontrado.";
        }
    }

    private static ProveedorM mapear(ResultSet rs) throws SQLException {
        ProveedorM p = new ProveedorM();
        p.id               = rs.getInt("id");
        p.razonSocial      = rs.getString("razon_social");
        p.contactoPrincipal = rs.getString("contacto_principal");
        p.telefono         = rs.getString("telefono");
        p.activo           = rs.getBoolean("activo");
        return p;
    }
}

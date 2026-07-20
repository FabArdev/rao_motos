package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/** DAO del catálogo `metodo_pago` (EFECTIVO, QR). */
public class MetodoPagoM {
    private int id;
    private String nombre;
    private boolean activo;

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public String getNombre() { return nombre; }
    public void setNombre(String nombre) { this.nombre = nombre; }
    public boolean isActivo() { return activo; }
    public void setActivo(boolean activo) { this.activo = activo; }

    private static MetodoPagoM mapear(ResultSet rs) throws SQLException {
        MetodoPagoM m = new MetodoPagoM();
        m.setId(rs.getInt("id"));
        m.setNombre(rs.getString("nombre"));
        m.setActivo(rs.getBoolean("activo"));
        return m;
    }

    public static List<MetodoPagoM> obtenerTodos() throws SQLException {
        List<MetodoPagoM> lista = new ArrayList<>();
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement("SELECT * FROM metodo_pago ORDER BY id");
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) lista.add(mapear(rs));
        }
        return lista;
    }

    /** Devuelve el id del método por su nombre (EFECTIVO/QR), o 0 si no existe. */
    public static int idPorNombre(String nombre) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement("SELECT id FROM metodo_pago WHERE LOWER(nombre)=LOWER(?)")) {
            ps.setString(1, nombre);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return rs.getInt("id");
            }
        }
        return 0;
    }
}

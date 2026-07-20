package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class MovimientoInventarioM {
    private int id;
    private int inventarioId;
    private String tipoMovimiento;
    private int cantidad;
    private String motivo;
    private Timestamp fecha;

    public MovimientoInventarioM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getInventarioId() { return inventarioId; }
    public void setInventarioId(int inventarioId) { this.inventarioId = inventarioId; }
    public String getTipoMovimiento() { return tipoMovimiento; }
    public void setTipoMovimiento(String tipoMovimiento) { this.tipoMovimiento = tipoMovimiento; }
    public int getCantidad() { return cantidad; }
    public void setCantidad(int cantidad) { this.cantidad = cantidad; }
    public String getMotivo() { return motivo; }
    public void setMotivo(String motivo) { this.motivo = motivo; }
    public Timestamp getFecha() { return fecha; }
    public void setFecha(Timestamp fecha) { this.fecha = fecha; }

    public String crear() throws SQLException {
        String sql = "INSERT INTO movimiento_inventario (inventario_id, tipo_movimiento, cantidad, motivo, fecha) VALUES (?, ?, ?, ?, ?)";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, this.inventarioId);
            pstmt.setString(2, this.tipoMovimiento);
            pstmt.setInt(3, this.cantidad);
            pstmt.setString(4, this.motivo);
            pstmt.setTimestamp(5, this.fecha);
            pstmt.executeUpdate();
            return "Movimiento de inventario creado exitosamente";
        }
    }

    public MovimientoInventarioM leer(int id) throws SQLException {
        String sql = "SELECT * FROM movimiento_inventario WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            ResultSet rs = pstmt.executeQuery();
            if (rs.next()) {
                this.id = rs.getInt("id");
                this.inventarioId = rs.getInt("inventario_id");
                this.tipoMovimiento = rs.getString("tipo_movimiento");
                this.cantidad = rs.getInt("cantidad");
                this.motivo = rs.getString("motivo");
                this.fecha = rs.getTimestamp("fecha");
                return this;
            }
            return null;
        }
    }

    public List<MovimientoInventarioM> obtenerTodos() throws SQLException {
        List<MovimientoInventarioM> lista = new ArrayList<>();
        String sql = "SELECT * FROM movimiento_inventario ORDER BY id";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql);
                ResultSet rs = pstmt.executeQuery()) {
            while (rs.next()) {
                MovimientoInventarioM obj = new MovimientoInventarioM();
                obj.setId(rs.getInt("id"));
                obj.setInventarioId(rs.getInt("inventario_id"));
                obj.setTipoMovimiento(rs.getString("tipo_movimiento"));
                obj.setCantidad(rs.getInt("cantidad"));
                obj.setMotivo(rs.getString("motivo"));
                obj.setFecha(rs.getTimestamp("fecha"));
                lista.add(obj);
            }
        }
        return lista;
    }

    public List<MovimientoInventarioM> obtenerPorInventario(int inventarioId) throws SQLException {
        List<MovimientoInventarioM> lista = new ArrayList<>();
        String sql = "SELECT * FROM movimiento_inventario WHERE inventario_id = ? ORDER BY fecha";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, inventarioId);
            ResultSet rs = pstmt.executeQuery();
            while (rs.next()) {
                MovimientoInventarioM obj = new MovimientoInventarioM();
                obj.setId(rs.getInt("id"));
                obj.setInventarioId(rs.getInt("inventario_id"));
                obj.setTipoMovimiento(rs.getString("tipo_movimiento"));
                obj.setCantidad(rs.getInt("cantidad"));
                obj.setMotivo(rs.getString("motivo"));
                obj.setFecha(rs.getTimestamp("fecha"));
                lista.add(obj);
            }
        }
        return lista;
    }

    public String actualizar() throws SQLException {
        String sql = "UPDATE movimiento_inventario SET inventario_id = ?, tipo_movimiento = ?, cantidad = ?, motivo = ?, fecha = ? WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, this.inventarioId);
            pstmt.setString(2, this.tipoMovimiento);
            pstmt.setInt(3, this.cantidad);
            pstmt.setString(4, this.motivo);
            pstmt.setTimestamp(5, this.fecha);
            pstmt.setInt(6, this.id);
            pstmt.executeUpdate();
            return "Movimiento de inventario actualizado exitosamente";
        }
    }

    public String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM movimiento_inventario WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            pstmt.executeUpdate();
            return "Movimiento de inventario eliminado exitosamente";
        }
    }
}

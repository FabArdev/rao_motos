package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class CompraM {
    private int id;
    private int proveedorId;
    private Timestamp fecha;
    private double total;
    private String estado;

    public CompraM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getProveedorId() { return proveedorId; }
    public void setProveedorId(int proveedorId) { this.proveedorId = proveedorId; }
    public Timestamp getFecha() { return fecha; }
    public void setFecha(Timestamp fecha) { this.fecha = fecha; }
    public double getTotal() { return total; }
    public void setTotal(double total) { this.total = total; }
    public String getEstado() { return estado; }
    public void setEstado(String estado) { this.estado = estado; }

    public String crear() throws SQLException {
        String sql = "INSERT INTO compra (proveedor_id, fecha, total, estado) VALUES (?, ?, ?, ?)";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, this.proveedorId);
            pstmt.setTimestamp(2, this.fecha);
            pstmt.setDouble(3, this.total);
            pstmt.setString(4, this.estado);
            pstmt.executeUpdate();
            return "Compra creada exitosamente";
        }
    }

    public CompraM leer(int id) throws SQLException {
        String sql = "SELECT * FROM compra WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            ResultSet rs = pstmt.executeQuery();
            if (rs.next()) {
                this.id = rs.getInt("id");
                this.proveedorId = rs.getInt("proveedor_id");
                this.fecha = rs.getTimestamp("fecha");
                this.total = rs.getDouble("total");
                this.estado = rs.getString("estado");
                return this;
            }
            return null;
        }
    }

    public List<CompraM> obtenerTodos() throws SQLException {
        List<CompraM> lista = new ArrayList<>();
        String sql = "SELECT * FROM compra ORDER BY id";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql);
                ResultSet rs = pstmt.executeQuery()) {
            while (rs.next()) {
                CompraM obj = new CompraM();
                obj.setId(rs.getInt("id"));
                obj.setProveedorId(rs.getInt("proveedor_id"));
                obj.setFecha(rs.getTimestamp("fecha"));
                obj.setTotal(rs.getDouble("total"));
                obj.setEstado(rs.getString("estado"));
                lista.add(obj);
            }
        }
        return lista;
    }

    public String actualizar() throws SQLException {
        String sql = "UPDATE compra SET proveedor_id = ?, fecha = ?, total = ?, estado = ? WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, this.proveedorId);
            pstmt.setTimestamp(2, this.fecha);
            pstmt.setDouble(3, this.total);
            pstmt.setString(4, this.estado);
            pstmt.setInt(5, this.id);
            pstmt.executeUpdate();
            return "Compra actualizada exitosamente";
        }
    }

    public String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM compra WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            pstmt.executeUpdate();
            return "Compra eliminada exitosamente";
        }
    }
}

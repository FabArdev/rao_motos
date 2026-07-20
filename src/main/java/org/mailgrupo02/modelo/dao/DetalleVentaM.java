package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class DetalleVentaM {
    private int id;
    private int ventaId;
    private int productoId;
    private int cantidad;
    private double precioUnitario;

    public DetalleVentaM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getVentaId() { return ventaId; }
    public void setVentaId(int ventaId) { this.ventaId = ventaId; }
    public int getProductoId() { return productoId; }
    public void setProductoId(int productoId) { this.productoId = productoId; }
    public int getCantidad() { return cantidad; }
    public void setCantidad(int cantidad) { this.cantidad = cantidad; }
    public double getPrecioUnitario() { return precioUnitario; }
    public void setPrecioUnitario(double precioUnitario) { this.precioUnitario = precioUnitario; }

    public String crear() throws SQLException {
        String sql = "INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, this.ventaId);
            pstmt.setInt(2, this.productoId);
            pstmt.setInt(3, this.cantidad);
            pstmt.setDouble(4, this.precioUnitario);
            pstmt.executeUpdate();
            return "Detalle de venta creado exitosamente";
        }
    }

    public DetalleVentaM leer(int id) throws SQLException {
        String sql = "SELECT * FROM detalle_venta WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            ResultSet rs = pstmt.executeQuery();
            if (rs.next()) {
                this.id = rs.getInt("id");
                this.ventaId = rs.getInt("venta_id");
                this.productoId = rs.getInt("producto_id");
                this.cantidad = rs.getInt("cantidad");
                this.precioUnitario = rs.getDouble("precio_unitario");
                return this;
            }
            return null;
        }
    }

    public List<DetalleVentaM> obtenerTodos() throws SQLException {
        List<DetalleVentaM> lista = new ArrayList<>();
        String sql = "SELECT * FROM detalle_venta ORDER BY id";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql);
                ResultSet rs = pstmt.executeQuery()) {
            while (rs.next()) {
                DetalleVentaM obj = new DetalleVentaM();
                obj.setId(rs.getInt("id"));
                obj.setVentaId(rs.getInt("venta_id"));
                obj.setProductoId(rs.getInt("producto_id"));
                obj.setCantidad(rs.getInt("cantidad"));
                obj.setPrecioUnitario(rs.getDouble("precio_unitario"));
                lista.add(obj);
            }
        }
        return lista;
    }

    public List<DetalleVentaM> obtenerPorVenta(int ventaId) throws SQLException {
        List<DetalleVentaM> lista = new ArrayList<>();
        String sql = "SELECT * FROM detalle_venta WHERE venta_id = ? ORDER BY id";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, ventaId);
            ResultSet rs = pstmt.executeQuery();
            while (rs.next()) {
                DetalleVentaM obj = new DetalleVentaM();
                obj.setId(rs.getInt("id"));
                obj.setVentaId(rs.getInt("venta_id"));
                obj.setProductoId(rs.getInt("producto_id"));
                obj.setCantidad(rs.getInt("cantidad"));
                obj.setPrecioUnitario(rs.getDouble("precio_unitario"));
                lista.add(obj);
            }
        }
        return lista;
    }

    public String actualizar() throws SQLException {
        String sql = "UPDATE detalle_venta SET venta_id = ?, producto_id = ?, cantidad = ?, precio_unitario = ? WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, this.ventaId);
            pstmt.setInt(2, this.productoId);
            pstmt.setInt(3, this.cantidad);
            pstmt.setDouble(4, this.precioUnitario);
            pstmt.setInt(5, this.id);
            pstmt.executeUpdate();
            return "Detalle de venta actualizado exitosamente";
        }
    }

    public String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM detalle_venta WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            pstmt.executeUpdate();
            return "Detalle de venta eliminado exitosamente";
        }
    }
}

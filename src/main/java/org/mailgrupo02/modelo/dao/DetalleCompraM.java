package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class DetalleCompraM {
    private int id;
    private int compraId;
    private int productoId;
    private int cantidad;
    private double precioUnitario;

    public DetalleCompraM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getCompraId() { return compraId; }
    public void setCompraId(int compraId) { this.compraId = compraId; }
    public int getProductoId() { return productoId; }
    public void setProductoId(int productoId) { this.productoId = productoId; }
    public int getCantidad() { return cantidad; }
    public void setCantidad(int cantidad) { this.cantidad = cantidad; }
    public double getPrecioUnitario() { return precioUnitario; }
    public void setPrecioUnitario(double precioUnitario) { this.precioUnitario = precioUnitario; }

    public String crear() throws SQLException {
        String sql = "INSERT INTO detalle_compra (compra_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, this.compraId);
            pstmt.setInt(2, this.productoId);
            pstmt.setInt(3, this.cantidad);
            pstmt.setDouble(4, this.precioUnitario);
            pstmt.executeUpdate();
            return "Detalle de compra creado exitosamente";
        }
    }

    public DetalleCompraM leer(int id) throws SQLException {
        String sql = "SELECT * FROM detalle_compra WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            ResultSet rs = pstmt.executeQuery();
            if (rs.next()) {
                this.id = rs.getInt("id");
                this.compraId = rs.getInt("compra_id");
                this.productoId = rs.getInt("producto_id");
                this.cantidad = rs.getInt("cantidad");
                this.precioUnitario = rs.getDouble("precio_unitario");
                return this;
            }
            return null;
        }
    }

    public List<DetalleCompraM> obtenerTodos() throws SQLException {
        List<DetalleCompraM> lista = new ArrayList<>();
        String sql = "SELECT * FROM detalle_compra ORDER BY id";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql);
                ResultSet rs = pstmt.executeQuery()) {
            while (rs.next()) {
                DetalleCompraM obj = new DetalleCompraM();
                obj.setId(rs.getInt("id"));
                obj.setCompraId(rs.getInt("compra_id"));
                obj.setProductoId(rs.getInt("producto_id"));
                obj.setCantidad(rs.getInt("cantidad"));
                obj.setPrecioUnitario(rs.getDouble("precio_unitario"));
                lista.add(obj);
            }
        }
        return lista;
    }

    public List<DetalleCompraM> obtenerPorCompra(int compraId) throws SQLException {
        List<DetalleCompraM> lista = new ArrayList<>();
        String sql = "SELECT * FROM detalle_compra WHERE compra_id = ? ORDER BY id";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, compraId);
            ResultSet rs = pstmt.executeQuery();
            while (rs.next()) {
                DetalleCompraM obj = new DetalleCompraM();
                obj.setId(rs.getInt("id"));
                obj.setCompraId(rs.getInt("compra_id"));
                obj.setProductoId(rs.getInt("producto_id"));
                obj.setCantidad(rs.getInt("cantidad"));
                obj.setPrecioUnitario(rs.getDouble("precio_unitario"));
                lista.add(obj);
            }
        }
        return lista;
    }

    public String actualizar() throws SQLException {
        String sql = "UPDATE detalle_compra SET compra_id = ?, producto_id = ?, cantidad = ?, precio_unitario = ? WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, this.compraId);
            pstmt.setInt(2, this.productoId);
            pstmt.setInt(3, this.cantidad);
            pstmt.setDouble(4, this.precioUnitario);
            pstmt.setInt(5, this.id);
            pstmt.executeUpdate();
            return "Detalle de compra actualizado exitosamente";
        }
    }

    public String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM detalle_compra WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            pstmt.executeUpdate();
            return "Detalle de compra eliminado exitosamente";
        }
    }
}

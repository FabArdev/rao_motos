package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class PedidoDetalleM {
    private int id;
    private int pedidoId;
    private int productoId;
    private int cantidad;

    public PedidoDetalleM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getPedidoId() { return pedidoId; }
    public void setPedidoId(int pedidoId) { this.pedidoId = pedidoId; }
    public int getProductoId() { return productoId; }
    public void setProductoId(int productoId) { this.productoId = productoId; }
    public int getCantidad() { return cantidad; }
    public void setCantidad(int cantidad) { this.cantidad = cantidad; }

    public static String crear(PedidoDetalleM detalle) throws SQLException {
        String sql = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad) VALUES (?, ?, ?)";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, detalle.pedidoId);
            pstmt.setInt(2, detalle.productoId);
            pstmt.setInt(3, detalle.cantidad);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Detalle creado con éxito" : "Error al crear detalle";
        } catch (SQLException e) {
            throw new SQLException("Error al crear detalle: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static PedidoDetalleM leer(int id) throws SQLException {
        String sql = "SELECT * FROM detalle_pedido WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, id);
            rs = pstmt.executeQuery();
            if (rs.next()) {
                PedidoDetalleM detalle = new PedidoDetalleM();
                detalle.setId(rs.getInt("id"));
                detalle.setPedidoId(rs.getInt("pedido_id"));
                detalle.setProductoId(rs.getInt("producto_id"));
                detalle.setCantidad(rs.getInt("cantidad"));
                return detalle;
            }
            throw new SQLException("Detalle no encontrado");
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static List<PedidoDetalleM> obtenerTodos() throws SQLException {
        List<PedidoDetalleM> detalles = new ArrayList<>();
        String sql = "SELECT * FROM detalle_pedido ORDER BY id";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                PedidoDetalleM detalle = new PedidoDetalleM();
                detalle.setId(rs.getInt("id"));
                detalle.setPedidoId(rs.getInt("pedido_id"));
                detalle.setProductoId(rs.getInt("producto_id"));
                detalle.setCantidad(rs.getInt("cantidad"));
                detalles.add(detalle);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return detalles;
    }

    public static List<PedidoDetalleM> obtenerPorPedido(int pedidoId) throws SQLException {
        List<PedidoDetalleM> detalles = new ArrayList<>();
        String sql = "SELECT * FROM detalle_pedido WHERE pedido_id = ? ORDER BY id";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, pedidoId);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                PedidoDetalleM detalle = new PedidoDetalleM();
                detalle.setId(rs.getInt("id"));
                detalle.setPedidoId(rs.getInt("pedido_id"));
                detalle.setProductoId(rs.getInt("producto_id"));
                detalle.setCantidad(rs.getInt("cantidad"));
                detalles.add(detalle);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return detalles;
    }

    public static String actualizar(PedidoDetalleM detalle) throws SQLException {
        String sql = "UPDATE detalle_pedido SET pedido_id=?, producto_id=?, cantidad=? WHERE id=?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, detalle.pedidoId);
            pstmt.setInt(2, detalle.productoId);
            pstmt.setInt(3, detalle.cantidad);
            pstmt.setInt(4, detalle.id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Detalle actualizado con éxito" : "Detalle no encontrado";
        } catch (SQLException e) {
            throw new SQLException("Error al actualizar detalle: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM detalle_pedido WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Detalle eliminado con éxito" : "Detalle no encontrado";
        } catch (SQLException e) {
            throw new SQLException("Error al eliminar detalle: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }
}

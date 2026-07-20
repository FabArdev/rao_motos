package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class PedidoM {
    private int id;
    private int clienteId;
    private Timestamp fecha;
    private String estado;

    public PedidoM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getClienteId() { return clienteId; }
    public void setClienteId(int clienteId) { this.clienteId = clienteId; }
    public Timestamp getFecha() { return fecha; }
    public void setFecha(Timestamp fecha) { this.fecha = fecha; }
    public String getEstado() { return estado; }
    public void setEstado(String estado) { this.estado = estado; }

    public static int crear(PedidoM pedido) throws SQLException {
        String sql = "INSERT INTO pedido (cliente_id, fecha, estado) VALUES (?, ?, ?)";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql, PreparedStatement.RETURN_GENERATED_KEYS);
            pstmt.setInt(1, pedido.clienteId);
            pstmt.setTimestamp(2, pedido.fecha);
            pstmt.setString(3, pedido.estado);
            pstmt.executeUpdate();
            rs = pstmt.getGeneratedKeys();
            if (rs.next()) return rs.getInt(1);
            throw new SQLException("No se pudo obtener el ID del pedido creado");
        } catch (SQLException e) {
            throw new SQLException("Error al crear pedido: " + e.getMessage());
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static List<PedidoM> obtenerPorCliente(int clienteId) throws SQLException {
        List<PedidoM> pedidos = new ArrayList<>();
        String sql = "SELECT * FROM pedido WHERE cliente_id = ? ORDER BY id DESC";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, clienteId);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                PedidoM p = new PedidoM();
                p.setId(rs.getInt("id"));
                p.setClienteId(rs.getInt("cliente_id"));
                p.setFecha(rs.getTimestamp("fecha"));
                p.setEstado(rs.getString("estado"));
                pedidos.add(p);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return pedidos;
    }

    public static PedidoM leer(int id) throws SQLException {
        String sql = "SELECT * FROM pedido WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, id);
            rs = pstmt.executeQuery();
            if (rs.next()) {
                PedidoM pedido = new PedidoM();
                pedido.setId(rs.getInt("id"));
                pedido.setClienteId(rs.getInt("cliente_id"));
                pedido.setFecha(rs.getTimestamp("fecha"));
                pedido.setEstado(rs.getString("estado"));
                return pedido;
            }
            throw new SQLException("Pedido no encontrado");
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static List<PedidoM> obtenerTodos() throws SQLException {
        List<PedidoM> pedidos = new ArrayList<>();
        String sql = "SELECT * FROM pedido ORDER BY id";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                PedidoM pedido = new PedidoM();
                pedido.setId(rs.getInt("id"));
                pedido.setClienteId(rs.getInt("cliente_id"));
                pedido.setFecha(rs.getTimestamp("fecha"));
                pedido.setEstado(rs.getString("estado"));
                pedidos.add(pedido);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return pedidos;
    }

    public static String actualizar(PedidoM pedido) throws SQLException {
        String sql = "UPDATE pedido SET cliente_id=?, fecha=?, estado=? WHERE id=?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, pedido.clienteId);
            pstmt.setTimestamp(2, pedido.fecha);
            pstmt.setString(3, pedido.estado);
            pstmt.setInt(4, pedido.id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Pedido actualizado con éxito" : "Pedido no encontrado";
        } catch (SQLException e) {
            throw new SQLException("Error al actualizar pedido: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM pedido WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Pedido eliminado con éxito" : "Pedido no encontrado";
        } catch (SQLException e) {
            throw new SQLException("Error al eliminar pedido: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }
}

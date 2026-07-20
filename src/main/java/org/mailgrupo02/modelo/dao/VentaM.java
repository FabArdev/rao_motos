package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class VentaM {
    private int id;
    private int clienteId;
    private Timestamp fecha;
    private double montoTotal;
    private String tipoVenta;
    private String metodoPago;
    private String estado;

    public VentaM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getClienteId() { return clienteId; }
    public void setClienteId(int clienteId) { this.clienteId = clienteId; }
    public Timestamp getFecha() { return fecha; }
    public void setFecha(Timestamp fecha) { this.fecha = fecha; }
    public double getMontoTotal() { return montoTotal; }
    public void setMontoTotal(double montoTotal) { this.montoTotal = montoTotal; }
    public String getTipoVenta() { return tipoVenta; }
    public void setTipoVenta(String tipoVenta) { this.tipoVenta = tipoVenta; }
    public String getMetodoPago() { return metodoPago; }
    public void setMetodoPago(String metodoPago) { this.metodoPago = metodoPago; }
    public String getEstado() { return estado; }
    public void setEstado(String estado) { this.estado = estado; }

    public static int crear(VentaM venta) throws SQLException {
        String sql = "INSERT INTO venta (cliente_id, fecha, monto_total, tipo_venta, metodo_pago, estado) VALUES (?, ?, ?, ?, ?, ?)";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql, PreparedStatement.RETURN_GENERATED_KEYS);
            pstmt.setInt(1, venta.clienteId);
            pstmt.setTimestamp(2, venta.fecha);
            pstmt.setDouble(3, venta.montoTotal);
            pstmt.setString(4, venta.tipoVenta);
            pstmt.setString(5, venta.metodoPago);
            pstmt.setString(6, venta.estado);
            pstmt.executeUpdate();
            rs = pstmt.getGeneratedKeys();
            if (rs.next()) {
                return rs.getInt(1);
            }
            throw new SQLException("No se pudo obtener el ID de la venta creada");
        } catch (SQLException e) {
            throw new SQLException("Error al crear venta: " + e.getMessage());
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static VentaM leer(int id) throws SQLException {
        String sql = "SELECT * FROM venta WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, id);
            rs = pstmt.executeQuery();
            if (rs.next()) {
                VentaM venta = new VentaM();
                venta.setId(rs.getInt("id"));
                venta.setClienteId(rs.getInt("cliente_id"));
                venta.setFecha(rs.getTimestamp("fecha"));
                venta.setMontoTotal(rs.getDouble("monto_total"));
                venta.setTipoVenta(rs.getString("tipo_venta"));
                venta.setMetodoPago(rs.getString("metodo_pago"));
                venta.setEstado(rs.getString("estado"));
                return venta;
            }
            throw new SQLException("Venta no encontrada");
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static List<VentaM> obtenerTodos() throws SQLException {
        List<VentaM> ventas = new ArrayList<>();
        String sql = "SELECT * FROM venta ORDER BY id";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                VentaM venta = new VentaM();
                venta.setId(rs.getInt("id"));
                venta.setClienteId(rs.getInt("cliente_id"));
                venta.setFecha(rs.getTimestamp("fecha"));
                venta.setMontoTotal(rs.getDouble("monto_total"));
                venta.setTipoVenta(rs.getString("tipo_venta"));
                venta.setMetodoPago(rs.getString("metodo_pago"));
                venta.setEstado(rs.getString("estado"));
                ventas.add(venta);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return ventas;
    }

    public static List<VentaM> obtenerPorCliente(int clienteId) throws SQLException {
        List<VentaM> ventas = new ArrayList<>();
        String sql = "SELECT * FROM venta WHERE cliente_id = ? ORDER BY fecha DESC";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, clienteId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    VentaM v = new VentaM();
                    v.setId(rs.getInt("id"));
                    v.setClienteId(rs.getInt("cliente_id"));
                    v.setFecha(rs.getTimestamp("fecha"));
                    v.setMontoTotal(rs.getDouble("monto_total"));
                    v.setTipoVenta(rs.getString("tipo_venta"));
                    v.setMetodoPago(rs.getString("metodo_pago"));
                    v.setEstado(rs.getString("estado"));
                    ventas.add(v);
                }
            }
        }
        return ventas;
    }

    public static String actualizar(VentaM venta) throws SQLException {
        String sql = "UPDATE venta SET cliente_id=?, fecha=?, monto_total=?, tipo_venta=?, metodo_pago=?, estado=? WHERE id=?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, venta.clienteId);
            pstmt.setTimestamp(2, venta.fecha);
            pstmt.setDouble(3, venta.montoTotal);
            pstmt.setString(4, venta.tipoVenta);
            pstmt.setString(5, venta.metodoPago);
            pstmt.setString(6, venta.estado);
            pstmt.setInt(7, venta.id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Venta actualizada con éxito" : "Venta no encontrada";
        } catch (SQLException e) {
            throw new SQLException("Error al actualizar venta: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM venta WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Venta eliminada con éxito" : "Venta no encontrada";
        } catch (SQLException e) {
            throw new SQLException("Error al eliminar venta: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }
}

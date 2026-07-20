package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class CreditoM {
    private int id;
    private int ventaId;
    private int numeroCuotas;
    private double tasaInteres;
    private double saldoPendiente;
    private String estado;

    public CreditoM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getVentaId() { return ventaId; }
    public void setVentaId(int ventaId) { this.ventaId = ventaId; }
    public int getNumeroCuotas() { return numeroCuotas; }
    public void setNumeroCuotas(int numeroCuotas) { this.numeroCuotas = numeroCuotas; }
    public double getTasaInteres() { return tasaInteres; }
    public void setTasaInteres(double tasaInteres) { this.tasaInteres = tasaInteres; }
    public double getSaldoPendiente() { return saldoPendiente; }
    public void setSaldoPendiente(double saldoPendiente) { this.saldoPendiente = saldoPendiente; }
    public String getEstado() { return estado; }
    public void setEstado(String estado) { this.estado = estado; }

    public static int crear(CreditoM credito) throws SQLException {
        String sql = "INSERT INTO credito (venta_id, numero_cuotas, tasa_interes, saldo_pendiente, estado) VALUES (?, ?, ?, ?, ?)";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql, PreparedStatement.RETURN_GENERATED_KEYS);
            pstmt.setInt(1, credito.ventaId);
            pstmt.setInt(2, credito.numeroCuotas);
            pstmt.setDouble(3, credito.tasaInteres);
            pstmt.setDouble(4, credito.saldoPendiente);
            pstmt.setString(5, credito.estado);
            pstmt.executeUpdate();
            rs = pstmt.getGeneratedKeys();
            if (rs.next()) {
                return rs.getInt(1);
            }
            throw new SQLException("No se pudo obtener el ID del crédito creado");
        } catch (SQLException e) {
            throw new SQLException("Error al crear crédito: " + e.getMessage());
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static CreditoM leer(int id) throws SQLException {
        String sql = "SELECT * FROM credito WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, id);
            rs = pstmt.executeQuery();
            if (rs.next()) {
                CreditoM credito = new CreditoM();
                credito.setId(rs.getInt("id"));
                credito.setVentaId(rs.getInt("venta_id"));
                credito.setNumeroCuotas(rs.getInt("numero_cuotas"));
                credito.setTasaInteres(rs.getDouble("tasa_interes"));
                credito.setSaldoPendiente(rs.getDouble("saldo_pendiente"));
                credito.setEstado(rs.getString("estado"));
                return credito;
            }
            throw new SQLException("Crédito no encontrado");
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static List<CreditoM> obtenerTodos() throws SQLException {
        List<CreditoM> creditos = new ArrayList<>();
        String sql = "SELECT * FROM credito ORDER BY id";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                CreditoM credito = new CreditoM();
                credito.setId(rs.getInt("id"));
                credito.setVentaId(rs.getInt("venta_id"));
                credito.setNumeroCuotas(rs.getInt("numero_cuotas"));
                credito.setTasaInteres(rs.getDouble("tasa_interes"));
                credito.setSaldoPendiente(rs.getDouble("saldo_pendiente"));
                credito.setEstado(rs.getString("estado"));
                creditos.add(credito);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return creditos;
    }

    public static CreditoM obtenerPorVenta(int ventaId) throws SQLException {
        String sql = "SELECT * FROM credito WHERE venta_id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, ventaId);
            rs = pstmt.executeQuery();
            if (rs.next()) {
                CreditoM credito = new CreditoM();
                credito.setId(rs.getInt("id"));
                credito.setVentaId(rs.getInt("venta_id"));
                credito.setNumeroCuotas(rs.getInt("numero_cuotas"));
                credito.setTasaInteres(rs.getDouble("tasa_interes"));
                credito.setSaldoPendiente(rs.getDouble("saldo_pendiente"));
                credito.setEstado(rs.getString("estado"));
                return credito;
            }
            return null;
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static String actualizar(CreditoM credito) throws SQLException {
        String sql = "UPDATE credito SET venta_id=?, numero_cuotas=?, tasa_interes=?, saldo_pendiente=?, estado=? WHERE id=?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, credito.ventaId);
            pstmt.setInt(2, credito.numeroCuotas);
            pstmt.setDouble(3, credito.tasaInteres);
            pstmt.setDouble(4, credito.saldoPendiente);
            pstmt.setString(5, credito.estado);
            pstmt.setInt(6, credito.id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Crédito actualizado con éxito" : "Crédito no encontrado";
        } catch (SQLException e) {
            throw new SQLException("Error al actualizar crédito: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static List<CreditoM> obtenerPorCliente(int clienteId) throws SQLException {
        List<CreditoM> lista = new ArrayList<>();
        String sql = "SELECT c.* FROM credito c JOIN venta v ON c.venta_id = v.id WHERE v.cliente_id = ? ORDER BY c.id DESC";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, clienteId);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                CreditoM c = new CreditoM();
                c.setId(rs.getInt("id"));
                c.setVentaId(rs.getInt("venta_id"));
                c.setNumeroCuotas(rs.getInt("numero_cuotas"));
                c.setTasaInteres(rs.getDouble("tasa_interes"));
                c.setSaldoPendiente(rs.getDouble("saldo_pendiente"));
                c.setEstado(rs.getString("estado"));
                lista.add(c);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return lista;
    }

    public static String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM credito WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Crédito eliminado con éxito" : "Crédito no encontrado";
        } catch (SQLException e) {
            throw new SQLException("Error al eliminar crédito: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }
}

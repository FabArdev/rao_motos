package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class EstadisticaM {

    public EstadisticaM() {}

    public List<Map<String, Object>> obtenerVentasPorMes(String mes) throws SQLException {
        List<Map<String, Object>> resultados = new ArrayList<>();
        String sql = "SELECT v.id, v.fecha, v.monto_total, v.tipo_venta, v.metodo_pago, v.estado, u.nombre as cliente_nombre " +
                     "FROM venta v " +
                     "INNER JOIN cliente c ON v.cliente_id = c.id " +
                     "INNER JOIN usuario u ON c.id = u.id " +
                     "WHERE TO_CHAR(v.fecha, 'YYYY-MM') = ? " +
                     "ORDER BY v.fecha DESC";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setString(1, mes);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                Map<String, Object> fila = new HashMap<>();
                fila.put("id_venta", rs.getInt("id"));
                fila.put("fecha_venta", rs.getDate("fecha"));
                fila.put("total", rs.getDouble("monto_total"));
                fila.put("tipo_venta", rs.getString("tipo_venta"));
                fila.put("metodo_pago", rs.getString("metodo_pago"));
                fila.put("estado", rs.getString("estado"));
                fila.put("cliente", rs.getString("cliente_nombre"));
                resultados.add(fila);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return resultados;
    }

    public List<Map<String, Object>> obtenerVentasPorCliente(int clienteId) throws SQLException {
        List<Map<String, Object>> resultados = new ArrayList<>();
        String sql = "SELECT v.id, v.fecha, v.monto_total, v.tipo_venta, v.metodo_pago, v.estado " +
                     "FROM venta v " +
                     "WHERE v.cliente_id = ? " +
                     "ORDER BY v.fecha DESC";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, clienteId);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                Map<String, Object> fila = new HashMap<>();
                fila.put("id_venta", rs.getInt("id"));
                fila.put("fecha_venta", rs.getDate("fecha"));
                fila.put("total", rs.getDouble("monto_total"));
                fila.put("tipo_venta", rs.getString("tipo_venta"));
                fila.put("metodo_pago", rs.getString("metodo_pago"));
                fila.put("estado", rs.getString("estado"));
                resultados.add(fila);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return resultados;
    }

    public List<Map<String, Object>> obtenerMorasPendientes() throws SQLException {
        List<Map<String, Object>> resultados = new ArrayList<>();
        String sql = "SELECT pc.id, pc.monto_cuota, pc.mora, c.saldo_pendiente, " +
                     "(CURRENT_DATE - pc.fecha_vencimiento) as dias_retraso, " +
                     "u.nombre as cliente_nombre " +
                     "FROM pago_cuota pc " +
                     "INNER JOIN credito c ON pc.credito_id = c.id " +
                     "INNER JOIN venta v ON c.venta_id = v.id " +
                     "INNER JOIN cliente cl ON v.cliente_id = cl.id " +
                     "INNER JOIN usuario u ON cl.id = u.id " +
                     "WHERE pc.estado IN ('PENDIENTE', 'VENCIDO') " +
                     "ORDER BY pc.fecha_vencimiento ASC";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                Map<String, Object> fila = new HashMap<>();
                fila.put("id_cuota", rs.getInt("id"));
                fila.put("monto_mora", rs.getDouble("mora"));
                fila.put("saldo_pendiente", rs.getDouble("saldo_pendiente"));
                fila.put("dias_retraso", rs.getInt("dias_retraso"));
                fila.put("cliente", rs.getString("cliente_nombre"));
                resultados.add(fila);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return resultados;
    }

    public Map<String, Object> obtenerTotalesMes(String mes) throws SQLException {
        Map<String, Object> totales = new HashMap<>();
        String sql = "SELECT " +
                     "COUNT(*) as total_ventas, " +
                     "SUM(monto_total) as monto_total, " +
                     "SUM(CASE WHEN tipo_venta = 'CONTADO' THEN monto_total ELSE 0 END) as total_contado, " +
                     "SUM(CASE WHEN tipo_venta = 'CREDITO' THEN monto_total ELSE 0 END) as total_credito " +
                     "FROM venta " +
                     "WHERE TO_CHAR(fecha, 'YYYY-MM') = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setString(1, mes);
            rs = pstmt.executeQuery();
            if (rs.next()) {
                totales.put("total_ventas", rs.getInt("total_ventas"));
                totales.put("monto_total", rs.getDouble("monto_total"));
                totales.put("total_contado", rs.getDouble("total_contado"));
                totales.put("total_credito", rs.getDouble("total_credito"));
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return totales;
    }

    public List<Map<String, Object>> obtenerProductosMasVendidos(String mes) throws SQLException {
        List<Map<String, Object>> resultados = new ArrayList<>();
        String sql = "SELECT pr.id, pr.nombre, SUM(dv.cantidad) as cantidad_vendida, " +
                     "SUM(dv.cantidad * pr.precio_venta_base) as monto_total " +
                     "FROM detalle_venta dv " +
                     "INNER JOIN venta v ON dv.venta_id = v.id " +
                     "INNER JOIN producto pr ON dv.producto_id = pr.id " +
                     "WHERE TO_CHAR(v.fecha, 'YYYY-MM') = ? " +
                     "GROUP BY pr.id, pr.nombre " +
                     "ORDER BY cantidad_vendida DESC " +
                     "LIMIT 10";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setString(1, mes);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                Map<String, Object> fila = new HashMap<>();
                fila.put("id_producto", rs.getInt("id"));
                fila.put("nombre", rs.getString("nombre"));
                fila.put("cantidad_vendida", rs.getInt("cantidad_vendida"));
                fila.put("monto_total", rs.getDouble("monto_total"));
                resultados.add(fila);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return resultados;
    }

    public List<Map<String, Object>> obtenerCreditosPendientesPorVencer() throws SQLException {
        List<Map<String, Object>> resultados = new ArrayList<>();
        String sql = "SELECT c.id, c.venta_id, c.numero_cuotas, c.tasa_interes, c.saldo_pendiente, c.estado, u.nombre as cliente_nombre " +
                     "FROM credito c " +
                     "INNER JOIN venta v ON c.venta_id = v.id " +
                     "INNER JOIN cliente cl ON v.cliente_id = cl.id " +
                     "INNER JOIN usuario u ON cl.id = u.id " +
                     "WHERE c.estado IN ('VIGENTE', 'MOROSO') " +
                     "ORDER BY c.id ASC";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                Map<String, Object> fila = new HashMap<>();
                fila.put("id_credito", rs.getInt("id"));
                fila.put("venta_id", rs.getInt("venta_id"));
                fila.put("numero_cuotas", rs.getInt("numero_cuotas"));
                fila.put("tasa_interes", rs.getDouble("tasa_interes"));
                fila.put("saldo_pendiente", rs.getDouble("saldo_pendiente"));
                fila.put("estado", rs.getString("estado"));
                fila.put("cliente", rs.getString("cliente_nombre"));
                resultados.add(fila);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return resultados;
    }
}

package org.mailgrupo02.modelo.servicio;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.modelo.entidad.*;

import org.mailgrupo02.infraestructura.Conexion;
import org.mailgrupo02.modelo.dao.InventarioM;
import org.mailgrupo02.modelo.dao.MovimientoInventarioM;
import org.mailgrupo02.modelo.dao.ProductoM;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Timestamp;
import java.util.List;

public class InventarioService {

    public String verInventario() throws SQLException {
        List<InventarioM> lista = new InventarioM().obtenerTodos();
        return mapear(lista);
    }

    public String verInventarioPorProducto(int productoId) throws SQLException {
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            String sql = "SELECT i.*, p.nombre FROM inventario i JOIN producto p ON i.producto_id = p.id WHERE i.producto_id = ?";
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, productoId);
            rs = pstmt.executeQuery();
            StringBuilder sb = new StringBuilder();
            String format = "%-5s %-30s %-12s %-20s %-15s%n";
            sb.append(String.format(format, "ID", "Producto", "Stock", "Tecnica Inventario", "Tecnica Costo"));
            sb.append("------------------------------------------------------------------------------\r\n");
            while (rs.next()) {
                sb.append(String.format(format,
                        rs.getInt("id"),
                        rs.getString("nombre"),
                        rs.getInt("stock_actual"),
                        rs.getString("tecnica_inventario") != null ? rs.getString("tecnica_inventario") : "N/A",
                        rs.getString("tecnica_costo") != null ? rs.getString("tecnica_costo") : "N/A"));
            }
            return sb.toString();
        } finally {
            if (rs != null)
                try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null)
                try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null)
                try { conn.close(); } catch (SQLException e) {}
        }
    }

    public String registrarIngreso(int productoId, int cantidad, String motivo) throws SQLException {
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            String checkSql = "SELECT id, stock_actual FROM inventario WHERE producto_id = ?";
            pstmt = conn.prepareStatement(checkSql);
            pstmt.setInt(1, productoId);
            rs = pstmt.executeQuery();

            if (rs.next()) {
                int inventarioId = rs.getInt("id");
                int stockActual = rs.getInt("stock_actual");
                rs.close();

                String updateSql = "UPDATE inventario SET stock_actual = ?, fecha_actualizacion = ? WHERE id = ?";
                pstmt = conn.prepareStatement(updateSql);
                pstmt.setInt(1, stockActual + cantidad);
                pstmt.setTimestamp(2, new Timestamp(System.currentTimeMillis()));
                pstmt.setInt(3, inventarioId);
                pstmt.executeUpdate();

                MovimientoInventarioM mov = new MovimientoInventarioM();
                mov.setInventarioId(inventarioId);
                mov.setTipoMovimiento("INGRESO");
                mov.setCantidad(cantidad);
                mov.setMotivo(motivo);
                mov.setFecha(new Timestamp(System.currentTimeMillis()));
                mov.crear();

                return "Ingreso registrado exitosamente";
            } else {
                rs.close();

                String insertSql = "INSERT INTO inventario (producto_id, stock_actual, tecnica_inventario, tecnica_costo, fecha_actualizacion) VALUES (?, ?, ?, ?, ?)";
                pstmt = conn.prepareStatement(insertSql, PreparedStatement.RETURN_GENERATED_KEYS);
                pstmt.setInt(1, productoId);
                pstmt.setInt(2, cantidad);
                pstmt.setString(3, "PERMANENTE");
                pstmt.setString(4, "PROMEDIO");
                pstmt.setTimestamp(5, new Timestamp(System.currentTimeMillis()));
                pstmt.executeUpdate();
                rs = pstmt.getGeneratedKeys();
                int inventarioId = 0;
                if (rs.next())
                    inventarioId = rs.getInt(1);
                rs.close();

                MovimientoInventarioM mov = new MovimientoInventarioM();
                mov.setInventarioId(inventarioId);
                mov.setTipoMovimiento("INGRESO");
                mov.setCantidad(cantidad);
                mov.setMotivo(motivo);
                mov.setFecha(new Timestamp(System.currentTimeMillis()));
                mov.crear();

                return "Inventario creado e ingreso registrado exitosamente";
            }
        } finally {
            if (rs != null)
                try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null)
                try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null)
                try { conn.close(); } catch (SQLException e) {}
        }
    }

    public String registrarEgreso(int productoId, int cantidad, String motivo) throws SQLException {
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            String checkSql = "SELECT id, stock_actual FROM inventario WHERE producto_id = ?";
            pstmt = conn.prepareStatement(checkSql);
            pstmt.setInt(1, productoId);
            rs = pstmt.executeQuery();

            if (rs.next()) {
                int inventarioId = rs.getInt("id");
                int stockActual = rs.getInt("stock_actual");
                rs.close();

                if (stockActual < cantidad) {
                    return "Stock insuficiente: disponible " + stockActual + ", solicitado " + cantidad;
                }

                String updateSql = "UPDATE inventario SET stock_actual = ?, fecha_actualizacion = ? WHERE id = ?";
                pstmt = conn.prepareStatement(updateSql);
                pstmt.setInt(1, stockActual - cantidad);
                pstmt.setTimestamp(2, new Timestamp(System.currentTimeMillis()));
                pstmt.setInt(3, inventarioId);
                pstmt.executeUpdate();

                MovimientoInventarioM mov = new MovimientoInventarioM();
                mov.setInventarioId(inventarioId);
                mov.setTipoMovimiento("EGRESO");
                mov.setCantidad(cantidad);
                mov.setMotivo(motivo);
                mov.setFecha(new Timestamp(System.currentTimeMillis()));
                mov.crear();

                return "Egreso registrado exitosamente";
            } else {
                return "No existe inventario para el producto especificado";
            }
        } finally {
            if (rs != null)
                try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null)
                try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null)
                try { conn.close(); } catch (SQLException e) {}
        }
    }

    /** Devuelve el stock actual del producto, o -1 si no tiene fila de inventario. */
    public int obtenerStock(int productoId) throws SQLException {
        String sql = "SELECT stock_actual FROM inventario WHERE producto_id = ?";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, productoId);
            try (ResultSet rs = ps.executeQuery()) {
                return rs.next() ? rs.getInt("stock_actual") : -1;
            }
        }
    }

    private String mapear(List<InventarioM> lista) throws SQLException {
        StringBuilder sb = new StringBuilder();
        String format = "%-5s %-30s %-12s %-20s %-15s%n";
        sb.append(String.format(format, "ID", "Producto", "Stock", "Tecnica Inventario", "Tecnica Costo"));
        sb.append("------------------------------------------------------------------------------\r\n");
        for (InventarioM inv : lista) {
            String nombreProducto;
            try {
                ProductoM prod = ProductoM.leer(inv.getProductoId());
                nombreProducto = prod != null ? prod.getNombre() : "N/A";
            } catch (SQLException e) {
                nombreProducto = "N/A";
            }
            sb.append(String.format(format,
                    inv.getId(),
                    nombreProducto,
                    inv.getStockActual(),
                    inv.getTecnicaInventario() != null ? inv.getTecnicaInventario() : "N/A",
                    inv.getTecnicaCosto() != null ? inv.getTecnicaCosto() : "N/A"));
        }
        return sb.toString();
    }
}

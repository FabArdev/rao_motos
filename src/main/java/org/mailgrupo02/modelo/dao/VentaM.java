package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * DAO de `venta`. Alineado al web: `vendedor_id`, `numero_venta`, estados
 * PENDIENTE→PAGADA→COMPLETADA/ANULADA y columnas PagoFácil.
 */
public class VentaM {
    private int id;
    private String numeroVenta;
    private int clienteId;
    private Integer vendedorId;
    private Timestamp fecha;
    private double montoTotal;
    private String tipoVenta;
    private String metodoPago;
    private String estado;

    public VentaM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public String getNumeroVenta() { return numeroVenta; }
    public void setNumeroVenta(String numeroVenta) { this.numeroVenta = numeroVenta; }
    public int getClienteId() { return clienteId; }
    public void setClienteId(int clienteId) { this.clienteId = clienteId; }
    public Integer getVendedorId() { return vendedorId; }
    public void setVendedorId(Integer vendedorId) { this.vendedorId = vendedorId; }
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

    private static VentaM mapear(ResultSet rs) throws SQLException {
        VentaM v = new VentaM();
        v.setId(rs.getInt("id"));
        v.setNumeroVenta(rs.getString("numero_venta"));
        v.setClienteId(rs.getInt("cliente_id"));
        int vend = rs.getInt("vendedor_id");
        v.setVendedorId(rs.wasNull() ? null : vend);
        v.setFecha(rs.getTimestamp("fecha"));
        v.setMontoTotal(rs.getDouble("monto_total"));
        v.setTipoVenta(rs.getString("tipo_venta"));
        v.setMetodoPago(rs.getString("metodo_pago"));
        v.setEstado(rs.getString("estado"));
        return v;
    }

    public static int crear(VentaM venta) throws SQLException {
        String sql = "INSERT INTO venta (numero_venta, cliente_id, vendedor_id, fecha, monto_total, " +
                     "tipo_venta, metodo_pago, estado) VALUES (?,?,?,?,?,?,?,?)";
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            pstmt.setString(1, venta.numeroVenta);
            pstmt.setInt(2, venta.clienteId);
            if (venta.vendedorId != null && venta.vendedorId > 0) pstmt.setInt(3, venta.vendedorId);
            else pstmt.setNull(3, Types.INTEGER);
            pstmt.setTimestamp(4, venta.fecha != null ? venta.fecha : new Timestamp(System.currentTimeMillis()));
            pstmt.setDouble(5, venta.montoTotal);
            pstmt.setString(6, venta.tipoVenta);
            pstmt.setString(7, venta.metodoPago);
            pstmt.setString(8, venta.estado);
            pstmt.executeUpdate();
            try (ResultSet rs = pstmt.getGeneratedKeys()) {
                if (rs.next()) return rs.getInt(1);
            }
            throw new SQLException("No se pudo obtener el ID de la venta creada");
        }
    }

    /** Asigna el número de venta legible (p. ej. V-000012) tras conocer el id. */
    public static void asignarNumero(int ventaId, String numero) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(
                 "UPDATE venta SET numero_venta=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?")) {
            ps.setString(1, numero);
            ps.setInt(2, ventaId);
            ps.executeUpdate();
        }
    }

    /** Cambia el estado de la venta (PENDIENTE/PAGADA/COMPLETADA/ANULADA). */
    public static String cambiarEstado(int ventaId, String nuevoEstado) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(
                 "UPDATE venta SET estado=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?")) {
            ps.setString(1, nuevoEstado);
            ps.setInt(2, ventaId);
            int rows = ps.executeUpdate();
            return rows > 0 ? "Venta " + ventaId + " → " + nuevoEstado : "Venta no encontrada";
        }
    }

    public static VentaM leer(int id) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement("SELECT * FROM venta WHERE id = ?")) {
            pstmt.setInt(1, id);
            try (ResultSet rs = pstmt.executeQuery()) {
                if (rs.next()) return mapear(rs);
            }
        }
        throw new SQLException("Venta no encontrada");
    }

    public static List<VentaM> obtenerTodos() throws SQLException {
        List<VentaM> ventas = new ArrayList<>();
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement("SELECT * FROM venta ORDER BY id");
             ResultSet rs = pstmt.executeQuery()) {
            while (rs.next()) ventas.add(mapear(rs));
        }
        return ventas;
    }

    public static List<VentaM> obtenerPorCliente(int clienteId) throws SQLException {
        List<VentaM> ventas = new ArrayList<>();
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement("SELECT * FROM venta WHERE cliente_id = ? ORDER BY fecha DESC")) {
            ps.setInt(1, clienteId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) ventas.add(mapear(rs));
            }
        }
        return ventas;
    }

    public static String actualizar(VentaM venta) throws SQLException {
        String sql = "UPDATE venta SET cliente_id=?, vendedor_id=?, fecha=?, monto_total=?, tipo_venta=?, " +
                     "metodo_pago=?, estado=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?";
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, venta.clienteId);
            if (venta.vendedorId != null && venta.vendedorId > 0) pstmt.setInt(2, venta.vendedorId);
            else pstmt.setNull(2, Types.INTEGER);
            pstmt.setTimestamp(3, venta.fecha);
            pstmt.setDouble(4, venta.montoTotal);
            pstmt.setString(5, venta.tipoVenta);
            pstmt.setString(6, venta.metodoPago);
            pstmt.setString(7, venta.estado);
            pstmt.setInt(8, venta.id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Venta actualizada con éxito" : "Venta no encontrada";
        }
    }

    public static String eliminar(int id) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement("DELETE FROM venta WHERE id = ?")) {
            pstmt.setInt(1, id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Venta eliminada con éxito" : "Venta no encontrada";
        }
    }

    /** Persiste los datos del QR de PagoFácil generado para la venta (RN13). */
    public static void guardarPagoFacil(int ventaId, String idTransaccion, String numeroPago,
                                        String imagenQr, String respuestaCruda) throws SQLException {
        String sql = "UPDATE venta SET pago_facil_id_transaccion=?, pago_facil_numero_pago=?, " +
                     "pago_facil_imagen_qr=?, pago_facil_estado='pending', pago_facil_respuesta_cruda=?, " +
                     "actualizado_en=CURRENT_TIMESTAMP WHERE id=?";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, idTransaccion);
            ps.setString(2, numeroPago);
            ps.setString(3, imagenQr);
            ps.setString(4, respuestaCruda);
            ps.setInt(5, ventaId);
            ps.executeUpdate();
        }
    }

    /** Marca el pago QR como completado. */
    public static void marcarPagoFacil(int ventaId, String estado) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(
                 "UPDATE venta SET pago_facil_estado=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?")) {
            ps.setString(1, estado);
            ps.setInt(2, ventaId);
            ps.executeUpdate();
        }
    }
}

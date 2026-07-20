package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * DAO de `pedido`. Flujo del web: SOLICITADO → APROBADO (genera venta PENDIENTE) / RECHAZADO
 * → (cliente paga) → DESPACHADO. Guarda `venta_id` (venta generada al aprobar) y `motivo_rechazo`.
 */
public class PedidoM {
    private int id;
    private int clienteId;
    private Timestamp fecha;
    private String estado;
    private String motivoRechazo;
    private Integer ventaId;

    public PedidoM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getClienteId() { return clienteId; }
    public void setClienteId(int clienteId) { this.clienteId = clienteId; }
    public Timestamp getFecha() { return fecha; }
    public void setFecha(Timestamp fecha) { this.fecha = fecha; }
    public String getEstado() { return estado; }
    public void setEstado(String estado) { this.estado = estado; }
    public String getMotivoRechazo() { return motivoRechazo; }
    public void setMotivoRechazo(String motivoRechazo) { this.motivoRechazo = motivoRechazo; }
    public Integer getVentaId() { return ventaId; }
    public void setVentaId(Integer ventaId) { this.ventaId = ventaId; }

    private static PedidoM mapear(ResultSet rs) throws SQLException {
        PedidoM p = new PedidoM();
        p.setId(rs.getInt("id"));
        p.setClienteId(rs.getInt("cliente_id"));
        p.setFecha(rs.getTimestamp("fecha"));
        p.setEstado(rs.getString("estado"));
        p.setMotivoRechazo(rs.getString("motivo_rechazo"));
        int vid = rs.getInt("venta_id");
        p.setVentaId(rs.wasNull() ? null : vid);
        return p;
    }

    public static int crear(PedidoM pedido) throws SQLException {
        String sql = "INSERT INTO pedido (cliente_id, fecha, estado) VALUES (?, ?, ?)";
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            pstmt.setInt(1, pedido.clienteId);
            pstmt.setTimestamp(2, pedido.fecha != null ? pedido.fecha : new Timestamp(System.currentTimeMillis()));
            pstmt.setString(3, pedido.estado != null ? pedido.estado : "SOLICITADO");
            pstmt.executeUpdate();
            try (ResultSet rs = pstmt.getGeneratedKeys()) {
                if (rs.next()) return rs.getInt(1);
            }
            throw new SQLException("No se pudo obtener el ID del pedido creado");
        }
    }

    public static List<PedidoM> obtenerPorCliente(int clienteId) throws SQLException {
        List<PedidoM> pedidos = new ArrayList<>();
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement("SELECT * FROM pedido WHERE cliente_id = ? ORDER BY id DESC")) {
            pstmt.setInt(1, clienteId);
            try (ResultSet rs = pstmt.executeQuery()) {
                while (rs.next()) pedidos.add(mapear(rs));
            }
        }
        return pedidos;
    }

    public static PedidoM leer(int id) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement("SELECT * FROM pedido WHERE id = ?")) {
            pstmt.setInt(1, id);
            try (ResultSet rs = pstmt.executeQuery()) {
                if (rs.next()) return mapear(rs);
            }
        }
        throw new SQLException("Pedido no encontrado");
    }

    public static List<PedidoM> obtenerTodos() throws SQLException {
        List<PedidoM> pedidos = new ArrayList<>();
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement("SELECT * FROM pedido ORDER BY id");
             ResultSet rs = pstmt.executeQuery()) {
            while (rs.next()) pedidos.add(mapear(rs));
        }
        return pedidos;
    }

    public static String actualizar(PedidoM pedido) throws SQLException {
        String sql = "UPDATE pedido SET cliente_id=?, fecha=?, estado=?, motivo_rechazo=?, venta_id=?, " +
                     "actualizado_en=CURRENT_TIMESTAMP WHERE id=?";
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, pedido.clienteId);
            pstmt.setTimestamp(2, pedido.fecha);
            pstmt.setString(3, pedido.estado);
            pstmt.setString(4, pedido.motivoRechazo);
            if (pedido.ventaId != null && pedido.ventaId > 0) pstmt.setInt(5, pedido.ventaId);
            else pstmt.setNull(5, Types.INTEGER);
            pstmt.setInt(6, pedido.id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Pedido actualizado con éxito" : "Pedido no encontrado";
        }
    }

    /** Cambia el estado del pedido. */
    public static void cambiarEstado(int pedidoId, String nuevoEstado) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(
                 "UPDATE pedido SET estado=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?")) {
            ps.setString(1, nuevoEstado);
            ps.setInt(2, pedidoId);
            ps.executeUpdate();
        }
    }

    /** Vincula la venta generada al aprobar y deja el pedido APROBADO. */
    public static void vincularVenta(int pedidoId, int ventaId) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(
                 "UPDATE pedido SET venta_id=?, estado='APROBADO', actualizado_en=CURRENT_TIMESTAMP WHERE id=?")) {
            ps.setInt(1, ventaId);
            ps.setInt(2, pedidoId);
            ps.executeUpdate();
        }
    }

    /** Rechaza el pedido con un motivo. */
    public static void rechazar(int pedidoId, String motivo) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(
                 "UPDATE pedido SET estado='RECHAZADO', motivo_rechazo=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?")) {
            ps.setString(1, motivo);
            ps.setInt(2, pedidoId);
            ps.executeUpdate();
        }
    }

    public static String eliminar(int id) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement("DELETE FROM pedido WHERE id = ?")) {
            pstmt.setInt(1, id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Pedido eliminado con éxito" : "Pedido no encontrado";
        }
    }
}

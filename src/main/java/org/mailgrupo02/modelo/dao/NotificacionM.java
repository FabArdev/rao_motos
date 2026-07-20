package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * DAO de `notificacion`. En el medio email cada notificación se guarda aquí Y se envía
 * como correo saliente (ver NotificacionService). Tipos: STOCK_BAJO, PEDIDO_POR_APROBAR,
 * VENTA_PAGADA, PEDIDO_APROBADO, PEDIDO_RECHAZADO, PEDIDO_DESPACHADO, MORA.
 */
public class NotificacionM {
    private int id;
    private int usuarioId;
    private String tipo;
    private String mensaje;
    private String recurso;
    private boolean leido;
    private Timestamp fecha;

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getUsuarioId() { return usuarioId; }
    public void setUsuarioId(int usuarioId) { this.usuarioId = usuarioId; }
    public String getTipo() { return tipo; }
    public void setTipo(String tipo) { this.tipo = tipo; }
    public String getMensaje() { return mensaje; }
    public void setMensaje(String mensaje) { this.mensaje = mensaje; }
    public String getRecurso() { return recurso; }
    public void setRecurso(String recurso) { this.recurso = recurso; }
    public boolean isLeido() { return leido; }
    public void setLeido(boolean leido) { this.leido = leido; }
    public Timestamp getFecha() { return fecha; }
    public void setFecha(Timestamp fecha) { this.fecha = fecha; }

    /** Registra una notificación y devuelve su id. */
    public static int crear(int usuarioId, String tipo, String mensaje, String recurso) throws SQLException {
        String sql = "INSERT INTO notificacion (usuario_id, tipo, mensaje, recurso) VALUES (?,?,?,?)";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            ps.setInt(1, usuarioId);
            ps.setString(2, tipo);
            ps.setString(3, mensaje);
            ps.setString(4, recurso);
            ps.executeUpdate();
            try (ResultSet rs = ps.getGeneratedKeys()) {
                if (rs.next()) return rs.getInt(1);
            }
        }
        return 0;
    }

    private static NotificacionM mapear(ResultSet rs) throws SQLException {
        NotificacionM n = new NotificacionM();
        n.setId(rs.getInt("id"));
        n.setUsuarioId(rs.getInt("usuario_id"));
        n.setTipo(rs.getString("tipo"));
        n.setMensaje(rs.getString("mensaje"));
        n.setRecurso(rs.getString("recurso"));
        n.setLeido(rs.getBoolean("leido"));
        n.setFecha(rs.getTimestamp("fecha"));
        return n;
    }

    /** Notificaciones de un usuario (más recientes primero). */
    public static List<NotificacionM> obtenerPorUsuario(int usuarioId) throws SQLException {
        List<NotificacionM> lista = new ArrayList<>();
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(
                 "SELECT * FROM notificacion WHERE usuario_id = ? ORDER BY fecha DESC")) {
            ps.setInt(1, usuarioId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) lista.add(mapear(rs));
            }
        }
        return lista;
    }

    /** Marca como leídas todas las notificaciones del usuario. */
    public static void marcarLeidas(int usuarioId) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(
                 "UPDATE notificacion SET leido = TRUE WHERE usuario_id = ?")) {
            ps.setInt(1, usuarioId);
            ps.executeUpdate();
        }
    }
}

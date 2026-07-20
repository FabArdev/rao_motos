package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * DAO de `bitacora` (accesos). En el medio email cada comando recibido se registra como
 * un acceso a recurso. Acciones: LOGIN_OK, LOGIN_FAIL, ACCESO_RECURSO.
 */
public class BitacoraM {
    private int id;
    private Integer usuarioId;
    private String correo;
    private String accion;
    private String recurso;
    private Timestamp fecha;

    public int getId() { return id; }
    public Integer getUsuarioId() { return usuarioId; }
    public String getCorreo() { return correo; }
    public String getAccion() { return accion; }
    public String getRecurso() { return recurso; }
    public Timestamp getFecha() { return fecha; }

    /** Registra un acceso. usuarioId puede ser null (remitente no registrado). */
    public static void registrar(Integer usuarioId, String correo, String accion, String recurso) {
        String sql = "INSERT INTO bitacora (usuario_id, correo, accion, recurso) VALUES (?,?,?,?)";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            if (usuarioId != null && usuarioId > 0) ps.setInt(1, usuarioId); else ps.setNull(1, Types.INTEGER);
            ps.setString(2, correo);
            ps.setString(3, accion);
            ps.setString(4, recurso);
            ps.executeUpdate();
        } catch (SQLException e) {
            // La bitácora no debe interrumpir el flujo del comando
            System.err.println("[Bitacora] No se pudo registrar: " + e.getMessage());
        }
    }

    private static BitacoraM mapear(ResultSet rs) throws SQLException {
        BitacoraM b = new BitacoraM();
        b.id = rs.getInt("id");
        int uid = rs.getInt("usuario_id");
        b.usuarioId = rs.wasNull() ? null : uid;
        b.correo = rs.getString("correo");
        b.accion = rs.getString("accion");
        b.recurso = rs.getString("recurso");
        b.fecha = rs.getTimestamp("fecha");
        return b;
    }

    /** Últimos N registros de la bitácora (más recientes primero). */
    public static List<BitacoraM> obtenerRecientes(int limite) throws SQLException {
        List<BitacoraM> lista = new ArrayList<>();
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement("SELECT * FROM bitacora ORDER BY fecha DESC LIMIT ?")) {
            ps.setInt(1, limite);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) lista.add(mapear(rs));
            }
        }
        return lista;
    }
}

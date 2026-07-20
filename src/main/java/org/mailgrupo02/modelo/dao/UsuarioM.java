package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * DAO de la tabla `usuario`.
 *   - El rol es una FK rol_id → rol; el accesor getRol()/setRol() maneja el NOMBRE del rol
 *     (rolId es el id numérico). Los SELECT hacen JOIN a rol para traer el nombre.
 *   - La columna de actividad es `estado`; el accesor isActivo()/setActivo() la mapea.
 */
public class UsuarioM {
    private int id;
    private String nombre;
    private String apellidos;
    private String ci;
    private String correo;
    private String telefono;
    private String direccion;
    private String fotoUrl;
    private String password;
    private int rolId;
    private String rol;          // nombre del rol (JOIN rol.nombre)
    private boolean activo;      // columna `estado`
    private Date fechaNacimiento;
    private Timestamp fechaReg;  // columna `creado_en`

    public UsuarioM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public String getNombre() { return nombre; }
    public void setNombre(String nombre) { this.nombre = nombre; }
    public String getApellidos() { return apellidos; }
    public void setApellidos(String apellidos) { this.apellidos = apellidos; }
    public String getCi() { return ci; }
    public void setCi(String ci) { this.ci = ci; }
    public String getCorreo() { return correo; }
    public void setCorreo(String correo) { this.correo = correo; }
    public String getTelefono() { return telefono; }
    public void setTelefono(String telefono) { this.telefono = telefono; }
    public String getDireccion() { return direccion; }
    public void setDireccion(String direccion) { this.direccion = direccion; }
    public String getFotoUrl() { return fotoUrl; }
    public void setFotoUrl(String fotoUrl) { this.fotoUrl = fotoUrl; }
    public String getPassword() { return password; }
    public void setPassword(String password) { this.password = password; }
    public int getRolId() { return rolId; }
    public void setRolId(int rolId) { this.rolId = rolId; }
    public String getRol() { return rol; }
    public void setRol(String rol) { this.rol = rol; }
    public boolean isActivo() { return activo; }
    public void setActivo(boolean activo) { this.activo = activo; }
    public Date getFechaNacimiento() { return fechaNacimiento; }
    public void setFechaNacimiento(Date fechaNacimiento) { this.fechaNacimiento = fechaNacimiento; }
    public Timestamp getFechaReg() { return fechaReg; }
    public void setFechaReg(Timestamp fechaReg) { this.fechaReg = fechaReg; }

    private static final String SELECT_BASE =
        "SELECT u.*, r.nombre AS rol_nombre FROM usuario u LEFT JOIN rol r ON r.id = u.rol_id ";

    private static UsuarioM mapear(ResultSet rs) throws SQLException {
        UsuarioM u = new UsuarioM();
        u.setId(rs.getInt("id"));
        u.setNombre(rs.getString("nombre"));
        u.setApellidos(rs.getString("apellidos"));
        u.setCi(rs.getString("ci"));
        u.setCorreo(rs.getString("correo"));
        u.setTelefono(rs.getString("telefono"));
        u.setDireccion(rs.getString("direccion"));
        u.setFotoUrl(rs.getString("foto_url"));
        u.setPassword(rs.getString("password"));
        u.setRolId(rs.getInt("rol_id"));
        u.setRol(rs.getString("rol_nombre"));
        u.setActivo(rs.getBoolean("estado"));
        u.setFechaNacimiento(rs.getDate("fecha_nacimiento"));
        u.setFechaReg(rs.getTimestamp("creado_en"));
        return u;
    }

    /** Resuelve rolId a partir del nombre de rol si no viene seteado. */
    private static int resolverRolId(UsuarioM u) throws SQLException {
        if (u.rolId > 0) return u.rolId;
        if (u.rol != null && !u.rol.isBlank()) {
            int id = RolM.idPorNombre(u.rol);
            if (id > 0) return id;
        }
        return RolM.idPorNombre("cliente"); // rol por defecto
    }

    public static int crear(UsuarioM u) throws SQLException {
        String sql = "INSERT INTO usuario (nombre, apellidos, ci, correo, telefono, direccion, foto_url, " +
                     "password, rol_id, estado, fecha_nacimiento) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            pstmt.setString(1, u.nombre);
            pstmt.setString(2, u.apellidos);
            pstmt.setString(3, u.ci);
            pstmt.setString(4, u.correo);
            pstmt.setString(5, u.telefono);
            pstmt.setString(6, u.direccion);
            pstmt.setString(7, u.fotoUrl);
            pstmt.setString(8, u.password);
            pstmt.setInt(9, resolverRolId(u));
            pstmt.setBoolean(10, u.activo);
            pstmt.setDate(11, u.fechaNacimiento);
            pstmt.executeUpdate();
            try (ResultSet rs = pstmt.getGeneratedKeys()) {
                if (rs.next()) return rs.getInt(1);
            }
            throw new SQLException("No se pudo obtener el ID del usuario creado");
        }
    }

    /**
     * Registro atómico de cliente (RN14): crea la fila en `usuario` y su fila 1:1 en `cliente`
     * dentro de una única transacción. Devuelve el id del nuevo usuario.
     */
    public static int registrarClienteAtomico(UsuarioM u, String nitCi) throws SQLException {
        String sqlU = "INSERT INTO usuario (nombre, apellidos, ci, correo, telefono, direccion, foto_url, " +
                      "password, rol_id, estado, fecha_nacimiento) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        String sqlC = "INSERT INTO cliente (id, nit_ci) VALUES (?, ?)";
        Connection conn = null;
        try {
            conn = Conexion.conectar();
            conn.setAutoCommit(false);
            int nuevoId;
            try (PreparedStatement ps = conn.prepareStatement(sqlU, Statement.RETURN_GENERATED_KEYS)) {
                ps.setString(1, u.nombre);
                ps.setString(2, u.apellidos);
                ps.setString(3, u.ci);
                ps.setString(4, u.correo);
                ps.setString(5, u.telefono);
                ps.setString(6, u.direccion);
                ps.setString(7, u.fotoUrl);
                ps.setString(8, u.password);
                ps.setInt(9, RolM.idPorNombre("cliente"));
                ps.setBoolean(10, true);
                ps.setDate(11, u.fechaNacimiento);
                ps.executeUpdate();
                try (ResultSet rs = ps.getGeneratedKeys()) {
                    if (!rs.next()) throw new SQLException("No se pudo crear el usuario");
                    nuevoId = rs.getInt(1);
                }
            }
            try (PreparedStatement ps = conn.prepareStatement(sqlC)) {
                ps.setInt(1, nuevoId);
                ps.setString(2, nitCi);
                ps.executeUpdate();
            }
            conn.commit();
            return nuevoId;
        } catch (SQLException e) {
            if (conn != null) try { conn.rollback(); } catch (SQLException ex) {}
            throw new SQLException("Error en registro atómico de cliente: " + e.getMessage());
        } finally {
            if (conn != null) try { conn.setAutoCommit(true); conn.close(); } catch (SQLException e) {}
        }
    }

    public static UsuarioM leer(int id) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(SELECT_BASE + "WHERE u.id = ?")) {
            pstmt.setInt(1, id);
            try (ResultSet rs = pstmt.executeQuery()) {
                if (rs.next()) return mapear(rs);
            }
        }
        throw new SQLException("Usuario no encontrado");
    }

    public static List<UsuarioM> obtenerTodos() throws SQLException {
        List<UsuarioM> usuarios = new ArrayList<>();
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(SELECT_BASE + "ORDER BY u.id");
             ResultSet rs = pstmt.executeQuery()) {
            while (rs.next()) usuarios.add(mapear(rs));
        }
        return usuarios;
    }

    /** Usuarios de un rol dado (por nombre). */
    public static List<UsuarioM> obtenerPorRol(String rolNombre) throws SQLException {
        List<UsuarioM> usuarios = new ArrayList<>();
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(SELECT_BASE + "WHERE LOWER(r.nombre) = LOWER(?) ORDER BY u.id")) {
            pstmt.setString(1, rolNombre);
            try (ResultSet rs = pstmt.executeQuery()) {
                while (rs.next()) usuarios.add(mapear(rs));
            }
        }
        return usuarios;
    }

    public static String actualizar(UsuarioM u) throws SQLException {
        String sql = "UPDATE usuario SET nombre=?, apellidos=?, ci=?, correo=?, telefono=?, direccion=?, " +
                     "foto_url=?, password=?, rol_id=?, estado=?, fecha_nacimiento=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?";
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setString(1, u.nombre);
            pstmt.setString(2, u.apellidos);
            pstmt.setString(3, u.ci);
            pstmt.setString(4, u.correo);
            pstmt.setString(5, u.telefono);
            pstmt.setString(6, u.direccion);
            pstmt.setString(7, u.fotoUrl);
            pstmt.setString(8, u.password);
            pstmt.setInt(9, resolverRolId(u));
            pstmt.setBoolean(10, u.activo);
            pstmt.setDate(11, u.fechaNacimiento);
            pstmt.setInt(12, u.id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Usuario actualizado con éxito" : "Usuario no encontrado";
        }
    }

    public static String eliminar(int id) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement("DELETE FROM usuario WHERE id = ?")) {
            pstmt.setInt(1, id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Usuario eliminado con éxito" : "Usuario no encontrado";
        }
    }

    /** Busca un usuario por correo. Retorna null si no existe. */
    public static UsuarioM buscarPorCorreo(String correo) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(SELECT_BASE + "WHERE u.correo = ?")) {
            pstmt.setString(1, correo);
            try (ResultSet rs = pstmt.executeQuery()) {
                if (rs.next()) return mapear(rs);
            }
        }
        return null;
    }

    /** Cambia el rol del usuario (por nombre de rol → rol_id). */
    public static String cambiarRol(int userId, String nuevoRol) throws SQLException {
        int rolId = RolM.idPorNombre(nuevoRol);
        if (rolId <= 0) return "Rol inválido: " + nuevoRol;
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(
                 "UPDATE usuario SET rol_id = ?, actualizado_en = CURRENT_TIMESTAMP WHERE id = ?")) {
            pstmt.setInt(1, rolId);
            pstmt.setInt(2, userId);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Rol actualizado" : "Usuario no encontrado";
        }
    }
}

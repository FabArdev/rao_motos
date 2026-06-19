package org.mailgrupo02.datos.modelo;

import org.mailgrupo02.datos.conexion.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class UsuarioM {
    private int id;
    private String nombre;
    private String email;
    private String telefono;
    private String direccion;
    private String fotoUrl;
    private String password;
    private String rol;
    private boolean activo;
    private Timestamp fechaReg;

    public UsuarioM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public String getNombre() { return nombre; }
    public void setNombre(String nombre) { this.nombre = nombre; }
    public String getEmail() { return email; }
    public void setEmail(String email) { this.email = email; }
    public String getTelefono() { return telefono; }
    public void setTelefono(String telefono) { this.telefono = telefono; }
    public String getDireccion() { return direccion; }
    public void setDireccion(String direccion) { this.direccion = direccion; }
    public String getFotoUrl() { return fotoUrl; }
    public void setFotoUrl(String fotoUrl) { this.fotoUrl = fotoUrl; }
    public String getPassword() { return password; }
    public void setPassword(String password) { this.password = password; }
    public String getRol() { return rol; }
    public void setRol(String rol) { this.rol = rol; }
    public boolean isActivo() { return activo; }
    public void setActivo(boolean activo) { this.activo = activo; }
    public Timestamp getFechaReg() { return fechaReg; }
    public void setFechaReg(Timestamp fechaReg) { this.fechaReg = fechaReg; }

    public static int crear(UsuarioM usuario) throws SQLException {
        String sql = "INSERT INTO usuario (nombre, email, telefono, direccion, foto_url, password, rol, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql, PreparedStatement.RETURN_GENERATED_KEYS);
            pstmt.setString(1, usuario.nombre);
            pstmt.setString(2, usuario.email);
            pstmt.setString(3, usuario.telefono);
            pstmt.setString(4, usuario.direccion);
            pstmt.setString(5, usuario.fotoUrl);
            pstmt.setString(6, usuario.password);
            pstmt.setString(7, usuario.rol);
            pstmt.setBoolean(8, usuario.activo);
            pstmt.executeUpdate();
            rs = pstmt.getGeneratedKeys();
            if (rs.next()) {
                return rs.getInt(1);
            }
            throw new SQLException("No se pudo obtener el ID del usuario creado");
        } catch (SQLException e) {
            throw new SQLException("Error al crear usuario: " + e.getMessage());
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static UsuarioM leer(int id) throws SQLException {
        String sql = "SELECT * FROM usuario WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, id);
            rs = pstmt.executeQuery();
            if (rs.next()) {
                UsuarioM usuario = new UsuarioM();
                usuario.setId(rs.getInt("id"));
                usuario.setNombre(rs.getString("nombre"));
                usuario.setEmail(rs.getString("email"));
                usuario.setTelefono(rs.getString("telefono"));
                usuario.setDireccion(rs.getString("direccion"));
                usuario.setFotoUrl(rs.getString("foto_url"));
                usuario.setPassword(rs.getString("password"));
                usuario.setRol(rs.getString("rol"));
                usuario.setActivo(rs.getBoolean("activo"));
                usuario.setFechaReg(rs.getTimestamp("fecha_reg"));
                return usuario;
            }
            throw new SQLException("Usuario no encontrado");
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static List<UsuarioM> obtenerTodos() throws SQLException {
        List<UsuarioM> usuarios = new ArrayList<>();
        String sql = "SELECT * FROM usuario ORDER BY id";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                UsuarioM usuario = new UsuarioM();
                usuario.setId(rs.getInt("id"));
                usuario.setNombre(rs.getString("nombre"));
                usuario.setEmail(rs.getString("email"));
                usuario.setTelefono(rs.getString("telefono"));
                usuario.setDireccion(rs.getString("direccion"));
                usuario.setFotoUrl(rs.getString("foto_url"));
                usuario.setPassword(rs.getString("password"));
                usuario.setRol(rs.getString("rol"));
                usuario.setActivo(rs.getBoolean("activo"));
                usuario.setFechaReg(rs.getTimestamp("fecha_reg"));
                usuarios.add(usuario);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return usuarios;
    }

    public static String actualizar(UsuarioM usuario) throws SQLException {
        String sql = "UPDATE usuario SET nombre=?, email=?, telefono=?, direccion=?, foto_url=?, password=?, rol=?, activo=? WHERE id=?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setString(1, usuario.nombre);
            pstmt.setString(2, usuario.email);
            pstmt.setString(3, usuario.telefono);
            pstmt.setString(4, usuario.direccion);
            pstmt.setString(5, usuario.fotoUrl);
            pstmt.setString(6, usuario.password);
            pstmt.setString(7, usuario.rol);
            pstmt.setBoolean(8, usuario.activo);
            pstmt.setInt(9, usuario.id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Usuario actualizado con éxito" : "Usuario no encontrado";
        } catch (SQLException e) {
            throw new SQLException("Error al actualizar usuario: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM usuario WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Usuario eliminado con éxito" : "Usuario no encontrado";
        } catch (SQLException e) {
            throw new SQLException("Error al eliminar usuario: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    /** Busca un usuario por email. Retorna null si no existe. */
    public static UsuarioM buscarPorEmail(String email) throws SQLException {
        String sql = "SELECT * FROM usuario WHERE email = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setString(1, email);
            rs = pstmt.executeQuery();
            if (rs.next()) {
                UsuarioM u = new UsuarioM();
                u.setId(rs.getInt("id"));
                u.setNombre(rs.getString("nombre"));
                u.setEmail(rs.getString("email"));
                u.setTelefono(rs.getString("telefono"));
                u.setDireccion(rs.getString("direccion"));
                u.setFotoUrl(rs.getString("foto_url"));
                u.setPassword(rs.getString("password"));
                u.setRol(rs.getString("rol"));
                u.setActivo(rs.getBoolean("activo"));
                u.setFechaReg(rs.getTimestamp("fecha_reg"));
                return u;
            }
            return null;
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    /** Actualiza solo el campo rol en la tabla usuario. */
    public static String cambiarRol(int userId, String nuevoRol) throws SQLException {
        String sql = "UPDATE usuario SET rol = ? WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setString(1, nuevoRol);
            pstmt.setInt(2, userId);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Rol actualizado" : "Usuario no encontrado";
        } catch (SQLException e) {
            throw new SQLException("Error al cambiar rol: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }
}

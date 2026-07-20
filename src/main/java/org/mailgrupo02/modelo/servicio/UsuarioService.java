package org.mailgrupo02.modelo.servicio;
import org.mailgrupo02.modelo.entidad.*;

import org.mailgrupo02.modelo.dao.*;

import java.sql.SQLException;
import java.util.List;

public class UsuarioService {

    /** Los 4 roles del sistema (RAO MOTOS). */
    public static final String[] ROLES = {"admin", "vendedor", "almacenero", "cliente"};

    public UsuarioService() {}
    public UsuarioService(UsuarioM usuarioM) {}

    public String obtenerUsuarios() throws SQLException {
        return mapear(UsuarioM.obtenerTodos());
    }

    public UsuarioN leerUsuario(int id) throws SQLException {
        return mapearUno(UsuarioM.leer(id));
    }

    /** Alta de usuario por un admin (cualquier rol). Cliente crea también su subtabla (atómico). */
    public String agregarUsuario(String nombre, String correo, String password, String rol,
            String telefono, String direccion) throws SQLException {
        UsuarioValidator.validarCampos(nombre, correo, password, rol);
        if (esCliente(rol)) {
            UsuarioM u = cargar(0, nombre, correo, password, rol, telefono, direccion, true);
            int userId = UsuarioM.registrarClienteAtomico(u, "N/A");
            return "Usuario creado con éxito (ID: " + userId + ")";
        }
        UsuarioM u = cargar(0, nombre, correo, password, rol, telefono, direccion, true);
        int userId = UsuarioM.crear(u);
        return "Usuario creado con éxito (ID: " + userId + ")";
    }

    /** Registro público de un cliente (comando CREATEUSUARIO). Atómico (RN14). */
    public String registrarCliente(String nombre, String correo, String password,
            String telefono, String direccion) throws SQLException {
        UsuarioValidator.validarCampos(nombre, correo, password, "cliente");
        if (UsuarioM.buscarPorCorreo(correo) != null) {
            return "Error: ya existe un usuario con el correo " + correo + ".";
        }
        UsuarioM u = cargar(0, nombre, correo, password, "cliente", telefono, direccion, true);
        int userId = UsuarioM.registrarClienteAtomico(u, "N/A");
        return "Cliente registrado con éxito (ID: " + userId + ")";
    }

    public String actualizarUsuario(int id, String nombre, String correo, String password,
            String rol, String telefono, String direccion, boolean activo) throws SQLException {
        UsuarioValidator.validarCampos(nombre, correo, password, rol);
        UsuarioM u = cargar(id, nombre, correo, password, rol, telefono, direccion, activo);
        return UsuarioM.actualizar(u);
    }

    public String eliminarUsuario(int id) throws SQLException {
        return UsuarioM.eliminar(id);
    }

    /** Retorna el rol del remitente o "DESCONOCIDO" si no está registrado o inactivo. */
    public String buscarRolPorCorreo(String correo) throws SQLException {
        UsuarioM u = UsuarioM.buscarPorCorreo(correo);
        if (u == null || !u.isActivo()) return "DESCONOCIDO";
        return u.getRol();
    }

    /** Retorna el userId del remitente o -1 si no existe. */
    public int buscarIdPorCorreo(String correo) throws SQLException {
        UsuarioM u = UsuarioM.buscarPorCorreo(correo);
        return u != null ? u.getId() : -1;
    }

    /** Cliente actualiza sus propios datos identificándose por correo (sin tocar rol ni estado). */
    public String actualizarPerfil(String correo, String nombre, String password,
            String telefono, String direccion) throws SQLException {
        UsuarioM u = UsuarioM.buscarPorCorreo(correo);
        if (u == null) return "Error: no existe un usuario con el correo " + correo + ".";
        if (nombre    != null && !nombre.isBlank())    u.setNombre(nombre);
        if (password  != null && !password.isBlank())  u.setPassword(password);
        if (telefono  != null && !telefono.isBlank())  u.setTelefono(telefono);
        if (direccion != null && !direccion.isBlank()) u.setDireccion(direccion);
        UsuarioM.actualizar(u);
        return "Perfil actualizado exitosamente (ID: " + u.getId() + ")";
    }

    /** Cambia el rol de un usuario, manteniendo la subtabla `cliente` coherente. */
    public String cambiarRol(int userId, String nuevoRol) throws SQLException {
        nuevoRol = nuevoRol.toLowerCase().trim();
        if (!esRolValido(nuevoRol)) {
            return "Error: rol inválido. Use admin, vendedor, almacenero o cliente.";
        }
        UsuarioM u = UsuarioM.leer(userId);
        String rolActual = u.getRol() != null ? u.getRol().toLowerCase() : "";
        if (rolActual.equals(nuevoRol)) return "El usuario ya tiene el rol " + nuevoRol + ".";

        // Si deja de ser cliente, quitar su fila de la subtabla
        if (rolActual.equals("cliente")) new ClienteM().eliminar(userId);

        UsuarioM.cambiarRol(userId, nuevoRol);

        // Si pasa a ser cliente, crear su fila de subtabla
        if (nuevoRol.equals("cliente")) {
            ClienteM c = new ClienteM();
            c.setId(userId);
            c.setNitCi("N/A");
            c.crear();
        }
        return "Rol del usuario ID " + userId + " cambiado de " + rolActual + " a " + nuevoRol + " (ID: " + userId + ")";
    }

    private static boolean esCliente(String rol) {
        return rol != null && rol.equalsIgnoreCase("cliente");
    }

    private static boolean esRolValido(String rol) {
        for (String r : ROLES) if (r.equalsIgnoreCase(rol)) return true;
        return false;
    }

    private String mapear(List<UsuarioM> usuarios) {
        StringBuilder sb = new StringBuilder();
        String format = "%-5s %-20s %-30s %-15s %-10s %-15s%n";
        sb.append(String.format(format, "ID", "Nombre", "Correo", "Rol", "Activo", "Teléfono"));
        sb.append("------------------------------------------------------------------------------------------------------\r\n");
        for (UsuarioM u : usuarios) {
            sb.append(String.format(format,
                    u.getId(), u.getNombre(), u.getCorreo(), u.getRol(),
                    u.isActivo() ? "Sí" : "No",
                    u.getTelefono() != null ? u.getTelefono() : "N/A"));
        }
        return sb.toString();
    }

    private UsuarioN mapearUno(UsuarioM u) {
        UsuarioN n = new UsuarioN();
        n.setId(u.getId());
        n.setNombre(u.getNombre());
        n.setCorreo(u.getCorreo());
        n.setTelefono(u.getTelefono());
        n.setDireccion(u.getDireccion());
        n.setFotoUrl(u.getFotoUrl());
        n.setPassword(u.getPassword());
        n.setRol(u.getRol());
        n.setActivo(u.isActivo());
        n.setFechaReg(u.getFechaReg() != null ? u.getFechaReg().toString() : null);
        return n;
    }

    private UsuarioM cargar(int id, String nombre, String correo, String password, String rol,
            String telefono, String direccion, boolean activo) {
        UsuarioM u = new UsuarioM();
        u.setId(id);
        u.setNombre(nombre);
        u.setCorreo(correo);
        u.setPassword(password);
        u.setRol(rol);
        u.setTelefono(telefono);
        u.setDireccion(direccion);
        u.setActivo(activo);
        return u;
    }
}

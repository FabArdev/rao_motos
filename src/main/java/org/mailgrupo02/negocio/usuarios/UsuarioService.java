package org.mailgrupo02.negocio.usuarios;

import org.mailgrupo02.datos.modelo.*;

import java.sql.SQLException;
import java.util.List;

public class UsuarioService {

    public UsuarioService(UsuarioM usuarioM) {
    }

    public String obtenerUsuarios() throws SQLException {
        List<UsuarioM> usuarios = UsuarioM.obtenerTodos();
        return mapear(usuarios);
    }

    public UsuarioN leerUsuario(int id) throws SQLException {
        UsuarioM usuarioMObj = UsuarioM.leer(id);
        return mapearUno(usuarioMObj);
    }

    public String agregarUsuario(String nombre, String email, String password, String rol,
            String telefono, String direccion) throws SQLException {
        UsuarioValidator.validarCampos(nombre, email, password, rol);
        UsuarioM usuarioMObj = cargar(0, nombre, email, password, rol, telefono, direccion, true);
        int userId = UsuarioM.crear(usuarioMObj);

        switch (rol) {
            case "CLIENTE": {
                ClienteM c = new ClienteM();
                c.setId(userId);
                c.setNitCi("N/A");
                c.setTipoCliente("REGULAR");
                c.crear();
                break;
            }
            case "PROPIETARIO": {
                PropietarioM p = new PropietarioM();
                p.setId(userId);
                p.setNivelAcceso("TOTAL");
                p.crear();
                break;
            }
        }

        return "Usuario creado con éxito (ID: " + userId + ")";
    }

    public String actualizarUsuario(int id, String nombre, String email, String password,
            String rol, String telefono, String direccion, boolean activo) throws SQLException {
        UsuarioValidator.validarCampos(nombre, email, password, rol);
        UsuarioM usuarioMObj = cargar(id, nombre, email, password, rol, telefono, direccion, activo);
        return UsuarioM.actualizar(usuarioMObj);
    }

    public String eliminarUsuario(int id) throws SQLException {
        return UsuarioM.eliminar(id);
    }

    /** Retorna el rol del remitente o "DESCONOCIDO" si no está registrado. */
    public String buscarRolPorEmail(String email) throws SQLException {
        UsuarioM u = UsuarioM.buscarPorEmail(email);
        if (u == null || !u.isActivo()) return "DESCONOCIDO";
        return u.getRol();
    }

    /** Retorna el userId del remitente o -1 si no existe. */
    public int buscarIdPorEmail(String email) throws SQLException {
        UsuarioM u = UsuarioM.buscarPorEmail(email);
        return u != null ? u.getId() : -1;
    }

    /**
     * Cambia el rol de un usuario migrando sus datos a la subtabla correcta.
     * Elimina la fila antigua en cliente/proveedor/propietario e inserta en la nueva.
     */
    public String cambiarRol(int userId, String nuevoRol) throws SQLException {
        nuevoRol = nuevoRol.toUpperCase().trim();
        if (!nuevoRol.equals("PROPIETARIO") && !nuevoRol.equals("CLIENTE")) {
            return "Error: rol inválido. Use PROPIETARIO o CLIENTE.";
        }
        UsuarioM u = UsuarioM.leer(userId);
        String rolActual = u.getRol();
        if (rolActual.equals(nuevoRol)) return "El usuario ya tiene el rol " + nuevoRol + ".";

        // Eliminar de la subtabla actual
        switch (rolActual) {
            case "CLIENTE":     new ClienteM().eliminar(userId); break;
            case "PROPIETARIO": new PropietarioM().eliminar(userId); break;
        }

        // Actualizar rol principal
        UsuarioM.cambiarRol(userId, nuevoRol);

        // Insertar en nueva subtabla
        switch (nuevoRol) {
            case "CLIENTE": {
                ClienteM c = new ClienteM();
                c.setId(userId);
                c.setNitCi("N/A");
                c.setTipoCliente("REGULAR");
                c.crear();
                break;
            }
            case "PROPIETARIO": {
                PropietarioM p = new PropietarioM();
                p.setId(userId);
                p.setNivelAcceso("TOTAL");
                p.crear();
                break;
            }
        }
        return "Rol del usuario ID " + userId + " cambiado de " + rolActual + " a " + nuevoRol + " exitosamente (ID: " + userId + ")";
    }

    private String mapear(List<UsuarioM> usuarios) throws SQLException {
        StringBuilder sb = new StringBuilder();
        String format = "%-5s %-20s %-30s %-15s %-10s %-15s%n";
        sb.append(String.format(format, "ID", "Nombre", "Email", "Rol", "Activo", "Teléfono"));
        sb.append(
                "------------------------------------------------------------------------------------------------------\r\n");

        for (UsuarioM usuario : usuarios) {
            sb.append(String.format(format,
                    usuario.getId(),
                    usuario.getNombre(),
                    usuario.getEmail(),
                    usuario.getRol(),
                    usuario.isActivo() ? "Sí" : "No",
                    usuario.getTelefono() != null ? usuario.getTelefono() : "N/A"));
        }
        return sb.toString();
    }

    private UsuarioN mapearUno(UsuarioM usuarioM) throws SQLException {
        UsuarioN usuarioN = new UsuarioN();
        usuarioN.setId(usuarioM.getId());
        usuarioN.setNombre(usuarioM.getNombre());
        usuarioN.setEmail(usuarioM.getEmail());
        usuarioN.setTelefono(usuarioM.getTelefono());
        usuarioN.setDireccion(usuarioM.getDireccion());
        usuarioN.setFotoUrl(usuarioM.getFotoUrl());
        usuarioN.setPassword(usuarioM.getPassword());
        usuarioN.setRol(usuarioM.getRol());
        usuarioN.setActivo(usuarioM.isActivo());
        usuarioN.setFechaReg(usuarioM.getFechaReg() != null ? usuarioM.getFechaReg().toString() : null);
        return usuarioN;
    }

    private UsuarioM cargar(int id, String nombre, String email, String password, String rol,
            String telefono, String direccion, boolean activo) throws SQLException {
        UsuarioM usuario = new UsuarioM();
        usuario.setId(id);
        usuario.setNombre(nombre);
        usuario.setEmail(email);
        usuario.setPassword(password);
        usuario.setRol(rol);
        usuario.setTelefono(telefono);
        usuario.setDireccion(direccion);
        usuario.setActivo(activo);
        return usuario;
    }
}

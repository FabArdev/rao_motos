package org.mailgrupo02.controlador;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.vista.*;
import org.mailgrupo02.modelo.entidad.*;

import org.mailgrupo02.modelo.dao.UsuarioM;
import org.mailgrupo02.modelo.dao.BitacoraM;
import org.mailgrupo02.modelo.servicio.UsuarioService;
import org.mailgrupo02.controlador.*;

import java.sql.SQLException;
import java.util.Arrays;
import java.util.Collections;
import java.util.List;

public class ComandoCorreoNuevo {

    public ComandoCorreoNuevo() throws SQLException {}

    // ── Punto de entrada ─────────────────────────────────────────────────────

    public String evaluarYEjecutar(String asunto, String correoRemitente) {
        try {
            if (asunto == null || asunto.trim().isEmpty()) {
                return PAyuda.generarError("El asunto del correo está vacío.");
            }
            asunto = asunto.trim();

            String[] parsed = parsearAsunto(asunto);
            String cmd = parsed[0].toUpperCase();
            List<String> params = parsed[1].isEmpty()
                    ? Collections.emptyList()
                    : Arrays.asList(parsed[1].replaceAll("\"", "").split(",\\s*"));

            // Resolver identidad y rol del remitente (la identidad ES el correo)
            UsuarioService usuarioService = new UsuarioService(null);
            String rol = usuarioService.buscarRolPorCorreo(correoRemitente);   // "DESCONOCIDO" si no tiene cuenta
            int userId = usuarioService.buscarIdPorCorreo(correoRemitente);

            // Bitácora del acceso (nunca interrumpe el flujo)
            BitacoraM.registrar(userId > 0 ? userId : null, correoRemitente, "ACCESO_RECURSO", cmd);

            // HELP filtrado por rol / WHOAMI
            if ("HELP".equals(cmd))   return ayudaPorRol(rol);
            if ("WHOAMI".equals(cmd)) return whoAmI(correoRemitente);

            // Gate RBAC (admin es superusuario)
            if (Permisos.existe(cmd) && !Permisos.puedeEjecutar(cmd, rol)) {
                if (!Permisos.esPublico(cmd) && "DESCONOCIDO".equalsIgnoreCase(rol)) {
                    return PAyuda.generarError(
                        "El correo <strong>" + correoRemitente + "</strong> no está registrado.<br>" +
                        "Regístrate con <strong>CREATEUSUARIO[nombre,correo,password,cliente,telefono,direccion]</strong> " +
                        "o envía <strong>HELP</strong>.");
                }
                return PAyuda.generarError(
                    "Tu rol (<strong>" + rol + "</strong>) no tiene permiso para ejecutar <strong>" + cmd + "</strong>.<br>" +
                    "Envía <strong>HELP</strong> para ver los comandos disponibles para tu rol.");
            }

            // Despachar al controlador correspondiente
            if (UsuarioControlador.canHandle(cmd))    return UsuarioControlador.handle(cmd, params, correoRemitente);
            if (ProveedorControlador.canHandle(cmd))  return ProveedorControlador.handle(cmd, params);
            if (ProductoControlador.canHandle(cmd))   return ProductoControlador.handle(cmd, params);
            if (VentaControlador.canHandle(cmd))      return VentaControlador.handle(cmd, params, correoRemitente);
            if (CompraControlador.canHandle(cmd))     return CompraControlador.handle(cmd, params);
            if (PedidoControlador.canHandle(cmd))     return PedidoControlador.handle(cmd, params, correoRemitente);
            if (InventarioControlador.canHandle(cmd)) return InventarioControlador.handle(cmd, params);
            if (PagoControlador.canHandle(cmd))       return PagoControlador.handle(cmd, params, correoRemitente);
            if (ReporteControlador.canHandle(cmd))    return ReporteControlador.handle(cmd, params);

            return PAyuda.generarError(
                "Comando no reconocido: <strong>" + asunto + "</strong><br>" +
                "Envía <strong>HELP</strong> en el asunto para ver todos los comandos disponibles.");

        } catch (Exception e) {
            return PAyuda.generarError("Error inesperado al procesar el comando: " + e.getMessage());
        }
    }

    /** HELP filtrado: solo los comandos que el rol puede ejecutar, agrupados por categoría. */
    private static String ayudaPorRol(String rol) {
        boolean anonimo = rol == null || rol.isBlank() || "DESCONOCIDO".equalsIgnoreCase(rol);
        StringBuilder sb = new StringBuilder();
        sb.append("<h2 style=\"font-size:18px;font-weight:700;color:#111827;margin:0 0 12px;\">Comandos disponibles");
        if (!anonimo) sb.append(" &mdash; rol: ").append(rol);
        sb.append("</h2>");
        if (anonimo) sb.append("<p style=\"color:#6b7280;font-size:13px;\">No est&aacute;s registrado: solo ves los comandos p&uacute;blicos.</p>");

        String categoriaActual = null;
        for (CatalogoComandos.Comando c : CatalogoComandos.todos()) {
            boolean permitido = anonimo ? Permisos.esPublico(c.nombre) : Permisos.puedeEjecutar(c.nombre, rol);
            if (!permitido) continue;
            if (!c.categoria.equals(categoriaActual)) {
                categoriaActual = c.categoria;
                sb.append("<h3 style=\"font-size:14px;font-weight:700;color:#b91c1c;margin:14px 0 6px;\">")
                  .append(categoriaActual).append("</h3>");
            }
            sb.append("<div style=\"margin:5px 0;\">")
              .append("<code style=\"font-family:'Courier New',monospace;background:#f1f5f9;color:#1d4ed8;")
              .append("padding:2px 7px;border-radius:4px;font-size:12px;\">").append(escapar(c.sintaxis)).append("</code>")
              .append(" &mdash; <span style=\"color:#374151;font-size:13px;\">").append(c.descripcion).append("</span></div>");
        }
        return PlantillaBase.envolver("Ayuda", sb.toString());
    }

    private static String escapar(String s) {
        return s == null ? "" : s.replace("<", "&lt;").replace(">", "&gt;");
    }

    private static String whoAmI(String correoRemitente) {
        try {
            UsuarioM u = UsuarioM.buscarPorCorreo(correoRemitente);
            if (u == null) {
                String cuerpo =
                    "<h2 style=\"font-size:18px;font-weight:700;color:#111827;margin:0 0 14px 0;padding-bottom:10px;border-bottom:2px solid #e5e7eb;\">&#128100; &iquest;Qui&eacute;n eres?</h2>" +
                    "<div style=\"background-color:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:16px 20px;color:#991b1b;font-size:15px;\">" +
                    "<strong>No est&aacute;s registrado</strong><br>" +
                    "El correo <strong>" + correoRemitente + "</strong> no tiene cuenta en el sistema.<br><br>" +
                    "Para registrarte env&iacute;a:<br>" +
                    "<code style=\"font-family:'Courier New',monospace;background-color:#f1f5f9;color:#1d4ed8;" +
                    "padding:4px 10px;border-radius:4px;font-size:13px;display:block;margin-top:8px;" +
                    "word-wrap:break-word;overflow-wrap:break-word;word-break:break-word;\">" +
                    "CREATEUSUARIO[TuNombre," + correoRemitente + ",TuContrase&ntilde;a,CLIENTE,TuTel&eacute;fono,TuDirecci&oacute;n]</code>" +
                    "</div>";
                return PlantillaBase.envolver("Mi Perfil", cuerpo);
            }
            String cuerpo =
                "<h2 style=\"font-size:18px;font-weight:700;color:#111827;margin:0 0 14px 0;padding-bottom:10px;border-bottom:2px solid #e5e7eb;\">&#128100; Tu perfil</h2>" +
                "<table style=\"width:100%;border-collapse:collapse;font-size:17px;\">" +
                fila("ID",            String.valueOf(u.getId())) +
                fila("Nombre",        nvl(u.getNombre())) +
                fila("Correo",         nvl(u.getCorreo())) +
                fila("Rol",           nvl(u.getRol())) +
                fila("Tel&eacute;fono", nvl(u.getTelefono())) +
                fila("Direcci&oacute;n", nvl(u.getDireccion())) +
                fila("Estado",        u.isActivo() ? "&#10004; Activo" : "Inactivo") +
                fila("Registro",      u.getFechaReg() != null ? u.getFechaReg().toString() : "—") +
                "</table>";
            return PlantillaBase.envolver("Mi Perfil", cuerpo);
        } catch (Exception e) {
            return PAyuda.generarError("No se pudo obtener tu perfil: " + e.getMessage());
        }
    }

    private static String fila(String label, String val) {
        return "<tr style=\"border-bottom:1px solid #e5e7eb;\">" +
               "<td style=\"padding:10px 14px;color:#6b7280;font-weight:600;width:35%;\">" + label + "</td>" +
               "<td style=\"padding:10px 14px;color:#111827;\">" + val + "</td>" +
               "</tr>";
    }

    private static String nvl(String v) { return v != null ? v : "—"; }

    private static String[] parsearAsunto(String asunto) {
        int inicio = asunto.indexOf('[');
        if (inicio == -1) return new String[]{asunto, ""};
        String cmd = asunto.substring(0, inicio).trim();
        int fin = asunto.lastIndexOf(']');
        String params = (fin > inicio) ? asunto.substring(inicio + 1, fin) : "";
        return new String[]{cmd, params};
    }
}

package org.mailgrupo02.presentacion.email.controladores;

import org.mailgrupo02.datos.modelo.ClienteM;
import org.mailgrupo02.datos.modelo.UsuarioM;
import org.mailgrupo02.negocio.usuarios.UsuarioService;
import org.mailgrupo02.negocio.usuarios.UsuarioN;
import org.mailgrupo02.presentacion.email.PUsuarios;

import java.util.List;
import java.util.regex.*;

public class UsuarioControlador {

    public static boolean canHandle(String cmd) {
        if (cmd == null) return false;
        switch (cmd.toUpperCase()) {
            case "LISTARUSUARIOS": case "LISTARUSUARIO":
            case "CREATEUSUARIO":
            case "UPDATEUSUARIO":
            case "UPDATECLIENTE":
            case "DELETEUSUARIO":
            case "GETUSUARIO":
                return true;
            default:
                return false;
        }
    }

    public static String handle(String cmd, List<String> params) {
        try {
            UsuarioService service = new UsuarioService(new UsuarioM());

            switch (cmd.toUpperCase()) {

                case "LISTARUSUARIOS":
                case "LISTARUSUARIO":
                    return PUsuarios.generarHtml(cmd, service.obtenerUsuarios());

                case "GETUSUARIO": {
                    if (params.isEmpty())
                        return PUsuarios.generarHtml(cmd, "Error: se requiere el ID del usuario.");
                    int id = Integer.parseInt(params.get(0).trim());
                    UsuarioN u = service.leerUsuario(id);
                    return PUsuarios.generarHtml(cmd, fichaUsuario(u, "get"));
                }

                case "CREATEUSUARIO": {
                    if (params.size() < 6)
                        return PUsuarios.generarHtml(cmd, "Error: se requieren 6 parámetros [nombre,email,password,rol,telefono,direccion].");
                    String msg = service.agregarUsuario(
                        params.get(0), params.get(1), params.get(2),
                        params.get(3), params.get(4), params.get(5));
                    int newId = extraerId(msg);
                    if (newId > 0) {
                        UsuarioN u = service.leerUsuario(newId);
                        return PUsuarios.generarHtml(cmd, fichaUsuario(u, "create"));
                    }
                    return PUsuarios.generarHtml(cmd, msg);
                }

                case "UPDATEUSUARIO": {
                    if (params.size() < 8)
                        return PUsuarios.generarHtml(cmd, "Error: se requieren 8 parámetros [id,nombre,email,password,rol,telefono,direccion,activo].");
                    int id = Integer.parseInt(params.get(0).trim());
                    UsuarioN antes = service.leerUsuario(id);
                    service.actualizarUsuario(id,
                        params.get(1), params.get(2), params.get(3), params.get(4),
                        params.get(5), params.get(6), Boolean.parseBoolean(params.get(7).trim()));
                    UsuarioN despues = service.leerUsuario(id);
                    return PUsuarios.generarHtml(cmd, diffUsuario(antes, despues));
                }

                case "UPDATECLIENTE": {
                    if (params.size() < 3)
                        return PUsuarios.generarHtml(cmd, "Error: se requieren 3 parámetros [id,nitCi,tipoCliente].");
                    int id = Integer.parseInt(params.get(0).trim());
                    String nitNuevo  = params.get(1).trim();
                    String tipoNuevo = params.get(2).trim().toUpperCase();
                    ClienteM cAntes = new ClienteM().leer(id);
                    if (cAntes == null)
                        return PUsuarios.generarHtml(cmd, "Error: no existe un cliente con ID " + id + ".");
                    UsuarioN usu = service.leerUsuario(id);
                    ClienteM cNuevo = new ClienteM();
                    cNuevo.setId(id);
                    cNuevo.setNitCi(nitNuevo);
                    cNuevo.setTipoCliente(tipoNuevo);
                    cNuevo.actualizar();
                    return PUsuarios.generarHtml(cmd, diffCliente(usu, cAntes, cNuevo));
                }

                case "DELETEUSUARIO": {
                    if (params.isEmpty())
                        return PUsuarios.generarHtml(cmd, "Error: se requiere el ID del usuario.");
                    int id = Integer.parseInt(params.get(0).trim());
                    UsuarioN u = null;
                    try { u = service.leerUsuario(id); } catch (Exception ignored) {}
                    String rawResult = service.eliminarUsuario(id);
                    if (u != null && !rawResult.toLowerCase().startsWith("error")
                            && !rawResult.toLowerCase().contains("no encontrado")) {
                        return PUsuarios.generarHtml(cmd, fichaUsuario(u, "delete"));
                    }
                    return PUsuarios.generarHtml(cmd, rawResult);
                }

                default:
                    return PUsuarios.generarHtml(cmd, "Error: Comando de usuarios no soportado.");
            }

        } catch (Exception e) {
            return PUsuarios.generarHtml(cmd, "Error: " + e.getMessage());
        }
    }

    // ─── Tarjetas HTML ───────────────────────────────────────────────────────

    private static String fichaUsuario(UsuarioN u, String tipo) {
        StringBuilder sb = new StringBuilder("<div class=\"detalle\">");
        switch (tipo) {
            case "create":
                sb.append("<div class=\"badge-ok\">&#10003; Usuario Creado Exitosamente &mdash; ID: ")
                  .append(u.getId()).append("</div>");
                break;
            case "delete":
                sb.append("<div class=\"badge-del\">&#128465; Usuario Eliminado &mdash; ID: ")
                  .append(u.getId()).append("</div>");
                break;
            default:
                sb.append("<div class=\"badge-ok\">&#128101; Datos del Usuario &mdash; ID: ")
                  .append(u.getId()).append("</div>");
        }
        sb.append("<table class=\"dt\">");
        fila(sb, "ID",               String.valueOf(u.getId()));
        fila(sb, "Nombre",           nvl(u.getNombre()));
        fila(sb, "Email",            nvl(u.getEmail()));
        fila(sb, "Rol",              nvl(u.getRol()));
        fila(sb, "Tel&eacute;fono",  nvl(u.getTelefono()));
        fila(sb, "Direcci&oacute;n", nvl(u.getDireccion()));
        fila(sb, "Estado",           u.isActivo() ? "&#10004; Activo" : "Inactivo");
        fila(sb, "Fecha Reg.",       nvl(u.getFechaReg()));
        sb.append("</table></div>");
        return sb.toString();
    }

    private static String diffUsuario(UsuarioN a, UsuarioN d) {
        StringBuilder sb = new StringBuilder("<div class=\"detalle\">");
        sb.append("<div class=\"badge-edit\">&#9998; Usuario Actualizado &mdash; ID: ")
          .append(d.getId()).append("</div>");
        sb.append("<table class=\"dif\">");
        sb.append("<tr><th>Campo</th><th>&#8592; Antes</th><th>Despu&eacute;s &#8594;</th></tr>");
        difFila(sb, "Nombre",           nvl(a.getNombre()),    nvl(d.getNombre()));
        difFila(sb, "Email",            nvl(a.getEmail()),     nvl(d.getEmail()));
        difFila(sb, "Rol",              nvl(a.getRol()),       nvl(d.getRol()));
        difFila(sb, "Tel&eacute;fono",  nvl(a.getTelefono()),  nvl(d.getTelefono()));
        difFila(sb, "Direcci&oacute;n", nvl(a.getDireccion()), nvl(d.getDireccion()));
        difFila(sb, "Estado",
            a.isActivo() ? "Activo" : "Inactivo",
            d.isActivo() ? "Activo" : "Inactivo");
        sb.append("</table></div>");
        return sb.toString();
    }

    private static String diffCliente(UsuarioN usu, ClienteM a, ClienteM d) {
        StringBuilder sb = new StringBuilder("<div class=\"detalle\">");
        sb.append("<div class=\"badge-edit\">&#9998; Datos de Cliente Actualizados &mdash; ID: ")
          .append(d.getId()).append("</div>");
        sb.append("<p style=\"font-size:17px;color:#374151;margin:0 0 14px;\">")
          .append("<strong>").append(nvl(usu.getNombre())).append("</strong>")
          .append(" &mdash; ").append(nvl(usu.getEmail()))
          .append(" &mdash; Rol: ").append(nvl(usu.getRol()))
          .append("</p>");
        sb.append("<table class=\"dif\">");
        sb.append("<tr><th>Campo</th><th>&#8592; Antes</th><th>Despu&eacute;s &#8594;</th></tr>");
        difFila(sb, "NIT / CI",      nvl(a.getNitCi()),       nvl(d.getNitCi()));
        difFila(sb, "Tipo Cliente",  nvl(a.getTipoCliente()), nvl(d.getTipoCliente()));
        sb.append("</table></div>");
        return sb.toString();
    }

    private static void fila(StringBuilder sb, String label, String val) {
        sb.append("<tr><td class=\"fl\">").append(label)
          .append("</td><td class=\"fv\">").append(val).append("</td></tr>");
    }

    private static void difFila(StringBuilder sb, String campo, String antes, String despues) {
        sb.append("<tr><td class=\"fn\">").append(campo).append("</td>")
          .append("<td class=\"bef\">").append(antes).append("</td>")
          .append("<td class=\"aft\">").append(despues).append("</td></tr>");
    }

    private static String nvl(String val) { return val != null ? val : "N/A"; }

    private static int extraerId(String msg) {
        if (msg == null) return -1;
        Matcher m = Pattern.compile("\\(ID:\\s*(\\d+)\\)").matcher(msg);
        return m.find() ? Integer.parseInt(m.group(1)) : -1;
    }
}

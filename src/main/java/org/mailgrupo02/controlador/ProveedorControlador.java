package org.mailgrupo02.controlador;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.vista.*;
import org.mailgrupo02.modelo.entidad.*;

import org.mailgrupo02.modelo.dao.ProveedorM;
import org.mailgrupo02.vista.PlantillaBase;

import java.sql.SQLException;
import java.util.List;

public class ProveedorControlador {

    public static boolean canHandle(String cmd) {
        if (cmd == null) return false;
        switch (cmd.toUpperCase()) {
            case "CREATEPROVEEDOR":
            case "LISTARPROVEEDORES":
            case "GETPROVEEDOR":
            case "DELETEPROVEEDOR":
            case "UPDATEPROVEEDOR":
                return true;
            default: return false;
        }
    }

    public static String handle(String cmd, List<String> params) {
        try {
            switch (cmd.toUpperCase()) {

                case "LISTARPROVEEDORES":
                    return html("Listado de Proveedores", listar());

                case "GETPROVEEDOR": {
                    if (params.isEmpty())
                        return html("Error", "Error: se requiere el ID del proveedor.");
                    int id = Integer.parseInt(params.get(0).trim());
                    ProveedorM p = ProveedorM.leer(id);
                    if (p == null)
                        return html("Error", "Error: Proveedor con ID " + id + " no encontrado.");
                    return html("Detalle de Proveedor", fichaProveedor(p, "get"));
                }

                case "CREATEPROVEEDOR": {
                    if (params.size() < 2)
                        return html("Error", "Error: se requieren al menos 2 par&aacute;metros [razonSocial,contacto,telefono].");
                    ProveedorM p = new ProveedorM();
                    p.setRazonSocial(params.get(0).trim());
                    p.setContactoPrincipal(params.get(1).trim());
                    p.setTelefono(params.size() > 2 ? params.get(2).trim() : "");
                    p.setActivo(true);
                    int newId = p.crear();
                    p = ProveedorM.leer(newId);
                    return html("Registrar Proveedor", fichaProveedor(p, "create"));
                }

                case "UPDATEPROVEEDOR": {
                    if (params.size() < 4)
                        return html("Error", "Error: se requieren 4 par&aacute;metros [id,razonSocial,contacto,telefono].");
                    int id = Integer.parseInt(params.get(0).trim());
                    ProveedorM antes = ProveedorM.leer(id);
                    if (antes == null)
                        return html("Error", "Error: Proveedor con ID " + id + " no encontrado.");
                    antes.setRazonSocial(params.get(1).trim());
                    antes.setContactoPrincipal(params.get(2).trim());
                    antes.setTelefono(params.get(3).trim());
                    ProveedorM snapshot = clonar(ProveedorM.leer(id));
                    antes.actualizar();
                    ProveedorM despues = ProveedorM.leer(id);
                    return html("Actualizar Proveedor", diffProveedor(snapshot, despues));
                }

                case "DELETEPROVEEDOR": {
                    if (params.isEmpty())
                        return html("Error", "Error: se requiere el ID del proveedor.");
                    int id = Integer.parseInt(params.get(0).trim());
                    ProveedorM p = ProveedorM.leer(id);
                    if (p == null)
                        return html("Error", "Error: Proveedor con ID " + id + " no encontrado.");
                    String raw = ProveedorM.eliminar(id);
                    if (raw.toLowerCase().startsWith("error"))
                        return html("Error", raw);
                    return html("Eliminar Proveedor", fichaProveedor(p, "delete"));
                }

                default:
                    return html("Error", "Comando de proveedor no soportado.");
            }
        } catch (Exception e) {
            return html("Error", "Error: " + e.getMessage());
        }
    }

    // ── Listado en texto (detectado como tabla por tablaHtml) ────────────────

    private static String listar() throws SQLException {
        List<ProveedorM> lista = ProveedorM.obtenerTodos();
        if (lista.isEmpty()) return "No hay proveedores registrados.";
        StringBuilder sb = new StringBuilder();
        String fmt = "%-5s %-30s %-25s %-15s %-6s%n";
        sb.append(String.format(fmt, "ID", "Razón Social", "Contacto", "Teléfono", "Activo"));
        sb.append("--------------------------------------------------------------------------------------------\r\n");
        for (ProveedorM p : lista) {
            sb.append(String.format(fmt,
                p.getId(),
                nvl(p.getRazonSocial()),
                nvl(p.getContactoPrincipal()),
                nvl(p.getTelefono()),
                p.isActivo() ? "SI" : "NO"));
        }
        return sb.toString();
    }

    // ─── Tarjetas HTML (estilos 100% inline — compatibles con Gmail) ──────────

    private static String fichaProveedor(ProveedorM p, String tipo) {
        StringBuilder sb = new StringBuilder();
        sb.append("<div style=\"border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:8px;\">");

        switch (tipo) {
            case "create":
                sb.append("<div style=\"display:block;background-color:#dcfce7;color:#166534;"
                        + "padding:10px 16px;font-weight:700;font-size:14px;"
                        + "border-bottom:1px solid #bbf7d0;\">")
                  .append("&#10003; Proveedor Registrado Exitosamente &mdash; ID: ").append(p.getId()).append("</div>");
                break;
            case "delete":
                sb.append("<div style=\"display:block;background-color:#fee2e2;color:#991b1b;"
                        + "padding:10px 16px;font-weight:700;font-size:14px;"
                        + "border-bottom:1px solid #fca5a5;\">")
                  .append("&#128465; Proveedor Eliminado &mdash; ID: ").append(p.getId()).append("</div>");
                break;
            default:
                sb.append("<div style=\"display:block;background-color:#dcfce7;color:#166534;"
                        + "padding:10px 16px;font-weight:700;font-size:14px;"
                        + "border-bottom:1px solid #bbf7d0;\">")
                  .append("&#128666; Datos del Proveedor &mdash; ID: ").append(p.getId()).append("</div>");
        }

        sb.append("<table style=\"width:100%;border-collapse:collapse;font-size:14px;\">");
        fila(sb, "ID",                    String.valueOf(p.getId()));
        fila(sb, "Raz&oacute;n Social",   nvl(p.getRazonSocial()));
        fila(sb, "Contacto Principal",    nvl(p.getContactoPrincipal()));
        fila(sb, "Tel&eacute;fono",       nvl(p.getTelefono()));
        fila(sb, "Estado",                p.isActivo() ? "&#10004; Activo" : "Inactivo");
        sb.append("</table></div>");
        return sb.toString();
    }

    private static String diffProveedor(ProveedorM a, ProveedorM d) {
        StringBuilder sb = new StringBuilder();
        sb.append("<div style=\"border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:8px;\">");
        sb.append("<div style=\"display:block;background-color:#fef9c3;color:#713f12;"
                + "padding:10px 16px;font-weight:700;font-size:14px;"
                + "border-bottom:1px solid #fef08a;\">")
          .append("&#9998; Proveedor Actualizado &mdash; ID: ").append(d.getId()).append("</div>");
        sb.append("<table style=\"width:100%;border-collapse:collapse;font-size:14px;\">");
        sb.append("<tr>")
          .append("<th style=\"background-color:#4a5568;color:#fff;padding:8px 14px;font-weight:700;width:28%;\">Campo</th>")
          .append("<th style=\"background-color:#fef2f2;color:#991b1b;padding:8px 14px;font-weight:700;width:36%;\">&#8592; Antes</th>")
          .append("<th style=\"background-color:#f0fdf4;color:#166534;padding:8px 14px;font-weight:700;width:36%;\">Despu&eacute;s &#8594;</th>")
          .append("</tr>");
        difFila(sb, "Raz&oacute;n Social", nvl(a.getRazonSocial()),       nvl(d.getRazonSocial()));
        difFila(sb, "Contacto",            nvl(a.getContactoPrincipal()), nvl(d.getContactoPrincipal()));
        difFila(sb, "Tel&eacute;fono",     nvl(a.getTelefono()),          nvl(d.getTelefono()));
        sb.append("</table></div>");
        return sb.toString();
    }

    private static void fila(StringBuilder sb, String label, String val) {
        sb.append("<tr>")
          .append("<td style=\"padding:8px 14px;color:#6b7280;font-weight:600;width:35%;"
                + "border-bottom:1px solid #f1f5f9;vertical-align:top;\">").append(label).append("</td>")
          .append("<td style=\"padding:8px 14px;color:#111827;"
                + "border-bottom:1px solid #f1f5f9;vertical-align:top;\">").append(val).append("</td>")
          .append("</tr>");
    }

    private static void difFila(StringBuilder sb, String campo, String antes, String despues) {
        sb.append("<tr>")
          .append("<td style=\"padding:8px 14px;color:#6b7280;font-weight:600;"
                + "border-bottom:1px solid #f1f5f9;vertical-align:top;\">").append(campo).append("</td>")
          .append("<td style=\"padding:8px 14px;color:#991b1b;background-color:#fff5f5;"
                + "border-bottom:1px solid #f1f5f9;vertical-align:top;\">").append(antes).append("</td>")
          .append("<td style=\"padding:8px 14px;color:#166534;background-color:#f0fdf4;"
                + "border-bottom:1px solid #f1f5f9;vertical-align:top;\">").append(despues).append("</td>")
          .append("</tr>");
    }

    // ── Presentación ─────────────────────────────────────────────────────────

    private static String html(String titulo, String resultado) {
        StringBuilder body = new StringBuilder();
        body.append(PlantillaBase.titulo(titulo));
        if (resultado.startsWith("<div style=\"border:1px solid #e2e8f0")) {
            body.append(resultado);
        } else if (resultado.contains("---") || resultado.contains("===")) {
            body.append(PlantillaBase.tablaHtml("&#128666;", resultado));
        } else if (resultado.trim().toLowerCase().startsWith("error")) {
            body.append(PlantillaBase.errCard(resultado));
        } else {
            String idStr = PlantillaBase.extraerId(resultado);
            String limpio = idStr != null ? resultado.replaceAll("\\s*\\(ID:\\s*\\d+\\)", "").trim() : resultado;
            body.append(PlantillaBase.okCard(limpio, idStr));
        }
        return PlantillaBase.envolver("Gesti&oacute;n de Proveedores", body.toString());
    }

    private static ProveedorM clonar(ProveedorM p) {
        ProveedorM c = new ProveedorM();
        c.setId(p.getId());
        c.setRazonSocial(p.getRazonSocial());
        c.setContactoPrincipal(p.getContactoPrincipal());
        c.setTelefono(p.getTelefono());
        c.setActivo(p.isActivo());
        return c;
    }

    private static String nvl(String s) { return s != null ? s : "N/A"; }
}

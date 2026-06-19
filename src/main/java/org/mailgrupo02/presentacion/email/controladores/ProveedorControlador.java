package org.mailgrupo02.presentacion.email.controladores;

import org.mailgrupo02.datos.modelo.ProveedorM;
import org.mailgrupo02.presentacion.email.PlantillaBase;

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
                    return html("Detalle de Proveedor", detalle(Integer.parseInt(params.get(0).trim())));
                }

                case "CREATEPROVEEDOR": {
                    if (params.size() < 2)
                        return html("Error", "Error: se requieren al menos 2 parámetros [razonSocial,contacto,telefono].");
                    String razonSocial = params.get(0).trim();
                    String contacto    = params.get(1).trim();
                    String telefono    = params.size() > 2 ? params.get(2).trim() : "";
                    return html("Registrar Proveedor", crear(razonSocial, contacto, telefono));
                }

                case "UPDATEPROVEEDOR": {
                    if (params.size() < 4)
                        return html("Error", "Error: se requieren 4 parámetros [id,razonSocial,contacto,telefono].");
                    return html("Actualizar Proveedor", actualizar(
                        Integer.parseInt(params.get(0).trim()),
                        params.get(1).trim(),
                        params.get(2).trim(),
                        params.get(3).trim()));
                }

                case "DELETEPROVEEDOR": {
                    if (params.isEmpty())
                        return html("Error", "Error: se requiere el ID del proveedor.");
                    return html("Eliminar Proveedor",
                        ProveedorM.eliminar(Integer.parseInt(params.get(0).trim())));
                }

                default:
                    return html("Error", "Comando de proveedor no soportado.");
            }
        } catch (Exception e) {
            return html("Error", "Error: " + e.getMessage());
        }
    }

    // ── CRUD ─────────────────────────────────────────────────────────────────

    private static String crear(String razonSocial, String contacto, String telefono) throws SQLException {
        ProveedorM p = new ProveedorM();
        p.setRazonSocial(razonSocial);
        p.setContactoPrincipal(contacto);
        p.setTelefono(telefono);
        p.setActivo(true);
        int id = p.crear();
        return "Proveedor registrado exitosamente (ID: " + id + ")";
    }

    private static String actualizar(int id, String razonSocial, String contacto, String telefono)
            throws SQLException {
        ProveedorM p = ProveedorM.leer(id);
        if (p == null) return "Error: Proveedor no encontrado.";
        p.setRazonSocial(razonSocial);
        p.setContactoPrincipal(contacto);
        p.setTelefono(telefono);
        return p.actualizar() + " (ID: " + id + ")";
    }

    private static String listar() throws SQLException {
        List<ProveedorM> lista = ProveedorM.obtenerTodos();
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

    private static String detalle(int id) throws SQLException {
        ProveedorM p = ProveedorM.leer(id);
        if (p == null) return "Error: Proveedor no encontrado.";
        return "Razón Social: " + nvl(p.getRazonSocial()) +
               " | Contacto: " + nvl(p.getContactoPrincipal()) +
               " | Teléfono: " + nvl(p.getTelefono()) +
               " | Activo: " + (p.isActivo() ? "SI" : "NO") +
               " (ID: " + id + ")";
    }

    // ── Presentación ─────────────────────────────────────────────────────────

    private static String html(String titulo, String resultado) {
        StringBuilder body = new StringBuilder();
        body.append(PlantillaBase.titulo(titulo));
        boolean esError = resultado.trim().toLowerCase().startsWith("error");
        if (resultado.contains("---") || resultado.contains("===")) {
            body.append(PlantillaBase.tablaHtml("&#128666;", resultado));
        } else if (esError) {
            body.append(PlantillaBase.errCard(resultado));
        } else {
            String idStr = PlantillaBase.extraerId(resultado);
            String limpio = idStr != null ? resultado.replaceAll("\\s*\\(ID:\\s*\\d+\\)", "").trim() : resultado;
            body.append(PlantillaBase.okCard(limpio, idStr));
        }
        return PlantillaBase.envolver("Gesti&oacute;n de Proveedores", body.toString());
    }

    private static String nvl(String s) { return s != null ? s : ""; }
}

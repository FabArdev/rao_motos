package org.mailgrupo02.presentacion.email.controladores;

import org.mailgrupo02.datos.conexion.Conexion;
import org.mailgrupo02.datos.modelo.UsuarioM;
import org.mailgrupo02.negocio.pagos.PagoCuotaService;
import org.mailgrupo02.negocio.usuarios.UsuarioService;
import org.mailgrupo02.presentacion.email.PlantillaBase;
import org.mailgrupo02.presentacion.email.PPagos;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.List;

public class PagoControlador {

    public static boolean canHandle(String cmd) {
        if (cmd == null) return false;
        switch (cmd.toUpperCase()) {
            case "LISTARCREDITOS":
            case "VERCUOTAS":
            case "PAGARCUOTA":
            case "REGISTRARPAGO":
            case "MISCREDITOS":
            case "MISCUOTAS":
            case "ESTADOCUENTA":
                return true;
            default:
                return false;
        }
    }

    public static String handle(String cmd, List<String> params, String emailRemitente) {
        try {
            PagoCuotaService service = new PagoCuotaService();
            UsuarioService usuarioService = new UsuarioService(null);
            String rawResult;

            switch (cmd.toUpperCase()) {

                // ── Admin ────────────────────────────────────────────────────────
                case "LISTARCREDITOS":
                    rawResult = service.listarCreditos();
                    break;

                case "VERCUOTAS":
                    if (params.isEmpty()) return PPagos.generarHtml(cmd, "Error: se requiere el ID del crédito.");
                    rawResult = service.verCuotas(Integer.parseInt(params.get(0).trim()));
                    break;

                // ── Cliente ──────────────────────────────────────────────────────
                case "MISCREDITOS": {
                    int clienteId = usuarioService.buscarIdPorEmail(emailRemitente);
                    if (clienteId < 0) return PPagos.generarHtml(cmd, PedidoControlador.msgNoRegistrado(emailRemitente));
                    rawResult = service.listarCreditosPorCliente(clienteId);
                    break;
                }

                case "MISCUOTAS": {
                    if (params.isEmpty()) return PPagos.generarHtml(cmd, "Error: se requiere el ID del crédito.");
                    int clienteId = usuarioService.buscarIdPorEmail(emailRemitente);
                    if (clienteId < 0) return PPagos.generarHtml(cmd, PedidoControlador.msgNoRegistrado(emailRemitente));
                    rawResult = service.verCuotasCliente(Integer.parseInt(params.get(0).trim()), clienteId);
                    break;
                }

                // ── Ambos ────────────────────────────────────────────────────────
                case "PAGARCUOTA":
                case "REGISTRARPAGO":
                    if (params.size() < 2) return PPagos.generarHtml(cmd, "Error: se requieren 2 parámetros [creditoId,numeroCuota].");
                    rawResult = service.registrarPago(
                        Integer.parseInt(params.get(0).trim()),
                        Integer.parseInt(params.get(1).trim()));
                    break;

                case "ESTADOCUENTA": {
                    int clienteId = usuarioService.buscarIdPorEmail(emailRemitente);
                    if (clienteId < 0) return PPagos.generarHtml(cmd, PedidoControlador.msgNoRegistrado(emailRemitente));
                    return PPagos.generarHtml(cmd, estadoCuenta(clienteId, emailRemitente));
                }

                default:
                    rawResult = "Error: Comando de pagos no soportado.";
            }

            return PPagos.generarHtml(cmd, rawResult);
        } catch (Exception e) {
            return PPagos.generarHtml(cmd, "Error: " + e.getMessage());
        }
    }

    // ── Estado de cuenta (resumen financiero del cliente) ────────────────────

    private static String estadoCuenta(int clienteId, String email) {
        StringBuilder sb = new StringBuilder();
        try {
            UsuarioM u = UsuarioM.leer(clienteId);
            String nombre = u != null && u.getNombre() != null ? u.getNombre() : email;

            sb.append("<div style=\"border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:16px;\">");
            sb.append("<div style=\"background-color:#1e3a8a;color:#fff;padding:12px 16px;font-weight:700;font-size:15px;\">")
              .append("&#128100; Estado de Cuenta &mdash; ").append(nombre).append("</div>");
            sb.append("<div style=\"padding:10px 16px;font-size:13px;color:#374151;\">")
              .append("<strong>Email:</strong> ").append(email).append("</div>");
            sb.append("</div>");

            // ── Cuotas vencidas ───────────────────────────────────────────────
            sb.append(seccionEstado("&#9888;&#65039; Cuotas Vencidas", "#fee2e2", "#991b1b",
                queryCuotas(clienteId, "VENCIDO")));

            // ── Cuotas pendientes (próximas 60 días) ──────────────────────────
            sb.append(seccionEstado("&#128197; Pr&oacute;ximas Cuotas (60 d&iacute;as)", "#fef9c3", "#713f12",
                queryCuotas(clienteId, "PENDIENTE")));

            // ── Créditos activos ──────────────────────────────────────────────
            sb.append(seccionEstado("&#128184; Cr&eacute;ditos Vigentes", "#dbeafe", "#1e3a8a",
                queryCreditos(clienteId)));

            // ── Pedidos activos ───────────────────────────────────────────────
            sb.append(seccionEstado("&#128221; Pedidos Activos", "#f3e8ff", "#6b21a8",
                queryPedidos(clienteId)));

        } catch (Exception e) {
            sb.append("<p style=\"color:#991b1b;\">Error al cargar estado de cuenta: ").append(e.getMessage()).append("</p>");
        }
        return sb.toString();
    }

    private static String seccionEstado(String titulo, String bgHeader, String colorHeader, String[][] filas) {
        StringBuilder sb = new StringBuilder();
        sb.append("<div style=\"border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:12px;\">");
        sb.append("<div style=\"background-color:").append(bgHeader).append(";color:").append(colorHeader)
          .append(";padding:8px 14px;font-weight:700;font-size:13px;\">")
          .append(titulo);
        if (filas.length > 0) sb.append(" <span style=\"font-weight:400;\">(").append(filas.length).append(")</span>");
        sb.append("</div>");
        if (filas.length == 0) {
            sb.append("<div style=\"padding:10px 14px;font-size:13px;color:#6b7280;\">Ninguno</div>");
        } else {
            sb.append("<table style=\"width:100%;border-collapse:collapse;font-size:13px;\">");
            for (String[] fila : filas) {
                sb.append("<tr>");
                for (int i = 0; i < fila.length; i++) {
                    String tdStyle = "padding:7px 12px;border-bottom:1px solid #f1f5f9;color:#1e293b;";
                    if (i == 0) tdStyle += "font-weight:600;color:#6b7280;width:22%;";
                    sb.append("<td style=\"").append(tdStyle).append("\">").append(fila[i]).append("</td>");
                }
                sb.append("</tr>");
            }
            sb.append("</table>");
        }
        sb.append("</div>");
        return sb.toString();
    }

    private static String[][] queryCuotas(int clienteId, String estado) {
        String sql = "SELECT pc.id, pc.numero_cuota, pc.monto_cuota, pc.fecha_vencimiento, " +
                     "pc.mora, c.id AS credito_id " +
                     "FROM pago_cuota pc " +
                     "JOIN credito c ON pc.credito_id = c.id " +
                     "JOIN venta v ON c.venta_id = v.id " +
                     "WHERE v.cliente_id = ? AND pc.estado = ? " +
                     (estado.equals("PENDIENTE") ? "AND pc.fecha_vencimiento <= CURRENT_DATE + INTERVAL '60 days' " : "") +
                     "ORDER BY pc.fecha_vencimiento ASC LIMIT 10";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, clienteId);
            ps.setString(2, estado);
            try (ResultSet rs = ps.executeQuery()) {
                java.util.List<String[]> rows = new java.util.ArrayList<>();
                while (rs.next()) {
                    String mora = rs.getDouble("mora") > 0
                        ? " (+Bs." + String.format("%.2f", rs.getDouble("mora")) + " mora)" : "";
                    rows.add(new String[]{
                        "Cr&eacute;dito #" + rs.getInt("credito_id"),
                        "Cuota " + rs.getInt("numero_cuota"),
                        "Bs. " + String.format("%.2f", rs.getDouble("monto_cuota")) + mora,
                        "Vence: " + rs.getDate("fecha_vencimiento")
                    });
                }
                return rows.toArray(new String[0][]);
            }
        } catch (Exception e) {
            return new String[][]{{"Error", e.getMessage(), "", ""}};
        }
    }

    private static String[][] queryCreditos(int clienteId) {
        String sql = "SELECT c.id, c.numero_cuotas, c.saldo_pendiente, c.estado, c.tasa_interes " +
                     "FROM credito c " +
                     "JOIN venta v ON c.venta_id = v.id " +
                     "WHERE v.cliente_id = ? AND c.estado IN ('VIGENTE','MOROSO') " +
                     "ORDER BY c.id DESC";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, clienteId);
            try (ResultSet rs = ps.executeQuery()) {
                java.util.List<String[]> rows = new java.util.ArrayList<>();
                while (rs.next()) {
                    rows.add(new String[]{
                        "Cr&eacute;dito #" + rs.getInt("id"),
                        "Saldo: Bs. " + String.format("%.2f", rs.getDouble("saldo_pendiente")),
                        rs.getInt("numero_cuotas") + " cuotas &bull; " + rs.getDouble("tasa_interes") + "% inter&eacute;s",
                        rs.getString("estado")
                    });
                }
                return rows.toArray(new String[0][]);
            }
        } catch (Exception e) {
            return new String[][]{{"Error", e.getMessage(), "", ""}};
        }
    }

    private static String[][] queryPedidos(int clienteId) {
        String sql = "SELECT id, estado, fecha_pedido FROM pedido " +
                     "WHERE cliente_id = ? AND estado = 'SOLICITADO' ORDER BY fecha_pedido DESC";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, clienteId);
            try (ResultSet rs = ps.executeQuery()) {
                java.util.List<String[]> rows = new java.util.ArrayList<>();
                while (rs.next()) {
                    rows.add(new String[]{
                        "Pedido #" + rs.getInt("id"),
                        rs.getString("estado"),
                        "Fecha: " + rs.getDate("fecha_pedido"),
                        "Env&iacute;a CANCELARPEDIDO[" + rs.getInt("id") + "] para cancelar"
                    });
                }
                return rows.toArray(new String[0][]);
            }
        } catch (Exception e) {
            return new String[][]{{"Error", e.getMessage(), "", ""}};
        }
    }
}

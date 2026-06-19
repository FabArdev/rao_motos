package org.mailgrupo02.presentacion.email.controladores;

import org.mailgrupo02.datos.modelo.CreditoM;
import org.mailgrupo02.datos.modelo.UsuarioM;
import org.mailgrupo02.datos.modelo.VentaM;
import org.mailgrupo02.negocio.pagos.PagoFacilService;
import org.mailgrupo02.negocio.usuarios.UsuarioService;
import org.mailgrupo02.negocio.ventas.VentaService;
import org.mailgrupo02.presentacion.email.PlantillaBase;
import org.mailgrupo02.presentacion.email.PVentas;

import java.sql.Timestamp;
import java.util.List;

public class VentaControlador {

    public static boolean canHandle(String cmd) {
        if (cmd == null) return false;
        switch (cmd.toUpperCase()) {
            case "LISTARVENTAS": case "LISTARVENTA":
            case "CREARVENTA_CONTADO":
            case "CREARVENTA_CREDITO":
            case "GETVENTA":
            case "DELETEVENTA":
            case "MISVENTAS":
            case "MIVENTA":
                return true;
            default:
                return false;
        }
    }

    public static String handle(String cmd, List<String> params, String emailRemitente) {
        try {
            VentaService service = new VentaService(new VentaM(), new CreditoM());
            String rawResult;

            switch (cmd.toUpperCase()) {
                case "LISTARVENTAS":
                case "LISTARVENTA":
                    rawResult = service.obtenerVentas();
                    break;

                case "GETVENTA": {
                    if (params.isEmpty()) return PVentas.generarHtml(cmd, "Error: se requiere el ID de la venta.");
                    rawResult = service.leerVenta(Integer.parseInt(params.get(0).trim())).toString();
                    break;
                }

                case "CREARVENTA_CONTADO": {
                    if (params.size() < 4) return PVentas.generarHtml(cmd, "Error: se requieren 4 par&aacute;metros [clienteId,fecha,montoTotal,metodoPago].");
                    String metodo = params.get(3).trim();
                    int cId = Integer.parseInt(params.get(0).trim());
                    double monto = Double.parseDouble(params.get(2).trim());
                    rawResult = service.crearVentaContado(cId, Timestamp.valueOf(params.get(1).trim()), monto, metodo);
                    if ("QR".equalsIgnoreCase(metodo)) {
                        String idStr = PlantillaBase.extraerId(rawResult);
                        if (idStr != null) {
                            String qrHtml = generarQRVenta(Integer.parseInt(idStr), cId, monto);
                            if (qrHtml != null) rawResult += qrHtml;
                        }
                    }
                    break;
                }

                case "CREARVENTA_CREDITO":
                    if (params.size() < 6) return PVentas.generarHtml(cmd, "Error: se requieren 6 par&aacute;metros [clienteId,fecha,montoTotal,nroCuotas,tasaInteres,metodoPago].");
                    rawResult = service.crearVentaCredito(
                        Integer.parseInt(params.get(0).trim()),
                        Timestamp.valueOf(params.get(1).trim()),
                        Double.parseDouble(params.get(2).trim()),
                        Integer.parseInt(params.get(3).trim()),
                        Double.parseDouble(params.get(4).trim()),
                        params.get(5).trim());
                    break;

                case "DELETEVENTA":
                    if (params.isEmpty()) return PVentas.generarHtml(cmd, "Error: se requiere el ID de la venta.");
                    rawResult = service.eliminarVenta(Integer.parseInt(params.get(0).trim()));
                    break;

                // ── Comandos CLIENTE ──────────────────────────────────────────────

                case "MISVENTAS": {
                    int clienteId = new UsuarioService(null).buscarIdPorEmail(emailRemitente);
                    if (clienteId < 0) return PVentas.generarHtml(cmd, PedidoControlador.msgNoRegistrado(emailRemitente));
                    rawResult = service.obtenerPorCliente(clienteId);
                    break;
                }

                case "MIVENTA": {
                    if (params.isEmpty()) return PVentas.generarHtml(cmd, "Error: se requiere el ID de la venta.");
                    int clienteId = new UsuarioService(null).buscarIdPorEmail(emailRemitente);
                    if (clienteId < 0) return PVentas.generarHtml(cmd, PedidoControlador.msgNoRegistrado(emailRemitente));
                    rawResult = service.leerVentaCliente(Integer.parseInt(params.get(0).trim()), clienteId).toString();
                    break;
                }

                default:
                    rawResult = "Error: Comando de ventas no soportado.";
            }

            return PVentas.generarHtml(cmd, rawResult);
        } catch (Exception e) {
            return PVentas.generarHtml(cmd, "Error: " + e.getMessage());
        }
    }

    private static String generarQRVenta(int ventaId, int clienteId, double monto) {
        String nombre = "", email = "", tel = "";
        try {
            UsuarioM u = UsuarioM.leer(clienteId);
            if (u != null) {
                nombre = u.getNombre()   != null ? u.getNombre()   : "";
                email  = u.getEmail()    != null ? u.getEmail()    : "";
                tel    = u.getTelefono() != null ? u.getTelefono() : "";
            }
        } catch (Exception e) {
            System.err.println("[VentaControlador] " + e.getMessage());
        }

        String txId = "VTA-" + ventaId;
        String[] qr = PagoFacilService.generarQR(nombre, tel, email, txId, monto, "Venta al contado #" + ventaId);
        if (qr == null) return null;

        PagoFacilService.registrarTransaccion(txId, email, monto, "venta;" + qr[0]);

        String b64 = qr[1].replace("\r", "").replace("\n", "").trim();
        return "<div style=\"text-align:center;margin:15px 0;\">" +
               "<img src=\"data:image/png;base64," + b64 +
               "\" style=\"max-width:250px;border:4px solid #1d4ed8;border-radius:12px;\"><br><br>" +
               "<strong style=\"color:#1d4ed8;font-size:15px;\">Monto: " +
               String.format("%.2f", monto) + " Bs.</strong><br>" +
               "<span style=\"color:#6b7280;font-size:12px;\">Ref: " + txId + "</span>" +
               "</div>";
    }
}

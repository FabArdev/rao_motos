package org.mailgrupo02.controlador;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.vista.*;
import org.mailgrupo02.modelo.entidad.*;

import org.mailgrupo02.modelo.dao.CreditoM;
import org.mailgrupo02.modelo.dao.UsuarioM;
import org.mailgrupo02.modelo.dao.VentaM;
import org.mailgrupo02.modelo.servicio.PagoFacilService;
import org.mailgrupo02.modelo.servicio.UsuarioService;
import org.mailgrupo02.modelo.servicio.VentaService;
import org.mailgrupo02.vista.PlantillaBase;
import org.mailgrupo02.vista.PVentas;

import java.sql.Timestamp;
import java.util.List;

public class VentaControlador {

    public static boolean canHandle(String cmd) {
        if (cmd == null) return false;
        switch (cmd.toUpperCase()) {
            case "LISTARVENTAS": case "LISTARVENTA":
            case "CREARVENTA_CONTADO":
            case "CREARVENTA_CREDITO":
            case "CONFIRMARPAGO":
            case "ANULARVENTA":
            case "GETVENTA":
            case "DELETEVENTA":
            case "MISVENTAS":
            case "MIVENTA":
                return true;
            default:
                return false;
        }
    }

    public static String handle(String cmd, List<String> params, String correoRemitente) {
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
                    if (params.size() < 3) return PVentas.generarHtml(cmd, "Error: formato CREARVENTA_CONTADO[clienteId,metodo,prod:cant;prod:cant].");
                    int cId = Integer.parseInt(params.get(0).trim());
                    String metodo = params.get(1).trim();
                    int vendedorId = new UsuarioService(null).buscarIdPorCorreo(correoRemitente);
                    rawResult = service.crearContadoItems(vendedorId, cId, metodo, VentaService.parseItems(params.get(2)));
                    if ("QR".equalsIgnoreCase(metodo)) {
                        String idStr = PlantillaBase.extraerId(rawResult);
                        if (idStr != null) {
                            double monto = VentaM.leer(Integer.parseInt(idStr)).getMontoTotal();
                            String qrHtml = generarQRVenta(Integer.parseInt(idStr), cId, monto);
                            if (qrHtml != null) rawResult += qrHtml;
                        }
                    }
                    break;
                }

                case "CREARVENTA_CREDITO":
                    if (params.size() < 5) return PVentas.generarHtml(cmd, "Error: formato CREARVENTA_CREDITO[clienteId,cuotas,interes,metodo,prod:cant;prod:cant].");
                    rawResult = service.crearCreditoItems(
                        new UsuarioService(null).buscarIdPorCorreo(correoRemitente),
                        Integer.parseInt(params.get(0).trim()),
                        Integer.parseInt(params.get(1).trim()),
                        Double.parseDouble(params.get(2).trim()),
                        params.get(3).trim(),
                        VentaService.parseItems(params.get(4)));
                    break;

                case "CONFIRMARPAGO":
                    if (params.isEmpty()) return PVentas.generarHtml(cmd, "Error: se requiere el ID de la venta.");
                    rawResult = service.confirmarPago(Integer.parseInt(params.get(0).trim()));
                    break;

                case "ANULARVENTA":
                    if (params.isEmpty()) return PVentas.generarHtml(cmd, "Error: se requiere el ID de la venta.");
                    rawResult = service.anularVenta(Integer.parseInt(params.get(0).trim()));
                    break;

                case "DELETEVENTA":
                    if (params.isEmpty()) return PVentas.generarHtml(cmd, "Error: se requiere el ID de la venta.");
                    rawResult = service.eliminarVenta(Integer.parseInt(params.get(0).trim()));
                    break;

                // ── Comandos CLIENTE ──────────────────────────────────────────────

                case "MISVENTAS": {
                    int clienteId = new UsuarioService(null).buscarIdPorCorreo(correoRemitente);
                    if (clienteId < 0) return PVentas.generarHtml(cmd, PedidoControlador.msgNoRegistrado(correoRemitente));
                    rawResult = service.obtenerPorCliente(clienteId);
                    break;
                }

                case "MIVENTA": {
                    if (params.isEmpty()) return PVentas.generarHtml(cmd, "Error: se requiere el ID de la venta.");
                    int clienteId = new UsuarioService(null).buscarIdPorCorreo(correoRemitente);
                    if (clienteId < 0) return PVentas.generarHtml(cmd, PedidoControlador.msgNoRegistrado(correoRemitente));
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
        String nombre = "", correo = "", tel = "";
        try {
            UsuarioM u = UsuarioM.leer(clienteId);
            if (u != null) {
                nombre = u.getNombre()   != null ? u.getNombre()   : "";
                correo  = u.getCorreo()    != null ? u.getCorreo()    : "";
                tel    = u.getTelefono() != null ? u.getTelefono() : "";
            }
        } catch (Exception e) {
            System.err.println("[VentaControlador] " + e.getMessage());
        }

        String txId = "VTA-" + ventaId;
        String[] qr = PagoFacilService.generarQR(nombre, tel, correo, txId, monto, "Venta al contado #" + ventaId);
        if (qr == null) return null;

        PagoFacilService.registrarTransaccion(txId, correo, monto, "venta;" + qr[0]);

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

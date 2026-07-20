package org.mailgrupo02.controlador;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.vista.*;
import org.mailgrupo02.modelo.entidad.*;

import org.mailgrupo02.modelo.servicio.CompraService;
import org.mailgrupo02.vista.PCompras;

import java.util.List;

public class CompraControlador {

    public static boolean canHandle(String cmd) {
        if (cmd == null) return false;
        switch (cmd.toUpperCase()) {
            case "LISTARCOMPRAS": case "LISTARCOMPRA":
            case "CREARCOMPRA":
            case "AGREGARDETALLECOMPRA":
            case "RECIBIRCOMPRA":
            case "ANULARCOMPRA":
            case "GETCOMPRA":
                return true;
            default:
                return false;
        }
    }

    public static String handle(String cmd, List<String> params) {
        try {
            CompraService service = new CompraService();
            String rawResult;

            switch (cmd.toUpperCase()) {
                case "LISTARCOMPRAS":
                case "LISTARCOMPRA":
                    rawResult = service.obtenerCompras();
                    break;

                case "GETCOMPRA": {
                    if (params.isEmpty()) return PCompras.generarHtml(cmd, "Error: se requiere el ID de la compra.");
                    rawResult = service.leerCompra(Integer.parseInt(params.get(0).trim())).toString();
                    break;
                }

                case "CREARCOMPRA":
                    if (params.isEmpty()) return PCompras.generarHtml(cmd, "Error: se requiere el ID del proveedor. Formato CREARCOMPRA[proveedorId].");
                    rawResult = service.crearCompra(Integer.parseInt(params.get(0).trim()), 0);
                    break;

                case "AGREGARDETALLECOMPRA":
                    if (params.size() < 4) return PCompras.generarHtml(cmd, "Error: formato AGREGARDETALLECOMPRA[compraId,productoId,cantidad,precioUnitario].");
                    rawResult = service.agregarDetalle(
                        Integer.parseInt(params.get(0).trim()),
                        Integer.parseInt(params.get(1).trim()),
                        Integer.parseInt(params.get(2).trim()),
                        Double.parseDouble(params.get(3).trim()));
                    break;

                case "RECIBIRCOMPRA":
                    if (params.isEmpty()) return PCompras.generarHtml(cmd, "Error: se requiere el ID de la compra.");
                    rawResult = service.recibirCompra(Integer.parseInt(params.get(0).trim()));
                    break;

                case "ANULARCOMPRA":
                    if (params.isEmpty()) return PCompras.generarHtml(cmd, "Error: se requiere el ID de la compra.");
                    rawResult = service.anularCompra(Integer.parseInt(params.get(0).trim()));
                    break;

                default:
                    rawResult = "Error: Comando de compras no soportado.";
            }

            return PCompras.generarHtml(cmd, rawResult);
        } catch (Exception e) {
            return PCompras.generarHtml(cmd, "Error: " + e.getMessage());
        }
    }
}

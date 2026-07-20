package org.mailgrupo02.controlador;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.vista.*;
import org.mailgrupo02.modelo.entidad.*;

import org.mailgrupo02.modelo.servicio.InventarioService;
import org.mailgrupo02.vista.PInventario;

import java.util.List;

public class InventarioControlador {

    public static boolean canHandle(String cmd) {
        if (cmd == null) return false;
        switch (cmd.toUpperCase()) {
            case "VERINVENTARIO":
            case "REGISTRARINGRESO":
            case "REGISTRAREGRESO":
                return true;
            default:
                return false;
        }
    }

    public static String handle(String cmd, List<String> params) {
        try {
            InventarioService service = new InventarioService();
            String rawResult;

            switch (cmd.toUpperCase()) {
                case "VERINVENTARIO":
                    if (params.isEmpty() || params.get(0).trim().equals("*")) {
                        rawResult = service.verInventario();
                    } else {
                        rawResult = service.verInventarioPorProducto(Integer.parseInt(params.get(0).trim()));
                    }
                    break;

                case "REGISTRARINGRESO":
                    if (params.size() < 3) return PInventario.generarHtml(cmd, "Error: se requieren 3 parámetros [productoId,cantidad,motivo].");
                    rawResult = service.registrarIngreso(
                        Integer.parseInt(params.get(0).trim()),
                        Integer.parseInt(params.get(1).trim()),
                        params.get(2).trim());
                    break;

                case "REGISTRAREGRESO":
                    if (params.size() < 3) return PInventario.generarHtml(cmd, "Error: se requieren 3 parámetros [productoId,cantidad,motivo].");
                    rawResult = service.registrarEgreso(
                        Integer.parseInt(params.get(0).trim()),
                        Integer.parseInt(params.get(1).trim()),
                        params.get(2).trim());
                    break;

                default:
                    rawResult = "Error: Comando de inventario no soportado.";
            }

            return PInventario.generarHtml(cmd, rawResult);
        } catch (Exception e) {
            return PInventario.generarHtml(cmd, "Error: " + e.getMessage());
        }
    }
}

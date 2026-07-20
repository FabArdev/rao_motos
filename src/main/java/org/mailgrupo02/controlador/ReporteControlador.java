package org.mailgrupo02.controlador;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.vista.*;
import org.mailgrupo02.modelo.entidad.*;

import org.mailgrupo02.modelo.dao.EstadisticaM;
import org.mailgrupo02.vista.PReportes;

import java.util.List;
import java.util.Map;

public class ReporteControlador {

    public static boolean canHandle(String cmd) {
        if (cmd == null) return false;
        switch (cmd.toUpperCase()) {
            case "REPORT_VENTAS_POR_MES":
            case "REPORT_VENTAS_POR_CLIENTE":
            case "REPORT_MORAS_PENDIENTES":
                return true;
            default:
                return false;
        }
    }

    public static String handle(String cmd, List<String> params) {
        try {
            EstadisticaM estadisticaM = new EstadisticaM();
            String rawResult;

            switch (cmd.toUpperCase()) {
                case "REPORT_VENTAS_POR_MES":
                    if (params.isEmpty()) return PReportes.generarHtml(cmd, "Error: se requiere el mes (YYYY-MM).");
                    rawResult = reporteVentasMes(estadisticaM, params.get(0).trim());
                    break;

                case "REPORT_VENTAS_POR_CLIENTE":
                    if (params.isEmpty()) return PReportes.generarHtml(cmd, "Error: se requiere el ID del cliente.");
                    rawResult = reporteVentasCliente(estadisticaM, Integer.parseInt(params.get(0).trim()));
                    break;

                case "REPORT_MORAS_PENDIENTES":
                    rawResult = reporteMoras(estadisticaM);
                    break;

                default:
                    rawResult = "Error: Comando de reportes no soportado.";
            }

            return PReportes.generarHtml(cmd, rawResult);
        } catch (Exception e) {
            return PReportes.generarHtml(cmd, "Error al generar reporte: " + e.getMessage());
        }
    }

    private static String reporteVentasMes(EstadisticaM m, String mes) throws Exception {
        List<Map<String, Object>> ventas = m.obtenerVentasPorMes(mes);
        Map<String, Object> totales = m.obtenerTotalesMes(mes);

        StringBuilder sb = new StringBuilder();
        sb.append("=== REPORTE DE VENTAS DEL MES ").append(mes).append(" ===\r\n\r\n");
        String format = "%-5s %-12s %-15s %-20s%n";
        sb.append(String.format(format, "ID", "Fecha", "Total", "Cliente"));
        sb.append("---------------------------------------------------------------------\r\n");
        for (Map<String, Object> v : ventas) {
            sb.append(String.format(format,
                v.get("id_venta"), v.get("fecha_venta"),
                String.format("%.2f", v.get("total")), v.get("cliente")));
        }
        sb.append("\r\n=== TOTALES ===\r\n");
        sb.append("Total Ventas:   ").append(totales.get("total_ventas")).append("\r\n");
        sb.append("Monto Total:    ").append(String.format("%.2f", totales.get("monto_total"))).append(" Bs.\r\n");
        sb.append("Total Contado:  ").append(String.format("%.2f", totales.get("total_contado"))).append(" Bs.\r\n");
        sb.append("Total Crédito:  ").append(String.format("%.2f", totales.get("total_credito"))).append(" Bs.\r\n");
        return sb.toString();
    }

    private static String reporteVentasCliente(EstadisticaM m, int clienteId) throws Exception {
        List<Map<String, Object>> ventas = m.obtenerVentasPorCliente(clienteId);

        StringBuilder sb = new StringBuilder();
        sb.append("=== REPORTE DE VENTAS POR CLIENTE (ID: ").append(clienteId).append(") ===\r\n\r\n");
        String format = "%-5s %-12s %-15s %-15s %-15s%n";
        sb.append(String.format(format, "ID", "Fecha", "Total", "Tipo Venta", "Estado"));
        sb.append("---------------------------------------------------------------------\r\n");
        double total = 0;
        for (Map<String, Object> v : ventas) {
            sb.append(String.format(format,
                v.get("id_venta"), v.get("fecha_venta"),
                String.format("%.2f", v.get("total")),
                v.get("tipo_venta"), v.get("estado")));
            total += (Double) v.get("total");
        }
        sb.append("\r\nTotal General: ").append(String.format("%.2f", total)).append(" Bs.\r\n");
        return sb.toString();
    }

    private static String reporteMoras(EstadisticaM m) throws Exception {
        List<Map<String, Object>> moras = m.obtenerMorasPendientes();

        StringBuilder sb = new StringBuilder();
        sb.append("=== REPORTE DE MORAS PENDIENTES ===\r\n\r\n");
        String format = "%-8s %-20s %-12s %-15s %-15s%n";
        sb.append(String.format(format, "ID Cuota", "Cliente", "Días Retraso", "Monto Mora", "Saldo Pend."));
        sb.append("---------------------------------------------------------------------\r\n");
        double totalMoras = 0;
        for (Map<String, Object> mora : moras) {
            sb.append(String.format(format,
                mora.get("id_cuota"), mora.get("cliente"),
                mora.get("dias_retraso"),
                String.format("%.2f", mora.get("monto_mora")),
                String.format("%.2f", mora.get("saldo_pendiente"))));
            totalMoras += (Double) mora.get("monto_mora");
        }
        sb.append("\r\nTotal Moras: ").append(String.format("%.2f", totalMoras)).append(" Bs.\r\n");
        return sb.toString();
    }
}

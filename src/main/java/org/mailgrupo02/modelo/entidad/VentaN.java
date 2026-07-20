package org.mailgrupo02.modelo.entidad;

import org.mailgrupo02.modelo.dao.*;

import java.io.Serializable;
import java.util.List;

public class VentaN implements Serializable {
    private static final long serialVersionUID = 1L;

    private int id;
    private int clienteId;
    private String fecha;
    private double montoTotal;
    private String tipoVenta;
    private String metodoPago;
    private String estado;
    private CreditoM credito;
    private List<PagoCuotaM> cuotas;
    private List<DetalleVentaM> detalles;

    public VentaN() {
    }

    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public int getClienteId() {
        return clienteId;
    }

    public void setClienteId(int clienteId) {
        this.clienteId = clienteId;
    }

    public String getFecha() {
        return fecha;
    }

    public void setFecha(String fecha) {
        this.fecha = fecha;
    }

    public double getMontoTotal() {
        return montoTotal;
    }

    public void setMontoTotal(double montoTotal) {
        this.montoTotal = montoTotal;
    }

    public String getTipoVenta() {
        return tipoVenta;
    }

    public void setTipoVenta(String tipoVenta) {
        this.tipoVenta = tipoVenta;
    }

    public String getMetodoPago() {
        return metodoPago;
    }

    public void setMetodoPago(String metodoPago) {
        this.metodoPago = metodoPago;
    }

    public String getEstado() {
        return estado;
    }

    public void setEstado(String estado) {
        this.estado = estado;
    }

    public CreditoM getCredito() {
        return credito;
    }

    public void setCredito(CreditoM credito) {
        this.credito = credito;
    }

    public List<PagoCuotaM> getCuotas() {
        return cuotas;
    }

    public void setCuotas(List<PagoCuotaM> cuotas) {
        this.cuotas = cuotas;
    }

    public List<DetalleVentaM> getDetalles() {
        return detalles;
    }

    public void setDetalles(List<DetalleVentaM> detalles) {
        this.detalles = detalles;
    }

    @Override
    public String toString() {
        StringBuilder sb = new StringBuilder();
        sb.append("=== DETALLE DE VENTA ===\r\n\r\n");
        sb.append("ID: ").append(id).append("\r\n");
        sb.append("Cliente ID: ").append(clienteId).append("\r\n");
        sb.append("Fecha: ").append(fecha).append("\r\n");
        sb.append("Monto Total: ").append(String.format("%.2f", montoTotal)).append("\r\n");
        sb.append("Tipo: ").append(tipoVenta).append("\r\n");
        sb.append("Método de Pago: ").append(metodoPago).append("\r\n");
        sb.append("Estado: ").append(estado).append("\r\n");

        if (detalles != null && !detalles.isEmpty()) {
            sb.append("\r\n--- Detalles de Venta ---\r\n");
            String fmt = "%-5s %-12s %-10s %-15s%n";
            sb.append(String.format(fmt, "ID", "Producto ID", "Cantidad", "Precio Unit."));
            sb.append("-----------------------------------------------\r\n");
            for (DetalleVentaM d : detalles) {
                sb.append(String.format(fmt, d.getId(), d.getProductoId(), d.getCantidad(),
                        String.format("%.2f", d.getPrecioUnitario())));
            }
        }

        if (credito != null) {
            sb.append("\r\n--- Crédito Asociado ---\r\n");
            sb.append("ID Crédito: ").append(credito.getId()).append("\r\n");
            sb.append("Número de Cuotas: ").append(credito.getNumeroCuotas()).append("\r\n");
            sb.append("Tasa de Interés: ").append(String.format("%.2f%%", credito.getTasaInteres())).append("\r\n");
            sb.append("Saldo Pendiente: ").append(String.format("%.2f", credito.getSaldoPendiente())).append("\r\n");
            sb.append("Estado Crédito: ").append(credito.getEstado()).append("\r\n");

            if (cuotas != null && !cuotas.isEmpty()) {
                sb.append("\r\n--- Cuotas ---\r\n");
                String cfmt = "%-6s %-12s %-16s %-12s %-10s%n";
                sb.append(String.format(cfmt, "Nro", "Monto", "Fecha Venc.", "Fecha Pago", "Estado"));
                sb.append("--------------------------------------------------------------\r\n");
                for (PagoCuotaM c : cuotas) {
                    sb.append(String.format(cfmt,
                            c.getNumeroCuota(),
                            String.format("%.2f", c.getMontoCuota()),
                            c.getFechaVencimiento() != null ? c.getFechaVencimiento().toString() : "N/A",
                            c.getFechaPago() != null ? c.getFechaPago().toString() : "PENDIENTE",
                            c.getEstado()));
                }
            }
        }

        return sb.toString();
    }
}

package org.mailgrupo02.modelo.entidad;

import org.mailgrupo02.modelo.dao.PedidoDetalleM;

import java.io.Serializable;
import java.util.List;

public class PedidoN implements Serializable {
    private static final long serialVersionUID = 1L;

    private int id;
    private int clienteId;
    private String fecha;
    private String estado;
    private List<PedidoDetalleM> detalles;

    public PedidoN() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getClienteId() { return clienteId; }
    public void setClienteId(int clienteId) { this.clienteId = clienteId; }
    public String getFecha() { return fecha; }
    public void setFecha(String fecha) { this.fecha = fecha; }
    public String getEstado() { return estado; }
    public void setEstado(String estado) { this.estado = estado; }
    public List<PedidoDetalleM> getDetalles() { return detalles; }
    public void setDetalles(List<PedidoDetalleM> detalles) { this.detalles = detalles; }

    @Override
    public String toString() {
        StringBuilder sb = new StringBuilder();
        sb.append("=== DETALLE DE PEDIDO ===\r\n\r\n");
        sb.append("ID: ").append(id).append("\r\n");
        sb.append("Cliente ID: ").append(clienteId).append("\r\n");
        sb.append("Fecha: ").append(fecha).append("\r\n");
        sb.append("Estado: ").append(estado).append("\r\n");

        if (detalles != null && !detalles.isEmpty()) {
            sb.append("\r\n--- Detalles del Pedido ---\r\n");
            String fmt = "%-5s %-12s %-10s%n";
            sb.append(String.format(fmt, "ID", "Producto ID", "Cantidad"));
            sb.append("-----------------------------------\r\n");
            for (PedidoDetalleM d : detalles) {
                sb.append(String.format(fmt, d.getId(), d.getProductoId(), d.getCantidad()));
            }
        }

        return sb.toString();
    }
}

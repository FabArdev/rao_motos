package org.mailgrupo02.modelo.entidad;

import org.mailgrupo02.modelo.dao.DetalleCompraM;

import java.io.Serializable;
import java.util.List;

public class CompraN implements Serializable {
    private static final long serialVersionUID = 1L;

    private int id;
    private int proveedorId;
    private String fecha;
    private double total;
    private String estado;
    private List<DetalleCompraM> detalles;

    public CompraN() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getProveedorId() { return proveedorId; }
    public void setProveedorId(int proveedorId) { this.proveedorId = proveedorId; }
    public String getFecha() { return fecha; }
    public void setFecha(String fecha) { this.fecha = fecha; }
    public double getTotal() { return total; }
    public void setTotal(double total) { this.total = total; }
    public String getEstado() { return estado; }
    public void setEstado(String estado) { this.estado = estado; }
    public List<DetalleCompraM> getDetalles() { return detalles; }
    public void setDetalles(List<DetalleCompraM> detalles) { this.detalles = detalles; }

    @Override
    public String toString() {
        StringBuilder sb = new StringBuilder();
        sb.append("=== DETALLE DE COMPRA ===\r\n\r\n");
        sb.append("ID: ").append(id).append("\r\n");
        sb.append("Proveedor ID: ").append(proveedorId).append("\r\n");
        sb.append("Fecha: ").append(fecha).append("\r\n");
        sb.append("Total: ").append(String.format("%.2f", total)).append("\r\n");
        sb.append("Estado: ").append(estado).append("\r\n");

        if (detalles != null && !detalles.isEmpty()) {
            sb.append("\r\n--- Detalles de Compra ---\r\n");
            String fmt = "%-5s %-12s %-10s %-15s%n";
            sb.append(String.format(fmt, "ID", "Producto ID", "Cantidad", "Precio Unit."));
            sb.append("-----------------------------------------------\r\n");
            for (DetalleCompraM d : detalles) {
                sb.append(String.format(fmt, d.getId(), d.getProductoId(), d.getCantidad(),
                        String.format("%.2f", d.getPrecioUnitario())));
            }
        }

        return sb.toString();
    }
}

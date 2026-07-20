package org.mailgrupo02.modelo.servicio;
import org.mailgrupo02.modelo.entidad.*;

import org.mailgrupo02.modelo.dao.*;

import java.sql.SQLException;
import java.sql.Timestamp;
import java.util.List;

public class CompraService {

    public String obtenerCompras() throws SQLException {
        List<CompraM> lista = new CompraM().obtenerTodos();
        return mapear(lista);
    }

    public String crearCompra(int proveedorId, double total) throws SQLException {
        CompraM compra = new CompraM();
        compra.setProveedorId(proveedorId);
        compra.setTotal(total);
        compra.setFecha(new Timestamp(System.currentTimeMillis()));
        compra.setEstado("PENDIENTE");
        return compra.crear();
    }

    public String agregarDetalle(int compraId, int productoId, int cantidad, double precioUnitario) throws SQLException {
        DetalleCompraM detalle = new DetalleCompraM();
        detalle.setCompraId(compraId);
        detalle.setProductoId(productoId);
        detalle.setCantidad(cantidad);
        detalle.setPrecioUnitario(precioUnitario);
        return detalle.crear();
    }

    public CompraN leerCompra(int id) throws SQLException {
        CompraM compra = new CompraM().leer(id);
        if (compra == null) {
            throw new SQLException("Compra no encontrada");
        }

        CompraN n = new CompraN();
        n.setId(compra.getId());
        n.setProveedorId(compra.getProveedorId());
        n.setFecha(compra.getFecha() != null ? compra.getFecha().toString() : "N/A");
        n.setTotal(compra.getTotal());
        n.setEstado(compra.getEstado());

        DetalleCompraM detM = new DetalleCompraM();
        n.setDetalles(detM.obtenerPorCompra(id));

        return n;
    }

    /**
     * Recibe una compra PENDIENTE (RN23): recalcula el total desde el detalle (RN11),
     * ingresa el stock al inventario y recalcula los precios de venta de cada producto
     * desde el costo × (1 + margen%). Los márgenes salen de `configuracion`.
     */
    public String recibirCompra(int id) throws SQLException {
        CompraM compra = new CompraM().leer(id);
        if (compra == null) return "Compra no encontrada";
        if (!"PENDIENTE".equals(compra.getEstado()))
            return "Error: solo se reciben compras PENDIENTE (estado actual: " + compra.getEstado() + ").";

        List<DetalleCompraM> dets = new DetalleCompraM().obtenerPorCompra(id);
        if (dets == null || dets.isEmpty())
            return "Error: la compra #" + id + " no tiene detalle. Use AGREGARDETALLECOMPRA.";

        double margenMin = ConfiguracionM.valorNum("margen_venta_minorista", 25) / 100.0;
        double margenMay = ConfiguracionM.valorNum("margen_venta_mayorista", 15) / 100.0;
        InventarioService inv = new InventarioService();

        double total = 0;
        for (DetalleCompraM d : dets) {
            total += d.getPrecioUnitario() * d.getCantidad();
            inv.registrarIngreso(d.getProductoId(), d.getCantidad(), "Compra #" + id + " recibida");
            double costo = d.getPrecioUnitario();
            ProductoM.actualizarPrecios(d.getProductoId(),
                    redondear2(costo * (1 + margenMin)),
                    redondear2(costo * (1 + margenMay)));
        }

        compra.setTotal(total);
        compra.setEstado("RECIBIDA");
        compra.actualizar();
        return "Compra #" + id + " RECIBIDA — total Bs. " + String.format("%.2f", total)
             + ", inventario ingresado y precios de venta recalculados.";
    }

    /** Anula una compra; si estaba RECIBIDA, revierte el inventario (RN15). */
    public String anularCompra(int id) throws SQLException {
        CompraM compra = new CompraM().leer(id);
        if (compra == null) return "Compra no encontrada";
        if ("ANULADA".equals(compra.getEstado())) return "La compra #" + id + " ya está anulada.";

        boolean revertir = "RECIBIDA".equals(compra.getEstado());
        if (revertir) {
            InventarioService inv = new InventarioService();
            for (DetalleCompraM d : new DetalleCompraM().obtenerPorCompra(id)) {
                inv.registrarEgreso(d.getProductoId(), d.getCantidad(), "Anulación compra #" + id);
            }
        }
        compra.setEstado("ANULADA");
        String res = compra.actualizar();
        return revertir ? res + " (inventario revertido)" : res;
    }

    private static double redondear2(double v) {
        return Math.round(v * 100.0) / 100.0;
    }

    private String mapear(List<CompraM> lista) {
        StringBuilder sb = new StringBuilder();
        String format = "%-5s %-12s %-22s %-12s %-10s%n";
        sb.append(String.format(format, "ID", "Proveedor", "Fecha", "Total", "Estado"));
        sb.append("---------------------------------------------------------------------\r\n");
        for (CompraM c : lista) {
            sb.append(String.format(format,
                    c.getId(),
                    c.getProveedorId(),
                    c.getFecha() != null ? c.getFecha().toString() : "N/A",
                    String.format("%.2f", c.getTotal()),
                    c.getEstado() != null ? c.getEstado() : "N/A"));
        }
        return sb.toString();
    }
}

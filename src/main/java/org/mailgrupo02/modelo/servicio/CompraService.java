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

    public String anularCompra(int id) throws SQLException {
        CompraM compra = new CompraM().leer(id);
        if (compra == null) {
            return "Compra no encontrada";
        }
        compra.setEstado("ANULADA");
        return compra.actualizar();
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

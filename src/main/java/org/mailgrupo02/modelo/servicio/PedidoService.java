package org.mailgrupo02.modelo.servicio;
import org.mailgrupo02.modelo.entidad.*;

import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.modelo.servicio.InventarioService;

import java.sql.SQLException;
import java.sql.Timestamp;
import java.util.List;

public class PedidoService {

    public String obtenerPedidos() throws SQLException {
        List<PedidoM> lista = PedidoM.obtenerTodos();
        return mapear(lista);
    }

    /** Admin: crea pedido vacío para un cliente (por clienteId). */
    public String crearPedido(int clienteId) throws SQLException {
        PedidoM pedido = new PedidoM();
        pedido.setClienteId(clienteId);
        pedido.setFecha(new Timestamp(System.currentTimeMillis()));
        pedido.setEstado("SOLICITADO");
        int id = PedidoM.crear(pedido);
        return "Pedido creado con éxito (ID: " + id + ")";
    }

    /**
     * Cliente: crea un pedido con sus productos en una sola operación.
     * Verifica existencia, estado activo y stock disponible antes de crear nada.
     * items = array de [productoId, cantidad].
     */
    public String crearConProductos(int clienteId, int[][] items) throws SQLException {
        if (items == null || items.length == 0) {
            return "Error: debe especificar al menos un producto.";
        }

        InventarioService invService = new InventarioService();

        // ── Fase 1: validar todo antes de escribir nada ───────────────────────
        StringBuilder errores = new StringBuilder();
        ProductoM[] productos = new ProductoM[items.length];
        for (int i = 0; i < items.length; i++) {
            int productoId = items[i][0];
            int cantidad   = items[i][1];
            ProductoM p = ProductoM.leer(productoId);
            if (p == null || !p.isActivo()) {
                errores.append("\n  • Producto ID ").append(productoId).append(": no existe o está inactivo.");
                continue;
            }
            productos[i] = p;
            int stock = invService.obtenerStock(productoId);
            if (stock < 0) {
                errores.append("\n  • ").append(p.getNombre())
                       .append(": sin inventario registrado (stock = 0).");
            } else if (stock < cantidad) {
                errores.append("\n  • ").append(p.getNombre())
                       .append(": stock insuficiente — disponible: ").append(stock)
                       .append(", solicitado: ").append(cantidad).append(".");
            }
        }
        if (errores.length() > 0) {
            return "No se pudo crear el pedido por problemas de stock:" + errores.toString();
        }

        // ── Fase 2: crear el pedido y sus detalles ────────────────────────────
        PedidoM pedido = new PedidoM();
        pedido.setClienteId(clienteId);
        pedido.setFecha(new Timestamp(System.currentTimeMillis()));
        pedido.setEstado("SOLICITADO");
        int pedidoId = PedidoM.crear(pedido);

        StringBuilder resumen = new StringBuilder();
        for (int i = 0; i < items.length; i++) {
            int productoId = items[i][0];
            int cantidad   = items[i][1];
            PedidoDetalleM det = new PedidoDetalleM();
            det.setPedidoId(pedidoId);
            det.setProductoId(productoId);
            det.setCantidad(cantidad);
            PedidoDetalleM.crear(det);
            resumen.append("\n  • ").append(productos[i].getNombre())
                   .append(" x").append(cantidad)
                   .append(" — Bs. ").append(String.format("%.2f", productos[i].getPrecioVentaBase() * cantidad));
        }
        return "Pedido registrado exitosamente (ID: " + pedidoId + ")" + resumen.toString();
    }

    /** Cliente: lista sus propios pedidos. */
    public String obtenerPorCliente(int clienteId) throws SQLException {
        List<PedidoM> lista = PedidoM.obtenerPorCliente(clienteId);
        if (lista.isEmpty()) return "No tienes pedidos registrados.";
        return mapear(lista);
    }

    public PedidoN leerPedido(int id) throws SQLException {
        PedidoM pedido = PedidoM.leer(id);
        PedidoN n = new PedidoN();
        n.setId(pedido.getId());
        n.setClienteId(pedido.getClienteId());
        n.setFecha(pedido.getFecha() != null ? pedido.getFecha().toString() : "N/A");
        n.setEstado(pedido.getEstado());
        n.setDetalles(PedidoDetalleM.obtenerPorPedido(id));
        return n;
    }

    /** Cliente: obtiene su pedido verificando que le pertenezca. */
    public String leerPedidoCliente(int pedidoId, int clienteId) throws SQLException {
        PedidoM p = PedidoM.leer(pedidoId);
        if (p.getClienteId() != clienteId) return "Error: el pedido no pertenece a tu cuenta.";
        PedidoN n = leerPedido(pedidoId);
        return formatearDetalle(n);
    }

    /** Cliente: cancela su pedido solo si está en estado SOLICITADO. */
    public String cancelarPorCliente(int pedidoId, int clienteId) throws SQLException {
        PedidoM p = PedidoM.leer(pedidoId);
        if (p.getClienteId() != clienteId) return "Error: el pedido no pertenece a tu cuenta.";
        if (!"SOLICITADO".equals(p.getEstado())) {
            return "Error: solo puedes cancelar pedidos en estado SOLICITADO. Estado actual: " + p.getEstado();
        }
        p.setEstado("ANULADO");
        return PedidoM.actualizar(p);
    }

    public String despacharPedido(int id) throws SQLException {
        PedidoM pedido = PedidoM.leer(id);
        if (pedido == null) return "Error: pedido #" + id + " no encontrado.";
        if ("DESPACHADO".equals(pedido.getEstado())) return "Error: el pedido ya fue despachado.";
        if ("ANULADO".equals(pedido.getEstado()))    return "Error: el pedido está anulado.";

        List<PedidoDetalleM> detalles = PedidoDetalleM.obtenerPorPedido(id);
        InventarioService invService = new InventarioService();

        // ── Verificar stock antes de despachar ────────────────────────────────
        StringBuilder errores = new StringBuilder();
        for (PedidoDetalleM det : detalles) {
            int stock = invService.obtenerStock(det.getProductoId());
            ProductoM p = ProductoM.leer(det.getProductoId());
            String nombre = p != null ? p.getNombre() : "ID:" + det.getProductoId();
            if (stock < 0) {
                errores.append("\n  • ").append(nombre).append(": sin inventario registrado.");
            } else if (stock < det.getCantidad()) {
                errores.append("\n  • ").append(nombre)
                       .append(": stock insuficiente — disponible: ").append(stock)
                       .append(", requerido: ").append(det.getCantidad()).append(".");
            }
        }
        if (errores.length() > 0) {
            return "No se puede despachar el pedido #" + id + " por stock insuficiente:" + errores.toString();
        }

        // ── Descontar stock de cada producto ──────────────────────────────────
        for (PedidoDetalleM det : detalles) {
            invService.registrarEgreso(det.getProductoId(), det.getCantidad(), "Despacho Pedido #" + id);
        }

        pedido.setEstado("DESPACHADO");
        return PedidoM.actualizar(pedido);
    }

    public String anularPedido(int id) throws SQLException {
        PedidoM pedido = PedidoM.leer(id);
        if (pedido == null) return "Error: pedido #" + id + " no encontrado.";
        if ("ANULADO".equals(pedido.getEstado())) return "Error: el pedido ya está anulado.";

        boolean fueDespachado = "DESPACHADO".equals(pedido.getEstado());
        pedido.setEstado("ANULADO");
        String resultado = PedidoM.actualizar(pedido);

        // Si ya estaba despachado, restaurar el stock
        if (fueDespachado) {
            List<PedidoDetalleM> detalles = PedidoDetalleM.obtenerPorPedido(id);
            InventarioService invService = new InventarioService();
            for (PedidoDetalleM det : detalles) {
                invService.registrarIngreso(det.getProductoId(), det.getCantidad(), "Anulación Pedido #" + id);
            }
            return resultado + " (stock restaurado por anulación de pedido despachado)";
        }
        return resultado;
    }

    public String agregarDetalle(int pedidoId, int productoId, int cantidad) throws SQLException {
        PedidoDetalleM det = new PedidoDetalleM();
        det.setPedidoId(pedidoId);
        det.setProductoId(productoId);
        det.setCantidad(cantidad);
        return PedidoDetalleM.crear(det);
    }

    private String mapear(List<PedidoM> lista) {
        StringBuilder sb = new StringBuilder();
        String fmt = "%-5s %-10s %-22s %-12s%n";
        sb.append(String.format(fmt, "ID", "Cliente", "Fecha", "Estado"));
        sb.append("---------------------------------------------------------\r\n");
        for (PedidoM p : lista) {
            sb.append(String.format(fmt,
                p.getId(), p.getClienteId(),
                p.getFecha() != null ? p.getFecha().toString() : "N/A",
                p.getEstado() != null ? p.getEstado() : "N/A"));
        }
        return sb.toString();
    }

    private String formatearDetalle(PedidoN n) throws SQLException {
        StringBuilder sb = new StringBuilder();
        sb.append(String.format("Pedido #%d | Estado: %s | Fecha: %s%n",
            n.getId(), n.getEstado(), n.getFecha()));
        sb.append("---------------------------------------------------------\r\n");
        List<PedidoDetalleM> detalles = n.getDetalles();
        if (detalles == null || detalles.isEmpty()) {
            sb.append("Sin productos registrados.\r\n");
        } else {
            String fmt = "%-5s %-30s %-8s%n";
            sb.append(String.format(fmt, "ProdID", "Producto", "Cantidad"));
            sb.append("-----------------------------------------\r\n");
            for (PedidoDetalleM d : detalles) {
                try {
                    ProductoM p = ProductoM.leer(d.getProductoId());
                    String nombre = p != null ? p.getNombre() : "ID:" + d.getProductoId();
                    sb.append(String.format(fmt, d.getProductoId(), nombre, d.getCantidad()));
                } catch (Exception e) {
                    sb.append(String.format(fmt, d.getProductoId(), "N/A", d.getCantidad()));
                }
            }
        }
        return sb.toString();
    }
}

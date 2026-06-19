package org.mailgrupo02.negocio.ventas;

import org.mailgrupo02.datos.modelo.*;

import java.sql.SQLException;
import java.sql.Timestamp;
import java.sql.Date;
import java.util.Calendar;
import java.util.List;

public class VentaService {

    public VentaService(VentaM ventaM, CreditoM creditoM) {
    }

    public String obtenerVentas() throws SQLException {
        List<VentaM> ventas = VentaM.obtenerTodos();
        return mapear(ventas);
    }

    public String crearVentaContado(int clienteId, Timestamp fecha, double montoTotal, String metodoPago)
            throws SQLException {
        VentaValidator.validarVentaContado(clienteId, fecha, montoTotal, metodoPago);

        VentaM venta = new VentaM();
        venta.setClienteId(clienteId);
        venta.setFecha(fecha);
        venta.setMontoTotal(montoTotal);
        venta.setTipoVenta("CONTADO");
        venta.setMetodoPago(metodoPago);
        venta.setEstado("COMPLETADA");

        int ventaId = VentaM.crear(venta);
        return "Venta al contado creada con éxito (ID: " + ventaId + ")";
    }

    public String crearVentaCredito(int clienteId, Timestamp fecha, double montoTotal,
            int numeroCuotas, double tasaInteres, String metodoPago) throws SQLException {
        VentaValidator.validarVentaCredito(clienteId, fecha, montoTotal, numeroCuotas, tasaInteres, metodoPago);

        VentaM venta = new VentaM();
        venta.setClienteId(clienteId);
        venta.setFecha(fecha);
        venta.setMontoTotal(montoTotal);
        venta.setTipoVenta("CREDITO");
        venta.setMetodoPago(metodoPago);
        venta.setEstado("PENDIENTE");

        int ventaId = VentaM.crear(venta);

        CreditoM credito = new CreditoM();
        credito.setVentaId(ventaId);
        credito.setNumeroCuotas(numeroCuotas);
        credito.setTasaInteres(tasaInteres);
        credito.setSaldoPendiente(montoTotal);
        credito.setEstado("VIGENTE");

        int creditoId = CreditoM.crear(credito);

        double montoCuota = (montoTotal * (1 + tasaInteres / 100)) / numeroCuotas;
        Calendar cal = Calendar.getInstance();
        cal.setTime(fecha);

        for (int i = 1; i <= numeroCuotas; i++) {
            PagoCuotaM pago = new PagoCuotaM();
            pago.setCreditoId(creditoId);
            pago.setNumeroCuota(i);
            pago.setMontoCuota(montoCuota);
            cal.add(Calendar.MONTH, 1);
            pago.setFechaVencimiento(new Date(cal.getTimeInMillis()));
            pago.setFechaPago(null);
            pago.setMora(0);
            pago.setEstado("PENDIENTE");
            pago.crear();
        }

        return "Venta a crédito creada con éxito (ID: " + ventaId + "), Crédito ID: " + creditoId
                + " con " + numeroCuotas + " cuotas generadas.";
    }

    public String actualizarVenta(int id, int clienteId, Timestamp fecha, double montoTotal,
            String tipoVenta, String metodoPago, String estado) throws SQLException {
        VentaValidator.validarActualizarVenta(id, clienteId, fecha, montoTotal, tipoVenta, metodoPago, estado);

        VentaM venta = new VentaM();
        venta.setId(id);
        venta.setClienteId(clienteId);
        venta.setFecha(fecha);
        venta.setMontoTotal(montoTotal);
        venta.setTipoVenta(tipoVenta);
        venta.setMetodoPago(metodoPago);
        venta.setEstado(estado);

        return VentaM.actualizar(venta);
    }

    public VentaN leerVenta(int id) throws SQLException {
        VentaValidator.validarEliminarVenta(id);
        VentaM venta = VentaM.leer(id);

        VentaN n = new VentaN();
        n.setId(venta.getId());
        n.setClienteId(venta.getClienteId());
        n.setFecha(venta.getFecha() != null ? venta.getFecha().toString() : "N/A");
        n.setMontoTotal(venta.getMontoTotal());
        n.setTipoVenta(venta.getTipoVenta());
        n.setMetodoPago(venta.getMetodoPago());
        n.setEstado(venta.getEstado());

        DetalleVentaM detM = new DetalleVentaM();
        n.setDetalles(detM.obtenerPorVenta(id));

        if ("CREDITO".equals(venta.getTipoVenta())) {
            CreditoM credito = CreditoM.obtenerPorVenta(id);
            if (credito != null) {
                n.setCredito(credito);
                PagoCuotaM pagM = new PagoCuotaM();
                n.setCuotas(pagM.obtenerPorCredito(credito.getId()));
            }
        }

        return n;
    }

    /**
     * Convierte un pedido en una venta, calculando el total desde detalle_pedido.
     * Crea los detalle_venta correspondientes y, si es crédito, genera cuotas.
     * Actualiza el estado del pedido a EN_PROCESO.
     */
    public String procesarDesdePedido(int pedidoId, String tipo, String metodoPago,
                                      int cuotas, double tasa) throws SQLException {
        tipo = tipo.toUpperCase().trim();
        metodoPago = metodoPago.toUpperCase().trim();

        PedidoM pedido = PedidoM.leer(pedidoId);
        if (pedido == null) return "Error: pedido ID " + pedidoId + " no encontrado.";
        if ("ANULADO".equals(pedido.getEstado()) || "DESPACHADO".equals(pedido.getEstado())) {
            return "Error: el pedido está " + pedido.getEstado() + " y no puede procesarse.";
        }

        List<PedidoDetalleM> items = PedidoDetalleM.obtenerPorPedido(pedidoId);
        if (items == null || items.isEmpty()) {
            return "Error: el pedido #" + pedidoId + " no tiene productos. Use PEDIDO[prodId:cant,...] para agregar.";
        }

        // Calcular total desde precio_venta_base de cada producto
        double total = 0;
        for (PedidoDetalleM d : items) {
            ProductoM p = ProductoM.leer(d.getProductoId());
            if (p == null) return "Error: producto ID " + d.getProductoId() + " no encontrado.";
            total += p.getPrecioVentaBase() * d.getCantidad();
        }

        Timestamp ahora = new Timestamp(System.currentTimeMillis());
        VentaM venta = new VentaM();
        venta.setClienteId(pedido.getClienteId());
        venta.setFecha(ahora);
        venta.setMontoTotal(total);
        venta.setTipoVenta(tipo);
        venta.setMetodoPago(metodoPago);
        venta.setEstado("CONTADO".equals(tipo) ? "COMPLETADA" : "PENDIENTE");

        int ventaId = VentaM.crear(venta);

        // Crear detalle_venta por cada ítem del pedido
        for (PedidoDetalleM d : items) {
            ProductoM p = ProductoM.leer(d.getProductoId());
            DetalleVentaM dv = new DetalleVentaM();
            dv.setVentaId(ventaId);
            dv.setProductoId(d.getProductoId());
            dv.setCantidad(d.getCantidad());
            dv.setPrecioUnitario(p.getPrecioVentaBase());
            dv.crear();
        }

        // Si es crédito, generar crédito y cuotas
        String extraInfo = "";
        if ("CREDITO".equals(tipo)) {
            if (cuotas < 2) return "Error: las cuotas deben ser al menos 2.";
            CreditoM credito = new CreditoM();
            credito.setVentaId(ventaId);
            credito.setNumeroCuotas(cuotas);
            credito.setTasaInteres(tasa);
            credito.setSaldoPendiente(total);
            credito.setEstado("VIGENTE");
            int creditoId = CreditoM.crear(credito);

            double montoCuota = (total * (1 + tasa / 100)) / cuotas;
            java.util.Calendar cal = java.util.Calendar.getInstance();
            cal.setTime(ahora);
            for (int i = 1; i <= cuotas; i++) {
                PagoCuotaM pago = new PagoCuotaM();
                pago.setCreditoId(creditoId);
                pago.setNumeroCuota(i);
                pago.setMontoCuota(montoCuota);
                cal.add(java.util.Calendar.MONTH, 1);
                pago.setFechaVencimiento(new java.sql.Date(cal.getTimeInMillis()));
                pago.setFechaPago(null);
                pago.setMora(0);
                pago.setEstado("PENDIENTE");
                pago.crear();
            }
            extraInfo = " | Crédito ID: " + creditoId + " — " + cuotas + " cuotas de Bs. "
                + String.format("%.2f", montoCuota);
        }

        // Marcar pedido como EN_PROCESO
        pedido.setEstado("EN_PROCESO");
        PedidoM.actualizar(pedido);

        return "Venta procesada exitosamente (ID: " + ventaId + ") — Total: Bs. "
            + String.format("%.2f", total) + extraInfo;
    }

    public String obtenerPorCliente(int clienteId) throws SQLException {
        List<VentaM> ventas = VentaM.obtenerPorCliente(clienteId);
        if (ventas.isEmpty()) return "No tienes ventas registradas.";
        return mapear(ventas);
    }

    public VentaN leerVentaCliente(int ventaId, int clienteId) throws SQLException {
        VentaM v = VentaM.leer(ventaId);
        if (v.getClienteId() != clienteId)
            throw new SQLException("La venta no pertenece a tu cuenta.");
        VentaN n = new VentaN();
        n.setId(v.getId());
        n.setClienteId(v.getClienteId());
        n.setFecha(v.getFecha() != null ? v.getFecha().toString() : "N/A");
        n.setMontoTotal(v.getMontoTotal());
        n.setTipoVenta(v.getTipoVenta());
        n.setMetodoPago(v.getMetodoPago());
        n.setEstado(v.getEstado());
        DetalleVentaM detM = new DetalleVentaM();
        n.setDetalles(detM.obtenerPorVenta(ventaId));
        if ("CREDITO".equals(v.getTipoVenta())) {
            CreditoM credito = CreditoM.obtenerPorVenta(ventaId);
            if (credito != null) {
                n.setCredito(credito);
                n.setCuotas(new PagoCuotaM().obtenerPorCredito(credito.getId()));
            }
        }
        return n;
    }

    public String eliminarVenta(int id) throws SQLException {
        VentaValidator.validarEliminarVenta(id);
        return VentaM.eliminar(id);
    }

    private String mapear(List<VentaM> ventas) throws SQLException {
        StringBuilder sb = new StringBuilder();
        String format = "%-5s %-10s %-22s %-15s %-12s %-15s %-15s%n";
        sb.append(String.format(format, "ID", "Cliente", "Fecha", "Monto Total", "Tipo", "Metodo Pago", "Estado"));
        sb.append(
                "----------------------------------------------------------------------------------------------------\r\n");

        for (VentaM venta : ventas) {
            sb.append(String.format(format,
                    venta.getId(),
                    venta.getClienteId(),
                    venta.getFecha() != null ? venta.getFecha().toString() : "N/A",
                    String.format("%.2f", venta.getMontoTotal()),
                    venta.getTipoVenta() != null ? venta.getTipoVenta() : "N/A",
                    venta.getMetodoPago() != null ? venta.getMetodoPago() : "N/A",
                    venta.getEstado() != null ? venta.getEstado() : "N/A"));
        }
        return sb.toString();
    }
}

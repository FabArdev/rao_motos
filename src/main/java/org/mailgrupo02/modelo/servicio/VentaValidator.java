package org.mailgrupo02.modelo.servicio;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.modelo.entidad.*;

import java.sql.Timestamp;

public class VentaValidator {

    public static void validarVentaContado(int clienteId, Timestamp fecha, double montoTotal, String metodoPago) {
        if (clienteId <= 0) {
            throw new IllegalArgumentException("El ID del cliente debe ser mayor a 0");
        }
        if (fecha == null) {
            throw new IllegalArgumentException("La fecha es obligatoria");
        }
        if (montoTotal <= 0) {
            throw new IllegalArgumentException("El monto total debe ser mayor a 0");
        }
        if (metodoPago == null || metodoPago.trim().isEmpty()) {
            throw new IllegalArgumentException("El método de pago es obligatorio");
        }
    }

    public static void validarVentaCredito(int clienteId, Timestamp fecha, double montoTotal,
            int numeroCuotas, double tasaInteres, String metodoPago) {
        if (clienteId <= 0) {
            throw new IllegalArgumentException("El ID del cliente debe ser mayor a 0");
        }
        if (fecha == null) {
            throw new IllegalArgumentException("La fecha es obligatoria");
        }
        if (montoTotal <= 0) {
            throw new IllegalArgumentException("El monto total debe ser mayor a 0");
        }
        if (numeroCuotas <= 0) {
            throw new IllegalArgumentException("El número de cuotas debe ser mayor a 0");
        }
        if (tasaInteres < 0) {
            throw new IllegalArgumentException("La tasa de interés no puede ser negativa");
        }
        if (metodoPago == null || metodoPago.trim().isEmpty()) {
            throw new IllegalArgumentException("El método de pago es obligatorio");
        }
    }

    public static void validarActualizarVenta(int id, int clienteId, Timestamp fecha, double montoTotal,
            String tipoVenta, String metodoPago, String estado) {
        if (id <= 0) {
            throw new IllegalArgumentException("El ID de la venta debe ser mayor a 0");
        }
        if (clienteId <= 0) {
            throw new IllegalArgumentException("El ID del cliente debe ser mayor a 0");
        }
        if (fecha == null) {
            throw new IllegalArgumentException("La fecha es obligatoria");
        }
        if (montoTotal <= 0) {
            throw new IllegalArgumentException("El monto total debe ser mayor a 0");
        }
        if (tipoVenta == null || tipoVenta.trim().isEmpty()) {
            throw new IllegalArgumentException("El tipo de venta es obligatorio");
        }
        if (metodoPago == null || metodoPago.trim().isEmpty()) {
            throw new IllegalArgumentException("El método de pago es obligatorio");
        }
        if (estado == null || estado.trim().isEmpty()) {
            throw new IllegalArgumentException("El estado es obligatorio");
        }
    }

    public static void validarEliminarVenta(int id) {
        if (id <= 0) {
            throw new IllegalArgumentException("El ID de la venta debe ser mayor a 0");
        }
    }
}

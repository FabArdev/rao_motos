package org.mailgrupo02.modelo.servicio;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.modelo.entidad.*;

public class ProductoValidator {

    public static void validarCampos(String codigo, String nombre, String marca, String modelo,
            String descripcion, double precioVentaBase, double precioMayorista, int cantidadMinimaMayorista) {
        if (codigo == null || codigo.trim().isEmpty()) {
            throw new IllegalArgumentException("El código es obligatorio");
        }

        if (nombre == null || nombre.trim().isEmpty() || nombre.length() > 100) {
            throw new IllegalArgumentException("Nombre de producto inválido (max 100 caracteres)");
        }

        if (marca != null && marca.length() > 80) {
            throw new IllegalArgumentException("La marca no puede exceder 80 caracteres");
        }

        if (modelo != null && modelo.length() > 80) {
            throw new IllegalArgumentException("El modelo no puede exceder 80 caracteres");
        }

        if (descripcion != null && descripcion.length() > 255) {
            throw new IllegalArgumentException("Descripción de producto inválida (max 255 caracteres)");
        }

        if (precioVentaBase <= 0) {
            throw new IllegalArgumentException("El precio de venta base (minorista) debe ser mayor a 0");
        }

        if (precioMayorista <= 0) {
            throw new IllegalArgumentException("El precio mayorista debe ser mayor a 0");
        }

        if (precioMayorista > precioVentaBase) {
            throw new IllegalArgumentException("El precio mayorista no puede ser mayor al minorista");
        }

        if (cantidadMinimaMayorista < 1) {
            throw new IllegalArgumentException("La cantidad mínima para mayoreo debe ser >= 1");
        }
    }
}

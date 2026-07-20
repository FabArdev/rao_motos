package org.mailgrupo02.modelo.servicio;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.modelo.entidad.*;

import org.mailgrupo02.modelo.dao.ProductoM;

import java.sql.SQLException;
import java.util.List;

public class ProductoService {

    public ProductoService(ProductoM productoM) {
    }

    public String obtenerProductos() throws SQLException {
        List<ProductoM> productos = ProductoM.obtenerTodos();
        return mapear(productos);
    }

    public ProductoN leerProducto(int id) throws SQLException {
        ProductoM productoMObj = ProductoM.leer(id);
        return mapearUno(productoMObj);
    }

    public String agregarProducto(String codigo, String nombre, String marca, String modelo,
            String descripcion, double precioVentaBase, double precioMayorista,
            int cantidadMinimaMayorista) throws SQLException {
        ProductoValidator.validarCampos(codigo, nombre, marca, modelo, descripcion,
                precioVentaBase, precioMayorista, cantidadMinimaMayorista);
        ProductoM productoMObj = cargar(0, codigo, nombre, marca, modelo, descripcion,
                precioVentaBase, precioMayorista, cantidadMinimaMayorista, true);
        int id = ProductoM.crear(productoMObj);
        return "Producto creado con éxito (ID: " + id + ")";
    }

    public String actualizarProducto(int id, String codigo, String nombre, String marca, String modelo,
            String descripcion, double precioVentaBase, double precioMayorista,
            int cantidadMinimaMayorista, boolean activo) throws SQLException {
        ProductoValidator.validarCampos(codigo, nombre, marca, modelo, descripcion,
                precioVentaBase, precioMayorista, cantidadMinimaMayorista);
        ProductoM productoMObj = cargar(id, codigo, nombre, marca, modelo, descripcion,
                precioVentaBase, precioMayorista, cantidadMinimaMayorista, activo);
        return ProductoM.actualizar(productoMObj);
    }

    public String eliminarProducto(int id) throws SQLException {
        return ProductoM.eliminar(id);
    }

    private String mapear(List<ProductoM> productos) throws SQLException {
        StringBuilder sb = new StringBuilder();
        String format = "%-5s %-12s %-28s %-16s %-14s %-14s %-8s %-8s%n";
        sb.append(String.format(format, "ID", "Código", "Nombre", "Marca",
                "P.Minorista", "P.Mayorista", "MinMay", "Activo"));
        sb.append(
                "------------------------------------------------------------------------------------------------------------------------\r\n");

        for (ProductoM producto : productos) {
            sb.append(String.format(format,
                    producto.getId(),
                    producto.getCodigo() != null ? producto.getCodigo() : "N/A",
                    producto.getNombre(),
                    producto.getMarca() != null ? producto.getMarca() : "N/A",
                    String.format("%.2f", producto.getPrecioVentaBase()),
                    String.format("%.2f", producto.getPrecioMayorista()),
                    producto.getCantidadMinimaMayorista(),
                    producto.isActivo() ? "Sí" : "No"));
        }
        return sb.toString();
    }

    private ProductoN mapearUno(ProductoM productoM) throws SQLException {
        ProductoN productoN = new ProductoN();
        productoN.setId(productoM.getId());
        productoN.setCodigo(productoM.getCodigo());
        productoN.setNombre(productoM.getNombre());
        productoN.setMarca(productoM.getMarca());
        productoN.setModelo(productoM.getModelo());
        productoN.setDescripcion(productoM.getDescripcion());
        productoN.setPrecioVentaBase(productoM.getPrecioVentaBase());
        productoN.setPrecioMayorista(productoM.getPrecioMayorista());
        productoN.setCantidadMinimaMayorista(productoM.getCantidadMinimaMayorista());
        productoN.setFotoUrl(productoM.getFotoUrl());
        productoN.setActivo(productoM.isActivo());
        productoN.setFechaReg(productoM.getFechaReg() != null ? productoM.getFechaReg().toString() : null);
        return productoN;
    }

    private ProductoM cargar(int id, String codigo, String nombre, String marca, String modelo,
            String descripcion, double precioVentaBase, double precioMayorista,
            int cantidadMinimaMayorista, boolean activo) throws SQLException {
        ProductoM productoMObj = new ProductoM();
        productoMObj.setId(id);
        productoMObj.setCodigo(codigo);
        productoMObj.setNombre(nombre);
        productoMObj.setMarca(marca);
        productoMObj.setModelo(modelo);
        productoMObj.setDescripcion(descripcion);
        productoMObj.setPrecioVentaBase(precioVentaBase);
        productoMObj.setPrecioMayorista(precioMayorista);
        productoMObj.setCantidadMinimaMayorista(cantidadMinimaMayorista);
        productoMObj.setActivo(activo);
        return productoMObj;
    }
}

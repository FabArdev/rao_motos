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
            String descripcion, double precioVentaBase) throws SQLException {
        ProductoValidator.validarCampos(codigo, nombre, marca, modelo, descripcion, precioVentaBase);
        ProductoM productoMObj = cargar(0, codigo, nombre, marca, modelo, descripcion, precioVentaBase, true);
        int id = ProductoM.crear(productoMObj);
        return "Producto creado con éxito (ID: " + id + ")";
    }

    public String actualizarProducto(int id, String codigo, String nombre, String marca, String modelo,
            String descripcion, double precioVentaBase, boolean activo) throws SQLException {
        ProductoValidator.validarCampos(codigo, nombre, marca, modelo, descripcion, precioVentaBase);
        ProductoM productoMObj = cargar(id, codigo, nombre, marca, modelo, descripcion, precioVentaBase, activo);
        return ProductoM.actualizar(productoMObj);
    }

    public String eliminarProducto(int id) throws SQLException {
        return ProductoM.eliminar(id);
    }

    private String mapear(List<ProductoM> productos) throws SQLException {
        StringBuilder sb = new StringBuilder();
        String format = "%-5s %-15s %-30s %-20s %-20s %-18s %-10s%n";
        sb.append(String.format(format, "ID", "Código", "Nombre", "Marca", "Modelo", "Precio Vta Base", "Activo"));
        sb.append(
                "------------------------------------------------------------------------------------------------------------------------\r\n");

        for (ProductoM producto : productos) {
            sb.append(String.format(format,
                    producto.getId(),
                    producto.getCodigo() != null ? producto.getCodigo() : "N/A",
                    producto.getNombre(),
                    producto.getMarca() != null ? producto.getMarca() : "N/A",
                    producto.getModelo() != null ? producto.getModelo() : "N/A",
                    String.format("%.2f", producto.getPrecioVentaBase()),
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
        productoN.setFotoUrl(productoM.getFotoUrl());
        productoN.setActivo(productoM.isActivo());
        productoN.setFechaReg(productoM.getFechaReg() != null ? productoM.getFechaReg().toString() : null);
        return productoN;
    }

    private ProductoM cargar(int id, String codigo, String nombre, String marca, String modelo,
            String descripcion, double precioVentaBase, boolean activo) throws SQLException {
        ProductoM productoMObj = new ProductoM();
        productoMObj.setId(id);
        productoMObj.setCodigo(codigo);
        productoMObj.setNombre(nombre);
        productoMObj.setMarca(marca);
        productoMObj.setModelo(modelo);
        productoMObj.setDescripcion(descripcion);
        productoMObj.setPrecioVentaBase(precioVentaBase);
        productoMObj.setActivo(activo);
        return productoMObj;
    }
}

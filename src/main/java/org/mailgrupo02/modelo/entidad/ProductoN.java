package org.mailgrupo02.modelo.entidad;

import java.io.Serializable;

public class ProductoN implements Serializable {
    private static final long serialVersionUID = 1L;

    private int id;
    private String codigo;
    private String nombre;
    private String marca;
    private String modelo;
    private String descripcion;
    private double precioVentaBase;
    private String fotoUrl;
    private boolean activo;
    private String fechaReg;

    public ProductoN() {}

    public ProductoN(int id, String codigo, String nombre, String marca, String modelo,
            String descripcion, double precioVentaBase, String fotoUrl,
            boolean activo, String fechaReg) {
        this.id = id;
        this.codigo = codigo;
        this.nombre = nombre;
        this.marca = marca;
        this.modelo = modelo;
        this.descripcion = descripcion;
        this.precioVentaBase = precioVentaBase;
        this.fotoUrl = fotoUrl;
        this.activo = activo;
        this.fechaReg = fechaReg;
    }

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public String getCodigo() { return codigo; }
    public void setCodigo(String codigo) { this.codigo = codigo; }
    public String getNombre() { return nombre; }
    public void setNombre(String nombre) { this.nombre = nombre; }
    public String getMarca() { return marca; }
    public void setMarca(String marca) { this.marca = marca; }
    public String getModelo() { return modelo; }
    public void setModelo(String modelo) { this.modelo = modelo; }
    public String getDescripcion() { return descripcion; }
    public void setDescripcion(String descripcion) { this.descripcion = descripcion; }
    public double getPrecioVentaBase() { return precioVentaBase; }
    public void setPrecioVentaBase(double precioVentaBase) { this.precioVentaBase = precioVentaBase; }
    public String getFotoUrl() { return fotoUrl; }
    public void setFotoUrl(String fotoUrl) { this.fotoUrl = fotoUrl; }
    public boolean isActivo() { return activo; }
    public void setActivo(boolean activo) { this.activo = activo; }
    public String getFechaReg() { return fechaReg; }
    public void setFechaReg(String fechaReg) { this.fechaReg = fechaReg; }

    @Override
    public String toString() {
        return "ProductoN{" +
                "id=" + id +
                ", codigo='" + codigo + '\'' +
                ", nombre='" + nombre + '\'' +
                ", marca='" + marca + '\'' +
                ", modelo='" + modelo + '\'' +
                ", descripcion='" + descripcion + '\'' +
                ", precioVentaBase=" + precioVentaBase +
                ", fotoUrl='" + fotoUrl + '\'' +
                ", activo=" + activo +
                ", fechaReg='" + fechaReg + '\'' +
                '}';
    }
}

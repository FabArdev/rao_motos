package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * DAO de `producto`. Dos precios (minorista + mayorista) con umbral de mayoreo
 * propio del producto (RN3/RN19): el mayoreo se decide por cantidad, no por tipo de cliente.
 */
public class ProductoM {
    private int id;
    private String codigo;
    private String nombre;
    private String marca;
    private String modelo;
    private String descripcion;
    private double precioVentaBase;            // minorista
    private double precioMayorista;            // por volumen
    private int cantidadMinimaMayorista;       // umbral de mayoreo
    private String fotoUrl;
    private boolean activo;
    private Timestamp fechaReg;

    public ProductoM() {}

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
    public double getPrecioMayorista() { return precioMayorista; }
    public void setPrecioMayorista(double precioMayorista) { this.precioMayorista = precioMayorista; }
    public int getCantidadMinimaMayorista() { return cantidadMinimaMayorista; }
    public void setCantidadMinimaMayorista(int c) { this.cantidadMinimaMayorista = c; }
    public String getFotoUrl() { return fotoUrl; }
    public void setFotoUrl(String fotoUrl) { this.fotoUrl = fotoUrl; }
    public boolean isActivo() { return activo; }
    public void setActivo(boolean activo) { this.activo = activo; }
    public Timestamp getFechaReg() { return fechaReg; }
    public void setFechaReg(Timestamp fechaReg) { this.fechaReg = fechaReg; }

    /**
     * Precio aplicable a una cantidad: mayorista si alcanza el umbral del producto,
     * minorista si no (RN3/RN19). No depende de tipo de cliente (no existe).
     */
    public double precioPara(int cantidad) {
        return cantidad >= cantidadMinimaMayorista ? precioMayorista : precioVentaBase;
    }

    private static ProductoM mapear(ResultSet rs) throws SQLException {
        ProductoM p = new ProductoM();
        p.setId(rs.getInt("id"));
        p.setCodigo(rs.getString("codigo"));
        p.setNombre(rs.getString("nombre"));
        p.setMarca(rs.getString("marca"));
        p.setModelo(rs.getString("modelo"));
        p.setDescripcion(rs.getString("descripcion"));
        p.setPrecioVentaBase(rs.getDouble("precio_venta_base"));
        p.setPrecioMayorista(rs.getDouble("precio_mayorista"));
        p.setCantidadMinimaMayorista(rs.getInt("cantidad_minima_mayorista"));
        p.setFotoUrl(rs.getString("foto_url"));
        p.setActivo(rs.getBoolean("activo"));
        p.setFechaReg(rs.getTimestamp("creado_en"));
        return p;
    }

    public static int crear(ProductoM p) throws SQLException {
        String sql = "INSERT INTO producto (codigo, nombre, marca, modelo, descripcion, " +
                     "precio_venta_base, precio_mayorista, cantidad_minima_mayorista, foto_url, activo) " +
                     "VALUES (?,?,?,?,?,?,?,?,?,?)";
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            pstmt.setString(1, p.codigo);
            pstmt.setString(2, p.nombre);
            pstmt.setString(3, p.marca);
            pstmt.setString(4, p.modelo);
            pstmt.setString(5, p.descripcion);
            pstmt.setDouble(6, p.precioVentaBase);
            pstmt.setDouble(7, p.precioMayorista);
            pstmt.setInt(8, p.cantidadMinimaMayorista <= 0 ? 1 : p.cantidadMinimaMayorista);
            pstmt.setString(9, p.fotoUrl);
            pstmt.setBoolean(10, p.activo);
            pstmt.executeUpdate();
            try (ResultSet rs = pstmt.getGeneratedKeys()) {
                if (rs.next()) return rs.getInt(1);
            }
            throw new SQLException("No se pudo obtener el ID del producto creado");
        }
    }

    public static ProductoM leer(int id) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement("SELECT * FROM producto WHERE id = ?")) {
            pstmt.setInt(1, id);
            try (ResultSet rs = pstmt.executeQuery()) {
                if (rs.next()) return mapear(rs);
            }
        }
        throw new SQLException("Producto no encontrado");
    }

    public static List<ProductoM> obtenerTodos() throws SQLException {
        List<ProductoM> productos = new ArrayList<>();
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement("SELECT * FROM producto ORDER BY id");
             ResultSet rs = pstmt.executeQuery()) {
            while (rs.next()) productos.add(mapear(rs));
        }
        return productos;
    }

    public static String actualizar(ProductoM p) throws SQLException {
        String sql = "UPDATE producto SET codigo=?, nombre=?, marca=?, modelo=?, descripcion=?, " +
                     "precio_venta_base=?, precio_mayorista=?, cantidad_minima_mayorista=?, foto_url=?, " +
                     "activo=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?";
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setString(1, p.codigo);
            pstmt.setString(2, p.nombre);
            pstmt.setString(3, p.marca);
            pstmt.setString(4, p.modelo);
            pstmt.setString(5, p.descripcion);
            pstmt.setDouble(6, p.precioVentaBase);
            pstmt.setDouble(7, p.precioMayorista);
            pstmt.setInt(8, p.cantidadMinimaMayorista <= 0 ? 1 : p.cantidadMinimaMayorista);
            pstmt.setString(9, p.fotoUrl);
            pstmt.setBoolean(10, p.activo);
            pstmt.setInt(11, p.id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Producto actualizado con éxito" : "Producto no encontrado";
        }
    }

    /** Recalcula precios desde el costo × (1 + margen%). Usado al recibir una compra (RN23). */
    public static void actualizarPrecios(int productoId, double nuevoBase, double nuevoMayorista) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(
                 "UPDATE producto SET precio_venta_base=?, precio_mayorista=?, actualizado_en=CURRENT_TIMESTAMP WHERE id=?")) {
            pstmt.setDouble(1, nuevoBase);
            pstmt.setDouble(2, nuevoMayorista);
            pstmt.setInt(3, productoId);
            pstmt.executeUpdate();
        }
    }

    /** Baja lógica (activo=false): no se borra físicamente porque lo referencian ventas/compras. */
    public static String eliminar(int id) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement pstmt = conn.prepareStatement(
                 "UPDATE producto SET activo=false, actualizado_en=CURRENT_TIMESTAMP WHERE id=?")) {
            pstmt.setInt(1, id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Producto dado de baja con éxito" : "Producto no encontrado";
        }
    }
}

package org.mailgrupo02.datos.modelo;

import org.mailgrupo02.datos.conexion.Conexion;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class ProductoM {
    private int id;
    private String codigo;
    private String nombre;
    private String marca;
    private String modelo;
    private String descripcion;
    private double precioVentaBase;
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
    public String getFotoUrl() { return fotoUrl; }
    public void setFotoUrl(String fotoUrl) { this.fotoUrl = fotoUrl; }
    public boolean isActivo() { return activo; }
    public void setActivo(boolean activo) { this.activo = activo; }
    public Timestamp getFechaReg() { return fechaReg; }
    public void setFechaReg(Timestamp fechaReg) { this.fechaReg = fechaReg; }

    public static int crear(ProductoM producto) throws SQLException {
        String sql = "INSERT INTO producto (codigo, nombre, marca, modelo, descripcion, precio_venta_base, foto_url, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql, PreparedStatement.RETURN_GENERATED_KEYS);
            pstmt.setString(1, producto.codigo);
            pstmt.setString(2, producto.nombre);
            pstmt.setString(3, producto.marca);
            pstmt.setString(4, producto.modelo);
            pstmt.setString(5, producto.descripcion);
            pstmt.setDouble(6, producto.precioVentaBase);
            pstmt.setString(7, producto.fotoUrl);
            pstmt.setBoolean(8, producto.activo);
            pstmt.executeUpdate();
            rs = pstmt.getGeneratedKeys();
            if (rs.next()) return rs.getInt(1);
            throw new SQLException("No se pudo obtener el ID del producto creado");
        } catch (SQLException e) {
            throw new SQLException("Error al crear producto: " + e.getMessage());
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static ProductoM leer(int id) throws SQLException {
        String sql = "SELECT * FROM producto WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, id);
            rs = pstmt.executeQuery();
            if (rs.next()) {
                ProductoM producto = new ProductoM();
                producto.setId(rs.getInt("id"));
                producto.setCodigo(rs.getString("codigo"));
                producto.setNombre(rs.getString("nombre"));
                producto.setMarca(rs.getString("marca"));
                producto.setModelo(rs.getString("modelo"));
                producto.setDescripcion(rs.getString("descripcion"));
                producto.setPrecioVentaBase(rs.getDouble("precio_venta_base"));
                producto.setFotoUrl(rs.getString("foto_url"));
                producto.setActivo(rs.getBoolean("activo"));
                producto.setFechaReg(rs.getTimestamp("fecha_reg"));
                return producto;
            }
            throw new SQLException("Producto no encontrado");
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static List<ProductoM> obtenerTodos() throws SQLException {
        List<ProductoM> productos = new ArrayList<>();
        String sql = "SELECT * FROM producto ORDER BY id";
        Connection conn = null;
        PreparedStatement pstmt = null;
        ResultSet rs = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            rs = pstmt.executeQuery();
            while (rs.next()) {
                ProductoM producto = new ProductoM();
                producto.setId(rs.getInt("id"));
                producto.setCodigo(rs.getString("codigo"));
                producto.setNombre(rs.getString("nombre"));
                producto.setMarca(rs.getString("marca"));
                producto.setModelo(rs.getString("modelo"));
                producto.setDescripcion(rs.getString("descripcion"));
                producto.setPrecioVentaBase(rs.getDouble("precio_venta_base"));
                producto.setFotoUrl(rs.getString("foto_url"));
                producto.setActivo(rs.getBoolean("activo"));
                producto.setFechaReg(rs.getTimestamp("fecha_reg"));
                productos.add(producto);
            }
        } finally {
            if (rs != null) try { rs.close(); } catch (SQLException e) {}
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
        return productos;
    }

    public static String actualizar(ProductoM producto) throws SQLException {
        String sql = "UPDATE producto SET codigo=?, nombre=?, marca=?, modelo=?, descripcion=?, precio_venta_base=?, foto_url=?, activo=? WHERE id=?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setString(1, producto.codigo);
            pstmt.setString(2, producto.nombre);
            pstmt.setString(3, producto.marca);
            pstmt.setString(4, producto.modelo);
            pstmt.setString(5, producto.descripcion);
            pstmt.setDouble(6, producto.precioVentaBase);
            pstmt.setString(7, producto.fotoUrl);
            pstmt.setBoolean(8, producto.activo);
            pstmt.setInt(9, producto.id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Producto actualizado con éxito" : "Producto no encontrado";
        } catch (SQLException e) {
            throw new SQLException("Error al actualizar producto: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }

    public static String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM producto WHERE id = ?";
        Connection conn = null;
        PreparedStatement pstmt = null;
        try {
            conn = Conexion.conectar();
            pstmt = conn.prepareStatement(sql);
            pstmt.setInt(1, id);
            int rows = pstmt.executeUpdate();
            return rows > 0 ? "Producto eliminado con éxito" : "Producto no encontrado";
        } catch (SQLException e) {
            throw new SQLException("Error al eliminar producto: " + e.getMessage());
        } finally {
            if (pstmt != null) try { pstmt.close(); } catch (SQLException e) {}
            if (conn != null) try { conn.close(); } catch (SQLException e) {}
        }
    }
}

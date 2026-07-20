package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class InventarioM {
    private int id;
    private int productoId;
    private int stockActual;
    private String tecnicaInventario;
    private String tecnicaCosto;
    private Timestamp fechaActualizacion;

    public InventarioM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getProductoId() { return productoId; }
    public void setProductoId(int productoId) { this.productoId = productoId; }
    public int getStockActual() { return stockActual; }
    public void setStockActual(int stockActual) { this.stockActual = stockActual; }
    public String getTecnicaInventario() { return tecnicaInventario; }
    public void setTecnicaInventario(String tecnicaInventario) { this.tecnicaInventario = tecnicaInventario; }
    public String getTecnicaCosto() { return tecnicaCosto; }
    public void setTecnicaCosto(String tecnicaCosto) { this.tecnicaCosto = tecnicaCosto; }
    public Timestamp getFechaActualizacion() { return fechaActualizacion; }
    public void setFechaActualizacion(Timestamp fechaActualizacion) { this.fechaActualizacion = fechaActualizacion; }

    public String crear() throws SQLException {
        String sql = "INSERT INTO inventario (producto_id, stock_actual, tecnica_inventario, tecnica_costo, fecha_actualizacion) VALUES (?, ?, ?, ?, ?)";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, this.productoId);
            pstmt.setInt(2, this.stockActual);
            pstmt.setString(3, this.tecnicaInventario);
            pstmt.setString(4, this.tecnicaCosto);
            pstmt.setTimestamp(5, this.fechaActualizacion);
            pstmt.executeUpdate();
            return "Inventario creado exitosamente";
        }
    }

    public InventarioM leer(int id) throws SQLException {
        String sql = "SELECT * FROM inventario WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            ResultSet rs = pstmt.executeQuery();
            if (rs.next()) {
                this.id = rs.getInt("id");
                this.productoId = rs.getInt("producto_id");
                this.stockActual = rs.getInt("stock_actual");
                this.tecnicaInventario = rs.getString("tecnica_inventario");
                this.tecnicaCosto = rs.getString("tecnica_costo");
                this.fechaActualizacion = rs.getTimestamp("fecha_actualizacion");
                return this;
            }
            return null;
        }
    }

    public List<InventarioM> obtenerTodos() throws SQLException {
        List<InventarioM> lista = new ArrayList<>();
        String sql = "SELECT * FROM inventario ORDER BY id";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql);
                ResultSet rs = pstmt.executeQuery()) {
            while (rs.next()) {
                InventarioM obj = new InventarioM();
                obj.setId(rs.getInt("id"));
                obj.setProductoId(rs.getInt("producto_id"));
                obj.setStockActual(rs.getInt("stock_actual"));
                obj.setTecnicaInventario(rs.getString("tecnica_inventario"));
                obj.setTecnicaCosto(rs.getString("tecnica_costo"));
                obj.setFechaActualizacion(rs.getTimestamp("fecha_actualizacion"));
                lista.add(obj);
            }
        }
        return lista;
    }

    public String actualizar() throws SQLException {
        String sql = "UPDATE inventario SET producto_id = ?, stock_actual = ?, tecnica_inventario = ?, tecnica_costo = ?, fecha_actualizacion = ? WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, this.productoId);
            pstmt.setInt(2, this.stockActual);
            pstmt.setString(3, this.tecnicaInventario);
            pstmt.setString(4, this.tecnicaCosto);
            pstmt.setTimestamp(5, this.fechaActualizacion);
            pstmt.setInt(6, this.id);
            pstmt.executeUpdate();
            return "Inventario actualizado exitosamente";
        }
    }

    public String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM inventario WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            pstmt.executeUpdate();
            return "Inventario eliminado exitosamente";
        }
    }
}

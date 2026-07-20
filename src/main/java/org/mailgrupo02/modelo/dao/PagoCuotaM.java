package org.mailgrupo02.modelo.dao;

import org.mailgrupo02.infraestructura.Conexion;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class PagoCuotaM {
    private int id;
    private int creditoId;
    private int numeroCuota;
    private double montoCuota;
    private Date fechaVencimiento;
    private Date fechaPago;
    private double mora;
    private String estado;

    public PagoCuotaM() {}

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getCreditoId() { return creditoId; }
    public void setCreditoId(int creditoId) { this.creditoId = creditoId; }
    public int getNumeroCuota() { return numeroCuota; }
    public void setNumeroCuota(int numeroCuota) { this.numeroCuota = numeroCuota; }
    public double getMontoCuota() { return montoCuota; }
    public void setMontoCuota(double montoCuota) { this.montoCuota = montoCuota; }
    public Date getFechaVencimiento() { return fechaVencimiento; }
    public void setFechaVencimiento(Date fechaVencimiento) { this.fechaVencimiento = fechaVencimiento; }
    public Date getFechaPago() { return fechaPago; }
    public void setFechaPago(Date fechaPago) { this.fechaPago = fechaPago; }
    public double getMora() { return mora; }
    public void setMora(double mora) { this.mora = mora; }
    public String getEstado() { return estado; }
    public void setEstado(String estado) { this.estado = estado; }

    public String crear() throws SQLException {
        String sql = "INSERT INTO pago_cuota (credito_id, numero_cuota, monto_cuota, fecha_vencimiento, fecha_pago, mora, estado) VALUES (?, ?, ?, ?, ?, ?, ?)";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, this.creditoId);
            pstmt.setInt(2, this.numeroCuota);
            pstmt.setDouble(3, this.montoCuota);
            pstmt.setDate(4, this.fechaVencimiento);
            pstmt.setDate(5, this.fechaPago);
            pstmt.setDouble(6, this.mora);
            pstmt.setString(7, this.estado);
            pstmt.executeUpdate();
            return "Pago de cuota creado exitosamente";
        }
    }

    public PagoCuotaM leer(int id) throws SQLException {
        String sql = "SELECT * FROM pago_cuota WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            ResultSet rs = pstmt.executeQuery();
            if (rs.next()) {
                this.id = rs.getInt("id");
                this.creditoId = rs.getInt("credito_id");
                this.numeroCuota = rs.getInt("numero_cuota");
                this.montoCuota = rs.getDouble("monto_cuota");
                this.fechaVencimiento = rs.getDate("fecha_vencimiento");
                this.fechaPago = rs.getDate("fecha_pago");
                this.mora = rs.getDouble("mora");
                this.estado = rs.getString("estado");
                return this;
            }
            return null;
        }
    }

    public List<PagoCuotaM> obtenerTodos() throws SQLException {
        List<PagoCuotaM> lista = new ArrayList<>();
        String sql = "SELECT * FROM pago_cuota ORDER BY id";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql);
                ResultSet rs = pstmt.executeQuery()) {
            while (rs.next()) {
                PagoCuotaM obj = new PagoCuotaM();
                obj.setId(rs.getInt("id"));
                obj.setCreditoId(rs.getInt("credito_id"));
                obj.setNumeroCuota(rs.getInt("numero_cuota"));
                obj.setMontoCuota(rs.getDouble("monto_cuota"));
                obj.setFechaVencimiento(rs.getDate("fecha_vencimiento"));
                obj.setFechaPago(rs.getDate("fecha_pago"));
                obj.setMora(rs.getDouble("mora"));
                obj.setEstado(rs.getString("estado"));
                lista.add(obj);
            }
        }
        return lista;
    }

    public PagoCuotaM obtenerPorCreditoYNumero(int creditoId, int numeroCuota) throws SQLException {
        String sql = "SELECT * FROM pago_cuota WHERE credito_id = ? AND numero_cuota = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, creditoId);
            pstmt.setInt(2, numeroCuota);
            ResultSet rs = pstmt.executeQuery();
            if (rs.next()) {
                PagoCuotaM obj = new PagoCuotaM();
                obj.setId(rs.getInt("id"));
                obj.setCreditoId(rs.getInt("credito_id"));
                obj.setNumeroCuota(rs.getInt("numero_cuota"));
                obj.setMontoCuota(rs.getDouble("monto_cuota"));
                obj.setFechaVencimiento(rs.getDate("fecha_vencimiento"));
                obj.setFechaPago(rs.getDate("fecha_pago"));
                obj.setMora(rs.getDouble("mora"));
                obj.setEstado(rs.getString("estado"));
                return obj;
            }
            return null;
        }
    }

    public List<PagoCuotaM> obtenerPorCredito(int creditoId) throws SQLException {
        List<PagoCuotaM> lista = new ArrayList<>();
        String sql = "SELECT * FROM pago_cuota WHERE credito_id = ? ORDER BY numero_cuota";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, creditoId);
            ResultSet rs = pstmt.executeQuery();
            while (rs.next()) {
                PagoCuotaM obj = new PagoCuotaM();
                obj.setId(rs.getInt("id"));
                obj.setCreditoId(rs.getInt("credito_id"));
                obj.setNumeroCuota(rs.getInt("numero_cuota"));
                obj.setMontoCuota(rs.getDouble("monto_cuota"));
                obj.setFechaVencimiento(rs.getDate("fecha_vencimiento"));
                obj.setFechaPago(rs.getDate("fecha_pago"));
                obj.setMora(rs.getDouble("mora"));
                obj.setEstado(rs.getString("estado"));
                lista.add(obj);
            }
        }
        return lista;
    }

    public String actualizar() throws SQLException {
        String sql = "UPDATE pago_cuota SET credito_id = ?, numero_cuota = ?, monto_cuota = ?, fecha_vencimiento = ?, fecha_pago = ?, mora = ?, estado = ? WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, this.creditoId);
            pstmt.setInt(2, this.numeroCuota);
            pstmt.setDouble(3, this.montoCuota);
            pstmt.setDate(4, this.fechaVencimiento);
            pstmt.setDate(5, this.fechaPago);
            pstmt.setDouble(6, this.mora);
            pstmt.setString(7, this.estado);
            pstmt.setInt(8, this.id);
            pstmt.executeUpdate();
            return "Pago de cuota actualizado exitosamente";
        }
    }

    public String eliminar(int id) throws SQLException {
        String sql = "DELETE FROM pago_cuota WHERE id = ?";
        try (Connection conn = Conexion.conectar();
                PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            pstmt.executeUpdate();
            return "Pago de cuota eliminado exitosamente";
        }
    }
}

package org.mailgrupo02.modelo.servicio;

import org.mailgrupo02.infraestructura.Conexion;
import org.mailgrupo02.modelo.dao.ConfiguracionM;
import org.mailgrupo02.modelo.dao.PagoCuotaM;

import java.sql.*;
import java.time.LocalDate;
import java.time.temporal.ChronoUnit;
import java.util.ArrayList;
import java.util.List;

/**
 * Lógica de cobranza: cálculo de mora (RN12/RN16) y tarea de marcado de cuotas vencidas.
 * Mora = días de atraso × tasa_mora_diaria% del monto de la cuota, con tope tope_mora_pct%.
 * Los parámetros salen de `configuracion` (con defaults).
 */
public class CreditoService {

    /** Mora acumulada de una cuota a la fecha de hoy (0 si no está vencida). */
    public static double calcularMora(PagoCuotaM cuota) throws SQLException {
        if (cuota.getFechaVencimiento() == null) return 0;
        LocalDate venc = cuota.getFechaVencimiento().toLocalDate();
        LocalDate hoy = LocalDate.now();
        if (!hoy.isAfter(venc)) return 0;
        long dias = ChronoUnit.DAYS.between(venc, hoy);
        double tasaDiaria = ConfiguracionM.valorNum("tasa_mora_diaria", 0.5) / 100.0;
        double topePct = ConfiguracionM.valorNum("tope_mora_pct", 20) / 100.0;
        double mora = cuota.getMontoCuota() * tasaDiaria * dias;
        double tope = cuota.getMontoCuota() * topePct;
        return Math.min(mora, tope);
    }

    /**
     * Tarea diaria: marca como VENCIDO las cuotas PENDIENTE ya vencidas, les aplica la mora
     * y pone su crédito en estado MOROSO. Devuelve cuántas cuotas se marcaron.
     */
    public static int marcarVencidas() throws SQLException {
        List<PagoCuotaM> vencidas = new ArrayList<>();
        String sel = "SELECT * FROM pago_cuota WHERE estado = 'PENDIENTE' AND fecha_vencimiento < CURRENT_DATE";
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(sel);
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                PagoCuotaM c = new PagoCuotaM();
                c.setId(rs.getInt("id"));
                c.setCreditoId(rs.getInt("credito_id"));
                c.setNumeroCuota(rs.getInt("numero_cuota"));
                c.setMontoCuota(rs.getDouble("monto_cuota"));
                c.setFechaVencimiento(rs.getDate("fecha_vencimiento"));
                c.setEstado(rs.getString("estado"));
                vencidas.add(c);
            }
        }
        for (PagoCuotaM c : vencidas) {
            c.setMora(calcularMora(c));
            c.setEstado("VENCIDO");
            c.actualizar();
            marcarCreditoMoroso(c.getCreditoId());
        }
        return vencidas.size();
    }

    private static void marcarCreditoMoroso(int creditoId) throws SQLException {
        try (Connection conn = Conexion.conectar();
             PreparedStatement ps = conn.prepareStatement(
                 "UPDATE credito SET estado = 'MOROSO', actualizado_en = CURRENT_TIMESTAMP WHERE id = ? AND estado = 'VIGENTE'")) {
            ps.setInt(1, creditoId);
            ps.executeUpdate();
        }
    }
}

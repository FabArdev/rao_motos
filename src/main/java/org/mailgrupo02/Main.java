package org.mailgrupo02;

import org.mailgrupo02.infraestructura.ClientePOP;
import org.mailgrupo02.infraestructura.ClienteSMTP;
import org.mailgrupo02.controlador.ComandoCorreoNuevo;
import org.mailgrupo02.vista.PPagos;
import org.mailgrupo02.vista.PVentas;
import org.mailgrupo02.infraestructura.Conexion;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.infraestructura.BackupService;
import org.mailgrupo02.modelo.servicio.PagoFacilService;
import org.mailgrupo02.modelo.servicio.PagoCuotaService;
import org.mailgrupo02.modelo.servicio.UsuarioService;
import org.mailgrupo02.modelo.servicio.ProductoService;
import org.mailgrupo02.modelo.servicio.VentaService;
import java.sql.Connection;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.Scanner;

public class Main {
    public static void main(String[] args) {
        if (args.length > 0 && args[0].equalsIgnoreCase("correo")) {
            iniciarServicioCorreo();
            return;
        }

        Scanner scanner = new Scanner(System.in);
        while (true) {
            try {
                System.out.println("\n=== SISTEMA DE VENTAS AL CRÉDITO - RAO MOTOS Grupo 02 ===");
                System.out.println("1. Probar conexion BD");
                System.out.println("2. Probar servicios CRUD");
                System.out.println("3. Servicio de correo");
                System.out.println("4. Salir");
                System.out.print("Opcion: ");
                int opcion = scanner.nextInt();
                switch (opcion) {
                    case 1:
                        probarConexionBD();
                        break;
                    case 2:
                        probarServicios();
                        break;
                    case 3:
                        iniciarServicioCorreo();
                        break;
                    case 4:
                        System.out.println("Saliendo...");
                        scanner.close();
                        return;
                    default:
                        System.out.println("Opcion invalida");
                }
            } catch (Exception e) {
                System.out.println("Error: " + e.getMessage());
                scanner.nextLine();
            }
        }
    }

    private static void probarConexionBD() throws SQLException {
        System.out.println("\n=== PROBANDO CONEXION BD ===");
        Connection conn = Conexion.conectar();
        if (conn != null && !conn.isClosed()) {
            System.out.println("OK - db_grupo02sa en mail.tecnoweb.org.bo");
            conn.close();
        } else {
            System.out.println("ERROR conexion");
        }
    }

    private static void probarServicios() throws SQLException {
        System.out.println("\n=== PROBANDO SERVICIOS ===");
        UsuarioService us = new UsuarioService(new UsuarioM());
        System.out.println("Usuarios: " + us.obtenerUsuarios());
        ProductoService ps = new ProductoService(new ProductoM());
        System.out.println("Productos: " + ps.obtenerProductos());
        VentaService ves = new VentaService(new VentaM(), new CreditoM());
        System.out.println("Ventas: " + ves.obtenerVentas());
    }

    private static void iniciarServicioCorreo() {
        System.out.println("\n=== SERVICIO EMAIL ===");
        System.out.println("Revisando cada 10s... Ctrl+C para salir");
        new ServicioCorreo().iniciar();
    }

    static class ServicioCorreo {
        private ClientePOP pop = new ClientePOP();
        private ClienteSMTP smtp = new ClienteSMTP();
        private ComandoCorreoNuevo cmd;

        // Backup: bandera de "hay datos nuevos" + contador para backup periódico
        private volatile boolean backupNecesario = false;
        private int cicloCount = 0;
        private static final int CICLOS_BACKUP_PERIODICO = 60; // 60 × 5s = 5 minutos

        public ServicioCorreo() {
            try {
                this.cmd = new ComandoCorreoNuevo();
            } catch (SQLException e) {
                System.err.println("Error inicializando ComandoCorreo: " + e.getMessage());
            }
        }

        public void iniciar() {
            // Backup inicial al arrancar (por si el servicio fue reiniciado)
            System.out.println("[Backup] Generando backup inicial...");
            BackupService.backup();

            while (true) {
                try {
                    cicloCount++;

                    // 1. Health check: si las tablas no existen → reconstruir y restaurar
                    if (!BackupService.healthCheck()) {
                        System.err.println("[ALERTA] Base de datos no disponible. Reconstruyendo...");
                        boolean reconstruido = BackupService.reconstruirTablas();
                        if (reconstruido) {
                            BackupService.restaurar();
                            backupNecesario = false; // los datos vienen del backup, no hace falta volver a guardar
                        } else {
                            System.err.println("[ALERTA] No se pudo reconstruir. Reintentando en el próximo ciclo.");
                            Thread.sleep(5000);
                            continue;
                        }
                    }

                    // 2. Backup si hay datos nuevos o toca ciclo periódico
                    if (backupNecesario || cicloCount % CICLOS_BACKUP_PERIODICO == 0) {
                        BackupService.backup();
                        backupNecesario = false;
                    }

                    reconciliarPagosQR();
                    System.out.println("[" + java.time.LocalDateTime.now() + "] Revisando...");
                    pop.conectar();
                    int total = pop.obtenerTotalDeCorreos();
                    if (total > 0) {
                        System.out.println(total + " correos");
                        for (int i = 1; i <= total; i++) {
                            procesarCorreo(pop.obtenerCorreoYEliminar(i));
                        }
                    } else {
                        System.out.println("  Sin correos nuevos");
                    }
                    pop.desconectar();
                    Thread.sleep(5000);
                } catch (Exception e) {
                    System.err.println("Error: " + e.getMessage());
                    try {
                        Thread.sleep(5000);
                    } catch (InterruptedException ie) {
                        break;
                    }
                }
            }
        }

        private void reconciliarPagosQR() {
            Map<String, String> transacciones = PagoFacilService.cargarTransacciones();
            if (transacciones.isEmpty()) return;

            System.out.println("[Reconciliacion] " + transacciones.size() + " transaccion(es) QR pendiente(s)...");
            List<String> completadas = new ArrayList<>();

            for (Map.Entry<String, String> entry : transacciones.entrySet()) {
                String txId = entry.getKey();
                String[] parts = entry.getValue().split(";");
                if (parts.length < 4) continue;

                String correo = parts[0];
                double monto;
                long pfTxId;
                try {
                    monto = Double.parseDouble(parts[1]);
                    // PagoFacil puede devolver el ID como "12345" o "12345.0"
                    pfTxId = (long) Double.parseDouble(parts[3]);
                } catch (NumberFormatException e) {
                    System.err.println("[Reconciliacion] Error al parsear transaccion " + txId
                        + " | valor='" + parts[3] + "': " + e.getMessage());
                    continue;
                }

                System.out.println("[Reconciliacion] Consultando " + txId + " (PF ID: " + pfTxId + ")...");
                boolean pagado = PagoFacilService.consultarEstado(pfTxId);

                if (pagado) {
                    System.out.println("[Reconciliacion] Pago confirmado: " + txId);
                    if (txId.startsWith("CUO-")) {
                        try {
                            String[] cparts = txId.substring(4).split("-");
                            int creditoId = Integer.parseInt(cparts[0]);
                            int numeroCuota = Integer.parseInt(cparts[1]);
                            String resultado = new PagoCuotaService().confirmarPago(creditoId, numeroCuota);
                            System.out.println("[Reconciliacion] " + resultado);
                            String html = PPagos.generarHtml("PAGARCUOTA",
                                "Pago de Cuota " + numeroCuota + " del Credito #" + creditoId
                                + " confirmado exitosamente. Monto: " + String.format("%.2f", monto) + " Bs.");
                            smtp.enviarCorreo(correo, "Confirmacion de Pago - " + txId, html);
                        } catch (Exception e) {
                            System.err.println("[Reconciliacion] Error al confirmar " + txId + ": " + e.getMessage());
                        }
                    } else if (txId.startsWith("VTA-")) {
                        try {
                            int ventaId = Integer.parseInt(txId.substring(4));
                            System.out.println("[Reconciliacion] Venta #" + ventaId + " pago QR confirmado.");
                            String html = PVentas.generarHtml("CREARVENTA_CONTADO",
                                "Pago de Venta #" + ventaId + " confirmado exitosamente. Monto: "
                                + String.format("%.2f", monto) + " Bs.");
                            smtp.enviarCorreo(correo, "Confirmacion de Pago - " + txId, html);
                        } catch (Exception e) {
                            System.err.println("[Reconciliacion] Error al confirmar " + txId + ": " + e.getMessage());
                        }
                    }
                    completadas.add(txId);
                } else {
                    System.out.println("[Reconciliacion] " + txId + " sigue pendiente.");
                }
            }

            for (String txId : completadas) {
                PagoFacilService.removerTransaccion(txId);
            }
        }

        private void procesarCorreo(String correo) {
            try {
                String from = extraer(correo, "From: ", 6);
                String subj = org.mailgrupo02.infraestructura.TextoMime.decodificar(extraer(correo, "Subject: ", 9));
                System.out.println("  De: " + from + " | " + subj);
                String correoRemitente = extraerCorreo(from);

                // Ignorar rebotes, notificaciones del sistema y bucles de auto-respuesta
                if (esCorreoSistema(correoRemitente, subj)) {
                    System.out.println("  [IGNORADO] Correo de sistema/rebote — no se procesa ni responde.");
                    return;
                }

                String resp = cmd.evaluarYEjecutar(subj, correoRemitente);
                smtp.enviarCorreo(correoRemitente, "Re: " + subj, resp);
                System.out.println("  Enviado (" + resp.length() + " chars)");
                if (esComandoEscritura(subj)) {
                    backupNecesario = true;
                }
            } catch (Exception e) {
                System.err.println("  Error: " + e.getMessage());
            }
        }

        private boolean esCorreoSistema(String correo, String subject) {
            if (correo == null) return true;
            String correoLower   = correo.toLowerCase();
            String subjectLower = subject != null ? subject.toLowerCase() : "";

            // Direcciones de rebote y sistema que nunca deben recibir respuesta
            if (correoLower.startsWith("mailer-daemon")
                    || correoLower.startsWith("postmaster@")
                    || correoLower.startsWith("noreply@")
                    || correoLower.startsWith("no-reply@")
                    || correoLower.startsWith("donotreply@")
                    || correoLower.startsWith("daemon@")
                    || correoLower.contains("mailer-daemon")) {
                return true;
            }

            // Asuntos típicos de rebotes y notificaciones automáticas
            if (subjectLower.startsWith("mail delivery")
                    || subjectLower.startsWith("returned mail")
                    || subjectLower.startsWith("delivery status")
                    || subjectLower.startsWith("delivery failure")
                    || subjectLower.startsWith("undeliverable")
                    || subjectLower.startsWith("auto:")
                    || subjectLower.startsWith("automatic reply")
                    || subjectLower.startsWith("out of office")) {
                return true;
            }

            return false;
        }

        private boolean esComandoEscritura(String subject) {
            if (subject == null) return false;
            String s = subject.toUpperCase();
            return s.startsWith("CREATE") || s.startsWith("UPDATE") || s.startsWith("DELETE")
                || s.startsWith("CREAR")  || s.startsWith("ANULAR") || s.startsWith("DESPACHAR")
                || s.startsWith("REGISTRAR") || s.startsWith("PROCESAR") || s.startsWith("CAMBIAR")
                || s.startsWith("PEDIDO[") || s.startsWith("CANCELAR") || s.startsWith("PAGAR")
                || s.startsWith("CREATEPROVEEDOR") || s.startsWith("UPDATEPROVEEDOR") || s.startsWith("DELETEPROVEEDOR");
        }

        private String extraerCorreo(String from) {
            int ini = from.lastIndexOf('<');
            int fin = from.lastIndexOf('>');
            if (ini != -1 && fin != -1 && fin > ini) {
                return from.substring(ini + 1, fin);
            }
            return from;
        }

        private String extraer(String txt, String key, int offset) {
            int i = txt.toLowerCase().indexOf(key.toLowerCase());
            if (i != -1) {
                int fin = txt.indexOf("\n", i + offset);
                if (fin != -1) {
                    String val = txt.substring(i + offset, fin).trim();
                    // RFC 2822: cabeceras plegadas continúan con espacio/tab en sig. línea
                    int cont = fin + 1;
                    while (cont < txt.length() && (txt.charAt(cont) == ' ' || txt.charAt(cont) == '\t')) {
                        int nl = txt.indexOf("\n", cont);
                        if (nl == -1) break;
                        val += txt.substring(cont, nl).trim();
                        cont = nl + 1;
                    }
                    return val;
                }
            }
            return "unknown";
        }
    }
}

package org.mailgrupo02.infraestructura;

import org.mailgrupo02.infraestructura.ConfigServidorCorreo;

import java.io.BufferedReader;
import java.io.DataOutputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.net.Socket;
import java.nio.charset.StandardCharsets;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class ClienteSMTP {

    private static final String SERVIDOR = ConfigServidorCorreo.HOST;
    private static final int PUERTO = Integer.parseInt(ConfigServidorCorreo.PORT_SMTP);
    private static final String EMISOR = ConfigServidorCorreo.MAIL;

    // ── Auto-detecta imagen base64 embebida y elige el tipo de envío ─────────
    public void enviarCorreo(String usuarioReceptor, String subject, String mensaje) throws IOException {
        Matcher m = Pattern.compile("src=\"data:image/[^;]+;base64,([^\"]+)\"")
                           .matcher(mensaje);
        if (m.find()) {
            // Extraer base64 limpio y reemplazar data-URI con CID
            String base64 = m.group(1).replace("\r", "").replace("\n", "").trim();
            String htmlConCid = m.replaceAll("src=\"cid:qr@rao\"");
            enviarMultipart(usuarioReceptor, subject, htmlConCid, base64);
        } else {
            enviarSimple(usuarioReceptor, subject, mensaje);
        }
    }

    // ── Envío simple: text/html (comportamiento original) ────────────────────
    private void enviarSimple(String to, String subject, String html) throws IOException {
        try (Socket socket = new Socket(SERVIDOR, PUERTO);
             BufferedReader entrada = new BufferedReader(new InputStreamReader(socket.getInputStream(), StandardCharsets.UTF_8));
             DataOutputStream salida = new DataOutputStream(socket.getOutputStream())) {

            System.out.println("S : " + entrada.readLine());
            enviarComando(salida, entrada, "EHLO " + SERVIDOR + "\r\n");
            enviarComando(salida, entrada, "MAIL FROM:<" + EMISOR + ">\r\n");
            enviarComando(salida, entrada, "RCPT TO:<" + to + ">\r\n");
            enviarComando(salida, entrada, "DATA\r\n");
            String headers = "Subject: " + TextoMime.codificarAsunto(subject) + "\r\n"
                    + "MIME-Version: 1.0\r\n"
                    + "Content-Type: text/html; charset=UTF-8\r\n"
                    + "Content-Transfer-Encoding: 8bit\r\n"
                    + "\r\n";
            String cuerpo = html.replace("\r\n", "\n").replace("\n", "\r\n");
            enviarComando(salida, entrada, headers + cuerpo + "\r\n.\r\n");
            enviarComando(salida, entrada, "QUIT\r\n");
        }
    }

    // ── Envío multipart/related: HTML + imagen QR como adjunto inline ─────────
    private void enviarMultipart(String to, String subject, String html, String base64Img) throws IOException {
        String boundary = "RAO_QR_" + System.currentTimeMillis();

        // Formatear base64 con cortes de 76 chars (RFC 2045)
        StringBuilder b64 = new StringBuilder();
        for (int i = 0; i < base64Img.length(); i += 76) {
            b64.append(base64Img, i, Math.min(i + 76, base64Img.length())).append("\r\n");
        }

        String cuerpo = html.replace("\r\n", "\n").replace("\n", "\r\n");

        String body =
            "Subject: " + TextoMime.codificarAsunto(subject) + "\r\n" +
            "MIME-Version: 1.0\r\n" +
            "Content-Type: multipart/related; boundary=\"" + boundary + "\"\r\n" +
            "\r\n" +
            "--" + boundary + "\r\n" +
            "Content-Type: text/html; charset=UTF-8\r\n" +
            "Content-Transfer-Encoding: 8bit\r\n" +
            "\r\n" +
            cuerpo + "\r\n" +
            "--" + boundary + "\r\n" +
            "Content-Type: image/png\r\n" +
            "Content-Transfer-Encoding: base64\r\n" +
            "Content-ID: <qr@rao>\r\n" +
            "Content-Disposition: inline; filename=\"qr.png\"\r\n" +
            "\r\n" +
            b64.toString() +
            "--" + boundary + "--\r\n";

        try (Socket socket = new Socket(SERVIDOR, PUERTO);
             BufferedReader entrada = new BufferedReader(new InputStreamReader(socket.getInputStream(), StandardCharsets.UTF_8));
             DataOutputStream salida = new DataOutputStream(socket.getOutputStream())) {

            System.out.println("S : " + entrada.readLine());
            enviarComando(salida, entrada, "EHLO " + SERVIDOR + "\r\n");
            enviarComando(salida, entrada, "MAIL FROM:<" + EMISOR + ">\r\n");
            enviarComando(salida, entrada, "RCPT TO:<" + to + ">\r\n");
            enviarComando(salida, entrada, "DATA\r\n");
            enviarComando(salida, entrada, body + "\r\n.\r\n");
            enviarComando(salida, entrada, "QUIT\r\n");
        }
    }

    private static void enviarComando(DataOutputStream salida, BufferedReader entrada, String comando)
            throws IOException {
        salida.write(comando.getBytes(StandardCharsets.UTF_8));
        salida.flush();
        String respuesta = leerRespuesta(entrada);
        int codigoRespuesta = Integer.parseInt(respuesta.substring(0, 3));
        if (codigoRespuesta >= 400) {
            throw new IOException(
                    "No se pudo enviar el correo, error durante el comando: " + comando + ".\nError" + respuesta);
        }
    }

    static protected String leerRespuesta(BufferedReader in) throws IOException {
        StringBuilder lines = new StringBuilder();
        String line;
        while ((line = in.readLine()) != null) {
            lines.append(line).append("\n");
            if (line.length() > 3 && line.charAt(3) == ' ')
                break;
        }
        if (line == null) {
            throw new IOException("S : Server unawares closed the connection.");
        }
        return lines.toString();
    }
}

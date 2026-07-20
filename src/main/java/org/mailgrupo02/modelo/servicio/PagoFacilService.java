package org.mailgrupo02.modelo.servicio;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.modelo.entidad.*;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.net.http.HttpRequest.BodyPublishers;
import java.net.http.HttpResponse.BodyHandlers;
import java.time.Duration;
import java.util.HashMap;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class PagoFacilService {

    private static final String BASE_URL = "https://masterqr.pagofacil.com.bo/api/services/v2";

    private static final Map<String, String> ENV = cargarEnv();

    private static final String TOKEN_SERVICE = getEnv("PAGOFACIL_TOKEN_SERVICE");
    private static final String TOKEN_SECRET   = getEnv("PAGOFACIL_TOKEN_SECRET");
    private static final boolean TEST_MODE     = "true".equalsIgnoreCase(getEnv("PAGOFACIL_TEST_MODE", "true"));

    private static final HttpClient client = HttpClient.newBuilder()
            .connectTimeout(Duration.ofSeconds(10))
            .build();

    private static Map<String, String> cargarEnv() {
        Map<String, String> env = new HashMap<>();
        File envFile = new File(".env");
        if (envFile.exists()) {
            try (BufferedReader reader = new BufferedReader(new FileReader(envFile))) {
                String line;
                while ((line = reader.readLine()) != null) {
                    if (line.contains("=")) {
                        String[] parts = line.split("=", 2);
                        env.put(parts[0].trim(), parts[1].trim());
                    }
                }
            } catch (IOException e) {
                System.err.println("Error al leer el archivo .env: " + e.getMessage());
            }
        }
        return env;
    }

    private static String getEnv(String key) {
        return getEnv(key, null);
    }

    private static String getEnv(String key, String defaultValue) {
        String value = ENV.get(key);
        return (value != null) ? value : System.getenv(key) != null ? System.getenv(key) : defaultValue;
    }

    public static String login() {
        if (TOKEN_SERVICE == null || TOKEN_SECRET == null) {
            System.err.println("[PagoFacil] Error: Credenciales no configuradas.");
            return null;
        }
        try {
            HttpRequest request = HttpRequest.newBuilder()
                    .uri(URI.create(BASE_URL + "/login"))
                    .timeout(Duration.ofSeconds(10))
                    .header("Content-Type", "application/json")
                    .header("tcTokenService", TOKEN_SERVICE)
                    .header("tcTokenSecret", TOKEN_SECRET)
                    .POST(BodyPublishers.noBody())
                    .build();

            HttpResponse<String> response = client.send(request, BodyHandlers.ofString());
            if (response.statusCode() == 200) {
                return parseJsonField(response.body(), "accessToken");
            } else {
                System.err.println("[PagoFacil] Error en login. Status: " + response.statusCode()
                        + " | Body: " + response.body());
            }
        } catch (Exception e) {
            System.err.println("[PagoFacil] Excepción en login: " + e.getMessage());
        }
        return null;
    }

    public static String[] generarQR(String clientName, String clientPhone, String clientEmail,
                                     String companyTxId, double amount, String itemDescription) {
        String token = login();
        if (token == null) {
            return null;
        }

        if (clientName == null || clientName.trim().isEmpty())       clientName = "Cliente RAO Motos";
        if (clientPhone == null || clientPhone.trim().isEmpty())     clientPhone = "70000000";
        if (clientEmail == null || clientEmail.trim().isEmpty())     clientEmail = "cliente@correo.com";
        if (itemDescription == null || itemDescription.trim().isEmpty()) itemDescription = "Pago RAO Motos";

        clientName = escapeJson(clientName);
        itemDescription = escapeJson(itemDescription);

        double amountToSend = TEST_MODE ? 0.1 : amount;

        String jsonBody = "{\n"
                + "    \"paymentMethod\": 34,\n"
                + "    \"clientName\": \"" + clientName + "\",\n"
                + "    \"documentType\": 1,\n"
                + "    \"documentId\": \"" + clientPhone + "\",\n"
                + "    \"phoneNumber\": \"" + clientPhone + "\",\n"
                + "    \"correo\": \"" + clientEmail + "\",\n"
                + "    \"paymentNumber\": \"" + companyTxId + "\",\n"
                + "    \"amount\": " + amountToSend + ",\n"
                + "    \"currency\": 2,\n"
                + "    \"clientCode\": \"" + companyTxId + "\",\n"
                + "    \"callbackUrl\": \"https://masterqr.pagofacil.com.bo/api/services/v2/callback-dummy\",\n"
                + "    \"orderDetail\": [\n"
                + "        {\n"
                + "            \"serial\": 1,\n"
                + "            \"product\": \"" + itemDescription + "\",\n"
                + "            \"quantity\": 1,\n"
                + "            \"price\": " + amountToSend + ",\n"
                + "            \"discount\": 0,\n"
                + "            \"total\": " + amountToSend + "\n"
                + "        }\n"
                + "    ]\n"
                + "}";

        try {
            HttpRequest request = HttpRequest.newBuilder()
                    .uri(URI.create(BASE_URL + "/generate-qr"))
                    .timeout(Duration.ofSeconds(15))
                    .header("Content-Type", "application/json")
                    .header("Authorization", "Bearer " + token)
                    .POST(BodyPublishers.ofString(jsonBody))
                    .build();

            HttpResponse<String> response = client.send(request, BodyHandlers.ofString());
            if (response.statusCode() == 200) {
                String rawBody = response.body();
                String transactionId = parseJsonField(rawBody, "transactionId");
                String rawQr = parseJsonField(rawBody, "qrBase64");
                if (transactionId != null && rawQr != null) {
                    String cleanQr = rawQr.replace("\\/", "/");
                    return new String[]{ transactionId, formatBase64(cleanQr) };
                }
            } else {
                System.err.println("[PagoFacil] Error al generar QR. Status: " + response.statusCode()
                        + " | Body: " + response.body());
            }
        } catch (Exception e) {
            System.err.println("[PagoFacil] Excepción al generar QR: " + e.getMessage());
        }
        return null;
    }

    public static boolean consultarEstado(long pfTxId) {
        String token = login();
        if (token == null) return false;

        String jsonBody = "{\n"
                + "    \"pagofacilTransactionId\": " + pfTxId + "\n"
                + "}";

        try {
            HttpRequest request = HttpRequest.newBuilder()
                    .uri(URI.create(BASE_URL + "/query-transaction"))
                    .timeout(Duration.ofSeconds(10))
                    .header("Content-Type", "application/json")
                    .header("Authorization", "Bearer " + token)
                    .POST(BodyPublishers.ofString(jsonBody))
                    .build();

            HttpResponse<String> response = client.send(request, BodyHandlers.ofString());
            if (response.statusCode() == 200) {
                String body = response.body();
                String paymentStatus = parseJsonField(body, "paymentStatus");
                String paymentStatusDesc = parseJsonField(body, "paymentStatusDescription");

                return "2".equals(paymentStatus)
                        || "5".equals(paymentStatus)
                        || "Pagado".equalsIgnoreCase(paymentStatus)
                        || "Paid".equalsIgnoreCase(paymentStatus)
                        || "Success".equalsIgnoreCase(paymentStatus)
                        || "Completed".equalsIgnoreCase(paymentStatus)
                        || "Pagado".equalsIgnoreCase(paymentStatusDesc)
                        || "Paid".equalsIgnoreCase(paymentStatusDesc)
                        || "Success".equalsIgnoreCase(paymentStatusDesc)
                        || "Completed".equalsIgnoreCase(paymentStatusDesc)
                        || "Revisión".equalsIgnoreCase(paymentStatusDesc)
                        || "Revision".equalsIgnoreCase(paymentStatusDesc);
            } else {
                System.err.println("[PagoFacil] Error al consultar estado. Status: " + response.statusCode()
                        + " | Body: " + response.body());
            }
        } catch (Exception e) {
            System.err.println("[PagoFacil] Excepción al consultar estado: " + e.getMessage());
        }
        return false;
    }

    private static final String TX_FILE_PATH = "qr_transactions_grupo02.json";

    public static synchronized java.util.Map<String, String> cargarTransacciones() {
        java.util.Map<String, String> map = new java.util.HashMap<>();
        java.io.File file = new java.io.File(TX_FILE_PATH);
        if (!file.exists()) return map;
        try {
            String content = java.nio.file.Files.readString(file.toPath());
            Pattern pattern = Pattern.compile("\"([^\"]+)\"\\s*:\\s*\"([^\"]+)\"");
            Matcher matcher = pattern.matcher(content);
            while (matcher.find()) {
                map.put(matcher.group(1), matcher.group(2));
            }
        } catch (Exception e) {
            System.err.println("[PagoFacil] Error al cargar transacciones: " + e.getMessage());
        }
        return map;
    }

    public static synchronized void guardarTransacciones(java.util.Map<String, String> map) {
        StringBuilder sb = new StringBuilder();
        sb.append("{\n");
        int size = map.size();
        int count = 0;
        for (java.util.Map.Entry<String, String> entry : map.entrySet()) {
            sb.append("  \"").append(entry.getKey()).append("\": \"").append(entry.getValue()).append("\"");
            count++;
            if (count < size) sb.append(",");
            sb.append("\n");
        }
        sb.append("}");
        try {
            java.nio.file.Files.writeString(java.nio.file.Path.of(TX_FILE_PATH), sb.toString());
        } catch (Exception e) {
            System.err.println("[PagoFacil] Error al guardar transacciones: " + e.getMessage());
        }
    }

    public static void registrarTransaccion(String txId, String correo, double monto, String tipo) {
        java.util.Map<String, String> map = cargarTransacciones();
        map.put(txId, correo + ";" + monto + ";" + tipo);
        guardarTransacciones(map);
        System.out.println("[PagoFacil] Transacción registrada: " + txId + " (" + tipo + ")");
    }

    public static void removerTransaccion(String txId) {
        java.util.Map<String, String> map = cargarTransacciones();
        if (map.remove(txId) != null) {
            guardarTransacciones(map);
            System.out.println("[PagoFacil] Transacción removida: " + txId);
        }
    }

    private static String parseJsonField(String json, String field) {
        if (json == null) return null;
        Pattern stringPattern = Pattern.compile("\"" + field + "\"[\\s:]*\"([^\"]+)\"");
        Matcher matcher = stringPattern.matcher(json);
        if (matcher.find()) return matcher.group(1);
        Pattern numberPattern = Pattern.compile("\"" + field + "\"[\\s:]*([\\d\\.]+)");
        matcher = numberPattern.matcher(json);
        if (matcher.find()) return matcher.group(1);
        return null;
    }

    private static String escapeJson(String text) {
        if (text == null) return "";
        return text.replace("\\", "\\\\")
                   .replace("\"", "\\\"")
                   .replace("\n", "\\n")
                   .replace("\r", "\\r");
    }

    private static String formatBase64(String base64) {
        if (base64 == null) return null;
        StringBuilder sb = new StringBuilder();
        int len = base64.length();
        for (int i = 0; i < len; i += 76) {
            int end = Math.min(i + 76, len);
            sb.append(base64, i, end).append("\r\n");
        }
        return sb.toString();
    }
}

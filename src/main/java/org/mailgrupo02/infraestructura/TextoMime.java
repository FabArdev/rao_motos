package org.mailgrupo02.infraestructura;

import java.io.ByteArrayOutputStream;
import java.nio.charset.StandardCharsets;
import java.util.Base64;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Soporte de caracteres especiales (acentos, ñ, ¿¡) en el correo.
 *  - {@link #decodificar} entiende asuntos MIME-encoded (RFC 2047): =?UTF-8?B?..?= y =?UTF-8?Q?..?=.
 *  - {@link #codificarAsunto} arma un asunto de respuesta RFC 2047 cuando lleva caracteres no-ASCII.
 * El cuerpo se envía como UTF-8 (ver ClienteSMTP), y la lectura POP también es UTF-8 (ClientePOP).
 */
public final class TextoMime {

    private TextoMime() {}

    private static final Pattern PALABRA = Pattern.compile("=\\?([^?]+)\\?([BbQq])\\?([^?]*)\\?=");

    /** Decodifica las "encoded-words" RFC 2047 presentes en una cabecera (p. ej. el Subject). */
    public static String decodificar(String texto) {
        if (texto == null) return null;
        Matcher m = PALABRA.matcher(texto);
        StringBuffer sb = new StringBuffer();
        while (m.find()) {
            String charset = m.group(1);
            String enc = m.group(2).toUpperCase();
            String datos = m.group(3);
            String decodificado;
            try {
                byte[] bytes = enc.equals("B")
                        ? Base64.getMimeDecoder().decode(datos)
                        : quotedPrintable(datos);
                decodificado = new String(bytes, charset);
            } catch (Exception e) {
                decodificado = m.group(0); // si falla, dejar tal cual
            }
            m.appendReplacement(sb, Matcher.quoteReplacement(decodificado));
        }
        m.appendTail(sb);
        return sb.toString();
    }

    private static byte[] quotedPrintable(String s) {
        ByteArrayOutputStream bos = new ByteArrayOutputStream();
        for (int i = 0; i < s.length(); i++) {
            char c = s.charAt(i);
            if (c == '_') {
                bos.write(' ');
            } else if (c == '=' && i + 2 < s.length()) {
                bos.write(Integer.parseInt(s.substring(i + 1, i + 3), 16));
                i += 2;
            } else {
                bos.write(c);
            }
        }
        return bos.toByteArray();
    }

    /** Codifica el asunto a RFC 2047 (=?UTF-8?B?..?=) solo si tiene caracteres no-ASCII. */
    public static String codificarAsunto(String asunto) {
        if (asunto == null) return "";
        if (esAscii(asunto)) return asunto;
        String b64 = Base64.getEncoder().encodeToString(asunto.getBytes(StandardCharsets.UTF_8));
        return "=?UTF-8?B?" + b64 + "?=";
    }

    private static boolean esAscii(String s) {
        for (int i = 0; i < s.length(); i++) if (s.charAt(i) > 127) return false;
        return true;
    }
}

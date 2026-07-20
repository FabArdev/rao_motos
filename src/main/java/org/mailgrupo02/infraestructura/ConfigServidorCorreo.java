package org.mailgrupo02.infraestructura;

public class ConfigServidorCorreo {
    public static String PORT_SMTP = EnvLoader.get("SMTP_PORT");
    public static String PROTOCOL = "smtp";
    public static String HOST = EnvLoader.get("SMTP_HOST");
    public static String USER = EnvLoader.get("EMAIL_USER");
    public static String PASSWORD = EnvLoader.get("EMAIL_PASSWORD");
    public static String MAIL = EnvLoader.get("EMAIL_ADDRESS");
    public static String MAIL_PASSWORD = EnvLoader.get("EMAIL_PASSWORD");
}

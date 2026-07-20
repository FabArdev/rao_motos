package org.mailgrupo02;

import org.mailgrupo02.infraestructura.Conexion;
import java.io.BufferedReader;
import java.io.FileReader;
import java.sql.Connection;
import java.sql.Statement;

public class CrearTablas {
    public static void main(String[] args) {
        try {
            System.out.println("Conectando a PostgreSQL...");
            Connection conn = Conexion.conectar();

            System.out.println("Leyendo database_schema.sql...");
            StringBuilder sql = new StringBuilder();
            BufferedReader reader = new BufferedReader(
                    new FileReader("database_schema.sql"));

            String line;
            while ((line = reader.readLine()) != null) {
                // Ignorar comentarios y líneas vacías
                if (!line.trim().startsWith("--") && !line.trim().isEmpty()) {
                    sql.append(line).append("\n");
                }
            }
            reader.close();

            System.out.println("Ejecutando script SQL...");
            Statement stmt = conn.createStatement();

            // Dividir por punto y coma para ejecutar cada statement
            String[] statements = sql.toString().split(";");
            int count = 0;

            for (String statement : statements) {
                String trimmed = statement.trim();
                if (!trimmed.isEmpty()) {
                    try {
                        stmt.execute(trimmed);
                        count++;
                        System.out.println("  ✓ Statement " + count + " ejecutado");
                    } catch (Exception e) {
                        System.err.println("  ✗ Error en statement " + count + ": " + e.getMessage());
                    }
                }
            }

            stmt.close();
            conn.close();

            System.out.println("\n✓ ¡Script ejecutado! Total: " + count + " statements");
            System.out.println("\nAhora ejecuta: mvn exec:java");

        } catch (Exception e) {
            System.err.println("ERROR: " + e.getMessage());
            e.printStackTrace();
        }
    }
}

package org.mailgrupo02.modelo.servicio;
import org.mailgrupo02.modelo.dao.*;
import org.mailgrupo02.modelo.entidad.*;

public class UsuarioValidator {

    public static void validarCampos(String nombre, String correo, String password, String rol) {
        if (nombre == null || nombre.trim().isEmpty() || nombre.length() > 80) {
            throw new IllegalArgumentException("El nombre es obligatorio y no puede exceder 80 caracteres");
        }

        if (correo != null && !esCorreoValido(correo)) {
            throw new IllegalArgumentException("El correo no tiene un formato válido");
        }

        if (password == null || password.length() < 8) {
            throw new IllegalArgumentException("La contraseña debe tener al menos 8 caracteres");
        }

        if (rol == null || (!rol.equals("PROPIETARIO") && !rol.equals("CLIENTE"))) {
            throw new IllegalArgumentException("El rol debe ser PROPIETARIO o CLIENTE");
        }
    }

    private static boolean esCorreoValido(String correo) {
        if (correo == null || correo.length() > 120) {
            return false;
        }
        String correoRegex = "^[\\w-\\.]+@([\\w-]+\\.)+[\\w-]{2,4}$";
        return correo.matches(correoRegex);
    }
}

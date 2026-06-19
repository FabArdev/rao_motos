package org.mailgrupo02.negocio.usuarios;

public class UsuarioValidator {

    public static void validarCampos(String nombre, String email, String password, String rol) {
        if (nombre == null || nombre.trim().isEmpty() || nombre.length() > 80) {
            throw new IllegalArgumentException("El nombre es obligatorio y no puede exceder 80 caracteres");
        }

        if (email != null && !esEmailValido(email)) {
            throw new IllegalArgumentException("El email no tiene un formato válido");
        }

        if (password == null || password.length() < 8) {
            throw new IllegalArgumentException("La contraseña debe tener al menos 8 caracteres");
        }

        if (rol == null || (!rol.equals("PROPIETARIO") && !rol.equals("CLIENTE"))) {
            throw new IllegalArgumentException("El rol debe ser PROPIETARIO o CLIENTE");
        }
    }

    private static boolean esEmailValido(String email) {
        if (email == null || email.length() > 120) {
            return false;
        }
        String emailRegex = "^[\\w-\\.]+@([\\w-]+\\.)+[\\w-]{2,4}$";
        return email.matches(emailRegex);
    }
}

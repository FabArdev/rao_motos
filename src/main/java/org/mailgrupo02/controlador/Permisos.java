package org.mailgrupo02.controlador;

/**
 * Gate de control de acceso basado en roles (RBAC). La verdad de qué rol puede ejecutar
 * qué comando vive en {@link CatalogoComandos}. El rol admin es superusuario.
 */
public class Permisos {

    /** ¿El comando es público (ejecutable sin cuenta)? */
    public static boolean esPublico(String cmd) {
        CatalogoComandos.Comando c = CatalogoComandos.get(cmd);
        return c != null && c.roles.contains("PUBLICO");
    }

    /** ¿El comando está declarado en el catálogo? */
    public static boolean existe(String cmd) {
        return CatalogoComandos.get(cmd) != null;
    }

    /**
     * ¿Un usuario con el rol dado puede ejecutar el comando?
     *  - admin: todo (superusuario).
     *  - PUBLICO: cualquiera, incluso sin cuenta.
     *  - TODOS: cualquier usuario autenticado.
     *  - resto: el rol debe estar en la lista de roles del comando.
     * rol == null o "DESCONOCIDO" ⇒ remitente sin cuenta (solo comandos PUBLICO).
     */
    public static boolean puedeEjecutar(String cmd, String rol) {
        CatalogoComandos.Comando c = CatalogoComandos.get(cmd);
        if (c == null) return true;                 // comandos no catalogados no se bloquean aquí
        if (c.roles.contains("PUBLICO")) return true;
        if (rol == null || rol.isBlank() || "DESCONOCIDO".equalsIgnoreCase(rol)) return false;
        if ("admin".equalsIgnoreCase(rol)) return true;   // superusuario
        if (c.roles.contains("TODOS")) return true;
        for (String r : c.roles) if (r.equalsIgnoreCase(rol)) return true;
        return false;
    }
}

package org.mailgrupo02.controlador;

import java.util.Arrays;
import java.util.Collection;
import java.util.LinkedHashMap;
import java.util.LinkedHashSet;
import java.util.Map;
import java.util.Set;

/**
 * Fuente de verdad de los comandos del sistema: sintaxis, descripción, categoría y roles.
 * De aquí salen tanto el gate de permisos ({@link Permisos}) como el HELP filtrado por rol.
 *
 * Roles válidos en {@code roles}: admin | vendedor | almacenero | cliente, más dos especiales:
 *   - "PUBLICO": cualquiera, incluso sin cuenta (p. ej. registro).
 *   - "TODOS":   cualquier usuario autenticado (cualquiera de los 4 roles).
 * El rol admin es superusuario (puede todo) — se resuelve en {@link Permisos}.
 */
public class CatalogoComandos {

    public static class Comando {
        public final String nombre;
        public final String categoria;
        public final String sintaxis;
        public final String descripcion;
        public final Set<String> roles;

        Comando(String nombre, String categoria, String sintaxis, String descripcion, String... roles) {
            this.nombre = nombre;
            this.categoria = categoria;
            this.sintaxis = sintaxis;
            this.descripcion = descripcion;
            this.roles = new LinkedHashSet<>(Arrays.asList(roles));
        }
    }

    private static final Map<String, Comando> COMANDOS = new LinkedHashMap<>();

    private static void add(String nombre, String categoria, String sintaxis, String descripcion, String... roles) {
        COMANDOS.put(nombre.toUpperCase(), new Comando(nombre, categoria, sintaxis, descripcion, roles));
    }

    static {
        // ── General ──
        add("HELP", "General", "HELP", "Ayuda con los comandos disponibles para tu rol", "PUBLICO");
        add("WHOAMI", "General", "WHOAMI", "Muestra tu perfil y rol", "TODOS");
        add("CREATEUSUARIO", "General", "CREATEUSUARIO[nombre,correo,password,cliente,telefono,direccion]",
                "Registro público como cliente", "PUBLICO");
        add("ACTUALIZARPERFIL", "General", "ACTUALIZARPERFIL[nombre,password,telefono,direccion]",
                "Actualiza tus propios datos", "TODOS");
        add("VERNOTIFICACIONES", "General", "VERNOTIFICACIONES", "Tu bandeja de notificaciones", "TODOS");

        // ── Usuarios (admin) ──
        add("LISTARUSUARIOS", "Usuarios", "LISTARUSUARIOS", "Lista todos los usuarios", "admin");
        add("GETUSUARIO", "Usuarios", "GETUSUARIO[id]", "Detalle de un usuario", "admin");
        add("UPDATEUSUARIO", "Usuarios", "UPDATEUSUARIO[id,nombre,correo,pass,rol,tel,dir,activo]", "Edita un usuario", "admin");
        add("UPDATECLIENTE", "Usuarios", "UPDATECLIENTE[id,nitCi]", "Edita el NIT/CI de un cliente", "admin");
        add("DELETEUSUARIO", "Usuarios", "DELETEUSUARIO[id]", "Elimina un usuario", "admin");
        add("CAMBIARROL", "Usuarios", "CAMBIARROL[id,rol]", "Cambia el rol de un usuario", "admin");

        // ── Productos ──
        add("LISTARPRODUCTOS", "Productos", "LISTARPRODUCTOS", "Catálogo de productos", "TODOS");
        add("GETPRODUCTO", "Productos", "GETPRODUCTO[id]", "Detalle de un producto", "TODOS");
        add("CREATEPRODUCTO", "Productos", "CREATEPRODUCTO[codigo,nombre,marca,modelo,desc,pMinorista,pMayorista,cantMinMay]",
                "Crea un producto", "almacenero");
        add("UPDATEPRODUCTO", "Productos", "UPDATEPRODUCTO[id,codigo,nombre,marca,modelo,desc,pMin,pMay,cantMinMay,activo]",
                "Edita un producto", "almacenero");
        add("DELETEPRODUCTO", "Productos", "DELETEPRODUCTO[id]", "Baja lógica de un producto", "almacenero");

        // ── Proveedores (almacenero) ──
        add("LISTARPROVEEDORES", "Proveedores", "LISTARPROVEEDORES", "Lista de proveedores", "almacenero");
        add("GETPROVEEDOR", "Proveedores", "GETPROVEEDOR[id]", "Detalle de un proveedor", "almacenero");
        add("CREATEPROVEEDOR", "Proveedores", "CREATEPROVEEDOR[razonSocial,contacto,nit,telefono]", "Crea un proveedor", "almacenero");
        add("UPDATEPROVEEDOR", "Proveedores", "UPDATEPROVEEDOR[id,razonSocial,contacto,nit,telefono,activo]", "Edita un proveedor", "almacenero");
        add("DELETEPROVEEDOR", "Proveedores", "DELETEPROVEEDOR[id]", "Elimina un proveedor", "almacenero");

        // ── Compras (almacenero) ──
        add("LISTARCOMPRAS", "Compras", "LISTARCOMPRAS", "Lista de compras", "almacenero");
        add("GETCOMPRA", "Compras", "GETCOMPRA[id]", "Detalle de una compra", "almacenero");
        add("CREARCOMPRA", "Compras", "CREARCOMPRA[proveedorId]", "Abre una compra PENDIENTE", "almacenero");
        add("AGREGARDETALLECOMPRA", "Compras", "AGREGARDETALLECOMPRA[compraId,productoId,cantidad,precioUnit]", "Agrega una línea a la compra", "almacenero");
        add("RECIBIRCOMPRA", "Compras", "RECIBIRCOMPRA[id]", "Recibe la compra: ingresa stock y recalcula precios (RN23)", "almacenero");
        add("ANULARCOMPRA", "Compras", "ANULARCOMPRA[id]", "Anula la compra (revierte stock si estaba recibida, RN15)", "almacenero");

        // ── Inventario ──
        add("VERINVENTARIO", "Inventario", "VERINVENTARIO[*|productoId]", "Stock de productos", "vendedor", "almacenero");
        add("REGISTRARINGRESO", "Inventario", "REGISTRARINGRESO[productoId,cantidad,motivo]", "Ingreso manual de stock", "almacenero");
        add("REGISTRAREGRESO", "Inventario", "REGISTRAREGRESO[productoId,cantidad,motivo]", "Egreso manual de stock", "almacenero");
        add("SETSTOCKMINIMO", "Inventario", "SETSTOCKMINIMO[productoId,minimo]", "Define el stock mínimo", "almacenero");

        // ── Ventas ──
        add("LISTARVENTAS", "Ventas", "LISTARVENTAS", "Lista de ventas", "vendedor");
        add("GETVENTA", "Ventas", "GETVENTA[id]", "Detalle de una venta", "vendedor");
        add("CREARVENTA_CONTADO", "Ventas", "CREARVENTA_CONTADO[clienteId,metodo,prod:cant;prod:cant]", "Venta al contado (total server-side)", "vendedor");
        add("CREARVENTA_CREDITO", "Ventas", "CREARVENTA_CREDITO[clienteId,cuotas,interes,metodo,prod:cant;prod:cant]", "Venta a crédito con cuotas", "vendedor");
        add("CONFIRMARPAGO", "Ventas", "CONFIRMARPAGO[ventaId]", "Confirma el pago de una venta pendiente", "vendedor");
        add("ANULARVENTA", "Ventas", "ANULARVENTA[ventaId]", "Anula una venta y repone stock", "vendedor");
        add("MISVENTAS", "Ventas", "MISVENTAS", "Tus compras (cliente)", "cliente");
        add("MIVENTA", "Ventas", "MIVENTA[ventaId]", "Detalle de una venta tuya", "cliente");

        // ── Pedidos ──
        add("PEDIDO", "Pedidos", "PEDIDO[prod:cant;prod:cant]", "Crea un pedido con productos (cliente)", "cliente");
        add("MISPEDIDOS", "Pedidos", "MISPEDIDOS", "Tus pedidos", "cliente");
        add("MIPEDIDO", "Pedidos", "MIPEDIDO[id]", "Detalle de un pedido tuyo", "cliente");
        add("CANCELARPEDIDO", "Pedidos", "CANCELARPEDIDO[id]", "Cancela tu pedido (si sigue SOLICITADO)", "cliente");
        add("PAGARPEDIDO", "Pedidos", "PAGARPEDIDO[id,QR|EFECTIVO]", "Paga la venta de tu pedido aprobado", "cliente");
        add("LISTARPEDIDOS", "Pedidos", "LISTARPEDIDOS", "Lista de pedidos", "vendedor", "almacenero");
        add("GETPEDIDO", "Pedidos", "GETPEDIDO[id]", "Detalle de un pedido", "vendedor", "almacenero");
        add("APROBARPEDIDO", "Pedidos", "APROBARPEDIDO[id]", "Aprueba un pedido → genera venta pendiente", "vendedor");
        add("RECHAZARPEDIDO", "Pedidos", "RECHAZARPEDIDO[id,motivo]", "Rechaza un pedido", "vendedor");
        add("DESPACHARPEDIDO", "Pedidos", "DESPACHARPEDIDO[id]", "Despacha un pedido pagado (egreso stock)", "almacenero");
        add("ANULARPEDIDO", "Pedidos", "ANULARPEDIDO[id]", "Anula un pedido", "vendedor", "almacenero");

        // ── Créditos / Cobranza ──
        add("LISTARCREDITOS", "Créditos", "LISTARCREDITOS", "Lista de créditos", "vendedor");
        add("VERCUOTAS", "Créditos", "VERCUOTAS[creditoId]", "Cuotas de un crédito", "vendedor");
        add("PAGARCUOTA", "Créditos", "PAGARCUOTA[creditoId,numeroCuota,QR|EFECTIVO]", "Paga una cuota", "vendedor", "cliente");
        add("CORRERMORA", "Créditos", "CORRERMORA", "Ejecuta el cálculo de mora (tarea diaria)", "admin");
        add("MISCREDITOS", "Créditos", "MISCREDITOS", "Tus créditos", "cliente");
        add("MISCUOTAS", "Créditos", "MISCUOTAS[creditoId]", "Cuotas de tu crédito", "cliente");

        // ── Reportes ──
        add("REPORT_VENTAS_POR_MES", "Reportes", "REPORT_VENTAS_POR_MES[yyyy-MM]", "Ventas por mes", "vendedor");
        add("REPORT_VENTAS_POR_CLIENTE", "Reportes", "REPORT_VENTAS_POR_CLIENTE[clienteId]", "Ventas por cliente", "vendedor");
        add("REPORT_MORAS_PENDIENTES", "Reportes", "REPORT_MORAS_PENDIENTES", "Moras pendientes", "vendedor");

        // ── Sistema (admin) ──
        add("VERCONFIG", "Sistema", "VERCONFIG", "Parámetros de configuración", "admin");
        add("SETCONFIG", "Sistema", "SETCONFIG[clave,valor]", "Cambia un parámetro", "admin");
        add("VERBITACORA", "Sistema", "VERBITACORA", "Últimos accesos registrados", "admin");
    }

    public static Comando get(String nombre) {
        return nombre == null ? null : COMANDOS.get(nombre.toUpperCase());
    }

    public static Collection<Comando> todos() {
        return COMANDOS.values();
    }
}

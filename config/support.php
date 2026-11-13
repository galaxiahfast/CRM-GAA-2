<?php

return [
    'timezone' => env('SUPPORT_TIMEZONE', 'America/Mexico_City'),
    'automated_user' => [
        'name' => 'Sofia',
        'last_name' => 'Soporte (bot)',
        'email' => env('SUPPORT_AUTOMATED_USER_EMAIL', 'sofia.soporte@sistema.local'),
        'avatar' => env('SUPPORT_AUTOMATED_USER_AVATAR', 'img/support/sofia-avatar.svg'),
    ],
    'always_online_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SUPPORT_ALWAYS_ONLINE_EMAILS', 'administrador@datamid.com.mx'))
    ))),
    'help_recipient_email' => env('SUPPORT_HELP_RECIPIENT_EMAIL', 'administrador@datamid.com.mx'),

    // Una categoría por cada apartado del menú lateral, en el mismo orden del menú.
    // Las opciones de Control de Horas (Cronómetro, Reloj Checador, Productividad, Gráficos de Control
    // y Supervisión de Horas) comparten una sola categoría.
    // Cada categoría lleva 4 preguntas; 'steps' alimenta la guía en PDF.
    'questions' => [
        'inicio' => [
            'label' => 'Inicio',
            'icon' => 'inicio',
            'menu' => 'General › Inicio',
            'summary' => 'Inicio es la pantalla de bienvenida que aparece después de iniciar sesión. Muestra tu nombre y tres accesos rápidos para entrar al Dashboard, al soporte y a las preguntas frecuentes sin buscar en el menú.',
            'items' => [
                'que_es' => [
                    'question' => '¿Qué es la pantalla de Inicio?',
                    'answer' => 'Es la pantalla de bienvenida que aparece al iniciar sesión. Te saluda con tu nombre y reúne tres accesos rápidos: Dashboard, Soporte y Preguntas.',
                    'steps' => [
                        'Inicia sesión con tu correo y tu contraseña.',
                        'Revisa el saludo con tu nombre en la parte superior de la pantalla.',
                        'Elige una de las tres tarjetas para comenzar o usa el menú lateral para ir a cualquier otro apartado.',
                    ],
                ],
                'tarjetas' => [
                    'question' => '¿A dónde llevan las tarjetas de Inicio?',
                    'answer' => 'Dashboard abre el Panel General, Soporte abre el Chat de Soporte para pedir ayuda y Preguntas abre este asistente con la guía de cada apartado.',
                    'steps' => [
                        'Dashboard: consulta clientes, archivos e indicadores en el Panel General.',
                        'Soporte: solicita ayuda en el chat general con los demás colaboradores.',
                        'Preguntas: elige un apartado y consulta cómo utilizarlo.',
                    ],
                ],
                'menu_lateral' => [
                    'question' => '¿Cómo contraigo el menú lateral o busco un apartado?',
                    'answer' => 'Usa el botón de la parte superior del menú para contraerlo o ampliarlo. El buscador “Buscar Apartados...” encuentra cualquier opción por su nombre y también acepta búsqueda por voz.',
                    'steps' => [
                        'Selecciona el botón de menú de la parte superior del panel lateral para contraerlo o ampliarlo.',
                        'Escribe el nombre del apartado en “Buscar Apartados...”.',
                        'Selecciona el resultado de la lista para abrirlo directamente.',
                        'Si prefieres hablar, usa el botón “Buscar por Voz”.',
                    ],
                ],
                'opciones_visibles' => [
                    'question' => '¿Por qué no veo algunas opciones del menú?',
                    'answer' => 'El menú se adapta a tu rol y a tus permisos: apartados como Administración, Clientes, Gestión de Respaldos u Hoja de Entrega solo aparecen para quien tiene acceso.',
                    'steps' => [
                        'Confirma con tu jefe directo qué apartados corresponden a tu puesto.',
                        'Solicita al administrador que revise tu rol y tus permisos.',
                        'Cuando te confirmen el cambio, recarga la página para ver el menú actualizado.',
                    ],
                ],
            ],
        ],
        'panel_general' => [
            'label' => 'Panel General',
            'icon' => 'panel',
            'menu' => 'General › Panel General',
            'summary' => 'El Panel General muestra los clientes que te corresponden y el avance mensual de los formatos de cada servicio contratado. Permite filtrar por mes, año y estatus, y abrir el seguimiento detallado o el resumen anual de cada cliente.',
            'items' => [
                'que_muestra' => [
                    'question' => '¿Qué muestra el Panel General?',
                    'answer' => 'Muestra el “Informe de clientes”: una tarjeta por cliente con el porcentaje de avance de cada servicio contratado en el mes elegido. Con permiso para administrar clientes, también verás los archivos subidos.',
                    'steps' => [
                        'Entra a General › Panel General.',
                        'Revisa las tarjetas del “Informe de clientes”; cada servicio muestra su porcentaje de avance.',
                        'Si tienes permiso para administrar clientes, consulta en la parte superior los archivos mensuales, anuales y totales subidos.',
                    ],
                ],
                'completos_incompletos' => [
                    'question' => '¿Cómo distingo los clientes completos de los incompletos?',
                    'answer' => 'Usa las pestañas Incompletos y Completos. Un cliente aparece como completo cuando el promedio de avance de sus servicios llega al 100 %; si es menor, aparece como incompleto. Si hay más clientes de los que caben en dos filas, usa las páginas al pie del informe.',
                    'steps' => [
                        'Selecciona la pestaña Incompletos para ver los clientes con formatos pendientes.',
                        'Selecciona la pestaña Completos para ver los clientes que ya cumplieron en el periodo.',
                        'Al cambiar de pestaña, el listado regresa a la primera página.',
                        'Usa los números de página o Anterior y Siguiente para consultar más clientes.',
                    ],
                ],
                'cambiar_periodo' => [
                    'question' => '¿Cómo cambio el mes o el año que consulto?',
                    'answer' => 'Elige otro mes o año en los selectores de la esquina superior derecha y el panel recalculará el avance. Por defecto se muestra el mes anterior al actual.',
                    'steps' => [
                        'Abre el selector de mes y elige el que necesitas.',
                        'Abre el selector de año y elige el año.',
                        'Espera a que el panel actualice los porcentajes y los totales de archivos.',
                    ],
                ],
                'seguimiento_resumen' => [
                    'question' => '¿Cómo abro el seguimiento o el resumen anual de un cliente?',
                    'answer' => 'Selecciona la tarjeta del cliente para abrir su seguimiento. Para el resumen anual, abre el menú de tres puntos de la tarjeta y elige “Resumen anual”.',
                    'steps' => [
                        'Selecciona la tarjeta del cliente para consultar su seguimiento y sus formatos.',
                        'Para el resumen anual, abre el menú de tres puntos de la tarjeta y elige “Resumen anual”.',
                        'En el “Desglose Anual” consulta el avance por servicio y por mes.',
                        'Presiona “Cerrar reporte” para regresar al panel.',
                    ],
                ],
            ],
        ],
        'conciliacion_ocr' => [
            'label' => 'Conciliación OCR',
            'icon' => 'ocr',
            'menu' => 'General › Conciliación Bancaria › Conciliación OCR',
            'summary' => 'La Conciliación OCR cruza el estado de cuenta bancario contra el auxiliar contable cuando el cliente entrega el estado de cuenta escaneado o como imagen. La herramienta reconoce el texto con OCR, aplica las reglas de cruce y genera la conciliación del mes en Excel.',
            'items' => [
                'cuando_usar' => [
                    'question' => '¿Cuándo debo usar la Conciliación OCR?',
                    'answer' => 'Cuando el estado de cuenta llega como imagen o PDF escaneado. Si el banco entrega un PDF digital con texto seleccionable, también puedes usar la Conciliación Bancaria estándar.',
                    'steps' => [
                        'Entra a General › Conciliación Bancaria › Conciliación OCR.',
                        'Verifica en qué formato recibiste el estado de cuenta: Excel, CSV, PDF digital, PDF escaneado o imagen.',
                        'Si es escaneado o reimpreso, continúa en esta herramienta; si es un PDF digital, puedes usar Conciliación Bancaria.',
                    ],
                ],
                'archivos' => [
                    'question' => '¿Qué archivos necesito cargar?',
                    'answer' => 'Tres archivos: el estado de cuenta bancario (Excel, CSV o PDF), el auxiliar contable de bancos (.xlsx) y la conciliación del mes anterior (.xls o .xlsx).',
                    'steps' => [
                        'En el paso 1, “Cargar los 3 archivos”, sube el Estado de Cuenta Bancario.',
                        'Sube el Auxiliar Contable de Bancos en .xlsx, con las columnas A a H: Fecha, Tipo póliza, Núm. póliza, Descripción, Factura/Cheque, Depósitos (Cargos), Retiros (Abonos) y Saldo.',
                        'Sube la Conciliación del Mes Anterior en .xls o .xlsx.',
                        'Si el estado de cuenta es un PDF escaneado o una imagen, espera a que termine la lectura OCR; el tiempo depende del número de hojas.',
                        'Confirma que los tres archivos quedaron cargados antes de continuar.',
                    ],
                ],
                'procesar' => [
                    'question' => '¿Cómo ejecuto la conciliación y leo el resumen?',
                    'answer' => 'Con los tres archivos cargados, presiona “Procesar Conciliación”. El resumen muestra los movimientos en banco y contabilidad, los cruzados, los sin cruzar y el porcentaje de conciliación.',
                    'steps' => [
                        'Presiona “Procesar Conciliación”.',
                        'Revisa el paso 2, “Resumen de la Conciliación”.',
                        'Consulta el porcentaje de conciliación: (cruzados en banco + cruzados en contabilidad) ÷ (total banco + total contabilidad) × 100.',
                        'Baja a los pasos 3, 4 y 5 para ver el detalle de los movimientos.',
                        'Revisa los movimientos sin cruzar en los pasos 4 (banco) y 5 (contabilidad) para validar si falta registrarlos o si son partidas en tránsito.',
                    ],
                ],
                'generar_excel' => [
                    'question' => '¿Cómo genero la conciliación del mes en Excel?',
                    'answer' => 'En el paso 7 captura el cliente, el RFC, el banco y los nombres de las firmas, y presiona “Descargar Conciliación en Excel”. Ese archivo se sube el mes siguiente como conciliación anterior.',
                    'steps' => [
                        'Escribe el nombre del cliente y su RFC (máximo 13 caracteres).',
                        'Selecciona el banco y, si hace falta, carga su logotipo en PNG o JPG.',
                        'Escribe el nombre del auxiliar contable y del contador supervisor.',
                        'Presiona “Descargar Conciliación en Excel” y conserva el archivo para el mes siguiente.',
                    ],
                ],
            ],
        ],
        'conciliacion_bancaria' => [
            'label' => 'Conciliación Bancaria',
            'icon' => 'bancaria',
            'menu' => 'General › Conciliación Bancaria › Conciliación Bancaria',
            'summary' => 'La Conciliación Bancaria cruza de forma automática el estado de cuenta contra el auxiliar contable de bancos y la conciliación del mes anterior. Está pensada para estados de cuenta digitales: PDF con texto seleccionable, Excel o CSV.',
            'items' => [
                'archivos' => [
                    'question' => '¿Qué archivos necesito cargar?',
                    'answer' => 'Tres archivos: el estado de cuenta bancario (Excel, CSV o PDF digital), el auxiliar contable de bancos (.xlsx) y la conciliación del mes anterior (.xls o .xlsx).',
                    'steps' => [
                        'En el paso 1, “Cargar los 3 archivos”, sube el Estado de Cuenta Bancario.',
                        'Sube el Auxiliar Contable de Bancos en .xlsx con las columnas A a H: Fecha, Tipo póliza, Núm. póliza, Descripción, Factura/Cheque, Depósitos (Cargos), Retiros (Abonos) y Saldo.',
                        'Sube la Conciliación del Mes Anterior en .xls o .xlsx.',
                        'No necesitas reordenar las columnas del estado de cuenta: se detectan por su encabezado.',
                        'Verifica que cada archivo aparezca como cargado.',
                    ],
                ],
                'procesar' => [
                    'question' => '¿Cómo ejecuto la conciliación y leo el resumen?',
                    'answer' => 'Presiona “Procesar Conciliación”. El resumen indica los movimientos en banco y contabilidad, los cruzados, los sin cruzar y el porcentaje de conciliación.',
                    'steps' => [
                        'Presiona “Procesar Conciliación”.',
                        'Revisa el paso 2, “Resumen de la Conciliación”.',
                        'Consulta en el paso 3 los movimientos cruzados con la regla que se aplicó a cada uno.',
                        'Los movimientos se cruzan con cinco reglas en cascada: cruce exacto, fecha cercana (hasta 3 días), cruce por nombre, suma de comisiones/IVA y suma grupal.',
                    ],
                ],
                'mes_anterior' => [
                    'question' => '¿Para qué sirve la conciliación del mes anterior?',
                    'answer' => 'Controla las partidas que quedaron abiertas: cargos y abonos del banco no correspondidos por contabilidad y viceversa. Si ya se reflejaron este mes, pasan a movimientos cruzados.',
                    'steps' => [
                        'Carga el archivo generado el mes anterior en el paso 1.',
                        'Después de procesar, abre el paso 6, “Control de Partidas del Mes Anterior”.',
                        'Verifica qué partidas ya se localizaron; salen de los reportes de movimientos sin cruzar.',
                    ],
                ],
                'generar_excel' => [
                    'question' => '¿Cómo genero y guardo la conciliación del mes?',
                    'answer' => 'En el paso 7 captura cliente, RFC, banco y firmas, y presiona “Descargar Conciliación en Excel”. El archivo incluye las partidas sin cruzar del mes y las anteriores aún no aclaradas.',
                    'steps' => [
                        'Escribe el nombre del cliente y su RFC.',
                        'Selecciona el banco o carga su logotipo en PNG o JPG.',
                        'Escribe el nombre del auxiliar contable y del contador supervisor.',
                        'Presiona “Descargar Conciliación en Excel” y guarda el archivo: se sube el mes siguiente como conciliación anterior.',
                    ],
                ],
            ],
        ],
        'administracion' => [
            'label' => 'Administración',
            'icon' => 'administracion',
            'menu' => 'Información › Administración',
            'summary' => 'Administración concentra la gestión de los colaboradores y de la estructura organizacional: usuarios, roles, puestos, áreas, permisos, asignaciones, clientes y actividades. Cada tarjeta ofrece sus acciones de creación, edición y eliminación, y el organigrama muestra la jerarquía de la organización.',
            'items' => [
                'que_hay' => [
                    'question' => '¿Qué puedo gestionar en Administración?',
                    'answer' => 'Usuarios, roles, puestos, áreas, permisos, asignaciones, clientes y actividades. Cada una tiene su tarjeta en el “Listado general” y el organigrama está en la otra pestaña.',
                    'steps' => [
                        'Entra a Información › Administración.',
                        'En “Listado general” recorre las tarjetas con las flechas laterales.',
                        'Usa los botones Crear, Editar o Eliminar de la tarjeta que necesites.',
                        'Cambia a la pestaña “Organigrama” para ver la estructura jerárquica.',
                    ],
                ],
                'crear_usuario' => [
                    'question' => '¿Cómo creo un usuario?',
                    'answer' => 'En la tarjeta Usuarios presiona Crear, captura sus datos personales, define su perfil organizacional (rol, puesto y área) y guarda. El sistema sugiere una contraseña que puedes cambiar.',
                    'steps' => [
                        'En la tarjeta Usuarios presiona Crear.',
                        'En Datos personales escribe nombres, apellidos y correo electrónico, y define la contraseña.',
                        'En Perfil organizacional elige el Rol, el Puesto de trabajo y el Área / Departamento.',
                        'Si la persona usa el reloj checador, selecciona su ID Checador y captura el precio por hora o el apoyo económico por día.',
                        'Presiona el botón para guardar.',
                    ],
                ],
                'editar_eliminar_usuario' => [
                    'question' => '¿Cómo edito o elimino un usuario?',
                    'answer' => 'En la tarjeta Usuarios elige Editar o Eliminar y busca a la persona por nombre o correo. Para eliminar se solicita confirmar con su nombre completo, su correo y la palabra ELIMINAR.',
                    'steps' => [
                        'En la tarjeta Usuarios presiona Editar o Eliminar.',
                        'En “Seleccionar usuario” busca a la persona por nombre o correo.',
                        'Para editar, cambia los datos necesarios y guarda; para cambiar la contraseña, usa los campos de nueva contraseña.',
                        'Para eliminar, completa la confirmación solicitada y presiona Eliminar.',
                    ],
                ],
                'roles' => [
                    'question' => '¿Cómo creo un rol y defino sus permisos?',
                    'answer' => 'En la tarjeta Roles presiona Crear, escribe el nombre y la descripción (máximo 255 caracteres) y marca los permisos por grupo. Con Editar modificas un rol y con Eliminar lo retiras.',
                    'steps' => [
                        'En la tarjeta Roles presiona Crear.',
                        'En “Información del rol” escribe el nombre del rol y su descripción.',
                        'En “Permisos del rol” elige un grupo y marca los permisos que tendrá.',
                        'Presiona Guardar.',
                        'Antes de eliminar un rol, reasigna a las personas que lo tienen para que no queden sin perfil.',
                    ],
                ],
            ],
        ],
        'clientes' => [
            'label' => 'Clientes',
            'icon' => 'clientes',
            'menu' => 'Información › Clientes',
            'summary' => 'Clientes administra el directorio de clientes del despacho: sus datos fiscales, los contadores asignados, los servicios contratados y el seguimiento mensual de sus formatos. Cada cliente tiene una ficha de seguimiento donde se suben y consultan los archivos PDF.',
            'items' => [
                'buscar' => [
                    'question' => '¿Cómo busco un cliente?',
                    'answer' => 'Escribe en el campo “Busca un cliente”. El listado se filtra por nombre, RFC, correo o dirección y muestra contador principal, teléfono y las acciones de cada cliente.',
                    'steps' => [
                        'Entra a Información › Clientes.',
                        'Escribe el nombre, el RFC, el correo o la dirección en “Busca un cliente”.',
                        'Usa la columna Acciones para abrir el seguimiento, editar o eliminar.',
                        'Haz clic en un correo o una dirección para copiarlos al portapapeles.',
                    ],
                ],
                'agregar' => [
                    'question' => '¿Cómo agrego un cliente?',
                    'answer' => 'Presiona “Agregar cliente”, captura nombre o razón social y RFC (campos con asterisco), asigna al menos un contador y selecciona los servicios contratados. Después presiona Guardar.',
                    'steps' => [
                        'Presiona “Agregar cliente” (disponible con permiso para administrar clientes).',
                        'Captura Nombre / Razón social, apellidos si es persona física, correo, teléfono, RFC de 13 caracteres, dirección y observaciones.',
                        'En Contadores selecciona a los responsables; el primero queda como Principal.',
                        'En Servicios contratados elige servicios y subservicios; si aplica, selecciona estados para impuestos sobre nómina y las declaraciones.',
                        'Presiona Guardar.',
                    ],
                ],
                'editar_eliminar' => [
                    'question' => '¿Cómo edito o elimino un cliente?',
                    'answer' => 'En la columna Acciones usa “Editar cliente” para modificar sus datos o “Eliminar cliente” para borrarlo. Antes de eliminar aparece la confirmación “¿Deseas eliminar este cliente?”.',
                    'steps' => [
                        'Localiza al cliente con el buscador.',
                        'Presiona “Editar cliente”, cambia los datos y presiona Actualizar.',
                        'Para borrarlo, presiona “Eliminar cliente” y confirma el aviso.',
                    ],
                ],
                'subir_pdf' => [
                    'question' => '¿Cómo subo un archivo PDF de un cliente?',
                    'answer' => 'En la ficha, entra al servicio y subservicio, y selecciona el recuadro del formato (acuse o comprobante). Solo se aceptan archivos PDF y puedes consultarlos o eliminarlos desde el menú de tres puntos.',
                    'steps' => [
                        'Abre la ficha y elige el mes y el año del periodo.',
                        'Selecciona el servicio y el subservicio correspondientes.',
                        'Haz clic en el recuadro del formato (acuse o comprobante, por estado o declaración) y elige el PDF.',
                        'Para revisar o quitar un archivo, abre el menú de tres puntos y elige Ver o Eliminar.',
                    ],
                ],
            ],
        ],
        'control_horas' => [
            'label' => 'Monitoreo',
            'icon' => 'reloj',
            'menu' => 'Monitoreo',
            'summary' => 'Monitoreo reúne las herramientas para registrar y revisar el tiempo de trabajo: el Cronómetro para llevar cada actividad, el Reloj Checador para consultar tus marcas, Productividad para revisar tu periodo, los Gráficos de Control con sus gráficas y, para quien tiene permiso, Supervisión de Horas. Según tu rol verás solo algunas de estas opciones.',
            'items' => [
                'cronometro' => [
                    'question' => '¿Cómo inicio una actividad en el Cronómetro?',
                    'answer' => 'En “Nuevo registro” elige el Cliente y la Actividad y presiona “Iniciar actividad”. El cronómetro empieza a contar de inmediato.',
                    'steps' => [
                        'Entra a Monitoreo › Control de Actividades › Cronómetro.',
                        'En Cliente, escribe o selecciona el cliente donde trabajarás.',
                        'En Actividad, escribe o selecciona la actividad que realizarás.',
                        'Presiona “Iniciar actividad”.',
                    ],
                ],
                'reloj_checador' => [
                    'question' => '¿Cómo consulto mis marcas del reloj checador?',
                    'answer' => 'Elige Fecha Inicio y Fecha Fin y presiona “Revisar Horas”. Por defecto se consultan los últimos 15 días.',
                    'steps' => [
                        'Entra a Monitoreo › Control de Horas › Reloj Checador.',
                        'Selecciona Fecha Inicio y Fecha Fin.',
                        'Presiona “Revisar Horas”.',
                        'Consulta el tiempo neto, las horas decimales, el pago base, el bono y el total del periodo.',
                    ],
                ],
                'productividad' => [
                    'question' => '¿Cómo consulto mi productividad de un periodo?',
                    'answer' => 'En “Periodo de consulta” elige Desde y Hasta y presiona “Consultar periodo”.',
                    'steps' => [
                        'Entra a Monitoreo › Control de Actividades › Productividad.',
                        'En Desde y Hasta selecciona las fechas.',
                        'Presiona “Consultar periodo”.',
                        'Revisa el resumen y el detalle del periodo.',
                    ],
                ],
                'panel_control' => [
                    'question' => '¿Cómo consulto un periodo en los Gráficos de Control?',
                    'answer' => 'En “Filtros de productividad” elige la fecha inicial y la final y presiona Buscar. Si eres administrador, también seleccionas al colaborador.',
                    'steps' => [
                        'Entra a Monitoreo › Control de Actividades › Gráficos de Control.',
                        'Si eres administrador, busca al colaborador por ID, nombre o correo.',
                        'Define la Fecha inicial y la Fecha final.',
                        'Presiona Buscar.',
                    ],
                ],
            ],
        ],
        'gestion_respaldos' => [
            'label' => 'Gestión de Respaldos',
            'icon' => 'respaldo',
            'menu' => 'Monitoreo › Gestión de Respaldos',
            'summary' => 'Gestión de Respaldos compara los respaldos de CONTPAQi que están en el servidor con los que ya se copiaron a la memoria externa. Permite respaldar empresas, revisar el estado de cada una, consultar el historial y descargar la guía técnica de configuración.',
            'items' => [
                'carpeta_destino' => [
                    'question' => '¿Cómo selecciono la carpeta de destino?',
                    'answer' => 'En la pestaña Respaldos navega por las carpetas de la memoria externa con Abrir y Volver; la carpeta elegida define dónde se comprueban y se guardan los respaldos.',
                    'steps' => [
                        'Conecta la memoria externa si aparece el aviso “Conecta la memoria”.',
                        'Usa Abrir para entrar a una subcarpeta y Volver para regresar a la anterior.',
                        'Selecciona la carpeta de destino.',
                        'Presiona “Actualizar y comprobar” para validar la sincronización.',
                    ],
                ],
                'estado_fecha' => [
                    'question' => '¿Cómo reviso el estado de los respaldos de una fecha?',
                    'answer' => 'Elige la fecha en la tabla “Fecha de respaldos” y filtra por estado: Todos, Completos, Por respaldar, Ya respaldadas o Incompletos. La pestaña Empresas muestra si cada una está respaldada o pendiente hoy.',
                    'steps' => [
                        'En la pestaña Respaldos elige la fecha; “Mostrar fechas anteriores” amplía la lista.',
                        'Filtra por Todos, Completos, Por respaldar, Ya respaldadas o Incompletos.',
                        'Revisa la etiqueta de cada empresa: Disponible, Ya respaldada, Copiando, Requiere actualización o Incompleta.',
                        'Abre la pestaña Empresas para ver el estado de hoy y el último respaldo localizado.',
                    ],
                ],
                'respaldar' => [
                    'question' => '¿Cómo respaldo una o varias empresas?',
                    'answer' => 'Marca las empresas disponibles y presiona “Respaldar N empresas”. El sistema valida, copia y muestra el avance a todos los usuarios; no se puede iniciar otro respaldo hasta que termine.',
                    'steps' => [
                        'Filtra por “Por respaldar” y confirma la carpeta de destino.',
                        'Marca las empresas o usa la casilla para seleccionar todas las disponibles.',
                        'Presiona “Respaldar” con el número de empresas seleccionadas (requiere permiso para respaldar).',
                        'Espera a que termine el avance; no podrás iniciar otro respaldo mientras tanto.',
                    ],
                ],
                'descargar' => [
                    'question' => '¿Cómo descargo un respaldo del servidor?',
                    'answer' => 'En el detalle de cada empresa selecciona “Base de datos · BAK” o “Documento digital · ZIP/RAR” para descargar el archivo correspondiente.',
                    'steps' => [
                        'Ubica la empresa y la fecha del respaldo.',
                        'Selecciona “Base de datos · BAK” para descargar la base de datos.',
                        'Selecciona “Documento digital” para descargar el ZIP o RAR.',
                        'Si aparece “Archivo no encontrado”, el respaldo está incompleto.',
                    ],
                ],
            ],
        ],
        'hoja_entrega' => [
            'label' => 'Hoja de Entrega',
            'icon' => 'hoja',
            'menu' => 'Documentos › Hoja de Entrega',
            'summary' => 'Hoja de Entrega controla en un solo expediente la recepción, entrega, préstamo y compra de los equipos. Su acceso es exclusivo para administradores; cada orden genera sus documentos y queda disponible en el historial.',
            'items' => [
                'tipos' => [
                    'question' => '¿Qué tipos de movimiento puedo registrar?',
                    'answer' => 'Recepción (equipo que ingresa a DataMID), Entrega (equipo que se devuelve o entrega), Préstamo (asignación temporal) y Compra (equipo adquirido recientemente).',
                    'steps' => [
                        'Entra a Documentos › Hoja de Entrega y abre “Nueva orden”.',
                        'En “Tipo de movimiento” elige Recepción, Entrega, Préstamo o Compra.',
                        'El botón final cambia según el movimiento: Generar recepción, Generar entrega, Generar préstamo o Registrar compra.',
                    ],
                ],
                'nueva_orden' => [
                    'question' => '¿Cómo creo una nueva orden?',
                    'answer' => 'En “Nueva orden” elige el tipo de movimiento, selecciona al cliente y quién entrega, captura los datos del equipo y las condiciones, y presiona el botón final para generar el documento.',
                    'steps' => [
                        'Elige el tipo de movimiento.',
                        'En “Cliente y entrega” busca al cliente y escribe quién entrega.',
                        'Registra el equipo: tipo, marca, modelo y número de serie.',
                        'Completa “Condiciones y explicación” con el trabajo realizado, el estado físico y las firmas confirmadas.',
                        'Presiona el botón final, por ejemplo “Generar recepción”.',
                    ],
                ],
                'prestamo' => [
                    'question' => '¿Cómo registro un préstamo y su devolución?',
                    'answer' => 'Elige Préstamo y define la operación: “Nuevo préstamo” o “Registrar devolución”. En el préstamo captura la fecha límite de devolución; en la devolución elige la orden pendiente.',
                    'steps' => [
                        'En “Tipo de movimiento” elige Préstamo.',
                        'En “Operación de préstamo” selecciona “Nuevo préstamo” o “Registrar devolución”.',
                        'Para una devolución, busca y elige la orden pendiente.',
                        'Captura la fecha límite de devolución y confirma el acta firmada.',
                    ],
                ],
                'historial' => [
                    'question' => '¿Cómo busco una orden anterior?',
                    'answer' => 'En “Historial” busca por folio, serie o cliente y filtra por movimiento y estado. Abre una orden para ver su información, seguimiento, evidencia y documentos.',
                    'steps' => [
                        'Abre la pestaña “Historial”.',
                        'Escribe el folio, la serie o el cliente en el buscador.',
                        'Filtra por Movimiento o Estado si lo necesitas.',
                        'Abre la orden para consultar su detalle; “Volver al historial” regresa a la lista.',
                        'Dentro de la orden, baja a “Documentos de la orden” para volver a descargar el acta o el documento que necesites.',
                    ],
                ],
            ],
        ],
        'reportes_soporte' => [
            'label' => 'Reportes de Soporte',
            'icon' => 'reporte',
            'menu' => 'Documentos › Reportes › Reportes de Soporte',
            'summary' => 'Reportes de Soporte genera los documentos membretados de los servicios técnicos realizados a un departamento o cliente. Cada reporte recibe un folio y una fecha automáticos, se guarda en el historial y puede descargarse en PDF.',
            'items' => [
                'generar' => [
                    'question' => '¿Cómo genero un reporte de soporte?',
                    'answer' => 'En “Generar reporte” marca los servicios realizados, captura la cantidad y las personas o equipos atendidos, revisa el resumen y presiona “Generar PDF”. El folio y la fecha se asignan automáticamente.',
                    'steps' => [
                        'Entra a Documentos › Reportes › Reportes de Soporte.',
                        'Busca los servicios en “Buscar servicios de soporte” y márcalos en la columna Seleccionar.',
                        'Indica la Cantidad y las Personas o equipos atendidos de cada servicio.',
                        'Presiona “Ver resumen” para revisar el destinatario y los servicios.',
                        'Presiona “Generar PDF”.',
                    ],
                ],
                'historial' => [
                    'question' => '¿Cómo busco, veo o descargo un reporte?',
                    'answer' => 'En “Historial” filtra por fecha, departamento o persona, o busca por folio o servicio. Con las acciones Ver o Descargar de cada fila abres o guardas el PDF.',
                    'steps' => [
                        'Abre la pestaña “Historial”.',
                        'Elige la fecha de soportes o escribe un folio o servicio en el buscador.',
                        'Filtra por departamento o persona si lo necesitas; “Limpiar” borra los filtros.',
                        'Presiona Ver o Descargar en la fila del reporte.',
                    ],
                ],
                'descarga_grupal' => [
                    'question' => '¿Cómo descargo varios reportes juntos?',
                    'answer' => 'Marca las filas (o “Seleccionar página”) y presiona “Descargar selección”. “Ver selección” muestra lo que elegiste antes de descargar.',
                    'steps' => [
                        'Aplica los filtros para reunir los reportes que necesitas.',
                        'Marca las casillas de las filas o usa “Seleccionar página”.',
                        'Presiona “Ver selección” para revisar lo elegido.',
                        'Presiona “Descargar selección”.',
                    ],
                ],
                'eliminar_papelera' => [
                    'question' => '¿Cómo elimino un reporte y cómo lo recupero?',
                    'answer' => 'Presiona Eliminar en la fila y confirma escribiendo el folio exacto y la palabra ELIMINAR. El reporte pasa a la papelera de reciclaje, desde donde puedes restaurarlo o eliminarlo de forma permanente.',
                    'steps' => [
                        'En la fila del reporte presiona Eliminar.',
                        'Escribe el folio exacto y la palabra ELIMINAR (no se permite pegar el texto).',
                        'Para recuperarlo, abre la papelera de reciclaje y presiona Restaurar.',
                        'Para borrarlo definitivamente, usa “Eliminar de forma permanente” con la misma verificación.',
                    ],
                ],
            ],
        ],
        'reporte_respaldos' => [
            'label' => 'Reporte de Respaldos',
            'icon' => 'reporte_respaldos',
            'menu' => 'Documentos › Reportes › Reporte de Respaldos',
            'summary' => 'Reporte de Respaldos genera el Informe Técnico de Respaldos: supervisa los servidores, sus perfiles de respaldo y las incidencias a partir de los registros de la última ejecución. Su acceso es exclusivo para administradores.',
            'items' => [
                'actualizar' => [
                    'question' => '¿Cómo actualizo la información del informe?',
                    'answer' => 'Al abrir el apartado se muestra la última lectura guardada, con la barra al 100 %, y no se leen los registros otra vez para no desgastar los servidores. Para actualizar la información presiona “Actualizar registros”: el sistema lee los registros en modo de solo acceso, perfil por perfil, y la barra muestra el avance, el porcentaje y el tiempo estimado. La barra se llena de forma continua y tiene un punto por servidor: cada servidor tarda al menos 5 segundos y, al llegar a su punto, este se marca con una palomita azul. Cada servidor tiene su globo junto a su punto (uno debajo de la barra, el siguiente arriba, y así) con su IP, su estado ([x] leído, [>] leyendo, [ ] pendiente), los perfiles leídos y el tiempo que tardó. “Actualizar registros” está debajo de la barra, a la derecha.',
                    'steps' => [
                        'Al abrir el apartado revisa la barra: indica la fecha de la última lectura guardada y se muestra al 100 %.',
                        'Si necesitas datos nuevos, presiona “Actualizar registros” y espera a que termine; la barra indica el avance y el tiempo estimado, y el globo de cada servidor, arriba o debajo de la barra, muestra su estado, los perfiles leídos y el tiempo.',
                        'Revisa el resumen de la tabla: perfiles revisados, correctos, con error y sin datos o pendientes.',
                        'Elige un servidor en la lista de la izquierda y después un perfil para ver su detalle e incidencias.',
                    ],
                ],
                'incidencias' => [
                    'question' => '¿Cómo agrego un perfil o registro una incidencia?',
                    'answer' => 'Elige el servidor en la lista de la izquierda y usa “Agregar perfil” al final de su lista de perfiles: captura el perfil, el nombre del log, la fecha y hora, las empresas con error (una por línea) y las incidencias generales.',
                    'steps' => [
                        'Elige el servidor en la lista de la izquierda y presiona “Agregar perfil”.',
                        'Escribe el Perfil y el Nombre del log.',
                        'Captura la fecha y hora y las empresas con error, una por línea.',
                        'Describe las incidencias generales y presiona Guardar.',
                        'Para corregir un perfil que ya existe, selecciónalo, presiona “Editar perfil”, ajusta los datos y presiona Guardar. Para cambiar el nombre, la IP o la carpeta del servidor usa “Editar servidor”.',
                    ],
                ],
                'guardar_pdf' => [
                    'question' => '¿Cómo guardo el informe en PDF?',
                    'answer' => 'Presiona “Guardar PDF” cuando la lectura haya terminado. Con la opción “Incluir este perfil en el PDF”, que aparece al editar un perfil, decides qué perfiles se incluyen en el documento.',
                    'steps' => [
                        'Para decidir qué perfiles aparecen, presiona “Editar perfil” y marca o desmarca “Incluir este perfil en el PDF”.',
                        'Presiona “Guardar PDF”.',
                        'Guarda el documento generado.',
                    ],
                ],
                'correo' => [
                    'question' => '¿Cómo envío el reporte por correo?',
                    'answer' => 'Presiona “Enviar reporte por correo”, selecciona uno o varios destinatarios (al menos uno) y confirma con “Enviar reporte”. Además, el CRM envía el informe solo, cada semana, a los destinatarios configurados: la barra “Envío automático” muestra el día, la hora y el estado del último envío.',
                    'steps' => [
                        'Presiona “Enviar reporte por correo”.',
                        'En “Enviar reporte a” selecciona uno o varios destinatarios.',
                        'Presiona “Enviar reporte” y espera la confirmación.',
                        'Para consultar el envío automático, presiona “Ver detalle” en la barra “Envío automático”.',
                    ],
                ],
            ],
        ],
        'chat_soporte' => [
            'label' => 'Chat de Soporte',
            'icon' => 'chat',
            'menu' => 'Soporte › Centro de Ayuda › Chat de Soporte',
            'summary' => 'El Chat de Soporte es un espacio compartido para conversar con todos los colaboradores y solicitar ayuda. Solo conserva los mensajes del día: al comenzar un nuevo día, los anteriores se eliminan automáticamente.',
            'items' => [
                'enviar' => [
                    'question' => '¿Cómo envío un mensaje?',
                    'answer' => 'Escribe en el campo “Escribe un mensaje…” (máximo 1,000 caracteres) y presiona Enviar o la tecla Enter.',
                    'steps' => [
                        'Entra a Soporte › Centro de Ayuda › Chat de Soporte.',
                        'Haz clic en “Escribe un mensaje…” y redacta tu mensaje.',
                        'Presiona Enter o el botón Enviar.',
                        'Si quieres, presiona Emojis para agregar uno al mensaje o Stickers para enviar uno al instante.',
                    ],
                ],
                'mencionar' => [
                    'question' => '¿Cómo menciono a una persona o a todos?',
                    'answer' => 'Escribe @ y selecciona a la persona en las sugerencias. Para avisar a todos elige @todos. La mención genera un aviso para la persona mencionada.',
                    'steps' => [
                        'En el mensaje escribe @ y las primeras letras del nombre.',
                        'Selecciona a la persona en la lista de sugerencias, o elige la opción para notificar a todos.',
                        'Termina de escribir tu mensaje y presiona Enter.',
                    ],
                ],
                'adjuntar' => [
                    'question' => '¿Cómo adjunto una imagen o un archivo?',
                    'answer' => 'Usa “Adjuntar imagen” (JPG, PNG o WebP, hasta 5 MB) o “Adjuntar archivo” (PDF, Word, Excel, CSV, TXT o ZIP, hasta 10 MB) y revisa la vista previa antes de enviar.',
                    'steps' => [
                        'Presiona “Adjuntar imagen” o “Adjuntar archivo”.',
                        'Selecciona el archivo; aparecerá como “listo para enviar”.',
                        'Si te equivocaste, usa “Quitar imagen” o “Quitar archivo”.',
                        'Presiona Enviar.',
                    ],
                ],
                'reacciones_eliminar' => [
                    'question' => '¿Cómo reacciono o elimino un mensaje?',
                    'answer' => 'Cada mensaje admite Me gusta, Me encanta y No me gusta. Puedes eliminar tus propios mensajes; los administradores de soporte pueden eliminar cualquiera.',
                    'steps' => [
                        'Ubica el mensaje en la conversación.',
                        'Elige una reacción: Me gusta, Me encanta o No me gusta.',
                        'Para borrar uno propio, presiona “Eliminar mensaje”; quedará como “Mensaje eliminado”.',
                    ],
                ],
            ],
        ],
        'preguntas' => [
            'label' => 'Preguntas',
            'icon' => 'preguntas',
            'menu' => 'Soporte › Centro de Ayuda › Preguntas',
            'summary' => 'Preguntas es el asistente de ayuda de DataMID. Cada apartado del menú lateral tiene su categoría con preguntas frecuentes, respuestas breves y una guía en PDF que puedes descargar.',
            'items' => [
                'usar' => [
                    'question' => '¿Cómo uso el asistente de preguntas?',
                    'answer' => 'Elige un apartado en la primera columna, selecciona una pregunta de la segunda y Sofia responderá en la conversación.',
                    'steps' => [
                        'Entra a Soporte › Centro de Ayuda › Preguntas.',
                        'Selecciona el apartado sobre el que tienes dudas.',
                        'Selecciona una pregunta de la lista.',
                        'Lee la respuesta que Sofia escribe en la conversación.',
                    ],
                ],
                'categorias' => [
                    'question' => '¿Qué categorías encuentro?',
                    'answer' => 'Una por cada apartado del menú lateral, en el mismo orden: desde Inicio hasta Preguntas. Cada una incluye 4 preguntas.',
                    'steps' => [
                        'Recorre la lista de categorías de la primera columna.',
                        'Cada categoría indica cuántas preguntas contiene.',
                        'Monitoreo reúne en una sola categoría Control de Actividades (Cronómetro, Gráficos de Control y Productividad) y Control de Horas (Reloj Checador y Supervisión de Horas).',
                        'Algunos apartados, como Administración u Hoja de Entrega, solo están disponibles según tu rol, aunque su guía aparece aquí.',
                    ],
                ],
                'guia_pdf' => [
                    'question' => '¿Cómo descargo la guía en PDF de un apartado?',
                    'answer' => 'Selecciona el apartado y presiona “Guía PDF” en el encabezado de la columna de preguntas. El documento reúne todas las preguntas del apartado con sus pasos.',
                    'steps' => [
                        'Selecciona el apartado en la primera columna.',
                        'Presiona “Guía PDF” en el encabezado de la columna de preguntas.',
                        'Guarda el documento descargado.',
                    ],
                ],
                'sin_respuesta' => [
                    'question' => '¿Qué hago si no encuentro mi respuesta?',
                    'answer' => 'Utiliza el Chat de Soporte para conversar con los demás colaboradores y pedir ayuda.',
                    'steps' => [
                        'Entra a Soporte › Centro de Ayuda › Chat de Soporte.',
                        'Escribe tu duda; usa @ para mencionar a quien pueda ayudarte.',
                        'Si es necesario, adjunta una imagen o un archivo.',
                    ],
                ],
            ],
        ],
    ],
];

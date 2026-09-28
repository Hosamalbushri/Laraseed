<?php

return [
    'acl' => [
        'students' => 'Estudiantes',
        'create' => 'Crear',
        'edit' => 'Editar',
        'view' => 'Ver',
        'delete' => 'Eliminar',
    ],

    'students' => [
        'title' => 'Estudiantes',
        'create-success' => 'Estudiante creado exitosamente.',
        'update-success' => 'Estudiante actualizado exitosamente.',
        'delete-success' => 'Estudiante eliminado exitosamente.',
        'delete-failed' => 'Error al eliminar el estudiante.',
        'all-delete-success' => 'Estudiantes seleccionados eliminados exitosamente.',
        'no-selection' => 'No se seleccionaron estudiantes.',

        'index' => [
            'title' => 'Estudiantes',
            'create-btn' => 'Agregar estudiante',

            'datagrid' => [
                'id' => 'ID',
                'name' => 'Nombre',
                'university-card-number' => 'Número de tarjeta universitaria',
                'registration-number' => 'Número de matrícula',
                'major' => 'Especialidad',
                'academic-level' => 'Nivel académico',
                'created-at' => 'Creado el',
                'view' => 'Ver',
                'edit' => 'Editar',
                'delete' => 'Eliminar',
            ],
        ],

        'create' => [
            'title' => 'Agregar estudiante',
            'save-btn' => 'Guardar estudiante',
        ],

        'edit' => [
            'title' => 'Editar estudiante',
            'save-btn' => 'Guardar cambios',
        ],

        'view' => [
            'title' => 'Estudiante: :name',
            'heading' => 'Detalles del estudiante',
            'edit-btn' => 'Editar estudiante',
            'general-info' => 'Información general',
        ],

        'form' => [
            'name' => 'Nombre',
            'university-card-number' => 'Número de tarjeta universitaria',
            'registration-number' => 'Número de matrícula',
            'major' => 'Especialidad',
            'academic-level' => 'Nivel académico',
            'password' => 'Contraseña',
            'password-confirmation' => 'Confirmar contraseña',
            'profile-image' => 'Imagen de perfil',
        ],
    ],

    'configuration' => [
        'student-login' => [
            'title' => 'Página de inicio de sesión de estudiantes',
            'info' => 'Configurar la marca y contenido del portal de inicio de sesión.',
            'logo-image' => 'Logo de inicio de sesión',
            'primary-color' => 'Color primario',
            'accent-color' => 'Color secundario',
            'surface-start' => 'Inicio del degradado de superficie',
            'surface-end' => 'Fin del degradado de superficie',
            'panel-start' => 'Inicio del degradado del panel lateral',
            'panel-end' => 'Fin del degradado del panel lateral',
            'field-title' => 'Título del encabezado',
            'description' => 'Descripción del encabezado',
            'eyebrow' => 'Texto preliminar',
            'panel-lead' => 'Párrafo principal del panel lateral',
            'card-number' => 'Etiqueta del número de tarjeta',
            'password' => 'Etiqueta de contraseña',
            'remember' => 'Etiqueta de recordarme',
            'submit' => 'Etiqueta del botón de envío',
            'back-portal' => 'Etiqueta del botón de regreso',
        ],

        'university-api' => [
            'title' => 'Integración con API universitaria',
            'info' => 'Configurar punto de enlace de verificación de estudiantes.',
            'endpoint-settings' => [
                'title' => 'Configuración del punto de enlace',
                'info' => 'Configurar la URL del punto de enlace de verificación.',
                'endpoint' => 'URL de la API de verificación',
                'endpoint-info' => 'Ingrese la URL completa para verificar estudiantes.',
            ],
        ],
    ],

    'components' => [
        'layouts' => [
            'header' => [
                'mega-search' => [
                    'explore-all-students' => 'Explorar todos los estudiantes',
                ],
            ],
        ],
    ],

    'login' => [
        'title' => 'Iniciar sesión de estudiante',
        'description' => 'Use su número de tarjeta universitaria y la contraseña proporcionada.',
        'eyebrow' => 'Acceso seguro',
        'panel_title' => 'Su portal universitario',
        'panel_lead' => 'Un solo lugar para eventos, actualizaciones y todo lo que necesita.',
        'feature_verify' => 'Identidad verificada por la universidad al primer ingreso',
        'feature_profile' => 'Perfil sincronizado con su registro oficial',
        'feature_portal' => 'Acceso sin problemas al portal estudiantil',
        'trust_note' => 'Las credenciales se verifican con su institución.',
        'back_portal' => 'Volver al inicio',
        'show_password' => 'Mostrar contraseña',
        'hide_password' => 'Ocultar contraseña',
        'card_number' => 'Número de tarjeta universitaria',
        'password' => 'Contraseña',
        'remember' => 'Recordarme',
        'submit' => 'Iniciar sesión',
        'failed' => 'Estas credenciales no coinciden con nuestros registros.',
        'welcome_back' => 'Bienvenido de nuevo.',
        'registered' => 'Su cuenta ha sido creada. Bienvenido.',
        'logged_out' => 'Ha cerrado sesión.',
    ],

    'university' => [
        'unavailable' => 'El servicio de verificación universitaria no está disponible.',
        'invalid_credentials' => 'La universidad no aceptó estas credenciales.',
        'invalid_response' => 'Respuesta inesperada de la universidad.',
    ],
];

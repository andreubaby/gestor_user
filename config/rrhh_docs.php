<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RRHH · Plantillas PDF
    |--------------------------------------------------------------------------
    | - Guardar los PDF base en: storage/app/rrhh_templates/
    | - "key" se usa en el <select> y en el backend para elegir plantilla.
    | - "template" es la ruta RELATIVA dentro de storage/app
    | - "filename" es el nombre sugerido del PDF generado (puedes añadir {nombre}, {dni}, {fecha})
    |
    | NOTA: si tus PDFs son “planos”, luego en el generador se estampará texto encima.
    | Si tienen campos (AcroForm), se rellenarán por nombre de campo.
    */

    'templates_dir' => 'rrhh_templates',

    'docs' => [

        // ========== EPIS ==========
        'epis_fumigador_entrega' => [
            'label'    => 'Entrega EPIS · Fumigador',
            'template' => 'rrhh_templates/1 Entrega EPIS Fumigador.pdf',
            'filename' => 'Entrega_EPIS_Fumigador_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'EPIS',
        ],

        'epis_general_entrega' => [
            'label'    => 'Entrega EPIS · General',
            'template' => 'rrhh_templates/2 Entrega EPIS General.pdf',
            'filename' => 'Entrega_EPIS_General_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'EPIS',
        ],

        'epis_bandejero_entrega' => [
            'label'    => 'Entrega EPIS · Bandejero',
            'template' => 'rrhh_templates/3 Entrega EPIS Bandejero.pdf',
            'filename' => 'Entrega_EPIS_Bandejero_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'EPIS',
        ],

        'epis_soldador_entrega' => [
            'label'    => 'Entrega EPIS · Soldador',
            'template' => 'rrhh_templates/4 Entrega EPIS Soldador.pdf',
            'filename' => 'Entrega_EPIS_Soldador_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'EPIS',
        ],

        // ========== AUTORIZACIÓN UTILIZACIÓN MAQUINARIA ==========
        'maq_facilitador_pedidos_aut' => [
            'label'    => 'Autorización Utilización Maquinaria · Facilitador de pedidos',
            'template' => 'rrhh_templates/2 AUTORIZACION UTILIZACION MAQUINARIA  Facilitador de Pedidos.pdf',
            'filename' => 'Autorizacion_Maquinaria_FacilitadorPedidos_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'Maquinaria',
        ],

        'maq_produccion_aut' => [
            'label'    => 'Autorización Utilización Maquinaria · Producción',
            'template' => 'rrhh_templates/3 AUTORIZACION UTILIZACION MAQUINARIA Produccion.pdf',
            'filename' => 'Autorizacion_Maquinaria_Produccion_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'Maquinaria',
        ],

        'maq_semillero_aut' => [
            'label'    => 'Autorización Utilización Maquinaria · Semillero',
            'template' => 'rrhh_templates/4 AUTORIZACION UTILIZACION MAQUINARIA  Semillero.pdf',
            'filename' => 'Autorizacion_Maquinaria_Semillero_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'Maquinaria',
        ],

        'maq_conductor_aut' => [
            'label'    => 'Autorización Utilización Maquinaria · Conductor',
            'template' => 'rrhh_templates/5 AUTORIZACION UTILIZACION MAQUINARIA  Conductor.pdf',
            'filename' => 'Autorizacion_Maquinaria_Conductor_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'Maquinaria',
        ],

        'maq_siembra_aut' => [
            'label'    => 'Autorización Utilización Maquinaria · Siembra',
            'template' => 'rrhh_templates/6 AUTORIZACION UTILIZACION MAQUINARIA  Siembra.pdf',
            'filename' => 'Autorizacion_Maquinaria_Siembra_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'Maquinaria',
        ],

        'maq_bandejero_aut' => [
            'label'    => 'Autorización Utilización Maquinaria · Bandejero',
            'template' => 'rrhh_templates/7 AUTORIZACION UTILIZACION MAQUINARIA Bandejero.pdf',
            'filename' => 'Autorizacion_Maquinaria_Bandejero_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'Maquinaria',
        ],

        'maq_empaquetadora_injertadora_aut' => [
            'label'    => 'Autorización Utilización Maquinaria · Empaquetadora / Injertadora',
            'template' => 'rrhh_templates/8 AUTORIZACION UTILIZACION MAQUINARIA Empaquetadora_Injertadora.pdf',
            'filename' => 'Autorizacion_Maquinaria_EmpaquetadoraInjertadora_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'Maquinaria',
        ],

        'maq_jefe_azul_aut' => [
            'label'    => 'Autorización Utilización Maquinaria · Jefe Azul',
            'template' => 'rrhh_templates/9 AUTORIZACION UTILIZACION MAQUINARIA Jefe Azul.pdf',
            'filename' => 'Autorizacion_Maquinaria_JefeAzul_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'Maquinaria',
        ],

        // ========== VEHÍCULO ==========
        'vehiculo_uso_conservacion_aut' => [
            'label'    => 'Autorizacion de uso de vehiculo y conservacion',
            'template' => 'rrhh_templates/Autorizacion de uso de vehiculo y conservacion en estado optimo.pdf',
            'filename' => 'Autorizacion_UsoVehiculo_Conservacion_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'Vehículo',
        ],

        // ========== FIRMAS / IT ==========
        'firma_epis_caminero' => [
            'label'    => 'Firma EPIS · Camionero',
            'template' => 'rrhh_templates/FIRMA Epi Caminero.pdf',
            'filename' => 'Firma_EPIS_Camionero_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'Firmas',
        ],

        'it2_manejo_segadora' => [
            'label'    => 'IT2 · Manejo y uso de la segadora',
            'template' => 'rrhh_templates/IT 2 Manejo y uso de la segadora.pdf',
            'filename' => 'IT2_Manejo_Segadora_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'IT',
        ],

        // ========== PLANTILLA (BASE) ==========
        'plantilla_aut_maquinaria' => [
            'label'    => 'Plantilla · Autorización Utilización Maquinaria (base)',
            'template' => 'rrhh_templates/Plantilla AUTORIZACION UTILIZACION MAQUINARIA _.pdf',
            'filename' => 'Plantilla_Autorizacion_Maquinaria_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'Plantillas',
        ],

        'entrega_info' => [
            'label'    => 'Entrega de informacion Firma',
            'template' => 'rrhh_templates/Entrega de informacion Firma.pdf',
            'filename' => 'Plantilla_Entrega_informacion_{nombre}_{dni}_{fecha}.pdf',
            'category' => 'Firmas',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Categorías (opcional)
    |--------------------------------------------------------------------------
    | Para ordenar en la UI si quieres agrupar por secciones.
    */
    'categories' => [
        'EPIS'       => 'EPIS',
        'Maquinaria' => 'Autorizaciones · Maquinaria',
        'Vehículo'   => 'Vehículo',
        'Firmas'     => 'Firmas',
        'IT'         => 'IT / Formación',
        'Plantillas' => 'Plantillas base',
    ],

];

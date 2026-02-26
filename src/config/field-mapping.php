<?php

declare(strict_types=1);

return [
    'fields' => [
        'sku' => [
            'sku', 'varenr', 'varenummer', 'vare_nr', 'produktnr', 'produktnummer', 'item_no', 'itemnumber', 'product_no', 'productnumber', 'ean',
        ],
        'product_name' => [
            'product_name', 'produktnavn', 'varenavn', 'name', 'navn', 'title', 'produkt',
        ],
        'description' => [
            'description', 'beskrivelse', 'produktbeskrivelse', 'lang_beskrivelse', 'kort_beskrivelse', 'tekst',
        ],
        'category' => [
            'category', 'kategori', 'produktgruppe', 'gruppe', 'sortiment', 'serie',
        ],
        'price' => [
            'price', 'pris', 'salgspris', 'udsalgspris', 'nettopris', 'bruttopris', 'price_dkk',
        ],
        'currency' => [
            'currency', 'valuta',
        ],
        'weight' => [
            'weight', 'vaegt', 'nettovaegt', 'bruttovaegt',
        ],
        'dimensions' => [
            'dimensions', 'dimensioner', 'maal', 'stoerrelse', 'size',
        ],
        'shipping_info' => [
            'shipping_info', 'shipping', 'fragt', 'fragtinfo', 'levering', 'leveringstid',
        ],
        'active' => [
            'active', 'aktiv', 'enabled', 'is_active',
        ],
        'barcode' => [
            'barcode', 'stregkode', 'ean',
        ],
        'hostedshop_id' => [
            'hostedshop_id', 'hostedshopid', 'shop_id', 'webshop_id',
        ],
        'supplier' => [
            'supplier', 'leverandoer', 'leverandor',
        ],
        'brand' => [
            'brand', 'maerke', 'mærke',
        ],
        'net_weight_grams' => [
            'net_weight_grams', 'nettovaegt', 'nettovægt', 'netto_weight', 'netto_vaegt', 'net_weight',
        ],
        'gross_weight_grams' => [
            'gross_weight_grams', 'bruttovaegt', 'bruttovægt', 'gross_weight', 'brutto_weight',
        ],
    ],
    'sheets' => [
        'aquadana' => [
            'sku' => ['aquadana_sku', 'aqd_sku', 'varenummer', 'varenr', 'sku'],
            'product_name' => ['aquadana_navn', 'produktnavn', 'varenavn', 'name'],
            'description' => ['aquadana_beskrivelse', 'beskrivelse', 'produktbeskrivelse'],
            'category' => ['aquadana_kategori', 'kategori', 'produktgruppe', 'serie'],
            'price' => ['aquadana_pris', 'pris', 'salgspris', 'udsalgspris'],
            'currency' => ['valuta', 'currency'],
            'weight' => ['aquadana_vaegt', 'vaegt', 'nettovaegt'],
            'dimensions' => ['aquadana_dimensioner', 'dimensioner', 'maal', 'stoerrelse'],
            'shipping_info' => ['aquadana_fragt', 'fragtinfo', 'shipping', 'leveringstid'],
        ],
        'sigdetsoedt' => [
            'sku' => ['sigdetsoedt_sku', 'sds_sku', 'varenummer', 'varenr', 'sku'],
            'product_name' => ['sigdetsoedt_navn', 'produktnavn', 'varenavn', 'name'],
            'description' => ['sigdetsoedt_beskrivelse', 'beskrivelse', 'produktbeskrivelse'],
            'category' => ['sigdetsoedt_kategori', 'kategori', 'produktgruppe', 'serie'],
            'price' => ['sigdetsoedt_pris', 'pris', 'salgspris', 'udsalgspris'],
            'currency' => ['valuta', 'currency'],
            'weight' => ['sigdetsoedt_vaegt', 'vaegt', 'nettovaegt'],
            'dimensions' => ['sigdetsoedt_dimensioner', 'dimensioner', 'maal', 'stoerrelse'],
            'shipping_info' => ['sigdetsoedt_fragt', 'fragtinfo', 'shipping', 'leveringstid'],
            'active' => ['sigdetsoedt_aktiv', 'aktiv', 'active'],
            'barcode' => ['sigdetsoedt_barcode', 'stregkode', 'barcode', 'ean'],
            'hostedshop_id' => ['sigdetsoedt_hostedshop_id', 'hostedshop_id', 'shop_id'],
            'supplier' => ['sigdetsoedt_supplier', 'leverandor', 'supplier'],
            'brand' => ['sigdetsoedt_brand', 'maerke', 'brand'],
            'net_weight_grams' => ['sigdetsoedt_nettovaegt', 'nettovaegt', 'net_weight_grams'],
            'gross_weight_grams' => ['sigdetsoedt_bruttovaegt', 'bruttovaegt', 'gross_weight_grams'],
        ],
    ],
];

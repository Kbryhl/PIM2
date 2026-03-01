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
        'stk_pr_kolli' => [
            'stk_pr_kolli', 'stk pr kolli', 'stk_pr_kasse', 'stk pr kasse',
        ],
        'stk_1_4_pl' => [
            'stk_1_4_pl', 'stk 1/4 pl', 'stk_1_4_palle',
        ],
        'stk_1_2_pl' => [
            'stk_1_2_pl', 'stk 1/2 pl', 'stk_1_2_palle',
        ],
        'stk_1_1_pl' => [
            'stk_1_1_pl', 'stk 1/1 pl', 'stk_1_1_palle',
        ],
        'inkl_fragt' => [
            'inkl_fragt', 'inkl fragt', 'inklusive_fragt',
        ],
        'bestil_interval' => [
            'bestil_interval', 'bestil interval', 'order_interval',
        ],
        'bestil_interval_unit' => [
            'bestil_interval_unit', 'bestil interval enhed', 'order_interval_unit',
        ],
        'net_weight_grams' => [
            'net_weight_grams', 'nettovaegt', 'nettovægt', 'netto_weight', 'netto_vaegt', 'net_weight',
        ],
        'gross_weight_grams' => [
            'gross_weight_grams', 'bruttovaegt', 'bruttovægt', 'gross_weight', 'brutto_weight',
        ],
        'holdbarhed_months' => [
            'holdbarhed_months', 'holdbarhed', 'shelf_life_months',
        ],
        'glutenfri' => [
            'glutenfri', 'gluten_free',
        ],
        'veggie' => [
            'veggie', 'vegetarian',
        ],
        'vegan' => [
            'vegan',
        ],
        'komposterbar' => [
            'komposterbar', 'compostable',
        ],
        'smagsvarianter' => [
            'smagsvarianter', 'smagsvariant', 'flavor_variants', 'flavours', 'flavors',
        ],
        'form_varianter' => [
            'form_varianter', 'formvarianter', 'shape_variants',
        ],
        'folie_varianter' => [
            'folie_varianter', 'folievarianter', 'foil_variants',
        ],
        'finish' => [
            'finish', 'finish_variants',
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
            'stk_pr_kolli' => ['sigdetsoedt_stk_pr_kolli', 'stk_pr_kolli', 'stk  pr kolli'],
            'stk_1_4_pl' => ['sigdetsoedt_stk_1_4_pl', 'stk_1_4_pl', 'stk 1/4 pl'],
            'stk_1_2_pl' => ['sigdetsoedt_stk_1_2_pl', 'stk_1_2_pl', 'stk 1/2 pl'],
            'stk_1_1_pl' => ['sigdetsoedt_stk_1_1_pl', 'stk_1_1_pl', 'stk 1/1 pl'],
            'inkl_fragt' => ['sigdetsoedt_inkl_fragt', 'inkl_fragt', 'inkl fragt'],
            'bestil_interval' => ['sigdetsoedt_bestil_interval', 'bestil_interval', 'bestil interval'],
            'bestil_interval_unit' => ['sigdetsoedt_bestil_interval_unit', 'bestil_interval_unit', 'bestil interval enhed'],
            'net_weight_grams' => ['sigdetsoedt_nettovaegt', 'nettovaegt', 'net_weight_grams'],
            'gross_weight_grams' => ['sigdetsoedt_bruttovaegt', 'bruttovaegt', 'gross_weight_grams'],
            'holdbarhed_months' => ['sigdetsoedt_holdbarhed', 'holdbarhed_months', 'holdbarhed'],
            'glutenfri' => ['sigdetsoedt_glutenfri', 'glutenfri'],
            'veggie' => ['sigdetsoedt_veggie', 'veggie'],
            'vegan' => ['sigdetsoedt_vegan', 'vegan'],
            'komposterbar' => ['sigdetsoedt_komposterbar', 'komposterbar'],
            'smagsvarianter' => ['sigdetsoedt_smagsvarianter', 'smagsvarianter', 'flavor_variants'],
            'form_varianter' => ['sigdetsoedt_form_varianter', 'form_varianter', 'formvarianter'],
            'folie_varianter' => ['sigdetsoedt_folie_varianter', 'folie_varianter', 'folievarianter'],
            'finish' => ['sigdetsoedt_finish', 'finish'],
        ],
    ],
];

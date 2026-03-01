<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PIM2 Add Product</title>
    <link rel="stylesheet" href="assets/css/styles.css" />
</head>
<body>
    <header class="topbar">
        <div class="container topbar-content">
            <h1>PIM2</h1>
            <nav>
                <a href="index.php">Products</a>
                <a href="import.php">Import</a>
                <a href="add-product.php" class="active">Add Product</a>
                <a href="options-admin.php">Options Admin</a>
            </nav>
        </div>
    </header>

    <main class="container layout narrow">
        <section class="card">
            <h2>Add Product</h2>
            <p class="hint">SIGDETSØDT manual product fields in running order.</p>

            <form id="addProductForm" class="import-form">
                <label for="sheetName">Sheet</label>
                <select id="sheetName" name="sheet_name">
                    <option value="SIGDETSØDT" selected>SIGDETSØDT</option>
                    <option value="AQUADANA">AQUADANA</option>
                    <option value="OTHER">OTHER</option>
                </select>

                <label for="sku">SKU</label>
                <input id="sku" name="sku" type="text" placeholder="Product SKU" />

                <label for="productName">Product Name *</label>
                <input id="productName" name="product_name" type="text" placeholder="Product name" required />

                <label for="changeLog">Change log (read only)</label>
                <textarea id="changeLog" name="change_log" rows="3" readonly placeholder="Will be generated on save."></textarea>

                <label class="checkbox-field" for="active">
                    <input id="active" name="active" type="checkbox" />
                    <span>Active</span>
                </label>

                <label for="barcode">Barcode</label>
                <input id="barcode" name="barcode" type="text" placeholder="Barcode" />

                <label for="hostedshopId">HostedShop ID</label>
                <input id="hostedshopId" name="hostedshop_id" type="text" placeholder="HostedShop ID" />

                <label for="supplier">Supplier</label>
                <input id="supplier" name="supplier" type="text" placeholder="Supplier" />

                <label for="brand">Brand</label>
                <input id="brand" name="brand" type="text" placeholder="Brand" />

                <label for="stkPrKolli">Stk pr Kolli</label>
                <input id="stkPrKolli" name="stk_pr_kolli" type="number" min="0" step="1" placeholder="0" />

                <label for="stkQuarterPl">Stk 1/4 pl</label>
                <input id="stkQuarterPl" name="stk_1_4_pl" type="number" min="0" step="1" placeholder="0" />

                <label for="stkHalfPl">Stk 1/2 pl</label>
                <input id="stkHalfPl" name="stk_1_2_pl" type="number" min="0" step="1" placeholder="0" />

                <label for="stkFullPl">Stk 1/1 pl</label>
                <input id="stkFullPl" name="stk_1_1_pl" type="number" min="0" step="1" placeholder="0" />

                <label class="checkbox-field" for="inklFragt">
                    <input id="inklFragt" name="inkl_fragt" type="checkbox" />
                    <span>Inkl. Fragt</span>
                </label>

                <label for="bestilInterval">Bestil Interval</label>
                <input id="bestilInterval" name="bestil_interval" type="number" min="0" step="1" placeholder="0" />

                <label for="bestilIntervalUnitSelect">Bestil Interval enhed</label>
                <select id="bestilIntervalUnitSelect" name="bestil_interval_unit">
                    <option value="">Select unit</option>
                </select>
                <div class="inline-row">
                    <input id="newBestilIntervalUnitInput" type="text" placeholder="Add new interval unit" />
                    <button id="addBestilIntervalUnitBtn" type="button">Add Unit</button>
                </div>

                <label for="minOrdre">Min. ordre</label>
                <input id="minOrdre" name="min_ordre" type="number" min="0" step="1" placeholder="0" />

                <label for="leveringstid">Leveringstid (hverdage)</label>
                <input id="leveringstid" name="leveringstid" type="number" min="0" step="1" placeholder="0" />

                <label for="produktionstid">Produktionstid (hverdage)</label>
                <input id="produktionstid" name="produktionstid" type="number" min="0" step="1" placeholder="0" />

                <label for="leveringText">Levering (read only)</label>
                <input id="leveringText" name="levering_text" type="text" readonly placeholder="X hverdage efter godkendt korrektur" />

                <label for="netWeightGrams">Nettovægt (grams)</label>
                <input id="netWeightGrams" name="net_weight_grams" type="number" min="0" step="1" placeholder="0" />

                <label for="grossWeightGrams">Bruttovægt (grams)</label>
                <input id="grossWeightGrams" name="gross_weight_grams" type="number" min="0" step="1" placeholder="0" />

                <label for="taraWeightGrams">Tara Weight (read only, grams)</label>
                <input id="taraWeightGrams" name="tara_weight_grams" type="number" readonly placeholder="Auto calculated" />

                <label for="holdbarhedMonths">Holdbarhed (måneder)</label>
                <input id="holdbarhedMonths" name="holdbarhed_months" type="number" min="0" step="1" placeholder="0" />

                <label for="holdbarhedText">Holdbarhed tekst (read only)</label>
                <input id="holdbarhedText" name="holdbarhed_text" type="text" readonly placeholder="ca. X måneder, ved korrekt opbevaring" />

                <label class="checkbox-field" for="glutenfri">
                    <input id="glutenfri" name="glutenfri" type="checkbox" />
                    <span>Glutenfri</span>
                </label>

                <label class="checkbox-field" for="veggie">
                    <input id="veggie" name="veggie" type="checkbox" />
                    <span>Veggie</span>
                </label>

                <label class="checkbox-field" for="vegan">
                    <input id="vegan" name="vegan" type="checkbox" />
                    <span>Vegan</span>
                </label>

                <label class="checkbox-field" for="komposterbar">
                    <input id="komposterbar" name="komposterbar" type="checkbox" />
                    <span>Komposterbar</span>
                </label>

                <label for="smagsvarianterSelect">Smagsvarianter (multi-select)</label>
                <select id="smagsvarianterSelect" name="smagsvarianter" multiple size="8"></select>
                <div class="inline-row">
                    <input id="newSmagsvariantInput" type="text" placeholder="Add new smagsvariant" />
                    <button id="addSmagsvariantBtn" type="button">Add Smagsvariant</button>
                </div>

                <label for="formVarianterSelect">Form varianter (multi-select)</label>
                <select id="formVarianterSelect" name="form_varianter" multiple size="8"></select>
                <div class="inline-row">
                    <input id="newFormVariantInput" type="text" placeholder="Add new form variant" />
                    <button id="addFormVariantBtn" type="button">Add Form Variant</button>
                </div>

                <label for="folieVarianterSelect">Folie varianter (multi-select)</label>
                <select id="folieVarianterSelect" name="folie_varianter" multiple size="8"></select>
                <div class="inline-row">
                    <input id="newFolieVariantInput" type="text" placeholder="Add new folie variant" />
                    <button id="addFolieVariantBtn" type="button">Add Folie Variant</button>
                </div>

                <label for="finishSelect">Finish (multi-select)</label>
                <select id="finishSelect" name="finish" multiple size="8"></select>
                <div class="inline-row">
                    <input id="newFinishInput" type="text" placeholder="Add new finish" />
                    <button id="addFinishBtn" type="button">Add Finish</button>
                </div>

                <label for="productPhotoUrl">Product Photo URL (read only)</label>
                <input id="productPhotoUrl" name="product_photo_url" type="url" readonly placeholder="https://filbank.dk/database/sigdetsoedt/produktfoto/SKU.png" />

                <label for="databladUrl">Datablad URL (read only)</label>
                <input id="databladUrl" name="datablad_url" type="url" readonly placeholder="https://filbank.dk/database/sigdetsoedt/datablade/SKU.pdf" />

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Description"></textarea>

                <label for="categorySelect">Category (multi-select)</label>
                <select id="categorySelect" name="category" multiple size="6"></select>
                <div class="inline-row">
                    <input id="newCategoryInput" type="text" placeholder="Add new category" />
                    <button id="addCategoryBtn" type="button">Add Category</button>
                </div>

                <label for="price">Price</label>
                <input id="price" name="price" type="text" placeholder="0.00" />

                <label for="produktMaal">Produkt mål</label>
                <input id="produktMaal" name="produkt_maal" type="text" placeholder="Fx 10 x 20 x 30 cm" />

                <label for="opstartPr">Opstart pr</label>
                <select id="opstartPr" name="opstart_pr">
                    <option value="">Select</option>
                    <option value="Kg">Kg</option>
                    <option value="Stk">Stk</option>
                </select>

                <section class="field-group" aria-labelledby="genbestil-group-title">
                    <h3 id="genbestil-group-title" class="field-group-title">Genbestilling</h3>
                    <div class="group-grid-3">
                        <div class="group-field">
                            <label for="opstartGenbestil">Opstart Genbestil</label>
                            <input id="opstartGenbestil" name="opstart_genbestil" type="number" min="0" step="0.01" placeholder="0" />
                        </div>
                        <div class="group-field">
                            <label for="opstartGenbestilAvance">Opstart Genbestil avance (%)</label>
                            <input id="opstartGenbestilAvance" name="opstart_genbestil_avance" type="number" min="0" step="0.01" placeholder="0" />
                        </div>
                        <div class="group-field">
                            <label for="opstartGenbestilVejl">Opstart Genbestil Vejl (read only)</label>
                            <input id="opstartGenbestilVejl" name="opstart_genbestil_vejl" type="number" readonly placeholder="Auto calculated" />
                        </div>
                    </div>
                </section>

                <label for="opstart">Opstart</label>
                <input id="opstart" name="opstart" type="number" min="0" step="0.01" placeholder="0" />

                <label for="opstartAvance">Opstart avance (%)</label>
                <input id="opstartAvance" name="opstart_avance" type="number" min="0" step="0.01" placeholder="0" />

                <label for="opstartVejl">Opstart Vejl (read only)</label>
                <input id="opstartVejl" name="opstart_vejl" type="number" readonly placeholder="Auto calculated" />

                <button type="submit">Save Product</button>
            </form>

            <pre id="addProductResult" class="result-box">No product saved yet.</pre>
        </section>
    </main>

    <script src="assets/js/add-product.js"></script>
</body>
</html>

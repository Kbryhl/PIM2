<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PIM2 Edit Product</title>
    <link rel="stylesheet" href="assets/css/styles.css" />
</head>
<body>
    <header class="topbar">
        <div class="container topbar-content">
            <h1>PIM2</h1>
            <nav>
                <a href="index.php">Products</a>
                <a href="import.php">Import</a>
                <a href="add-product.php">Add Product</a>
                <a href="edit-product.php" class="active">Edit Product</a>
                <a href="options-admin.php">Options Admin</a>
            </nav>
        </div>
    </header>

    <main class="container layout narrow">
        <section class="card">
            <h2>Edit Product</h2>
            <p class="hint">Edit all non-read-only fields and save.</p>

            <form id="editProductForm" class="import-form">
                <input type="hidden" id="productId" name="id" />

                <label for="sheetName">Sheet</label>
                <select id="sheetName" name="sheet_name">
                    <option value="SIGDETSØDT">SIGDETSØDT</option>
                    <option value="AQUADANA">AQUADANA</option>
                    <option value="OTHER">OTHER</option>
                </select>

                <label for="sku">SKU</label>
                <input id="sku" name="sku" type="text" placeholder="Product SKU" />

                <label for="productName">Product Name *</label>
                <input id="productName" name="product_name" type="text" placeholder="Product name" required />

                <label for="changeLog">Change log (read only)</label>
                <textarea id="changeLog" name="change_log" rows="3" readonly></textarea>

                <label class="checkbox-field" for="active">
                    <input id="active" name="active" type="checkbox" />
                    <span>Active</span>
                </label>

                <label for="barcode">Barcode</label>
                <input id="barcode" name="barcode" type="text" />

                <label for="hostedshopId">HostedShop ID</label>
                <input id="hostedshopId" name="hostedshop_id" type="text" />

                <label for="supplier">Supplier</label>
                <input id="supplier" name="supplier" type="text" />

                <label for="brand">Brand</label>
                <input id="brand" name="brand" type="text" />

                <label for="netWeightGrams">Nettovægt (grams)</label>
                <input id="netWeightGrams" name="net_weight_grams" type="number" min="0" step="1" />

                <label for="grossWeightGrams">Bruttovægt (grams)</label>
                <input id="grossWeightGrams" name="gross_weight_grams" type="number" min="0" step="1" />

                <label for="taraWeightGrams">Tara Weight (read only, grams)</label>
                <input id="taraWeightGrams" name="tara_weight_grams" type="number" readonly />

                <label for="holdbarhedMonths">Holdbarhed (måneder)</label>
                <input id="holdbarhedMonths" name="holdbarhed_months" type="number" min="0" step="1" />

                <label for="holdbarhedText">Holdbarhed tekst (read only)</label>
                <input id="holdbarhedText" name="holdbarhed_text" type="text" readonly />

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
                <input id="productPhotoUrl" name="product_photo_url" type="url" readonly />

                <label for="databladUrl">Datablad URL (read only)</label>
                <input id="databladUrl" name="datablad_url" type="url" readonly />

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"></textarea>

                <label for="categorySelect">Category (multi-select)</label>
                <select id="categorySelect" name="category" multiple size="6"></select>
                <div class="inline-row">
                    <input id="newCategoryInput" type="text" placeholder="Add new category" />
                    <button id="addCategoryBtn" type="button">Add Category</button>
                </div>

                <label for="price">Price</label>
                <input id="price" name="price" type="text" />

                <label for="currency">Currency</label>
                <input id="currency" name="currency" type="text" />

                <label for="weight">Weight</label>
                <input id="weight" name="weight" type="text" />

                <label for="dimensions">Dimensions</label>
                <input id="dimensions" name="dimensions" type="text" />

                <label for="shippingInfo">Shipping Info</label>
                <textarea id="shippingInfo" name="shipping_info" rows="3"></textarea>

                <div id="dynamicFields"></div>

                <button type="submit">Save Changes</button>
            </form>

            <pre id="editProductResult" class="result-box">Loading product...</pre>
        </section>
    </main>

    <script src="assets/js/edit-product.js"></script>
</body>
</html>

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

                <label for="netWeightGrams">Nettovægt (grams)</label>
                <input id="netWeightGrams" name="net_weight_grams" type="number" min="0" step="1" placeholder="0" />

                <label for="grossWeightGrams">Bruttovægt (grams)</label>
                <input id="grossWeightGrams" name="gross_weight_grams" type="number" min="0" step="1" placeholder="0" />

                <label for="taraWeightGrams">Tara Weight (read only, grams)</label>
                <input id="taraWeightGrams" name="tara_weight_grams" type="number" readonly placeholder="Auto calculated" />

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Description"></textarea>

                <label for="category">Category</label>
                <input id="category" name="category" type="text" placeholder="Category" />

                <label for="price">Price</label>
                <input id="price" name="price" type="text" placeholder="0.00" />

                <label for="currency">Currency</label>
                <input id="currency" name="currency" type="text" placeholder="DKK" />

                <label for="weight">Weight</label>
                <input id="weight" name="weight" type="text" placeholder="Weight" />

                <label for="dimensions">Dimensions</label>
                <input id="dimensions" name="dimensions" type="text" placeholder="L x W x H" />

                <label for="shippingInfo">Shipping Info</label>
                <textarea id="shippingInfo" name="shipping_info" rows="3" placeholder="Shipping details"></textarea>

                <button type="submit">Save Product</button>
            </form>

            <pre id="addProductResult" class="result-box">No product saved yet.</pre>
        </section>
    </main>

    <script src="assets/js/add-product.js"></script>
</body>
</html>

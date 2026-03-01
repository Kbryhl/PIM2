<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PIM2 Options Admin</title>
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
                <a href="options-admin.php" class="active">Options Admin</a>
            </nav>
        </div>
    </header>

    <main class="container layout">
        <section class="card">
            <h2>Variant Options Admin</h2>
            <p class="hint">Manage reusable options for Smagsvarianter, Form varianter, Folie varianter, Finish and Bestil Interval enhed.</p>
            <div id="optionsAdminRoot"></div>
            <pre id="optionsAdminResult" class="result-box">Loading options...</pre>
        </section>
    </main>

    <script src="assets/js/options-admin.js"></script>
</body>
</html>

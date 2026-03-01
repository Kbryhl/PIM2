<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PIM2 Import</title>
    <link rel="stylesheet" href="assets/css/styles.css" />
</head>
<body>
    <header class="topbar">
        <div class="container topbar-content">
            <h1>PIM2</h1>
            <nav>
                <a href="index.php">Products</a>
            </nav>
        </div>
    </header>

    <main class="container layout narrow">
        <section class="card">
            <h2>Import Product Data</h2>
            <p class="hint">Use CSV exported from your Excel tabs (recommended), or XLSX if PhpSpreadsheet is installed.</p>

            <form id="importForm" class="import-form">
                <label for="sheetName">Target Sheet Name</label>
                <select id="sheetName" name="sheet_name">
                    <option value="AQUADANA">AQUADANA</option>
                    <option value="SIGDETSØDT">SIGDETSØDT</option>
                    <option value="OTHER">OTHER</option>
                </select>

                <label for="fileInput">File</label>
                <input id="fileInput" type="file" name="file" accept=".csv,.xlsx" required />

                <button type="submit">Import File</button>
            </form>

            <pre id="importResult" class="result-box">No import yet.</pre>
        </section>
    </main>

    <script src="assets/js/import.js"></script>
</body>
</html>

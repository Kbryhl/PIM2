<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PIM2 Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css" />
</head>
<body>
    <header class="topbar">
        <div class="container topbar-content">
            <h1>PIM2</h1>
            <nav>
                <a href="index.php" class="active">Products</a>
                <a href="import.php">Import</a>
                <a href="options-admin.php">Options Admin</a>
            </nav>
        </div>
    </header>

    <main class="container layout">
        <section class="controls card">
            <div class="tabs">
                <button class="tab active" data-sheet="">All</button>
                <button class="tab" data-sheet="AQUADANA">AQUADANA</button>
                <button class="tab" data-sheet="SIGDETSØDT">SIGDETSØDT</button>
            </div>
            <div class="search-row">
                <input id="searchInput" type="search" placeholder="Search by name, SKU or category" />
                <button id="searchBtn">Search</button>
            </div>
        </section>

        <section class="card">
            <div class="table-head">
                <h2>Products</h2>
                <div class="table-actions">
                    <a href="add-product.php" class="link-btn action-link">ADD PRODUCT</a>
                    <button id="deleteSelectedBtn" type="button">Delete Selected</button>
                    <span id="resultInfo">Loading...</span>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllRows" aria-label="Select all rows" /></th>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Active</th>
                            <th>Photo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody"></tbody>
                </table>
            </div>

            <div class="pager">
                <button id="prevBtn">Previous</button>
                <span id="pageInfo"></span>
                <button id="nextBtn">Next</button>
            </div>
        </section>
    </main>

    <script src="assets/js/app.js"></script>
</body>
</html>

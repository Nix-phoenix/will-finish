document.addEventListener('DOMContentLoaded', function() {
    var openBtn = document.getElementById('openAddProductModal');
    var closeBtn = document.getElementById('closeAddProductModal');
    var modal = document.getElementById('addProductModal');

    if (openBtn && closeBtn && modal) {
        openBtn.onclick = function() {
            modal.style.display = 'block';
        };
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        };
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        };
    }
});

function addProduct() {
    const productList = document.getElementById('product-list');
    const productDiv = document.createElement('div');
    productDiv.innerHTML = `
        <input type="text" placeholder="Product ID" required>
        <input type="number" placeholder="Quantity" required>
    `;
    productList.appendChild(productDiv);
}

function confirmDelete() {
    return confirm('Are you sure you want to delete this item?');
}
// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Quantity input handlers for cart
    const quantityInputs = document.querySelectorAll('.quantity-input');
    if (quantityInputs) {
        quantityInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                const form = this.closest('form');
                if (form) {
                    form.submit();
                }
            });
        });
    }

    // Product image preview for admin
    const productImageInput = document.getElementById('product-image');
    const imagePreview = document.getElementById('image-preview');
    
    if (productImageInput && imagePreview) {
        productImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.delete-btn');
    if (deleteButtons) {
        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            });
        });
    }

    // Add to cart animation
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    if (addToCartButtons) {
        addToCartButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                button.innerHTML = '<i class="fas fa-check"></i> Added!';
                button.classList.remove('btn-primary');
                button.classList.add('btn-success');
                
                setTimeout(function() {
                    button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                    button.classList.remove('btn-success');
                    button.classList.add('btn-primary');
                }, 2000);
            });
        });
    }

    // Password visibility toggle
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.querySelector('.password-input');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
});
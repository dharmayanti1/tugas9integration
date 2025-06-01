<?php
// Konfigurasi database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecommerce_db";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Buat tabel jika belum ada
$createTables = "
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
";

$pdo->exec($createTables);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_product':
            $name = $_POST['name'];
            $description = $_POST['description'];
            $price = $_POST['price'];
            $image_path = '';
            
            // Handle file upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $upload_dir = 'uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_path = $upload_path;
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $image_path]);
            
            echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan!']);
            exit;
            
        case 'update_product':
            $id = $_POST['id'];
            $name = $_POST['name'];
            $description = $_POST['description'];
            $price = $_POST['price'];
            
            // Get existing image path
            $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $existing_product = $stmt->fetch();
            $image_path = $existing_product['image_path'];
            
            // Handle new image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $upload_dir = 'uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Delete old image
                    if ($image_path && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    $image_path = $upload_path;
                }
            }
            
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_path = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $image_path, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Produk berhasil diperbarui!']);
            exit;
            
        case 'delete_product':
            $id = $_POST['id'];
            
            // Get image path before deletion
            $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            // Delete product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            // Delete image file
            if ($product && $product['image_path'] && file_exists($product['image_path'])) {
                unlink($product['image_path']);
            }
            
            echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus!']);
            exit;
            
        case 'add_to_cart':
            $product_id = $_POST['product_id'];
            
            // Check if product already in cart
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update quantity
                $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE product_id = ?");
                $stmt->execute([$product_id]);
            } else {
                // Add new item
                $stmt = $pdo->prepare("INSERT INTO cart (product_id, quantity) VALUES (?, 1)");
                $stmt->execute([$product_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Produk ditambahkan ke keranjang!']);
            exit;
            
        case 'update_cart':
            $product_id = $_POST['product_id'];
            $quantity = $_POST['quantity'];
            
            if ($quantity <= 0) {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE product_id = ?");
                $stmt->execute([$product_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE product_id = ?");
                $stmt->execute([$quantity, $product_id]);
            }
            
            echo json_encode(['success' => true]);
            exit;
            
        case 'remove_from_cart':
            $product_id = $_POST['product_id'];
            
            $stmt = $pdo->prepare("DELETE FROM cart WHERE product_id = ?");
            $stmt->execute([$product_id]);
            
            echo json_encode(['success' => true]);
            exit;
            
        case 'clear_cart':
            $stmt = $pdo->prepare("DELETE FROM cart");
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Keranjang berhasil dikosongkan!']);
            exit;
    }
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_products':
            $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($products);
            exit;
            
        case 'get_cart':
            $stmt = $pdo->query("
                SELECT c.*, p.name, p.price, p.image_path 
                FROM cart c 
                JOIN products p ON c.product_id = p.id
            ");
            $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($cart_items);
            exit;
    }
}

// Jika bukan request AJAX, tampilkan halaman HTML
?>
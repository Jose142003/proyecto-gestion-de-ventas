<?php
namespace PIC\Controllers;

use PIC\Models\Product;
use PDO;

class ProductController
{
    private Product $productModel;

    public function __construct(PDO $pdo)
    {
        $this->productModel = new Product($pdo);
    }

    public function index(): void
    {
        $category = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $products = $this->productModel->getAll($category, $search, $limit, $offset);
        echo json_encode(['success' => true, 'data' => $products]);
    }

    public function show(int $id): void
    {
        $product = $this->productModel->findById($id);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            return;
        }
        echo json_encode(['success' => true, 'data' => $product]);
    }
}

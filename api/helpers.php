<?php
function apiResponse(mixed $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function apiError(string $message, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function apiPaginate(PDO $pdo, string $query, array $params, int $page = 1, int $perPage = 20): array {
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;

    $countQuery = "SELECT COUNT(*) as total FROM ($query) AS count_table";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $total = (int) $stmt->fetch()['total'];

    $dataQuery = "$query LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($dataQuery);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    $totalPages = (int) ceil($total / $perPage);

    return [
        'success' => true,
        'data' => $data,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1,
        ]
    ];
}

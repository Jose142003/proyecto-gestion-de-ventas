<?php
namespace PIC\Tests;

use PHPUnit\Framework\TestCase;

class ResenaTest extends TestCase
{
    public function testPuntuacionRange(): void
    {
        $validScores = [1, 2, 3, 4, 5];
        $invalidScores = [0, 6, -1, 10, 100, -5];

        foreach ($validScores as $score) {
            $this->assertTrue($score >= 1 && $score <= 5,
                "Score $score should be valid (1-5)");
        }

        foreach ($invalidScores as $score) {
            $this->assertFalse($score >= 1 && $score <= 5,
                "Score $score should be invalid");
        }
    }

    public function testPuntuacionBoundaries(): void
    {
        $this->assertTrue(1 >= 1 && 1 <= 5, 'Min score 1 should be valid');
        $this->assertTrue(5 >= 1 && 5 <= 5, 'Max score 5 should be valid');
        $this->assertFalse(0 >= 1 && 0 <= 5, 'Score 0 should be invalid');
        $this->assertFalse(6 >= 1 && 6 <= 5, 'Score 6 should be invalid');
    }

    public function testCommentLength(): void
    {
        $validComments = [
            'Excelente producto',
            str_repeat('a', 500),
            'Bueno',
        ];

        $invalidComments = [
            '',
            '   ',
            str_repeat('a', 2001),
        ];

        foreach ($validComments as $comment) {
            $trimmed = trim($comment);
            $length = mb_strlen($trimmed);
            $this->assertGreaterThanOrEqual(1, $length,
                'Comment should not be empty');
            $this->assertLessThanOrEqual(2000, $length,
                'Comment should not exceed 2000 chars');
        }

        foreach ($invalidComments as $comment) {
            $trimmed = trim($comment);
            $length = mb_strlen($trimmed);
            $valid = $length >= 1 && $length <= 2000;
            $this->assertFalse($valid,
                'Comment should be invalid');
        }
    }

    public function testRequiredFields(): void
    {
        $review = [
            'producto_id' => 1,
            'usuario_id' => 1,
            'puntuacion' => 4,
            'comentario' => 'Muy buen producto',
        ];

        $requiredFields = ['producto_id', 'usuario_id', 'puntuacion', 'comentario'];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $review,
                "Field '$field' should be present in review");
            $this->assertNotNull($review[$field],
                "Field '$field' should not be null");
        }

        $this->assertIsInt($review['producto_id']);
        $this->assertIsInt($review['usuario_id']);
        $this->assertIsInt($review['puntuacion']);
        $this->assertIsString($review['comentario']);
    }

    public function testReviewValidation(): void
    {
        $validReview = function(array $data): array {
            $errors = [];

            if (!isset($data['puntuacion']) || $data['puntuacion'] < 1 || $data['puntuacion'] > 5) {
                $errors[] = 'Puntuacion must be between 1 and 5';
            }

            $comment = trim($data['comentario'] ?? '');
            if (empty($comment)) {
                $errors[] = 'Comment is required';
            } elseif (mb_strlen($comment) > 2000) {
                $errors[] = 'Comment must not exceed 2000 characters';
            }

            if (empty($data['producto_id'])) {
                $errors[] = 'Product ID is required';
            }

            if (empty($data['usuario_id'])) {
                $errors[] = 'User ID is required';
            }

            return $errors;
        };

        $this->assertEmpty($validReview([
            'producto_id' => 1,
            'usuario_id' => 42,
            'puntuacion' => 5,
            'comentario' => 'Excelente',
        ]));

        $errors = $validReview([
            'producto_id' => null,
            'usuario_id' => null,
            'puntuacion' => 6,
            'comentario' => '',
        ]);
        $this->assertCount(4, $errors);
        $this->assertStringContainsString('Puntuacion', $errors[0]);
        $this->assertStringContainsString('Comment', $errors[1]);
        $this->assertStringContainsString('Product', $errors[2]);
        $this->assertStringContainsString('User', $errors[3]);
    }
}

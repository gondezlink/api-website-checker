<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

/** @var App $app */

$app->post('/upload', function (Request $request, Response $response) {
    $uploadedFiles = $request->getUploadedFiles();

    if (empty($uploadedFiles['document'])) {
        $response->getBody()->write(json_encode(['error' => 'No document uploaded']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    /** @var \Psr\Http\Message\UploadedFileInterface $file */
    $file = $uploadedFiles['document'];

    if ($file->getError() !== UPLOAD_ERR_OK) {
        $response->getBody()->write(json_encode(['error' => 'Upload failed']));
        return $response->withStatus(400);
    }

    // Simple validation: PDF only, max 10MB
    if ($file->getClientMediaType() !== 'application/pdf' || $file->getSize() > 10 * 1024 * 1024) {
        $response->getBody()->write(json_encode(['error' => 'Only PDF files under 10MB allowed']));
        return $response->withStatus(400);
    }

    $originalName = $file->getClientFilename();
    $safeName = uniqid() . '-' . preg_replace('/[^A-Za-z0-9\._-]/', '_', $originalName);
    $key = "documents/{$safeName}";

    /** @var S3Client $s3 */
    $s3 = $this->get('s3');

    try {
        $s3->putObject([
            'Bucket'      => $_ENV['MINIO_BUCKET'],
            'Key'         => $key,
            'Body'        => $file->getStream(),
            'ContentType' => 'application/pdf',
        ]);

        /** @var PDO $db */
        $db = $this->get('db');
        $stmt = $db->prepare("INSERT INTO documents (filename, minio_path, size_bytes) VALUES (?, ?, ?)");
        $stmt->execute([$originalName, $key, $file->getSize()]);

        $id = $db->lastInsertId();

        $data = [
            'message'    => 'Document uploaded and checked',
            'id'         => $id,
            'filename'   => $originalName,
            'storage_key'=> $key,
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response->withStatus(500);
    }
});

/**
 * @OA\Get(
 *     path="/documents",
 *     summary="List all uploaded documents",
 *     tags={"Documents"},
 *     @OA\Response(
 *         response=200,
 *         description="List of documents",
 *         @OA\JsonContent(type="array", @OA\Items(
 *             @OA\Property(property="id", type="integer"),
 *             @OA\Property(property="filename", type="string"),
 *             @OA\Property(property="minio_path", type="string"),
 *             @OA\Property(property="size_bytes", type="integer"),
 *             @OA\Property(property="created_at", type="string", format="date-time")
 *         ))
 *     )
 * )
 */
$app->get('/documents', function (Request $request, Response $response) {
    $db = $this->get('db');
    $stmt = $db->query("SELECT * FROM documents ORDER BY id DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($rows));
    return $response->withHeader('Content-Type', 'application/json');
});
<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use OpenApi\Attributes as OA;

/** @var App $app */

/**
 * @OA\Info(
 *     title="API Website Document Checker",
 *     version="1.0.0",
 *     description="API untuk upload dan check dokumen (PDF) dengan penyimpanan di MinIO dan metadata di PostgreSQL"
 * )
 * @OA\Server(url="/", description="Local server")
 */

$app->post('/upload', function (Request $request, Response $response) {
    // ... kode upload sama seperti sebelumnya ...
});

/**
 * @OA\Post(
 *     path="/upload",
 *     summary="Upload dan validasi dokumen PDF",
 *     tags={"Documents"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="document",
 *                     description="File PDF yang akan diupload dan dicek",
 *                     type="string",
 *                     format="binary"
 *                 ),
 *                 required={"document"}
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Dokumen berhasil diupload",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string"),
 *             @OA\Property(property="id", type="integer"),
 *             @OA\Property(property="filename", type="string"),
 *             @OA\Property(property="storage_key", type="string")
 *         )
 *     ),
 *     @OA\Response(response=400, description="Validasi gagal atau file tidak valid")
 * )
 */

$app->get('/documents', function (Request $request, Response $response) {
    // ... kode list sama ...
});

/**
 * @OA\Get(
 *     path="/documents",
 *     summary="Daftar semua dokumen yang sudah diupload",
 *     tags={"Documents"},
 *     @OA\Response(
 *         response=200,
 *         description="Daftar dokumen",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="filename", type="string"),
 *                 @OA\Property(property="minio_path", type="string"),
 *                 @OA\Property(property="size_bytes", type="integer"),
 *                 @OA\Property(property="created_at", type="string", format="date-time")
 *             )
 *         )
 *     )
 * )
 */
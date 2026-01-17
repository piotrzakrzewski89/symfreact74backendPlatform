<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/upload', name: 'api_upload_')]
#[IsGranted('ROLE_USER')]
class UploadController extends AbstractController
{
    public function __construct(
        private SluggerInterface $slugger,
        private string $uploadDirectory
    ) {
    }

    #[Route('/book-cover', methods: ['POST'])]
    public function uploadBookCover(Request $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('cover');

        if (!$file) {
            return $this->json([
                'success' => false,
                'error' => 'No file uploaded'
            ], 400);
        }

        // Validate file type
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid file type. Only JPG, PNG and WEBP are allowed.'
            ], 400);
        }

        // Validate file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json([
                'success' => false,
                'error' => 'File too large. Maximum size is 5MB.'
            ], 400);
        }

        try {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

            $file->move($this->uploadDirectory, $newFilename);

            return $this->json([
                'success' => true,
                'filename' => $newFilename,
                'url' => '/uploads/book-covers/' . $newFilename
            ]);
        } catch (FileException $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to upload file: ' . $e->getMessage()
            ], 500);
        }
    }
}

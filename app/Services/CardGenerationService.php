<?php

namespace App\Services;

use App\Models\EmployeeCard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CardGenerationService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Generate ID card for employee
     */
    public function generateCard(EmployeeCard $card): array
    {
        try {
            // Generate PDF
            $pdfResult = $this->generatePDF($card);
            
            if (!$pdfResult['success']) {
                return $pdfResult;
            }

            // Generate preview image (optional)
            $this->generatePreviewImage($card, $pdfResult['pdf_path']);

            return [
                'success' => true,
                'pdf_path' => $pdfResult['pdf_path'],
                'message' => 'Kartu berhasil digenerate'
            ];

        } catch (\Exception $e) {
            Log::error('Card generation failed', [
                'card_id' => $card->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Gagal generate kartu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate PDF card
     */
    protected function generatePDF(EmployeeCard $card): array
    {
        $template = $this->getCardTemplate($card->design_template);
        
        $html = view('cards.templates.' . $card->design_template, [
            'card' => $card,
            'template' => $template
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper([0, 0, 243, 153], 'portrait') // Credit card size (86mm x 54mm)
            ->setOptions([
                'dpi' => 300,
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        $fileName = 'employee-cards/' . $card->card_number . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filePath = storage_path('app/public/' . $fileName);

        // Ensure directory exists
        Storage::disk('public')->makeDirectory('employee-cards');

        $pdf->save($filePath);

        return [
            'success' => true,
            'pdf_path' => $fileName,
            'full_path' => $filePath
        ];
    }

    /**
     * Generate preview image from PDF
     */
    protected function generatePreviewImage(EmployeeCard $card, string $pdfPath): void
    {
        try {
            // This would require additional PDF to image conversion
            // For now, we'll create a simple preview using the card data
            $this->createSimplePreview($card);
        } catch (\Exception $e) {
            Log::warning('Preview image generation failed', [
                'card_id' => $card->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create simple preview image
     */
    protected function createSimplePreview(EmployeeCard $card): void
    {
        $width = 860; // 86mm at 254 DPI
        $height = 540; // 54mm at 254 DPI

        $image = $this->imageManager->create($width, $height);
        
        // Set background color based on template
        $bgColor = $this->getTemplateBackgroundColor($card->design_template);
        $image->fill($bgColor);

        // Add border
        $image->drawRectangle(10, 10, function ($rectangle) {
            $rectangle->size($width - 20, $height - 20);
            $rectangle->border('#CCCCCC', 2);
        });

        // Add employee photo placeholder
        try {
            if ($card->photo_path && Storage::disk('public')->exists($card->photo_path)) {
                $photoPath = storage_path('app/public/' . $card->photo_path);
                $photo = $this->imageManager->read($photoPath);
                $photo->resize(120, 150, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $image->place($photo, 'top-left', 30, 30);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to add photo to preview', ['error' => $e->getMessage()]);
        }

        // Save preview image
        $fileName = 'employee-cards/previews/' . $card->card_number . '_preview.jpg';
        Storage::disk('public')->makeDirectory('employee-cards/previews');
        $image->toJpeg(85)->save(storage_path('app/public/' . $fileName));

        $card->update(['image_path' => $fileName]);
    }

    /**
     * Get card template configuration
     */
    protected function getCardTemplate(string $template): array
    {
        return match ($template) {
            'modern' => [
                'name' => 'Modern Template',
                'colors' => [
                    'primary' => '#3B82F6',
                    'secondary' => '#1E40AF',
                    'accent' => '#F59E0B',
                    'text' => '#1F2937',
                    'background' => '#FFFFFF'
                ],
                'fonts' => [
                    'header' => 'font-bold text-lg',
                    'body' => 'font-medium text-sm',
                    'small' => 'font-normal text-xs'
                ]
            ],
            'classic' => [
                'name' => 'Classic Template',
                'colors' => [
                    'primary' => '#059669',
                    'secondary' => '#047857',
                    'accent' => '#DC2626',
                    'text' => '#374151',
                    'background' => '#F9FAFB'
                ],
                'fonts' => [
                    'header' => 'font-bold text-lg',
                    'body' => 'font-medium text-sm',
                    'small' => 'font-normal text-xs'
                ]
            ],
            'minimalist' => [
                'name' => 'Minimalist Template',
                'colors' => [
                    'primary' => '#6B7280',
                    'secondary' => '#4B5563',
                    'accent' => '#EF4444',
                    'text' => '#111827',
                    'background' => '#FFFFFF'
                ],
                'fonts' => [
                    'header' => 'font-semibold text-base',
                    'body' => 'font-normal text-sm',
                    'small' => 'font-light text-xs'
                ]
            ],
            default => [
                'name' => 'Default Template',
                'colors' => [
                    'primary' => '#7C3AED',
                    'secondary' => '#5B21B6',
                    'accent' => '#F59E0B',
                    'text' => '#1F2937',
                    'background' => '#FFFFFF'
                ],
                'fonts' => [
                    'header' => 'font-bold text-lg',
                    'body' => 'font-medium text-sm',
                    'small' => 'font-normal text-xs'
                ]
            ]
        };
    }

    /**
     * Get template background color
     */
    protected function getTemplateBackgroundColor(string $template): string
    {
        $config = $this->getCardTemplate($template);
        return $config['colors']['background'];
    }

    /**
     * Get card dimensions (in pixels for 300 DPI)
     */
    public function getCardDimensions(): array
    {
        return [
            'width' => 1016,  // 86mm at 300 DPI
            'height' => 638,  // 54mm at 300 DPI
            'width_mm' => 86,
            'height_mm' => 54
        ];
    }

    /**
     * Validate card data
     */
    public function validateCardData(EmployeeCard $card): array
    {
        $errors = [];

        if (empty($card->employee_name)) {
            $errors[] = 'Nama pegawai tidak boleh kosong';
        }

        if (empty($card->employee_id)) {
            $errors[] = 'ID pegawai tidak boleh kosong';
        }

        if (empty($card->position)) {
            $errors[] = 'Jabatan tidak boleh kosong';
        }

        if (empty($card->department)) {
            $errors[] = 'Departemen tidak boleh kosong';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get supported templates
     */
    public function getSupportedTemplates(): array
    {
        return [
            'default' => 'Default Template',
            'modern' => 'Modern Template',
            'classic' => 'Classic Template',
            'minimalist' => 'Minimalist Template'
        ];
    }

    /**
     * Batch generate cards
     */
    public function batchGenerateCards(array $cardIds): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($cardIds as $cardId) {
            $card = EmployeeCard::find($cardId);
            if (!$card) {
                $results['failed']++;
                $results['errors'][] = "Kartu dengan ID {$cardId} tidak ditemukan";
                continue;
            }

            $result = $this->generateCard($card);
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Kartu {$card->card_number}: " . $result['message'];
            }
        }

        return $results;
    }
}
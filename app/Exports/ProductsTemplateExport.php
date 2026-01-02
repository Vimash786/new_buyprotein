<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProductsTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected array $columns;
    protected bool $hasVariants;
    protected array $variantTypes;
    protected array $sampleData;

    /**
     * @param array $columns Array of column definitions with 'key', 'label', 'required', 'example'
     * @param bool $hasVariants Whether the template is for variant products
     * @param array $variantTypes Array of variant type names (e.g., ['Size', 'Flavour', 'Package'])
     */
    public function __construct(array $columns, bool $hasVariants = false, array $variantTypes = [])
    {
        $this->columns = $columns;
        $this->hasVariants = $hasVariants;
        $this->variantTypes = $variantTypes;
        $this->generateSampleData();
    }

    protected function generateSampleData(): void
    {
        $this->sampleData = [];
        
        if ($this->hasVariants) {
            // Generate 3 sample rows for variant products (same product, different variants)
            $sampleVariants = [
                ['Size' => '1kg', 'Flavour' => 'Chocolate', 'Package' => 'Jar'],
                ['Size' => '2kg', 'Flavour' => 'Vanilla', 'Package' => 'Pouch'],
                ['Size' => '5kg', 'Flavour' => 'Strawberry', 'Package' => 'Bucket'],
            ];

            for ($i = 0; $i < 3; $i++) {
                $row = [];
                foreach ($this->columns as $col) {
                    // Check if this is a variant column
                    $isVariantCol = false;
                    foreach ($this->variantTypes as $varType) {
                        if (strcasecmp($col['key'], $varType) === 0 || 
                            strcasecmp($col['key'], str_replace(' ', '_', strtolower($varType))) === 0) {
                            $row[] = $sampleVariants[$i][$varType] ?? $col['example'] ?? '';
                            $isVariantCol = true;
                            break;
                        }
                    }
                    if (!$isVariantCol) {
                        $row[] = $col['example'] ?? '';
                    }
                }
                $this->sampleData[] = $row;
            }
        } else {
            // Generate 2 sample rows for non-variant products
            for ($i = 0; $i < 2; $i++) {
                $row = [];
                foreach ($this->columns as $col) {
                    $row[] = $col['example'] ?? '';
                }
                $this->sampleData[] = $row;
            }
        }
    }

    public function array(): array
    {
        return $this->sampleData;
    }

    public function headings(): array
    {
        return array_map(fn($col) => $col['label'], $this->columns);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $this->getColumnLetter(count($this->columns));
        
        // Style header row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];

        // Mark required columns with different color
        $requiredIndices = [];
        foreach ($this->columns as $index => $col) {
            if ($col['required'] ?? false) {
                $requiredIndices[] = $index;
            }
        }

        // Highlight required columns with different color
        foreach ($this->columns as $index => $col) {
            $colLetter = $this->getColumnLetter($index + 1);
            
            // Highlight required columns in red
            if ($col['required'] ?? false) {
                $sheet->getStyle($colLetter . '1')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('C00000');
            }
        }

        // Add a note row for variant products
        if ($this->hasVariants) {
            $noteRow = count($this->sampleData) + 3;
            $sheet->setCellValue('A' . $noteRow, 'Note: For variant products, use the same ASIN/Product identifier for all variants of the same product. Each row represents one variant combination.');
            $sheet->mergeCells('A' . $noteRow . ':' . $lastColumn . $noteRow);
            $sheet->getStyle('A' . $noteRow)->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('666666'));
        }

        return [
            1 => $headerStyle,
        ];
    }

    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->columns as $index => $col) {
            $letter = $this->getColumnLetter($index + 1);
            $width = max(strlen($col['label']), strlen($col['example'] ?? '')) + 5;
            $widths[$letter] = min(max($width, 12), 40);
        }
        return $widths;
    }

    protected function getColumnLetter(int $columnNumber): string
    {
        $letter = '';
        while ($columnNumber > 0) {
            $columnNumber--;
            $letter = chr(65 + ($columnNumber % 26)) . $letter;
            $columnNumber = intdiv($columnNumber, 26);
        }
        return $letter;
    }

    /**
     * Get default columns for non-variant products
     */
    public static function getNonVariantColumns(): array
    {
        return [
            ['key' => 'category', 'label' => 'Category', 'required' => true, 'example' => 'Protein', 'description' => 'Product category name'],
            ['key' => 'name', 'label' => 'Product Name', 'required' => true, 'example' => 'Whey Protein Isolate', 'description' => 'Full product name'],
            ['key' => 'description', 'label' => 'Description', 'required' => false, 'example' => 'High quality whey protein', 'description' => 'Product description'],
            ['key' => 'hsn', 'label' => 'HSN', 'required' => false, 'example' => '21069099', 'description' => 'HSN code for taxation'],
            ['key' => 'product_type', 'label' => 'Product Type', 'required' => true, 'example' => 'Supplement', 'description' => 'Type of product'],
            ['key' => 'sku', 'label' => 'SKU', 'required' => false, 'example' => 'WPI-001', 'description' => 'Stock keeping unit'],
            ['key' => 'gym_owner_price', 'label' => 'Gym Owner Price', 'required' => true, 'example' => '2500', 'description' => 'Price for gym owners'],
            ['key' => 'regular_user_price', 'label' => 'Regular User Price', 'required' => true, 'example' => '3000', 'description' => 'Price for regular users'],
            ['key' => 'shop_owner_price', 'label' => 'Shop Owner Price', 'required' => true, 'example' => '2200', 'description' => 'Price for shop owners'],
            ['key' => 'gym_owner_discount', 'label' => 'Gym Owner Discount %', 'required' => false, 'example' => '10', 'description' => 'Discount percentage'],
            ['key' => 'regular_user_discount', 'label' => 'Regular User Discount %', 'required' => false, 'example' => '5', 'description' => 'Discount percentage'],
            ['key' => 'shop_owner_discount', 'label' => 'Shop Owner Discount %', 'required' => false, 'example' => '15', 'description' => 'Discount percentage'],
            ['key' => 'stock_quantity', 'label' => 'Stock Quantity', 'required' => true, 'example' => '100', 'description' => 'Available stock'],
            ['key' => 'weight', 'label' => 'Weight', 'required' => false, 'example' => '1kg', 'description' => 'Product weight'],
            ['key' => 'image1', 'label' => 'Image 1 (Thumbnail)', 'required' => false, 'example' => 'https://example.com/image1.jpg', 'description' => 'Main product image URL'],
            ['key' => 'image2', 'label' => 'Image 2', 'required' => false, 'example' => 'https://example.com/image2.jpg', 'description' => 'Additional image URL'],
            ['key' => 'image3', 'label' => 'Image 3', 'required' => false, 'example' => 'https://example.com/image3.jpg', 'description' => 'Additional image URL'],
        ];
    }

    /**
     * Get default columns for variant products
     */
    public static function getVariantColumns(array $variantTypes = ['Size', 'Flavour', 'Package']): array
    {
        $columns = [
            ['key' => 'asin', 'label' => 'ASIN/Product ID', 'required' => true, 'example' => 'PROD-001', 'description' => 'Unique identifier to group product variants'],
            ['key' => 'category', 'label' => 'Category', 'required' => true, 'example' => 'Protein', 'description' => 'Product category name'],
            ['key' => 'tally_name', 'label' => 'Tally Name', 'required' => true, 'example' => 'Whey Protein Isolate', 'description' => 'Product name for tally/accounting'],
            ['key' => 'hsn', 'label' => 'HSN', 'required' => false, 'example' => '21069099', 'description' => 'HSN code for taxation'],
            ['key' => 'product_type', 'label' => 'Product Type', 'required' => true, 'example' => 'Supplement', 'description' => 'Type of product'],
            ['key' => 'sku', 'label' => 'SKU', 'required' => false, 'example' => 'WPI-1KG-CHOC-JAR', 'description' => 'Stock keeping unit for this variant'],
        ];

        // Add variant type columns
        foreach ($variantTypes as $varType) {
            $columns[] = [
                'key' => str_replace(' ', '_', strtolower($varType)),
                'label' => $varType,
                'required' => true,
                'example' => $varType === 'Size' ? '1kg' : ($varType === 'Flavour' ? 'Chocolate' : 'Jar'),
                'description' => "Variant option for {$varType}",
            ];
        }

        // Add pricing and other columns
        $columns = array_merge($columns, [
            ['key' => 'gym_owner_price', 'label' => 'Gym Owner Price', 'required' => false, 'example' => '2500', 'description' => 'Price for gym owners (variant level)'],
            ['key' => 'regular_user_price', 'label' => 'Regular User Price', 'required' => false, 'example' => '3000', 'description' => 'Price for regular users (variant level)'],
            ['key' => 'shop_owner_price', 'label' => 'Shop Owner Price', 'required' => false, 'example' => '2200', 'description' => 'Price for shop owners (variant level)'],
            ['key' => 'gym_owner_discount', 'label' => 'Gym Owner Discount %', 'required' => false, 'example' => '10', 'description' => 'Discount percentage'],
            ['key' => 'regular_user_discount', 'label' => 'Regular User Discount %', 'required' => false, 'example' => '5', 'description' => 'Discount percentage'],
            ['key' => 'shop_owner_discount', 'label' => 'Shop Owner Discount %', 'required' => false, 'example' => '15', 'description' => 'Discount percentage'],
            ['key' => 'stock_quantity', 'label' => 'Stock Quantity', 'required' => false, 'example' => '50', 'description' => 'Available stock for this variant'],
            ['key' => 'image1', 'label' => 'Image 1 (Thumbnail)', 'required' => false, 'example' => 'https://example.com/image1.jpg', 'description' => 'Main product image URL'],
            ['key' => 'image2', 'label' => 'Image 2', 'required' => false, 'example' => 'https://example.com/image2.jpg', 'description' => 'Additional image URL'],
            ['key' => 'image3', 'label' => 'Image 3', 'required' => false, 'example' => 'https://example.com/image3.jpg', 'description' => 'Additional image URL'],
        ]);

        return $columns;
    }
}
